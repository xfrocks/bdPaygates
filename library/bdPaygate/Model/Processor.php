<?php

class bdPaygate_Model_Processor extends XenForo_Model
{
    public function getCurrencies()
    {
        return array(
            bdPaygate_Processor_Abstract::CURRENCY_USD => 'USD',
            bdPaygate_Processor_Abstract::CURRENCY_CAD => 'CAD',
            bdPaygate_Processor_Abstract::CURRENCY_AUD => 'AUD',
            bdPaygate_Processor_Abstract::CURRENCY_GBP => 'GBP',
            bdPaygate_Processor_Abstract::CURRENCY_EUR => 'EUR',
        );
    }

    public function getEnabledCurrencies()
    {
        $currencies = $this->getCurrencies();
        $optionValue = bdPaygate_Option::get('enabledCurrencies');
        $enabledCurrencies = array();

        foreach ($currencies as $currencyCode => $currencyName) {
            if (!isset($optionValue[$currencyCode]) OR !empty($optionValue[$currencyCode])) {
                $enabledCurrencies[$currencyCode] = $currencyName;
            }
        }

        return $enabledCurrencies;
    }

    public function getProcessorNames()
    {
        return array('paypal' => 'bdPaygate_Processor_PayPal');
    }

    public function generateItemId($action, XenForo_Visitor $visitor, array $data)
    {
        $user = $visitor->toArray();

        return strval($action)
        . '|' . $user['user_id']
        . '|' . $this->generateHashForItemId($action, $user, $data)
        . (!empty($data) ? ('|' . implode('|', array_map('strval', $data))) : '');
    }

