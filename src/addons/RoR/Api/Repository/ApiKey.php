<?php

namespace RoR\Api\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class ApiKey extends Repository
{
    /**
	 * @param $userId
	 *
	 * @return null|\RoR\Api\Entity\ApiKey
	 */
    public function findApiKeysForUser(int $userId)
    {
		return $this->finder('RoR\Api:ApiKey')->where([
			'user_id' => $userId
        ]);
    }
}