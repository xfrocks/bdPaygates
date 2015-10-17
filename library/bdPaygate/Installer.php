<?php

class bdPaygate_Installer
{

	/* Start auto-generated lines of code. Change made will be overwriten... */

	protected static $_tables = array(
		'log' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdpaygate_log` (
				`log_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`processor` VARCHAR(20) NOT NULL
				,`transaction_id` VARCHAR(50) NOT NULL
				,`log_type` VARCHAR(20) NOT NULL
				,`log_message` VARCHAR(255) NOT NULL
				,`log_details` MEDIUMBLOB
				,`log_date` INT(10) UNSIGNED NOT NULL
				, PRIMARY KEY (`log_id`)
				, INDEX `transaction_id` (`transaction_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdpaygate_log`',
		),
		'purchase' => array(
			'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_bdpaygate_purchase` (
				`purchase_id` INT(10) UNSIGNED AUTO_INCREMENT
				,`user_id` INT(10) UNSIGNED NOT NULL
				,`content_type` VARCHAR(25) NOT NULL
				,`content_id` INT(10) UNSIGNED NOT NULL
				,`purchase_date` INT(10) UNSIGNED NOT NULL
				,`purchased_amount` VARCHAR(10) NOT NULL
				,`purchased_currency` VARCHAR(3) NOT NULL
				, PRIMARY KEY (`purchase_id`)
				, INDEX `content_type_content_id` (`content_type`,`content_id`)
				, INDEX `user_id` (`user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdpaygate_purchase`',
		),
	);
	protected static $_patches = array( array(
			'table' => 'xf_resource_category',
			'field' => 'bdpaygate_allow_commercial_local',
			'showTablesQuery' => 'SHOW TABLES LIKE \'xf_resource_category\'',
			'showColumnsQuery' => 'SHOW COLUMNS FROM `xf_resource_category` LIKE \'bdpaygate_allow_commercial_local\'',
			'alterTableAddColumnQuery' => 'ALTER TABLE `xf_resource_category` ADD COLUMN `bdpaygate_allow_commercial_local` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'',
			'alterTableDropColumnQuery' => 'ALTER TABLE `xf_resource_category` DROP COLUMN `bdpaygate_allow_commercial_local`',
		), );

	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table)
		{
			$db->query($table['createQuery']);
		}

		foreach (self::$_patches as $patch)
		{
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted))
			{
				continue;
			}

			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (empty($existed))
			{
				$db->query($patch['alterTableAddColumnQuery']);
			}
		}

		self::installCustomized($existingAddOn, $addOnData);
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		foreach (self::$_patches as $patch)
		{
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted))
			{
				continue;
			}

			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (!empty($existed))
			{
				$db->query($patch['alterTableDropColumnQuery']);
			}
		}

		foreach (self::$_tables as $table)
		{
			$db->query($table['dropQuery']);
		}

		self::uninstallCustomized();
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	private static function installCustomized($existingAddOn, $addOnData)
	{
		if (XenForo_Application::$versionId < 1020000)
		{
			throw new XenForo_Exception('[bd] Paygates requires XenForo 1.2.0+');
		}

		$effectiveVersionId = 0;
		if (!empty($existingAddOn['version_id']))
		{
			$effectiveVersionId = $existingAddOn['version_id'];
		}

		if ($effectiveVersionId < 29)
		{
			if (XenForo_Application::getDb()->fetchOne('SHOW TABLES LIKE \'xf_resource_category\''))
			{
				XenForo_Application::getDb()->query('UPDATE xf_resource_category SET bdpaygate_allow_commercial_local = allow_local;');
			}
		}

		if ($effectiveVersionId < 32)
		{
			XenForo_Application::getDb()->query("
				INSERT IGNORE INTO xf_permission_entry
					(user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
				SELECT user_group_id, user_id, 'forum', 'bdPaygate_allThreads', permission_value, 0
				FROM xf_permission_entry
				WHERE permission_group_id = 'forum' AND permission_id = 'warn'
			");
		}
	}

	private static function uninstallCustomized()
	{
		bdPaygate_ShippableHelper_Updater::onUninstall(bdPaygate_Option::UPDATER_URL);
	}

}
