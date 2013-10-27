<?php

class bdPaygate_DataWriter_Purchase extends XenForo_DataWriter {

/* Start auto-generated lines of code. Change made will be overwriten... */

	protected function _getFields() {
		return array(
			'xf_bdpaygate_purchase' => array(
				'purchase_id' => array('type' => 'uint', 'autoIncrement' => true),
				'user_id' => array('type' => 'uint', 'required' => true),
				'content_type' => array('type' => 'string', 'required' => true, 'maxLength' => 25),
				'content_id' => array('type' => 'uint', 'required' => true),
				'purchase_date' => array('type' => 'uint', 'required' => true),
				'purchased_amount' => array('type' => 'string', 'maxLength' => 10),
				'purchased_currency' => array('type' => 'string', 'maxLength' => 3)
			)
		);
	}

	protected function _getExistingData($data) {
		if (!$id = $this->_getExistingPrimaryKey($data, 'purchase_id')) {
			return false;
		}

		return array('xf_bdpaygate_purchase' => $this->_getPurchaseModel()->getPurchaseById($id));
	}

	protected function _getUpdateCondition($tableName) {
		$conditions = array();

		foreach (array('purchase_id') as $field) {
			$conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
		}

		return implode(' AND ', $conditions);
	}

	protected function _getPurchaseModel() {
		return $this->getModelFromCache('bdPaygate_Model_Purchase');
	}

/* End auto-generated lines of code. Feel free to make changes below */

}