<?php

abstract class bdPaygate_Processor_Abstract
{
	const CURRENCY_USD = 'usd';
	const CURRENCY_CAD = 'cad';
	const CURRENCY_AUD = 'aud';
	const CURRENCY_GBP = 'gbp';
	const CURRENCY_EUR = 'eur';
	const CURRENCY_VND = 'vnd';
	
	const RECURRING_UNIT_DAY = 'day';
	const RECURRING_UNIT_MONTH = 'month';
	const RECURRING_UNIT_YEAR = 'year';
	
	const EXTRA_RETURN_URL = 'returnUrl';
	const EXTRA_DETAIL_URL = 'detailUrl';
	
	const PAYMENT_STATUS_ACCEPTED = 'accepted';
	const PAYMENT_STATUS_REJECTED = 'rejected';
	const PAYMENT_STATUS_OTHER = 'other';
	
	protected $_lastError = false;
	
	/**
	 * Checks whether the processor is available and ready
	 * to accept payments
	 * 
	 * @return bool
	 */
	public function isAvailable()
	{
		return true;
	}
	
	/**
	 * Returns list of supported currencies.
	 * 
	 * @return array
	 */
	public abstract function getSupportedCurrencies();
	
	/**
	 * Returns boolean value whether this processor supports
	 * specified currency.
	 * 
	 * @param string $currency
	 */
	public function isCurrencySupported($currency)
	{
		$all = $this->getSupportedCurrencies();
		return in_array(strtolower($currency), $all);
	}
	
	/**
	 * Returns boolean value whether this processor supports recurring.
	 * 
	 * @return bool
	 */
	public abstract function isRecurringSupported();
	
