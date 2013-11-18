<?php

class bdPaygate_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygate_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygate_hijackOptions();
		
		return parent::actionIndex();
	}
	
	protected function _getUpgradeAddEditResponse(array $upgrade)
	{
		$response = parent::_getUpgradeAddEditResponse($upgrade);
		
		if ($response instanceof XenForo_ControllerResponse_View)
		{
			$response->params['bdPaygate_currencies'] = $this->getModelFromCache('bdPaygate_Model_Processor')->getEnabledCurrencies();
		}
		
		return $response;
	}
}