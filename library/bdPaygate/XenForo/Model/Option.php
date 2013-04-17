<?php

class bdPaygate_XenForo_Model_Option extends XFCP_bdPaygate_XenForo_Model_Option
{
	// this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
	// for no apparent reason use XenForo_Model::create to create the optionModel
	// (instead of using XenForo_Controller::getModelFromCache)
	private static $_bdPaygate_hijackOptions = false;
	
	public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
	{
		if (self::$_bdPaygate_hijackOptions === true)
		{
			$optionIds[] = 'bdPaygate0_emailOnFailure';
		}
		
		$options = parent::getOptionsByIds($optionIds, $fetchOptions);
		
		self::$_bdPaygate_hijackOptions = false;

		return $options;
	}
	
	public function bdPaygate_hijackOptions()
	{
		self::$_bdPaygate_hijackOptions = true;
	}
}