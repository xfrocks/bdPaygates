<?php

class bdPaygate_DataWriter_Purchase extends XenForo_DataWriter
{
    protected function _getFields()
    {
        return array(
            'xf_bdpaygate_purchase' => array(
                'purchase_id' => array('type' => XenForo_DataWriter::TYPE_UINT, 'autoIncrement' => true),
                'user_id' => array('type' => XenForo_DataWriter::TYPE_UINT, 'required' => true),
                'content_type' => array(
                    'type' => XenForo_DataWriter::TYPE_STRING,
                    'required' => true,
                    'maxLength' => 25
                ),
                'content_id' => array('type' => XenForo_DataWriter::TYPE_UINT, 'required' => true),
                'purchase_date' => array('type' => XenForo_DataWriter::TYPE_UINT, 'required' => true),
                'purchased_amount' => array('type' => XenForo_DataWriter::TYPE_STRING, 'maxLength' => 10),
                'purchased_currency' => array('type' => XenForo_DataWriter::TYPE_STRING, 'maxLength' => 3)
            )
        );
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'purchase_id')) {
            return false;
        }

        return array('xf_bdpaygate_purchase' => $this->_getPurchaseModel()->getPurchaseById($id));
    }

    protected function _getUpdateCondition($tableName)
    {
        $conditions = array();

        foreach (array('purchase_id') as $field) {
            $conditions[] = $field . ' = ' . $this->_db->quote($this->getExisting($field));
        }

        return implode(' AND ', $conditions);
    }

    /**
     * @return bdPaygate_Model_Purchase
     */
    protected function _getPurchaseModel()
    {
        return $this->getModelFromCache('bdPaygate_Model_Purchase');
    }

}