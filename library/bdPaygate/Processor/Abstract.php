<?php

abstract class bdPaygate_Processor_Abstract
{
    const CURRENCY_USD = 'usd';
    const CURRENCY_CAD = 'cad';
    const CURRENCY_AUD = 'aud';
    const CURRENCY_GBP = 'gbp';
    const CURRENCY_EUR = 'eur';

    const RECURRING_UNIT_DAY = 'day';
    const RECURRING_UNIT_MONTH = 'month';
    const RECURRING_UNIT_YEAR = 'year';

    const EXTRA_RETURN_URL = 'returnUrl';
    const EXTRA_DETAIL_URL = 'detailUrl';

    const PAYMENT_STATUS_ACCEPTED = 'accepted';
    const PAYMENT_STATUS_REJECTED = 'rejected';
    const PAYMENT_STATUS_ERROR = 'error';
    const PAYMENT_STATUS_OTHER = 'other';

    const TRANSACTION_DETAILS_REJECTED_TID = '_rejectedTransactionId';
    const TRANSACTION_DETAILS_PARENT_TID = '_rejectedTransactionId';
    const TRANSACTION_DETAILS_SUBSCRIPTION_ID = '_subscriptionId';
    const TRANSACTION_DETAILS_CALLBACK_IP = '_callbackIp';

    protected $_lastError = false;
    protected $_lastTransactionId = false;
    protected $_lastPaymentStatus = false;
    protected $_lastTransactionDetails = array();

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
     * @return bool
     */
    public function isCurrencySupported($currency)
    {
        $currency = strtolower($currency);

        $enabledCurrencies = $this->_getProcessorModel()->getEnabledCurrencies();
        if (empty($enabledCurrencies[$currency])) {
            return false;
        }

        $all = $this->getSupportedCurrencies();
        return in_array($currency, $all);
    }

    /**
     * Returns boolean value whether this processor supports recurring.
     *
     * @return bool
     */
    public abstract function isRecurringSupported();

    /**
     * Validates callback from payment gateway.
     * THIS METHOD HAS BEEN DEPRECATED, please implement validateCallback2
     *
     * @param Zend_Controller_Request_Http $request
     * @param string $transactionId
     * @param string $paymentStatus
     * @param string $transactionDetails
     * @param string $itemId
     *
     * @return bool
     */
    public abstract function validateCallback(Zend_Controller_Request_Http $request,
                                              &$transactionId,
                                              &$paymentStatus,
                                              &$transactionDetails,
                                              &$itemId);

    /**
     * Validates callback from payment gateway.
     * This is version 2 of the method validateCallback which supports
     * amount and currency extraction, allow the system to detect a few
     * type of malicious activities.
     *
     * @param Zend_Controller_Request_Http $request
     * @param string $transactionId
     * @param string $paymentStatus
     * @param string $transactionDetails
     * @param string $itemId
     * @param string $amount
     * @param string $currency
     *
     * @return bool
     */
    public function validateCallback2(Zend_Controller_Request_Http $request,
                                      &$transactionId,
                                      &$paymentStatus,
                                      &$transactionDetails,
                                      &$itemId,
                                      &$amount,
                                      &$currency)
    {
        throw new bdPaygate_Exception_NotImplemented();
    }

    /**
     * Redirects the request if needed.
     *
     * @param Zend_Controller_Request_Http $request
     * @param string $paymentStatus
     * @param string $processMessage
     *
     * @return bool true if redirected, false otherwise.
     */
    public function redirectOnCallback(Zend_Controller_Request_Http $request, $paymentStatus, $processMessage)
    {
        return false;
    }

    /**
     * Generates form data ready to be submitted.
     *
     * @param double $amount
     * @param string $currency
     * @param string $itemName
     * @param string $itemId
     * @param string|bool $recurringInterval
     * @param string|bool $recurringUnit
     * @param array $extraData
     *
     * @return string
     */
    public abstract function generateFormData($amount,
                                              $currency,
                                              $itemName,
                                              $itemId,
                                              $recurringInterval = false,
                                              $recurringUnit = false,
                                              array $extraData = array());

    /**
     * Returns the latest error occurred. If no error is recorded, this method
     * will return boolean value false.
     *
     * @return string|bool|XenForo_Phrase
     */
    public function getLastError()
    {
        return $this->_lastError;
    }

