<?php

class bdPaygate_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygate_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygate_hijackOptions();
		
		return parent::actionIndex();
	}
}