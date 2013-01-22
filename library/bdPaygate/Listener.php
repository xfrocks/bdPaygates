<?php

class bdPaygate_Listener
{
	public static function load_class($class, array &$extend)
	{
		static $classes = array(
			'XenForo_ControllerAdmin_Log',
			'XenForo_ControllerAdmin_UserUpgrade',
			'XenForo_ControllerPublic_Account',
			'XenForo_Model_Option',
			'XenForo_ViewPublic_Account_Upgrades',
		);
		
		if (in_array($class, $classes))
		{
			$extend[] = 'bdPaygate_' . $class;
		}
	}
	
	public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += bdPaygate_FileSums::getHashes();
	}
}