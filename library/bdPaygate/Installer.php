<?php
class bdPaygate_Installer {

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
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdpaygate_log`'
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
				, INDEX `content_type_content_id_user_id` (`content_type`,`content_id`,`user_id`)
			) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
			'dropQuery' => 'DROP TABLE IF EXISTS `xf_bdpaygate_purchase`'
		)
	);
	protected static $_patches = array();

	public static function install() {
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table) {
			$db->query($table['createQuery']);
		}
		
		foreach (self::$_patches as $patch) {
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted)) {
				continue;
			}
			
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (empty($existed)) {
				$db->query($patch['alterTableAddColumnQuery']);
			}
		}
		
		self::installCustomized();
	}
	
	public static function uninstall() {
		$db = XenForo_Application::get('db');
		
		foreach (self::$_patches as $patch) {
			$tableExisted = $db->fetchOne($patch['showTablesQuery']);
			if (empty($tableExisted)) {
				continue;
			}
			
			$existed = $db->fetchOne($patch['showColumnsQuery']);
			if (!empty($existed)) {
				$db->query($patch['alterTableDropColumnQuery']);
			}
		}
		
		foreach (self::$_tables as $table) {
			$db->query($table['dropQuery']);
		}
		
		self::uninstallCustomized();
	}

	/* End auto-generated lines of code. Feel free to make changes below */
	
	private static function installCustomized() {
		// customized install script goes here
	}
	
	private static function uninstallCustomized() {
		// customized uninstall script goes here
	}
	
}