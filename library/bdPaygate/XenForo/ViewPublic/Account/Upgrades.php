<?php

class bdPaygate_XenForo_ViewPublic_Account_Upgrades extends XFCP_bdPaygate_XenForo_ViewPublic_Account_Upgrades
{
	public function renderHtml()
	{
		// TODO: find way to safely trigger parent's
		// parent::renderHtml();
		
		$processors =& $this->_params['processors'];
		if (!empty($processors))
		{
			$available =& $this->_params['available'];
			$visitor = XenForo_Visitor::getInstance();
			$itemId = false;
			$itemName = false;
			
			foreach ($available as &$upgrade)
			{
				$processor = reset($processors);
				$itemId = $processor->getModelFromCache('bdPaygate_Model_Processor')->generateItemId('user_upgrade', $visitor, array($upgrade['user_upgrade_id']));
				$itemName = strval(new XenForo_Phrase('account_upgrade') . ': ' . $upgrade['title'] . ' (' . $visitor['username'] . ')');
						
				$upgrade['paymentForms'] = bdPaygate_Processor_Abstract::prepareForms(
					$processors,
					$upgrade['cost_amount'],
					$upgrade['cost_currency'],
					$itemName,
					$itemId,
					$upgrade['recurring'] ? $upgrade['length_amount'] : false,
					$upgrade['recurring'] ? $upgrade['length_unit'] : false,
					array(
						bdPaygate_Processor_Abstract::EXTRA_RETURN_URL => XenForo_Link::buildPublicLink('full:account/upgrade-purchase'),
					)
				);
			}
		}
	}
}