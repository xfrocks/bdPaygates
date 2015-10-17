<?php

class bdPaygate_XenForo_ViewPublic_Account_Upgrades extends XFCP_bdPaygate_XenForo_ViewPublic_Account_Upgrades
{
    public function prepareParams()
    {
        parent::prepareParams();

        if (isset($this->_params['processors'])
            && count($this->_params['processors']) > 0
            && isset($this->_params['available'])
            && count($this->_params['available']) > 0
        ) {
            $visitor = XenForo_Visitor::getInstance();
            /** @var bdPaygate_Processor_Abstract $processor */
            $processor = reset($this->_params['processors']);
            /** @var bdPaygate_Model_Processor $processorModel */
            $processorModel = $processor->getModelFromCache('bdPaygate_Model_Processor');

            foreach ($this->_params['available'] as &$upgradeRef) {
                $itemId = $processorModel->generateItemId('user_upgrade', $visitor,
                    array($upgradeRef['user_upgrade_id']));
                $itemName = strval(new XenForo_Phrase('account_upgrade')
                    . ': ' . $upgradeRef['title'] . ' (' . $visitor['username'] . ')');

                $upgradeRef['paymentForms'] = bdPaygate_Processor_Abstract::prepareForms($this->_params['processors'],
                    $upgradeRef['cost_amount'], $upgradeRef['cost_currency'], $itemName, $itemId,
                    $upgradeRef['recurring'] ? $upgradeRef['length_amount'] : false,
                    $upgradeRef['recurring'] ? $upgradeRef['length_unit'] : false,
                    array(
                        bdPaygate_Processor_Abstract::EXTRA_RETURN_URL
                        => XenForo_Link::buildPublicLink('full:account/upgrade-purchase'),
                    )
                );
            }
        }
    }

}
