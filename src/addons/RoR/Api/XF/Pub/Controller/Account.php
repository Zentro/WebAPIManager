<?php

namespace RoR\Api\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Mvc\FormAction;
use XF\Mvc\Reply\View;

class Account extends XFCP_Account
{
    public function actionApiKey()
    {
        $this->assertApiKeyPasswordVerified();
        // TODO: prohibit unconfirmed registered members from seeing this

        $visitor = \XF::visitor();

        /** @var \RoR\Api\Repository\ApiKey $apiKeyRepo */
        $apiKeyRepo = $this->repository('RoR\Api:ApiKey');

        $userId = \XF::visitor()->user_id;
        $apiKeys = $apiKeyRepo->findApiKeysForUser($userId)->fetch();

        $newKeyId = $this->filter('new_key_id', 'uint');
        if ($newKeyId)
		{
			$newKey = $this->em()->find('RoR\Api:ApiKey', $newKeyId);
		}
		else
		{
			$newKey = null;
		}       

        $viewParams = [
            'apiKeys' => $apiKeys,
            'newKey' => $newKey
        ];
        $view = $this->view('XF:Account\ApiKey', 'account_api_key_list', $viewParams);
        return $this->addAccountWrapperParams($view, 'api_key');
    }

    public function actionApiKeyToggle()
    {
        $this->assertApiKeyPasswordVerified();

		/** @var \XF\ControllerPlugin\Toggle $plugin */
		$plugin = $this->plugin('XF:Toggle');
		return $plugin->actionToggle('RoR\Api:ApiKey');
    }

    public function actionApiKeyAdd()
    {
        $this->assertApiKeyPasswordVerified();

        if ($this->isPost())
        {
            $apiKey = $this->em()->create('RoR\Api:ApiKey');

            /** @var \RoR\Api\Service\ApiKey\Manager $keyManager */
		    $keyManager = $this->service('RoR\Api:ApiKey\Manager', $apiKey);

            $this->apiKeySaveProcess($keyManager)->run();

            $params = ['new_key_id' => $apiKey->api_key_id];
            return $this->redirect($this->buildLink('account/api-keys', null, $params));
        }
        else
        {
            $view = $this->view('XF:Account\ApiKey', 'account_api_key_add');
            return $this->addAccountWrapperParams($view, 'api_key');
        }
    }

    public function actionApiKeyEdit()
    {
        // There's essentially no point to this. It just makes things feel more 'complete'.
        // A few editable options shouldn't hurt, though.

        $this->assertApiKeyPasswordVerified();

        if ($this->isPost())
        {
            return $this->redirect($this->buildLink('account/api-keys', null, null));
        }
        else
        {
            $view = $this->view('XF:Account\ApiKey', 'account_api_key_edit');
            return $this->addAccountWrapperParams($view, 'api_key');
        }
    }

    protected function apiKeySaveProcess(\RoR\Api\Service\ApiKey\Manager $keyManager)
	{
		$form = $this->formAction();

		$form->basicValidateServiceSave($keyManager, function() use ($keyManager)
		{
			$input = $this->filter([
				'title' => 'str',
				'fqdn' => 'str',
			]);

			$keyManager->setTitle($input['title']);
			$keyManager->setFqdn($input['fqdn']);
		});

		return $form;
	}

    public function actionApiKeyDelete(ParameterBag $params)
    {
        $this->assertApiKeyPasswordVerified();

        $apiKey = $this->assertApiKeyExists($params->api_key_id);

        /** @var \XF\ControllerPlugin\Delete $plugin */
		$plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $apiKey,
            $this->buildLink('account/api-keys/delete', $apiKey),
            null,
            $this->buildLink('account/api-keys'),
            $apiKey->title
        );
    }

    protected function assertApiKeyPasswordVerified()
    {
        $this->assertPasswordVerified(3600, null, function($view) 
        {
            return $this->addAccountWrapperParams($view, 'api_key');
        });
    }

    /**
	 * @param string $apiKeyId
	 * @param null|string|array $with
	 * @param null|string $phraseKey
	 *
	 * @return \RoR\Api\Entity\ApiKey
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
    protected function assertApiKeyExists($apiKeyId, $with = null, $pharseKey = null)
    {
        return $this->assertRecordExists('RoR\Api:ApiKey', $apiKeyId, $with, $pharseKey);
    }
}