<?php

class bdPaygate_Model_Log extends XenForo_Model {

/* Start auto-generated lines of code. Change made will be overwriten... */

	public function getList(array $conditions = array(), array $fetchOptions = array()) {
		$data = $this->getLogs($conditions, $fetchOptions);
		$list = array();

		foreach ($data as $id => $row) {
			$list[$id] = $row['processor'];
		}

		return $list;
	}

	public function getLogById($id, array $fetchOptions = array()) {
		$data = $this->getLogs(array ('log_id' => $id), $fetchOptions);

		return reset($data);
	}

	public function getLogs(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareLogConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareLogOrderOptions($fetchOptions);
		$joinOptions = $this->prepareLogFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$all = $this->fetchAllKeyed($this->limitQueryResults("
			SELECT log.*
				$joinOptions[selectFields]
			FROM `xf_bdpaygate_log` AS log
				$joinOptions[joinTables]
			WHERE $whereConditions
				$orderClause
			", $limitOptions['limit'], $limitOptions['offset']
		), 'log_id');

		$this->_getLogsCustomized($all, $fetchOptions);

		return $all;
	}

	public function countLogs(array $conditions = array(), array $fetchOptions = array()) {
		$whereConditions = $this->prepareLogConditions($conditions, $fetchOptions);

		$orderClause = $this->prepareLogOrderOptions($fetchOptions);
		$joinOptions = $this->prepareLogFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne("
			SELECT COUNT(*)
			FROM `xf_bdpaygate_log` AS log
				$joinOptions[joinTables]
			WHERE $whereConditions
		");
	}

	public function prepareLogConditions(array $conditions = array(), array $fetchOptions = array()) {
		$sqlConditions = array();
		$db = $this->_getDb();

		if (isset($conditions['log_id'])) {
			if (is_array($conditions['log_id'])) {
				if (!empty($conditions['log_id'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.log_id IN (" . $db->quote($conditions['log_id']) . ")";
				}
			} else {
				$sqlConditions[] = "log.log_id = " . $db->quote($conditions['log_id']);
			}
		}

		if (isset($conditions['processor'])) {
			if (is_array($conditions['processor'])) {
				if (!empty($conditions['processor'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.processor IN (" . $db->quote($conditions['processor']) . ")";
				}
			} else {
				$sqlConditions[] = "log.processor = " . $db->quote($conditions['processor']);
			}
		}

		if (isset($conditions['transaction_id'])) {
			if (is_array($conditions['transaction_id'])) {
				if (!empty($conditions['transaction_id'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.transaction_id IN (" . $db->quote($conditions['transaction_id']) . ")";
				}
			} else {
				$sqlConditions[] = "log.transaction_id = " . $db->quote($conditions['transaction_id']);
			}
		}

		if (isset($conditions['log_type'])) {
			if (is_array($conditions['log_type'])) {
				if (!empty($conditions['log_type'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.log_type IN (" . $db->quote($conditions['log_type']) . ")";
				}
			} else {
				$sqlConditions[] = "log.log_type = " . $db->quote($conditions['log_type']);
			}
		}

		if (isset($conditions['log_message'])) {
			if (is_array($conditions['log_message'])) {
				if (!empty($conditions['log_message'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.log_message IN (" . $db->quote($conditions['log_message']) . ")";
				}
			} else {
				$sqlConditions[] = "log.log_message = " . $db->quote($conditions['log_message']);
			}
		}

		if (isset($conditions['log_date'])) {
			if (is_array($conditions['log_date'])) {
				if (!empty($conditions['log_date'])) {
					// only use IN condition if the array is not empty (nasty!)
					$sqlConditions[] = "log.log_date IN (" . $db->quote($conditions['log_date']) . ")";
				}
			} else {
				$sqlConditions[] = "log.log_date = " . $db->quote($conditions['log_date']);
			}
		}

		$this->_prepareLogConditionsCustomized($sqlConditions, $conditions, $fetchOptions);

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareLogFetchOptions(array $fetchOptions = array()) {
		$selectFields = '';
		$joinTables = '';

		$this->_prepareLogFetchOptionsCustomized($selectFields,  $joinTables, $fetchOptions);

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}

	public function prepareLogOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '') {
		$choices = array(	
		);

		$this->_prepareLogOrderOptionsCustomized($choices, $fetchOptions);

		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

/* End auto-generated lines of code. Feel free to make changes below */

	protected function _getLogsCustomized(array &$data, array $fetchOptions) {
		foreach ($data as &$entry) {
			$entry['logDetails'] = @unserialize($entry['log_details']);
			if (empty($entry['logDetails'])) $entry['logDetails'] = array();
		}
	}

	protected function _prepareLogConditionsCustomized(array &$sqlConditions, array $conditions, array $fetchOptions) {
		// customized code goes here
	}

	protected function _prepareLogFetchOptionsCustomized(&$selectFields, &$joinTables, array $fetchOptions) {
		// customized code goes here
	}

	protected function _prepareLogOrderOptionsCustomized(array &$choices, array &$fetchOptions) {
		$choices['log_id'] = 'log.log_id';
	}
	
	public function clearLog() {
		$this->_getDb()->query('TRUNCATE TABLE xf_bdpaygate_log');
	}

}