    public function breakdownItemId($itemId, &$action, &$user, &$data)
    {
        $parts = explode('|', $itemId);

        if (count($parts) >= 3) {
            // item id should have at least 3 parts
            $action = array_shift($parts);
            $userId = intval(array_shift($parts));
            $hash = array_shift($parts);
            $data = $parts;

            /** @var XenForo_Model_User $userModel */
            $userModel = $this->getModelFromCache('XenForo_Model_User');
            if ($userId > 0) {
                $user = $userModel->getFullUserById($userId);
                if (!$user) {
                    return false;
                }
            } else {
                $user = $userModel->getVisitingGuestUser();
            }

            if ($this->generateHashForItemId($action, $user, $data) != $hash) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function generateHashForItemId($action, array $user, array $data)
    {
        // this one is needed because some processor doesn't support very long item id
        return substr(md5($action . $user['csrf_token'] . implode(',', $data)), -5);
    }

    public function log($processorId, $transactionId, $logType, $logMessage, $logDetails)
    {
        $this->_getDb()->insert('xf_bdpaygate_log', array(
            'processor' => $processorId,
            'transaction_id' => $transactionId,
            'log_type' => $logType,
            'log_message' => substr($logMessage, 0, 255),
            'log_details' => serialize($logDetails),
            'log_date' => XenForo_Application::$time
        ));

        $logId = $this->_getDb()->lastInsertId();

        if ($logType === bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED) {
            $emailOnFailure = XenForo_Application::getOptions()->get('bdPaygate0_emailOnFailure');

            if (!empty($emailOnFailure)) {
                // send email notification to administrator for failed transaction
                $mail = XenForo_Mail::create('bdpaygate_failure', array(
                    'processorId' => $processorId,
                    'transactionId' => $transactionId,
                    'logType' => $logType,
                    'logMessage' => $logMessage,
                    'logDetails' => $logDetails,
                    'logId' => $logId,
                ));

                $mail->queue($emailOnFailure);
            }
        }

        return $logId;
    }

    public function getLogByTransactionId($transactionId)
    {
        if ($transactionId === '') {
            // some processors do not support transaction id
            // this may result in bad performance
            return array();
        }

        /** @var bdPaygate_Model_Log $logModel */
        $logModel = $this->getModelFromCache('bdPaygate_Model_Log');
        $logs = $logModel->getLogs(array('transaction_id' => $transactionId));

        return reset($logs);
    }

    public function processItem($itemId, bdPaygate_Processor_Abstract $processor, $amount, $currency)
    {
        if ($this->breakdownItemId($itemId, $action, $user, $data)) {
            switch ($action) {
                case 'user_upgrade':
                    $message = $this->_processUserUpgrade(true, $user, $data, $processor, $amount, $currency);
                    break;
                case 'resource_purchase':
                    $message = $this->_processResourcePurchase(true, $user, $data, $processor, $amount, $currency);
                    break;
                case 'bdshop':
                    $message = $this->_processBdShop(true, $user, $data, $processor, $amount, $currency);
                    break;
                default:
                    $message = $this->_processIntegratedAction($action, $user, $data, $processor, $amount, $currency);
                    break;
            }
        } else {
            $message = 'Unable to breakdown item id';
        }

        return $message;
    }

    public function revertItem($itemId, bdPaygate_Processor_Abstract $processor, $amount, $currency)
    {
        if ($this->breakdownItemId($itemId, $action, $user, $data)) {
            switch ($action) {
                case 'user_upgrade':
                    $message = $this->_processUserUpgrade(false, $user, $data, $processor, $amount, $currency);
                    break;
                case 'resource_purchase':
                    $message = $this->_processResourcePurchase(false, $user, $data, $processor, $amount, $currency);
                    break;
                case 'bdshop':
                    $message = $this->_processBdShop(false, $user, $data, $processor, $amount, $currency);
                    break;
                default:
                    $message = $this->_revertIntegratedAction($action, $user, $data, $processor, $amount, $currency);
                    break;
            }
        } else {
            $message = 'Unable to breakdown item id';
        }

        return $message;
    }

    public function updateSubscriptionForItem($itemId, bdPaygate_Processor_Abstract $processor, $subscriptionId)
    {
        $message = '';

        if ($this->breakdownItemId($itemId, $action, $user, $data)) {
            switch ($action) {
                case 'user_upgrade':
                    $message = $this->_updateSubscriptionForUserUpgrade($user, $data, $processor, $subscriptionId);
                    break;
            }
        }

        return $message;
    }

    protected function _processUserUpgrade($isAccepted,
                                           $user,
                                           $data,
                                           bdPaygate_Processor_Abstract $processor,
                                           $amount,
                                           $currency)
    {
        /** @var XenForo_Model_UserUpgrade $upgradeModel */
        $upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');

        $upgrade = $upgradeModel->getUserUpgradeById($data[0]);
        if (empty($upgrade)) {
            return '[ERROR] Could not find specified upgrade';
        }

        $upgradeRecord = $upgradeModel->getActiveUserUpgradeRecord($user['user_id'], $upgrade['user_upgrade_id']);

        if (!$upgradeRecord AND $processor->getLastSubscriptionId()) {
            $parentLogs = $upgradeModel->getLogsBySubscriberId($processor->getLastSubscriptionId());
            foreach (array_reverse($parentLogs) AS $parentLog) {
                if ($parentLog['user_upgrade_record_id']) {
                    $upgradeRecord = $upgradeModel->getExpiredUserUpgradeRecordById($parentLog['user_upgrade_record_id']);
                    if ($upgradeRecord) {
                        break;
                    }
                }
            }
        }

        if (!$upgradeRecord AND $processor->getLastParentTransactionId()) {
            $parentLogs = $upgradeModel->getLogsByTransactionId($processor->getLastParentTransactionId());
            foreach (array_reverse($parentLogs) AS $parentLog) {
                if ($parentLog['user_upgrade_record_id']) {
                    $upgradeRecord = $upgradeModel->getExpiredUserUpgradeRecordById($parentLog['user_upgrade_record_id']);
                    if ($upgradeRecord) {
                        break;
                    }
                }
            }
        }

        if ($isAccepted) {
            $messages = array();

            if ($amount !== false AND $currency !== false) {
                if ($upgradeRecord) {
                    //  verify payment amount with an existing upgrade record
                    $extra = unserialize($upgradeRecord['extra']);
                    $upgradeCost = $extra['cost_amount'];
                    $upgradeCurrency = $extra['cost_currency'];
                } else {
                    // verify payment amount with the upgrade itself
                    $upgradeCost = $upgrade['cost_amount'];
                    $upgradeCurrency = $upgrade['cost_currency'];
                }

                if (!$this->_verifyPaymentAmount($processor, $amount, $currency, $upgradeCost, $upgradeCurrency)) {
                    return '[ERROR] Invalid payment amount';
                }
            }

            if ($processor->getLastParentTransactionId() AND $upgradeRecord) {
                // for PayPal, this is a Canceled_Reversal transaction
                $endDate = isset($upgradeRecord['original_end_date'])
                    ? $upgradeRecord['original_end_date']
                    : $upgradeRecord['end_date'];
                $upgradeRecordId = $upgradeModel->upgradeUser($user['user_id'], $upgrade, true, $endDate);
                $messages[] = sprintf('Restored user upgrade for user %s (end date %s, upgrade record #%d)',
                    $user['username'], $endDate, $upgradeRecordId);
            } else {
                $upgradeRecordId = $upgradeModel->upgradeUser($user['user_id'], $upgrade);
                $messages[] = sprintf('Upgraded user %s (upgrade record #%d)', $user['username'], $upgradeRecordId);
            }

            $subscriptionId = $processor->getLastSubscriptionId();
            if (!empty($upgradeRecordId) AND !empty($subscriptionId)) {
                // try to associate user upgrade with a subscription
                $newUpgradeRecord = $upgradeModel->getActiveUserUpgradeRecordById($upgradeRecordId);
                $messages[] = $this->_updateSubscriptionForUserUpgradeRecord($newUpgradeRecord,
                    $processor, $subscriptionId);
            }

            $messages = implode(".\n", $messages);

            $upgradeModel->logProcessorCallback(
                intval($upgradeRecordId),
                'bdpaygate',
                $processor->getLastTransactionId(),
                'payment',
                $messages,
                $processor->getLastTransactionDetails(),
                $processor->getLastSubscriptionId()
            );

            return $messages;
        } else {
            if (!empty($upgradeRecord)) {
                $upgradeModel->downgradeUserUpgrade($upgradeRecord);

                return sprintf('Downgraded user %s (upgrade record #%d)',
                    $user['username'], $upgradeRecord['user_upgrade_record_id']);
            } else {
                return '[ERROR] Could not find active upgrade record to downgrade';
            }
        }
    }

    protected function _updateSubscriptionForUserUpgrade($user,
                                                         $data,
                                                         bdPaygate_Processor_Abstract $processor,
                                                         $subscriptionId)
    {
        /** @var XenForo_Model_UserUpgrade $upgradeModel */
        $upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');

        $upgrade = $upgradeModel->getUserUpgradeById($data[0]);
        if (empty($upgrade)) {
            return '[ERROR] Could not find specified upgrade';
        }

        $upgradeRecord = $upgradeModel->getActiveUserUpgradeRecord($user['user_id'], $upgrade['user_upgrade_id']);
        if (empty($upgradeRecord)) {
            return 'Could not find active upgrade record';
        }

        return $this->_updateSubscriptionForUserUpgradeRecord($upgradeRecord, $processor, $subscriptionId);
    }

    protected function _updateSubscriptionForUserUpgradeRecord($upgradeRecord,
                                                               bdPaygate_Processor_Abstract $processor,
                                                               $subscriptionId)
    {
        $extra = unserialize($upgradeRecord['extra']);
        $extra['bdPaygate_processorClass'] = get_class($processor);
        $extra['bdPaygate_subscriptionId'] = $subscriptionId;
        $extraSerialized = serialize($extra);
        if ($extraSerialized == $upgradeRecord['extra']) {
            return sprintf('Subscription ID is up to date "%s" (upgrade record #%d)',
                $subscriptionId, $upgradeRecord['user_upgrade_record_id']);
        }

        $this->_getDb()->update('xf_user_upgrade_active',
            array('extra' => $extraSerialized),
            array('user_upgrade_record_id = ?' => $upgradeRecord['user_upgrade_record_id']));

        return sprintf('Updated Subscription ID "%s" (upgrade record #%d)',
            $subscriptionId, $upgradeRecord['user_upgrade_record_id']);
    }

    protected function _processResourcePurchase($isAccepted,
                                                $user,
                                                $data,
                                                bdPaygate_Processor_Abstract $processor,
                                                $amount,
                                                $currency)
    {
        /* @var $resourceModel XenResource_Model_Resource */
        $resourceModel = $this->getModelFromCache('XenResource_Model_Resource');
        /* @var $purchaseModel bdPaygate_Model_Purchase */
        $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');

        $resource = $resourceModel->getResourceById($data[0]);
        if (empty($resource)) {
            return '[ERROR] Could not find specified resource';
        }

        if ($isAccepted) {
            if ($amount !== false AND $currency !== false) {
                if (!$this->_verifyPaymentAmount($processor, $amount, $currency,
                    $resource['price'], $resource['currency'])
                ) {
                    return '[ERROR] Invalid payment amount';
                }
            }

            $purchaseRecordId = $purchaseModel->createRecord('resource', $resource['resource_id'], $user['user_id'],
                $amount, $currency);
            return sprintf('Created purchase record for %s #%d, user %s (record #%d)',
                'resource', $resource['resource_id'], $user['username'], $purchaseRecordId);
        } else {
            // TODO: verify payment amount?

            $recordCount = $purchaseModel->deleteRecords('resource', $resource['resource_id'], $user['user_id']);
            return sprintf('Deleted purchase record for %s #%d, user %s (records: %d)',
                'resource', $resource['resource_id'], $user['username'], $recordCount);
        }
    }

    protected function _processBdShop($isAccepted,
                                      $user,
                                      $data,
                                      bdPaygate_Processor_Abstract $processor,
                                      $amount,
                                      $currency)
    {
        if ($isAccepted) {
            if (count($data) < 2) {
                return '[ERROR] Invalid payment data';
            }

            $reversed = array_reverse($data);
            $dataAmount = array_shift($reversed);
            $dataCurrency = array_shift($reversed);

            if ($amount !== false
                && $currency !== false
                && !$this->_verifyPaymentAmount($processor, $amount, $currency, $dataAmount, $dataCurrency)
            ) {
                return '[ERROR] Invalid payment amount';
            }

            /** @var bdPaygate_bdShop_StockPricing $pricingSystemObj */
            $pricingSystemObj = bdShop_StockPricing_Abstract::create('bdPaygate_bdShop_StockPricing');

            $transaction = $processor->getLastTransactionDetails();
            if (empty($transaction)) {
                $transaction = array();
            }
            $transaction = $transaction + array(
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_ID => $processor->getLastTransactionId(),
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_AMOUNT => $dataAmount,
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_CURRENCY => $dataCurrency,
                );

            $processed = $pricingSystemObj->process($data, $transaction);

            if (is_bool($processed)) {
                $processed = $processed ? 'true' : 'false';
            } elseif (is_array($processed)) {
                $processed = var_export($processed, true);
            }

            return sprintf('[bd] Shop\'s result: %s', $processed);
        } else {
            $pricingSystemObj = bdShop_StockPricing_Abstract::create('bdPaygate_bdShop_StockPricing');

            $transaction = $processor->getLastTransactionDetails();
            if (empty($transaction)) {
                $transaction = array();
            }
            $transaction = $transaction + array(
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_ID => $processor->getLastTransactionId(),
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_AMOUNT => $amount,
                    bdShop_StockPricing_Abstract::TRANSACTION_DATA_CURRENCY => $currency,
                );

            if (!empty($transaction[bdPaygate_Processor_Abstract::TRANSACTION_DETAILS_PARENT_TID])) {
                $transaction[bdShop_StockPricing_Abstract::TRANSACTION_DATA_PARENT_ID]
                    = $transaction[bdPaygate_Processor_Abstract::TRANSACTION_DETAILS_PARENT_TID];
            }

            $processed = $pricingSystemObj->revert($data, $transaction);

            if (is_bool($processed)) {
                $processed = $processed ? 'true' : 'false';
            } elseif (is_array($processed)) {
                $processed = var_export($processed, true);
            }

            return sprintf('[bd] Shop\'s result: %s', $processed);
        }
    }

    protected function _processIntegratedAction($action,
                                                $user,
                                                $data,
                                                bdPaygate_Processor_Abstract $processor,
                                                $amount,
                                                $currency)
    {
        XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (process): ' . $action));

        return false;
    }

    protected function _revertIntegratedAction($action,
                                               $user,
                                               $data,
                                               bdPaygate_Processor_Abstract $processor,
                                               $amount,
                                               $currency)
    {
        XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (revert): ' . $action));

        return false;
    }

    protected function _verifyPaymentAmount(bdPaygate_Processor_Abstract $processor,
                                            $actualAmount,
                                            $actualCurrency,
                                            $expectAmount,
                                            $expectCurrency)
    {
        if (utf8_strtolower($actualCurrency) !== utf8_strtolower($expectCurrency)) {
            return false;
        }

        if ($actualAmount > $expectAmount) {
            // user sends more money than expected? Good.
            return true;
        } else {
            $valueActual = sprintf('%0.10f', floatval($actualAmount));
            $valueExpect = sprintf('%0.10f', floatval($expectAmount));
            if ($valueActual !== $valueExpect) {
                return false;
            }

            return true;
        }
    }

}
