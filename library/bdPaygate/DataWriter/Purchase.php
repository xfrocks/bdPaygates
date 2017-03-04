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

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        $this->_doAnalytics();
    }

    protected function _doAnalytics()
    {
        if (!XenForo_Application::getOptions()->get('bdAnalytics_trackEcommerce')) {
            XenForo_Helper_File::log(__CLASS__, 'No option');
            return;
        }

        $addOns = XenForo_Application::get('addOns');
        if (empty($addOns['bdAnalytics'])) {
            XenForo_Helper_File::log(__CLASS__, 'No add-on');
            return;
        }

        /** @var XenForo_Model_User $userModel */
        $userModel = $this->getModelFromCache('XenForo_Model_User');
        $user = $userModel->getUserById($this->get('user_id'), array('join' => XenForo_Model_User::FETCH_USER_FULL));
        if (empty($user)) {
            XenForo_Helper_File::log(__CLASS__, 'No user');
            return;
        }

        $transactionId = $this->get('purchase_id');
        $revenue = $this->get('purchased_amount');
        $currency = $this->get('purchased_currency');
        $itemName = sprintf('%s_%d', $this->get('content_type'), $this->get('content_id'));

        $result = bdAnalytics_Helper_GoogleAnalyticsCollect::postTransaction($user, $transactionId, $revenue, $currency, array(
            array(
                'name' => $itemName,
                'price' => $revenue,
                'quantity' => 1,
                'category' => __CLASS__,
            )
        ));
        XenForo_Helper_File::log(__CLASS__, var_export($result, true));
    }

    /**
     * @return bdPaygate_Model_Purchase
     */
    protected function _getPurchaseModel()
    {
        return $this->getModelFromCache('bdPaygate_Model_Purchase');
    }

}