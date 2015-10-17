<?php

class bdPaygate_XenForo_DataWriter_User extends XFCP_bdPaygate_XenForo_DataWriter_User
{
    protected function _postDelete()
    {
        /** @var bdPaygate_Model_Purchase $purchaseModel */
        $purchaseModel = $this->getModelFromCache('bdPaygate_Model_Purchase');
        $purchaseModel->deleteUserRecords($this->get('user_id'));

        parent::_postDelete();
    }
}