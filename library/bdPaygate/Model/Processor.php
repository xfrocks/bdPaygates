<?php

class bdPaygate_Model_Processor extends XenForo_Model
{
	public function getProcessorNames()
	{
		// this method can be easily extended to support more processors
		return array(
			'paypal' => 'bdPaygate_Processor_PayPal',
		);
	}
	
	public function generateItemId($action, XenForo_Visitor $visitor, array $data)
	{
		return strval($action)
			. '|' . $visitor['user_id']
			. '|' . $visitor['csrf_token_page']
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
			$csrfToken = array_shift($parts);
			$data = $parts;
			
			$user = $this->getModelFromCache('XenForo_Model_User')->getFullUserById($userId);
			if (!$user)
			{
				return false;
			}
	
			$tokenParts = explode(',', $csrfToken);
			if (count($tokenParts) != 3 || sha1($tokenParts[1] . $user['csrf_token']) != $tokenParts[2])
			{
				return false;
			}
			
			return true;
		}
		
		return false;
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

		return $this->_getDb()->lastInsertId();
	}
	
	public function getLogByTransactionId($transactionId)
	{
		if ($transactionId === '')
		{
			// some processors do not support transaction id
			// this may result in bad performance
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_bdpaygate_log
			WHERE transaction_id = ?
			ORDER BY log_date
		', 'log_id', $transactionId);
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