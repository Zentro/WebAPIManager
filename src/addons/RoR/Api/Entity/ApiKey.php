<?php

namespace RoR\Api\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null api_key_id
 * @property string api_key
 * @property int user_id
 * @property string title
 * @property bool active
 * @property string fqdn
 * @property int creation_date
 * @property int last_use_date
 * 
 * GETTERS
 * @property mixed key_type
 * 
 * RELATIONS
 * @property \XF\Entity\User User
 */
class ApiKey extends Entity
{
    public function generateKeyValue()
    {
        return \XF::generateRandomString(32);
    }

    public function getApiKeySnippet()
	{
		return substr($this->api_key, 0, 8) . '...';
	}

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_rorwebapi_api_key';
        $structure->shortName = 'RoR\Api:ApiKey';
        $structure->primaryKey = 'api_key_id';
        $structure->columns = [
            'api_key_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'api_key' => ['type' => self::STR, 'required' => true, 'maxlength' => 32],
            'title' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'fqdn' => ['type' => self::STR, 'maxLength' => 255, 'required' => true], // FIXME: another reminder to make this IP not FQDN
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'active' => ['type' => self::BOOL, 'default' => true],
            'creation_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'last_use_date' => ['type' => self::UINT, 'default' => 0],
        ];
        $structure->behaviors = [];
        $structure->getters = [
            'api_key_snippet' => true
        ];
        $structure->relations = [
            'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
        ];

        return $structure;
    }

    protected function _preSave()
    {
		if ($this->isInsert())
		{
			$this->api_key = $this->generateKeyValue();

            if (!$this->user_id) // FIXME: we should assign the user ID before saving
			{
				$this->user_id = \XF::visitor()->user_id;
			}
		}
    }
}