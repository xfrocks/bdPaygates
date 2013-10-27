<?php

class bdPaygate_Model_Purchase extends XenForo_Model
{
	const FETCH_USER = 0x01;

	public function getUsersWhoPurchased($contentType, $contentId, array $fetchOptions = array())
	{
		if (empty($fetchOptions['join']))
		{
			$fetchOptions['join'] = 0;
		}
		$fetchOptions['join'] |= self::FETCH_USER;

		$purchases = $this->getPurchases(array(
			'content_type' => $contentType,
			'content_id' => $contentId,
		), $fetchOptions);

		return $purchases;
	}

	public function getPurchaseByContentAndUser($contentType, $contentId, $userId, array $fetchOptions = array())
	{
		$purchases = $this->getPurchases(array(
			'content_type' => $contentType,
			'content_id' => $contentId,
			'user_id' => $userId,
		), $fetchOptions);

		return reset($purchases);
	}

	public function createRecord($contentType, $contentId, $userId, $amount = 0, $currency = '')
	{
		$dw = XenForo_DataWriter::create('bdPaygate_DataWriter_Purchase');
		$dw->set('content_type', $contentType);
		$dw->set('content_id', $contentId);
		$dw->set('user_id', $userId);
		$dw->set('purchase_date', XenForo_Application::$time);

		if (!empty($amount) AND !empty($currency))
		{
			$dw->set('purchased_amount', $amount);
			$dw->set('purchased_currency', $currency);
		}
		else
		{
			$dw->set('purchased_amount', 0);
			$dw->set('purchased_currency', '');
		}

		$dw->save();
		$record = $dw->getMergedData();

		return $record['purchase_id'];
	}

	public function deleteRecords($contentType, $contentId, $userId)
	{
		$records = $this->getPurchases(array(
			'content_type' => $contentType,
			'content_id' => $contentId,
			'user_id' => $userId,
		));

		XenForo_Db::beginTransaction();

		foreach ($records as $record)
		{
			$dw = XenForo_DataWriter::create('bdPaygate_DataWriter_Purchase');
			$dw->setExistingData($record, true);
			$dw->delete();
		}

		XenForo_Db::commit();

		return count($records);
	}

	public function deleteUserRecords($userId)
	{
		$records = $this->getPurchases(array('user_id' => $userId, ));

		XenForo_Db::beginTransaction();

		foreach ($records as $record)
		{
			$dw = XenForo_DataWriter::create('bdPaygate_DataWriter_Purchase');
			$dw->setExistingData($record, true);
			$dw->delete();
		}

		XenForo_Db::commit();

		return count($records);
	}

	/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array())
	{
		$data = $this->getPurchases($conditions, $fetchOptions);
		$list = array();

		foreach ($data as $id => $row)
		{
			$list[$id] = $row['content_type'];
		}

		return $list;
	}

	public function getPurchaseById($id, array $fetchOptions = array())
	{
		$data = $this->getPurchases(array('purchase_id' => $id), $fetchOptions);

		return reset($data);
	}

	public function getPurchases(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePurchaseConditions($conditions, $fetchOptions);

		$orderClause = $this->preparePurchaseOrderOptions($fetchOptions);
		$joinOptions = $this->preparePurchaseFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$all = $this->fetchAllKeyed($this->limitQueryResults("
				SELECT purchase.*
				$joinOptions[selectFields]
				FROM `xf_bdpaygate_purchase` AS purchase
				$joinOptions[joinTables]
				WHERE $whereConditions
				$orderClause
				", $limitOptions['limit'], $limitOptions['offset']), 'purchase_id');

		$this->_getPurchasesCustomized($all, $fetchOptions);

		return $all;
	}

	public function countPurchases(array $conditions = array(), array $fetchOptions = array())
	{
		$whereConditions = $this->preparePurchaseConditions($conditions, $fetchOptions);

		$orderClause = $this->preparePurchaseOrderOptions($fetchOptions);
		$joinOptions = $this->preparePurchaseFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
				SELECT COUNT(*)
				FROM `xf_bdpaygate_purchase` AS purchase
				$joinOptions[joinTables]
				WHERE $whereConditions
				");
	}

	public function preparePurchaseConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['purchase_id']))
		{
			if (is_array($conditions['purchase_id']))
			{
				if (!empty($conditions['purchase_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.purchase_id IN (" . $db->quote($conditions['purchase_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.purchase_id = " . $db->quote($conditions['purchase_id']);
			}
		}

		if (isset($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				if (!empty($conditions['user_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.user_id IN (" . $db->quote($conditions['user_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.user_id = " . $db->quote($conditions['user_id']);
			}
		}

		if (isset($conditions['content_type']))
		{
			if (is_array($conditions['content_type']))
			{
				if (!empty($conditions['content_type']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.content_type IN (" . $db->quote($conditions['content_type']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.content_type = " . $db->quote($conditions['content_type']);
			}
		}

		if (isset($conditions['content_id']))
		{
			if (is_array($conditions['content_id']))
			{
				if (!empty($conditions['content_id']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.content_id IN (" . $db->quote($conditions['content_id']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.content_id = " . $db->quote($conditions['content_id']);
			}
		}

		if (isset($conditions['purchase_date']))
		{
			if (is_array($conditions['purchase_date']))
			{
				if (!empty($conditions['purchase_date']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.purchase_date IN (" . $db->quote($conditions['purchase_date']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.purchase_date = " . $db->quote($conditions['purchase_date']);
			}
		}

		if (isset($conditions['purchased_amount']))
		{
			if (is_array($conditions['purchased_amount']))
			{
				if (!empty($conditions['purchased_amount']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.purchased_amount IN (" . $db->quote($conditions['purchased_amount']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.purchased_amount = " . $db->quote($conditions['purchased_amount']);
			}
		}

		if (isset($conditions['purchased_currency']))
		{
			if (is_array($conditions['purchased_currency']))
			{
				if (!empty($conditions['purchased_currency']))
				{
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "purchase.purchased_currency IN (" . $db->quote($conditions['purchased_currency']) . ")";
				}
			}
			else
			{
				$sqlConditions[] = "purchase.purchased_currency = " . $db->quote($conditions['purchased_currency']);
			}
		}

		$this->_preparePurchaseConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function preparePurchaseFetchOptions(array $fetchOptions = array())
	{
		$selectFields = '';
		$joinTables = '';

		$this->_preparePurchaseFetchOptionsCustomized($selectFields, $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables' => $joinTables
		);
	}

	public function preparePurchaseOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array();

		$this->_preparePurchaseOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getPurchasesCustomized(array &$data, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _preparePurchaseConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions)
	{
		// customized code goes here
	}

	protected function _preparePurchaseFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions)
	{
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_USER)
			{
				$selectFields .= '
					,user.*
				';
				$joinTables .= '
					INNER JOIN `xf_user` AS user
					ON (user.user_id = purchase.user_id)
				';
			}
		}
	}

	protected function _preparePurchaseOrderOptionsCustomized(array &$choice, array &$fetchOptions)
	{
		// customized code goes here
	}

}
