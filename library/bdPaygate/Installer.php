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
		)
	);
	protected static $_patches = array();

	public static function install() {
		$db = XenForo_Application::get('db');

		foreach (self::$_tables as $table) {
			$db->query($table['createQuery']);
		}
		
		foreach (self::$_patches as $patch) {
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