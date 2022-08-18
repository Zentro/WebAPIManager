<?php

namespace RoR\Api;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;

	// ################################ INSTALLATION ####################

	public function installStep1()
	{
		$sm = $this->schemaManager();

		foreach ($this->getTables() AS $tableName => $closure)
		{
			$sm->createTable($tableName, $closure);
		}
	}

	// ############################################ UNINSTALL #########################

	public function uninstallStep1()
	{
		$sm = $this->schemaManager();

		foreach (array_keys($this->getTables()) AS $tableName)
		{
			$sm->dropTable($tableName);
		}
	}

	// ############################# TABLE / DATA DEFINITIONS ##############################

	protected function getTables()
	{
		$tables = [];

		$tables['xf_rorwebapi_api_key'] = function(Create $table)
		{
			$table->addColumn('api_key_id', 'int')->autoIncrement();
			$table->addColumn('api_key', 'varbinary', 32);
			$table->addColumn('user_id', 'int');
			$table->addColumn('title', 'varchar', 50);
			$table->addColumn('active', 'tinyint')->setDefault(1);
			$table->addColumn('fqdn', 'varbinary', 255);
			$table->addColumn('creation_date', 'int');
			$table->addColumn('last_use_date', 'int');
			
			$table->addPrimaryKey('api_key_id');
			$table->addKey('user_id');
		};

		return $tables;
	}
}