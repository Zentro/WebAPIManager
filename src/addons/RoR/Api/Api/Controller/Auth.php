<?php
/**
 * Copyright (C) 2022 Rafael Galvan <rafael.galvan@rigsofrods.org>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace RoR\Api\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;
use XF\ControllerPlugin\Login;
use XF\Repository\Tfa;
use XF\Api\Mvc\Reply\ApiResult;
use XF\Api\Controller\AbstractController;

/**
 * @api-group Auth
 */
class Auth extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertSuperUserKey();
		$this->assertApiScope('auth');
	}

	/**
	 * @api-in <req> str $login The username or email address of the user to test
	 * @api-in <req> str $password The password of the user
	 * @api-in <req> str $limit_ip The IP that should be considered to be making the request. If provided, this will be used to prevent brute force attempts.
	 * 
	 * @api-out str $login_token
	 * @api-out int $expiry_date Unix timestamp of when the token expires. An error will be displayed if the token is expired or invalid
	 * @api-out User $user
	 */
    public function actionPost()
    {
		$this->assertRequiredApiInput(['login', 'password', 'limit_ip']);

		$input = $this->filter([
			'login' => 'str',
			'password' => 'str',
			'limit_ip' => 'str'
		]);

		/** @var \XF\Service\User\Login $loginService */
		$loginService = $this->service('XF:User\Login', $input['login'], $input['limit_ip']);
		if ($loginService->isLoginLimited($limitType))
		{
			return $this->error(\XF::phrase('your_account_has_temporarily_been_locked_due_to_failed_login_attempts'));
		}
        
        /** @var \XF\Entity\User|null $user */
		$user = $loginService->validate($input['password'], $error);
		if (!$user)
		{
			return $this->error($error);
		}

		if ($user->security_lock)
		{
			return $this->error(\XF::phrase('your_account_is_currently_security_locked'));
		}
        
        if(!$this->runTfaValidation($user)) {
            throw $this->errorException(\XF::phrase('two_step_verification_value_could_not_be_confirmed'));
        }

		/** @var \XF\Entity\ApiLoginToken $loginToken */
		$loginToken = $this->em()->create('XF:ApiLoginToken');
		$loginToken->user_id = $user->user_id;
		if ($input['limit_ip'])
		{
			$loginToken->limit_ip = $input['limit_ip'];
		}

		$loginToken->save();

		return $this->apiResult([
			'login_token' => $loginToken->login_token,
			'expiry_date' => $loginToken->expiry_date,
		]);
    }

    protected function runTfaValidation(\XF\Entity\User $user): bool
    {
        $loginPlugin = $this->plugin('XF:Login');
        if (!$loginPlugin->isTfaConfirmationRequired($user)) {
            return true;
        }

        $provider = $this->filter('tfa_provider', 'str');
        /** @var Tfa $tfaRepo */
        $tfaRepo = $this->repository('XF:Tfa');
        $providers = $tfaRepo->getAvailableProvidersForUser($user->user_id);
        $response = $this->app()->response();
        $response->header('XF-Tfa-Providers', implode(',', array_keys($providers)));

        if (!isset($providers[$provider])) {
            throw $this->exception($this->message(\XF::phrase('two_step_verification_required'), 202));
        }

        /** @var \XF\Service\User\Tfa $tfaService */
        $tfaService = $this->service('XF:User\Tfa', $user);

        if (!$tfaService->isTfaAvailable()) {
            return true;
        }

        if ($this->filter('tfa_trigger', 'bool') === true) {
            $tfaService->trigger($this->request(), $provider);

            throw $this->exception($this->message('changes_saved'));
        }

        if ($tfaService->hasTooManyTfaAttempts()) {
            throw $this->errorException(\XF::phrase('your_account_has_temporarily_been_locked_due_to_failed_login_attempts'));
        }

        if (!$tfaService->verify($this->request(), $provider)) {
            throw $this->errorException(\XF::phrase('two_step_verification_value_could_not_be_confirmed'));
        }

        return true;
    }
}