    /**
     * Returns the latest transaction id processed. If no transaction has been
     * processed, this method will return boolean value false.
     *
     * @return string|bool
     */
    public function getLastTransactionId()
    {
        return $this->_lastTransactionId;
    }

    /**
     * Returns the latest payment status processed. If no transaction has been
     * processed, this method will return boolean value false.
     *
     * @return string|bool
     */
    public function getLastPaymentStatus()
    {
        return $this->_lastPaymentStatus;
    }

    /**
     * Returns the latest transaction details processed.
     *
     * @return array
     */
    public function getLastTransactionDetails()
    {
        return $this->_lastTransactionDetails;
    }

    /**
     * Returns the latest subscription id of the most recent transaction. If no
     * transaction has been processed or it doesn't carry a subscription id, this
     * method will return boolean value false.
     *
     * @return string|bool
     */
    public function getLastSubscriptionId()
    {
        if (empty($this->_lastTransactionDetails[self::TRANSACTION_DETAILS_SUBSCRIPTION_ID])) {
            return false;
        }

        return $this->_lastTransactionDetails[self::TRANSACTION_DETAILS_SUBSCRIPTION_ID];
    }

    /**
     * Returns the latest parent id of the most recent transaction. If no
     * transaction has been processed or it doesn't have a parent, this
     * method will return boolean value false.
     *
     * @return string|bool
     */
    public function getLastParentTransactionId()
    {
        if (empty($this->_lastTransactionDetails[self::TRANSACTION_DETAILS_PARENT_TID])) {
            return false;
        }

        return $this->_lastTransactionDetails[self::TRANSACTION_DETAILS_PARENT_TID];
    }

    /**
     * Saves the latest transaction information. This method should only be called by
     * callback.php script
     *
     * @param string $transactionId
     * @param string $paymentStatus
     * @param array $transactionDetails
     */
    public function saveLastTransaction($transactionId, $paymentStatus, array $transactionDetails)
    {
        $this->_lastTransactionId = $transactionId;
        $this->_lastPaymentStatus = $paymentStatus;
        $this->_lastTransactionDetails = $transactionDetails;
    }

    /**
     * Processes transaction (after successful validation)
     *
     * @param string $paymentStatus
     * @param string $itemId
     * @param float $amount
     * @param string $currency
     *
     * @return string meaningful message for logging
     */
    public function processTransaction($paymentStatus, $itemId, $amount, $currency)
    {
        $message = false;

        switch ($paymentStatus) {
            case bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED:
                $message = $this->_getProcessorModel()->processItem($itemId, $this, $amount, $currency);
                break;
            case bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED:
                $message = $this->_getProcessorModel()->revertItem($itemId, $this, $amount, $currency);
                break;
        }

        return $message;
    }

    /**
     * Gets the XenForo_Model object for the requested model.
     *
     * @param string $model
     * @return XenForo_Model
     */
    public function getModelFromCache($model)
    {
        static $processorModel = false;

        if ($processorModel === false) {
            $processorModel = XenForo_Model::create('bdPaygate_Model_Processor');
        }

        if ($model != 'bdPaygate_Model_Processor') {
            return $processorModel->getModelFromCache($model);
        } else {
            return $processorModel;
        }
    }

    /**
     * @param string $message
     */
    protected function _setError($message)
    {
        $this->_lastError = $message;
    }

    /**
     * @param string $amount
     * @return bool
     * @throws XenForo_Exception
     */
    protected function _assertAmount(&$amount)
    {
        if (!is_numeric($amount)) {
            throw new XenForo_Exception('$amount must be numeric');
        }

        if ($amount <= 0) {
            throw new XenForo_Exception('$amount must be a positive number');
        }

        return true;
    }

    /**
     * @param string $currency
     * @return bool
     * @throws XenForo_Exception
     */
    protected function _assertCurrency(&$currency)
    {
        $currency = utf8_strtolower($currency);

        $supportedCurrencies = $this->getSupportedCurrencies();

        foreach ($supportedCurrencies as $supportedCurrency) {
            if (utf8_strtolower($supportedCurrency) === $currency) {
                return true;
            }
        }

        throw new XenForo_Exception("Currency '{$currency}' is not supported");
    }

