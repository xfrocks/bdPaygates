<?php

class bdPaygate_XenForo_ControllerPublic_Account extends XFCP_bdPaygate_XenForo_ControllerPublic_Account
{
	public function actionUpgrades()
	{
		$response = parent::actionUpgrades();

		if ($response instanceof XenForo_ControllerResponse_View AND $response->subView != null AND $response->subView->templateName == 'account_upgrades')
		{
			$viewParams = &$response->subView->params;

			// prepare all available processors
			/* @var $processorModel bdPaygate_Model_Processor */
			$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
			$processorNames = $processorModel->getProcessorNames();
			$processors = array();
			foreach ($processorNames as $processorId => $processorClass)
			{
				$processors[$processorId] = bdPaygate_Processor_Abstract::create($processorClass);
			}
			$viewParams['processors'] = $processors;

			if (XenForo_Application::$versionId < 1020000)
			{
				// we are going to switch the template here in order to render
				// ours instead of the original one. It's expected that doing
				// this will break other paygate add-ons.
				// XenForo 1.1.x only though...
				$response->subView->templateName = 'bdpaygate_account_upgrades';
			}
		}

		return $response;
	}

}
