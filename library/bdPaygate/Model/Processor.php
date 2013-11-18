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

		foreach ($currencies as $currencyCode => $currencyName)
		{
			if (!isset($optionValue[$currencyCode]) OR !empty($optionValue[$currencyCode]))
			{
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
		return strval($action) . '|' . $visitor['user_id'] . '|' . $this->generateHashForItemId($action, $visitor, $data) . (!empty($data) ? ('|' . implode('|', array_map('strval', $data))) : '');
	}

	public function breakdownItemId($itemId, &$action, &$user, &$data)
	{
		$parts = explode('|', $itemId);

		if (count($parts) >= 3)
		{
			// item id should have at least 3 parts
			$action = array_shift($parts);
			$userId = intval(array_shift($parts));
			$hash = array_shift($parts);
			$data = $parts;

			if ($userId > 0)
			{
				$user = $this->getModelFromCache('XenForo_Model_User')->getFullUserById($userId);
				if (!$user)
				{
					return false;
				}
			}
			else
			{
				// sondh@2013-02-27
				// support proper guest user info
				$user = $this->getModelFromCache('XenForo_Model_User')->getVisitingGuestUser();
			}

			if ($this->generateHashForItemId($action, $user, $data) != $hash)
			{
				return false;
			}

			return true;
		}

		return false;
	}

	public function generateHashForItemId($action, $user, $data)
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

		if ($logType === bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED)
		{
			$emailOnFailure = XenForo_Application::getOptions()->get('bdPaygate0_emailOnFailure');

			if (!empty($emailOnFailure))
			{
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
		if ($transactionId === '')
		{
			// some processors do not support transaction id
			// this may result in bad performance
			return array();
		}

		$logs = $this->getModelFromCache('bdPaygate_Model_Log')->getLogs(array('transaction_id' => $transactionId));

		return reset($logs);
	}

	public function processItem($itemId, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		$message = false;

		if ($this->breakdownItemId($itemId, $action, $user, $data))
		{
			switch ($action)
			{
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
		}
		else
		{
			$message = 'Unable to breakdown item id';
		}

		return $message;
	}

	public function revertItem($itemId, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		$message = false;

		if ($this->breakdownItemId($itemId, $action, $user, $data))
		{
			switch ($action)
			{
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
		}
		else
		{
			$message = 'Unable to breakdown item id';
		}

		return $message;
	}

	protected function _processUserUpgrade($isAccepted, $user, $data, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');

		$upgrade = $upgradeModel->getUserUpgradeById($data[0]);
		if (empty($upgrade))
		{
			return '[ERROR] Could not find specified upgrade';
		}

		if ($isAccepted)
		{
			if ($amount !== false AND $currency !== false)
			{
				$upgradeRecord = $upgradeModel->getActiveUserUpgradeRecord($user['user_id'], $upgrade['user_upgrade_id']);
				if ($upgradeRecord)
				{
					$extra = unserialize($upgradeRecord['extra']);
					$upgradeCost = $extra['cost_amount'];
					$upgradeCurrency = $extra['cost_currency'];
				}
				else
				{
					$upgradeCost = $upgrade['cost_amount'];
					$upgradeCurrency = $upgrade['cost_currency'];
				}

				if (!$this->_verifyPaymentAmount($processor, $amount, $currency, $upgradeCost, $upgradeCurrency))
				{
					return '[ERROR] Invalid payment amount';
				}
			}

			$upgradeRecordId = $upgradeModel->upgradeUser($user['user_id'], $upgrade);
			return 'Upgraded user ' . $user['username'] . ' (upgrade record #' . $upgradeRecordId . ')';
		}
		else
		{
			// TODO: verify payment amount?

			$upgradeRecord = $upgradeModel->getActiveUserUpgradeRecord($user['user_id'], $upgrade['user_upgrade_id']);
			if (!empty($upgradeRecord))
			{
				$upgradeModel->downgradeUserUpgrade($this->_upgradeRecord);

				return 'Downgraded user ' . $user['username'] . ' (upgrade record #' . $upgradeRecord['user_upgrade_record_id'] . ')';
			}
			else
			{
				return '[ERROR] Could not find active upgrade record to downgrade';
			}
		}
	}

	protected function _processResourcePurchase($isAccepted, $user, $data, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		/* @var $resourceModel XenResource_Model_Resource */
		$resourceModel = $this->getModelFromCache('XenResource_Model_Resource');
		/* @var $purchaseModel bdPaygate_Model_Purchase */
		$purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');

		$resource = $resourceModel->getResourceById($data[0]);
		if (empty($resource))
		{
			return '[ERROR] Could not find specified resource';
		}

		if ($isAccepted)
		{
			if ($amount !== false AND $currency !== false)
			{
				if (!$this->_verifyPaymentAmount($processor, $amount, $currency, $resource['price'], $resource['currency']))
				{
					return '[ERROR] Invalid payment amount';
				}
			}

			$purchaseRecordId = $purchaseModel->createRecord('resource', $resource['resource_id'], $user['user_id'], $amount, $currency);
			return sprintf('Created purchase record for %s #%d, user %s (record #%d)', 'resource', $resource['resource_id'], $user['username'], $purchaseRecordId);
		}
		else
		{
			// TODO: verify payment amount?

			$recordCount = $purchaseModel->deleteRecords('resource', $resource['resource_id'], $user['user_id']);
			return sprintf('Deleted purchase record for %s #%d, user %s (records: %d)', 'resource', $resource['resource_id'], $user['username'], $recordCount);
		}
	}

	protected function _processBdShop($isAccepted, $user, $data, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		if ($isAccepted)
		{
			if (count($data) < 2)
			{
				return '[ERROR] Invalid payment data';
			}

			$reversed = array_reverse($data);
			$dataAmount = array_shift($reversed);
			$dataCurrency = array_shift($reversed);

			if (!$this->_verifyPaymentAmount($processor, $amount, $currency, $dataAmount, $dataCurrency))
			{
				return '[ERROR] Invalid payment amount';
			}

			$pricingSystemObj = bdShop_StockPricing_Abstract::create('bdPaygate_bdShop_StockPricing');

			$transaction = $processor->getLastTransactionDetails();
			if (empty($transaction))
			{
				$transaction = array();
			}
			$transaction = $transaction + array(
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_ID => $processor->getLastTransactionId(),
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_AMOUNT => $amount,
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_CURRENCY => $currency,
			);

			$processed = $pricingSystemObj->process($data, $transaction);

			if (is_bool($processed))
			{
				$processed = $processed ? 'true' : 'false';
			}
			elseif (is_array($processed))
			{
				$processed = var_export($processed, true);
			}

			return sprintf('[bd] Shop\'s result: %s', $processed);
		}
		else
		{
			$pricingSystemObj = bdShop_StockPricing_Abstract::create('bdPaygate_bdShop_StockPricing');

			$transaction = $processor->getLastTransactionDetails();
			if (empty($transaction))
			{
				$transaction = array();
			}
			$transaction = $transaction + array(
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_ID => $processor->getLastTransactionId(),
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_AMOUNT => $amount,
				bdShop_StockPricing_Abstract::TRANSACTION_DATA_CURRENCY => $currency,
			);

			if (!empty($transaction[bdPaygate_Processor_Abstract::TRANSACTION_DETAILS_REJECTED_TID]))
			{
				$transaction[bdShop_StockPricing_Abstract::TRANSACTION_DATA_PARENT_ID] = $transaction[bdPaygate_Processor_Abstract::TRANSACTION_DETAILS_REJECTED_TID];
			}

			$processed = $pricingSystemObj->revert($data, $transaction);

			if (is_bool($processed))
			{
				$processed = $processed ? 'true' : 'false';
			}
			elseif (is_array($processed))
			{
				$processed = var_export($processed, true);
			}

			return sprintf('[bd] Shop\'s result: %s', $processed);
		}
	}

	protected function _processIntegratedAction($action, $user, $data, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (process): ' . $action));

		return false;
	}

	protected function _revertIntegratedAction($action, $user, $data, bdPaygate_Processor_Abstract $processor, $amount, $currency)
	{
		XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (revert): ' . $action));

		return false;
	}

	protected function _verifyPaymentAmount(bdPaygate_Processor_Abstract $processor, $actualAmount, $actualCurrency, $expectAmount, $expectCurrency)
	{
		if (utf8_strtolower($actualCurrency) !== utf8_strtolower($expectCurrency))
		{
			return false;
		}

		$valueActual = sprintf('%0.10f', floatval($actualAmount));
		$valueExpect = sprintf('%0.10f', floatval($expectAmount));
		if ($valueActual !== $valueExpect)
		{
			return false;
		}

		return true;
	}

}
