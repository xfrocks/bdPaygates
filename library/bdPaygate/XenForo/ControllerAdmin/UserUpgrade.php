<?php

class bdPaygate_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygate_XenForo_ControllerAdmin_UserUpgrade
{
    public function actionIndex()
    {
        /** @var bdPaygate_XenForo_Model_Option $optionModel */
        $optionModel = $this->getModelFromCache('XenForo_Model_Option');
        $optionModel->bdPaygate_hijackOptions();

        return parent::actionIndex();
    }

    protected function _getUpgradeAddEditResponse(array $upgrade)
    {
        $response = parent::_getUpgradeAddEditResponse($upgrade);

        if ($response instanceof XenForo_ControllerResponse_View) {
            /** @var bdPaygate_Model_Processor $processorModel */
            $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
            $response->params['bdPaygate_currencies'] = $processorModel->getEnabledCurrencies();
        }

        return $response;
    }
}