	/**
	 * Validates callback from payment gateway.
	 * 
	 * @param Zend_Controller_Request_Http $request
	 * @param $transactionId
	 * @param $paymentStatus
	 * @param $transactionDetails
	 * @param $itemId
	 * 
	 * @return bool
	 */
	public abstract function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId);
	
	/**
	 * Generates form data ready to be submitted.
	 * 
	 * @param double $amount
	 * @param string $currency
	 * @param string $itemName
	 * @param string $itemId
	 * @param array $extraData
	 */
	public abstract function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array());
	
	/**
	 * Returns the latest error occured. If no error is recorded, this method
	 * will return boolean value false.
	 * 
	 * @return string || XenForo_Phrase || bool
	 */
	public function getLastError()
	{
		return $this->_lastError;
	}
	
	/**
	 * Processes transaction (after successful validation)
	 * 
	 * @param string $paymentStatus
	 * @param string $itemId
	 * 
	 * @return string meaningful message for logging
	 */
	public function processTransaction($paymentStatus, $itemId)
	{
		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
		$message = false;
		
		switch ($paymentStatus)
		{
			case bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED:
				$message = $processorModel->processItem($itemId);
				break;
			case bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED:
				$message = $processorModel->revertItem($itemId);
				break;
		}

		return $message;
	}
	
	/**
	 * Gets the XenForo_Model object for the requested model.
	 * 
	 * @param string $model
	 */
	public function getModelFromCache($model)
	{
		static $processorModel = false;
		
		if ($processorModel === false)
		{
			$processorModel = XenForo_Model::create('bdPaygate_Model_Processor');
		}
		
		if ($model != 'bdPaygate_Model_Processor')
		{
			return $processorModel->getModelFromCache($model);
		}
		else 
		{
			return $processorModel;
		}
	}
	
	protected function _setError($message)
	{
		$this->_lastError = $message;
	}
	
	protected function _assertAmount(&$amount)
	{
		if (!is_numeric($amount))
		{
			throw new XenForo_Exception('$amount must be numeric');
		}
		
		if ($amount <= 0)
		{
			throw new XenForo_Exception('$amount must be a positive number');
		}
		
		return true;
	}
	
	protected function _assertCurrency(&$currency)
	{
		$currency = utf8_strtolower($currency);
		
		switch ($currency)
		{
			case self::CURRENCY_USD:
			case self::CURRENCY_CAD:
			case self::CURRENCY_AUD:
			case self::CURRENCY_GBP:
			case self::CURRENCY_EUR:
				// good
				break;
			default:
				throw new XenForo_Exception("Currency '{$currency}' is not supported");
		}
		
		return true;
	}
	
	protected function _assertItem(&$itemName, &$itemId)
	{
		$itemName = utf8_trim($itemName);
		$itemId = utf8_trim($itemId);
		
		if (utf8_strlen($itemName) == 0)
		{
			throw new XenForo_Exception('$itemName must be a string');
		}
		
		if (utf8_strlen($itemId) == 0)
		{
			throw new XenForo_Exception('$itemId must be a string');
		}
		
		return true;
	}
	
	protected function _assertRecurring(&$recurringInterval, &$recurringUnit)
	{
		if ($recurringInterval === false AND $recurringUnit === false)
		{
			// nothing to do here
			return true;
		}
		
		if (!is_numeric($recurringInterval))
		{
			throw new XenForo_Exception('$$recurringInterval must be numeric');
		}
		
		$recurringUnit = utf8_strtolower($recurringUnit);
		switch ($recurringUnit)
		{
			case self::RECURRING_UNIT_DAY:
			case self::RECURRING_UNIT_MONTH:
			case self::RECURRING_UNIT_YEAR:
				// good
				break;
			default:
				throw new XenForo_Exception("Unit '{$recurringUnit}' is not supported");
		}
		
		return true;
	}
	
	protected function _generateReturnUrl($extraData)
	{
		if (!empty($extraData[self::EXTRA_RETURN_URL]))
		{
			return $extraData[self::EXTRA_RETURN_URL];
		}
		
		return XenForo_Link::buildPublicLink('full:index');
	}
	
	protected function _generateCallbackUrl($extraData)
	{
		$thisProcessorId = false;
		$thisClassName = get_class($this);
		$names = $this->getModelFromCache('bdPaygate_Model_Processor')->getProcessorNames();
		
		foreach ($names as $processorId => $className)
		{
			if ($thisClassName === $className)
			{
				$thisProcessorId = $processorId;
			}
		}
		
		if ($thisProcessorId === false)
		{
			throw new XenForo_Exception("Could not determine processor id for class {$thisClassName}.");
		}
		
		return XenForo_Application::getOptions()->get('boardUrl') . '/bdpaygate/callback.php?p=' . $thisProcessorId;
	}
	
	protected function _generateDetailUrl($extraData)
	{
		if (!empty($extraData[self::EXTRA_DETAIL_URL]))
		{
			return $extraData[self::EXTRA_DETAIL_URL];
		}
		
		return XenForo_Link::buildPublicLink('full:index');
	}
	
	protected function _sandboxMode()
	{
		$sandboxMode = intval(XenForo_Application::getOptions()->get('bdPaygate0_sandboxMode'));
		return $sandboxMode > 0;
	}
	
	public static function create($class)
	{
		$createClass = XenForo_Application::resolveDynamicClass($class, 'bdpaygate_processor');
		
		if (!$createClass)
		{
			throw new XenForo_Exception("Invalid processor '$class' specified");
		}

		$obj = new $createClass;
		if (!$obj instanceof bdPaygate_Processor_Abstract)
		{
			throw new XenForo_Exception("Incompatible processor '$class' specified");
		}
		
		return $obj;
	}
	
	public static function prepareForms(array $processors, $amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$forms = array();
			
		foreach ($processors as $processorId => $processor)
		{
			if (!$processor->isAvailable())
			{
				// some processor may be not available at some specific time
				// since v0.9-dev3
				continue;
			}
			
			if ((!empty($recurringInterval) OR !empty($recurringUnit)) AND !$processor->isRecurringSupported())
			{
				// this upgrade require recurring payments
				// but this processor doesn't support it, next
				continue;
			}
			
			if (!$processor->isCurrencySupported($currency))
			{
				// this processor doesn't support specified currency for
				// this upgrade, next
				continue;
			}

			$form = $processor->generateFormData(
				$amount,
				$currency,
				$itemName,
				$itemId,
				$recurringInterval,
				$recurringUnit,
				$extraData
			);
			$form = utf8_trim($form);
			
			if (!empty($form))
			{
				$forms[$processorId] = $form;
			}
		}
		
		return $forms;
	}
}