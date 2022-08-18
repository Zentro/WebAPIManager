<?php

namespace RoR\Api\Service\ApiKey;

use RoR\Api\Entity\ApiKey;
use XF\Service\AbstractService;

class Manager extends AbstractService
{
    use \XF\Service\ValidateAndSavableTrait;

    /**
	 * @var ApiKey
	 */
	protected $key;

    public function __construct(\XF\App $app, ApiKey $key)
	{
		parent::__construct($app);

		$this->key = $key;
	}

    /**
	 * @return ApiKey
	 */
	public function getKey()
	{
		return $this->key;
	}

    public function setTitle($title)
	{
		$this->key->title = $title;
	}

	public function setFqdn($fqdn) // TODO: CHANGEME!!! should not be FQDN but IP
	{
		$this->key->fqdn = $fqdn;
	}

	public function setActive($active)
	{
		$this->key->active = $active;
	}

    protected function _validate()
	{
		$this->key->preSave();
		return $this->key->getErrors();
	}

    protected function _save()
	{
		$key = $this->key;

        // TODO: check if the user allows us to send a notification alert and or push

        $this->key->save();

        // TODO: actually send the notification
	}
}