<?php

class bdPaygate_Model_Processor extends XenForo_Model
{
	public function getProcessorNames()
	{
		return array(
			'paypal' => 'bdPaygate_Processor_PayPal',
		);
	}
	
	public function generateItemId($action, XenForo_Visitor $visitor, array $data)
	{
		return strval($action)
			. '|' . $visitor['user_id']
			. '|' . $this->generateHashForItemId($action, $visitor, $data)
			. (!empty($data) ? ('|' . implode('|', array_map('strval', $data))) : '');
	}
	
	public function breakdownItemId($itemId, &$action, &$user, &$data)
	{
		$parts = explode('|', $itemId);
		
		if (count($parts) >= 3)
		{
			// item id should have at least 3 parts
			$action = array_shift($parts);
			$userId = array_shift($parts);
			$hash = array_shift($parts);
			$data = $parts;
			
			$user = $this->getModelFromCache('XenForo_Model_User')->getFullUserById($userId);
			if (!$user)
			{
				return false;
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
		
		if ($logType !== bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED)
		{
			$emailOnFailure = XenForo_Application::getOptions()->get('bdPaygate0_emailOnFailure');
			file_put_contents('internal_data/bdpaygate.txt', $emailOnFailure);
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
	
	public function processItem($itemId)
	{
		$message = false;
		
		if ($this->breakdownItemId($itemId, $action, $user, $data))
		{
			switch ($action)
			{
				case 'user_upgrade':
					$message = $this->_processUserUpgrade(true, $user, $data);
					break;
				default:
					$message = $this->_processIntegratedAction($action, $user, $data);
					break;
			}
		}
		else 
		{
			$message = 'Unable to breakdown item id';
		}
		
		return $message;
	}
	
	public function revertItem($itemId)
	{
		$message = false;
		
		if ($this->breakdownItemId($itemId, $action, $user, $data))
		{
		switch ($action)
			{
				case 'user_upgrade':
					$message = $this->_processUserUpgrade(false, $user, $data);
					break;
				default:
					$message = $this->_revertIntegratedAction($action, $user, $data);
					break;
			}
		}
		else 
		{
			$message = 'Unable to breakdown item id';
		}
		
		return $message;
	}
	
	protected function _processUserUpgrade($isAccepted, $user, $data)
	{
		$upgradeModel = $this->getModelFromCache('XenForo_Model_UserUpgrade');
		
		$upgrade = $upgradeModel->getUserUpgradeById($data[0]);
		if (empty($upgrade))
		{
			return '[ERROR] Could not find specified upgrade';
		}
		
		if ($isAccepted)
		{
			$upgradeRecordId = $upgradeModel->upgradeUser($user['user_id'], $upgrade);
			return 'Upgraded user ' . $user['username'] . ' (upgrade record #' . $upgradeRecordId . ')';
		}
		else 
		{
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
	
	protected function _processIntegratedAction($action, $user, $data)
	{
		XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (process): ' . $action));
		
		return false;
	}
	
	protected function _revertIntegratedAction($action, $user, $data)
	{
		XenForo_Error::logException(new XenForo_Exception('Unhandled payment action (revert): ' . $action));
		
		return false;
	}
}