<?php
class bdPaygate_ViewPublic_Resource_Purchase extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$processors =& $this->_params['processors'];
		if (!empty($processors))
		{
			$resource =& $this->_params['resource'];
			$visitor = XenForo_Visitor::getInstance();
			$processor = reset($processors);

			$this->_params['forms'] = bdPaygate_Processor_Abstract::prepareForms(
					$processors,
					$resource['price'],
					$resource['currency'],
					sprintf('%s: %s (%s)', new XenForo_Phrase('bdpaygate_purchase_resource'), $resource['title'], $visitor['username']),
					$processor->getModelFromCache('bdPaygate_Model_Processor')->generateItemId('resource_purchase', $visitor, array($resource['resource_id'])),
					false,
					false,
					array(
							bdPaygate_Processor_Abstract::EXTRA_RETURN_URL => XenForo_Link::buildPublicLink('full:resources/purchase-complete', $resource),
					)
			);
		}
	}
}