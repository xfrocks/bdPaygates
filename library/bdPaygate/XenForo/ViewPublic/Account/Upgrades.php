<?php

class bdPaygate_XenForo_ViewPublic_Account_Upgrades extends XFCP_bdPaygate_XenForo_ViewPublic_Account_Upgrades
{
	public function renderHtml()
	{
		if ($this->_bdPaygate_parentHasMethod('renderHtml'))
		{
			parent::renderHtml();
		}

		$processors = &$this->_params['processors'];
		if (!empty($processors))
		{
			$available = &$this->_params['available'];
			$visitor = XenForo_Visitor::getInstance();
			$itemId = false;
			$itemName = false;

			foreach ($available as &$upgrade)
			{
				$processor = reset($processors);
				$itemId = $processor->getModelFromCache('bdPaygate_Model_Processor')->generateItemId('user_upgrade', $visitor, array($upgrade['user_upgrade_id']));
				$itemName = strval(new XenForo_Phrase('account_upgrade') . ': ' . $upgrade['title'] . ' (' . $visitor['username'] . ')');

				$upgrade['paymentForms'] = bdPaygate_Processor_Abstract::prepareForms($processors, $upgrade['cost_amount'], $upgrade['cost_currency'], $itemName, $itemId, $upgrade['recurring'] ? $upgrade['length_amount'] : false, $upgrade['recurring'] ? $upgrade['length_unit'] : false, array(bdPaygate_Processor_Abstract::EXTRA_RETURN_URL => XenForo_Link::buildPublicLink('full:account/upgrade-purchase'), ));
			}
		}
	}

	protected function _bdPaygate_parentHasMethod($method)
	{
		$us = 'XFCP_' . __CLASS__;
		$usFound = false;

		foreach (class_parents($this) as $parent)
		{
			if ($parent === $us)
			{
				$usFound = true;
				continue;
			}
			if (!$usFound)
			{
				continue;
			}

			// Do not perform method check until we found ourself in the class hierarchy.
			// That needs to be done to safely trigger parent::$method.
			// Performing method_exists(get_parent_class($this), $method) is not enough
			// if our class is in the middle of the hierarchy:
			//
			// SomeAddOn_Class extends XFCP_SomeAddOn_Class...
			// Our_Class extends XFCP_Our_Class...
			// Target_Class
			//
			// pretty confusing...
			if (method_exists($parent, $method))
			{
				return true;
			}
		}

		return false;
	}

}
