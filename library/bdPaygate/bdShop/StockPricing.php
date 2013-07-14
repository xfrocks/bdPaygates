<?php

class bdPaygate_bdShop_StockPricing extends bdShop_StockPricing_Abstract
{

	protected static $_currencies = false;

	public function getConfiguration()
	{
		if (self::$_currencies === false)
		{
			self::$_currencies = array();
			$processorNames = $this->_getProcessorModel()->getProcessorNames();

			foreach ($processorNames as $processorName)
			{
				$processor = bdPaygate_Processor_Abstract::create($processorName);
				if ($processor->isAvailable())
				{
					$processorCurrencies = $processor->getSupportedCurrencies();
					foreach ($processorCurrencies as $currency)
					{
						self::$_currencies[utf8_strtolower($currency)] = utf8_strtoupper($currency);
					}
				}
			}
		}

		return array(
				'title' => '[bd] Paygate',
				'currencies' => self::$_currencies
		);
	}

	public function generateHtml($amount, $currency, $comment, array $data, XenForo_View $view)
	{
		$data[] = utf8_strtolower($currency);
		$data[] = $amount;

		$processorModel = $this->_getProcessorModel();
		$itemId = $processorModel->generateItemId('bdshop', XenForo_Visitor::getInstance(), $data);

		$processorNames = $processorModel->getProcessorNames();
		$processors = array();
		foreach ($processorNames as $processorId => $processorClass)
		{
			$processors[$processorId] = bdPaygate_Processor_Abstract::create($processorClass);
		}

		return implode('', bdPaygate_Processor_Abstract::prepareForms($processors, $amount, $currency, $comment, $itemId, false, false, array(
				bdPaygate_Processor_Abstract::EXTRA_RETURN_URL => XenForo_Link::buildPublicLink('full:shop/thanks')
		)));
	}

	/**
	 *
	 * @return bdPaygate_Model_Processor
	 */
	protected function _getProcessorModel()
	{
		return $this->getModelFromCache('bdPaygate_Model_Processor');
	}
}