    /**
     * @param string $itemName
     * @param string $itemId
     * @return bool
     * @throws XenForo_Exception
     */
    protected function _assertItem(&$itemName, &$itemId)
    {
        $itemName = utf8_trim($itemName);
        $itemId = utf8_trim($itemId);

        if (utf8_strlen($itemName) == 0) {
            throw new XenForo_Exception('$itemName must be a string');
        }

        if (utf8_strlen($itemId) == 0) {
            throw new XenForo_Exception('$itemId must be a string');
        }

        return true;
    }

    /**
     * @param string $recurringInterval
     * @param string $recurringUnit
     * @return bool
     * @throws XenForo_Exception
     */
    protected function _assertRecurring(&$recurringInterval, &$recurringUnit)
    {
        if ($recurringInterval === false AND $recurringUnit === false) {
            // nothing to do here
            return true;
        }

        if (!is_numeric($recurringInterval)) {
            throw new XenForo_Exception('$$recurringInterval must be numeric');
        }

        $recurringUnit = utf8_strtolower($recurringUnit);
        switch ($recurringUnit) {
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

    /**
     * @param array $extraData
     * @return string
     */
    protected function _generateReturnUrl(array $extraData)
    {
        if (!empty($extraData[self::EXTRA_RETURN_URL])) {
            return $extraData[self::EXTRA_RETURN_URL];
        }

        return XenForo_Link::buildPublicLink('full:index');
    }

    /**
     * @param array $extraData
     * @return string
     * @throws XenForo_Exception
     */
    protected function _generateCallbackUrl(array $extraData)
    {
        $thisProcessorId = false;
        $thisClassName = get_class($this);
        $names = $this->_getProcessorModel()->getProcessorNames();

        foreach ($names as $processorId => $className) {
            if ($thisClassName === $className) {
                $thisProcessorId = $processorId;
            }
        }

        if ($thisProcessorId === false) {
            throw new XenForo_Exception("Could not determine processor id for class {$thisClassName}.");
        }

        return XenForo_Application::getOptions()->get('boardUrl') . '/bdpaygate/callback.php?p=' . $thisProcessorId;
    }

    /**
     * @param array $extraData
     * @return string
     */
    protected function _generateDetailUrl(array $extraData)
    {
        if (!empty($extraData[self::EXTRA_DETAIL_URL])) {
            return $extraData[self::EXTRA_DETAIL_URL];
        }

        return XenForo_Link::buildPublicLink('full:index');
    }

    /**
     * @return bool
     */
    protected function _sandboxMode()
    {
        $sandboxMode = intval(XenForo_Application::getOptions()->get('bdPaygate0_sandboxMode'));
        return $sandboxMode > 0;
    }

    /**
     * @return bdPaygate_Model_Processor
     */
    protected function _getProcessorModel()
    {
        return $this->getModelFromCache('bdPaygate_Model_Processor');
    }

    /**
     * @param string $class
     * @throws XenForo_Exception
     * @return bdPaygate_Processor_Abstract
     */
    public static function create($class)
    {
        $createClass = XenForo_Application::resolveDynamicClass($class, 'bdpaygate_processor');

        if (!$createClass) {
            throw new XenForo_Exception("Invalid processor '$class' specified");
        }

        $obj = new $createClass;
        if (!$obj instanceof bdPaygate_Processor_Abstract) {
            throw new XenForo_Exception("Incompatible processor '$class' specified");
        }

        return $obj;
    }

    public static function prepareForms(array $processors,
                                        $amount,
                                        $currency,
                                        $itemName,
                                        $itemId,
                                        $recurringInterval = false,
                                        $recurringUnit = false,
                                        array $extraData = array())
    {
        $forms = array();

        foreach ($processors as $processorId => $processor) {
            /** @var bdPaygate_Processor_Abstract $processor */
            if (!$processor->isAvailable()) {
                // some processor may be not available at some specific time
                // since v0.9-dev3
                continue;
            }

            if ((
                    !empty($recurringInterval)
                    || !empty($recurringUnit)
                ) && !$processor->isRecurringSupported()
            ) {
                // this upgrade require recurring payments
                // but this processor doesn't support it, next
                continue;
            }

            if (!$processor->isCurrencySupported($currency)) {
                // this processor doesn't support specified currency for
                // this upgrade, next
                continue;
            }

            $form = $processor->generateFormData($amount, $currency, $itemName, $itemId,
                $recurringInterval, $recurringUnit, $extraData);
            $form = utf8_trim($form);

            if (!empty($form)) {
                $forms[$processorId] = $form;
            }
        }

        return $forms;
    }

}
