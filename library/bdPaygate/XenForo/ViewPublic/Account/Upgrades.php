<?php

class bdPaygate_XenForo_ViewPublic_Account_Upgrades extends XFCP_bdPaygate_XenForo_ViewPublic_Account_Upgrades
{
	public function renderHtml()
	{
		// TODO: find way to safely trigger parent's
		// parent::renderHtml();
		
		$available =& $this->_params['available'];
		$processors =& $this->_params['processors'];
		$visitor = XenForo_Visitor::getInstance();
		$itemId = false;
		$itemName = false;
		
		foreach ($available as &$upgrade)
		{
			$upgrade['paymentForms'] = array();
			
			foreach ($processors as $processorId => $processor)
			{
				if ($upgrade['recurring'] AND !$processor->isRecurringSupported())
				{
					// this upgrade require recurring payments
					// but this processor doesn't support it, next
					continue;
				}
				
				if (!$processor->isCurrencySupported($upgrade['currency']))
				{
					// this processor doesn't support specified currency for
					// this upgrade, next
					continue;
				}
				
				if ($itemId === false)
				{
					// the item id hasn't been calculated yet, let's do it now
					$itemId = $processor->getModelFromCache('bdPaygate_Model_Processor')->generateItemId('user_upgrade', $visitor, array($upgrade['user_upgrade_id']));
					$itemName = strval(new XenForo_Phrase('account_upgrade') . ': ' . $upgrade['title'] . ' (' . $visitor['username'] . ')');
				}
				
				$upgrade['paymentForms'][$processorId] = $processor->generateFormData(
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