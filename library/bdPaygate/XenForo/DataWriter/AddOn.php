<?php

class bdPaygate_XenForo_DataWriter_AddOn extends XFCP_bdPaygate_XenForo_DataWriter_AddOn
{
    protected function _postSaveAfterTransaction()
    {
        if ($this->isInsert() AND $this->get('addon_id') == 'XenResource') {
            $existingAddOn = $this->_getAddOnModel()->getAddOnById('bdPaygate');

            bdPaygate_Installer::install($existingAddOn, $existingAddOn);
        }

        parent::_postSaveAfterTransaction();
    }

}
