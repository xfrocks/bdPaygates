<?php

class bdPaygate_XenForo_DataWriter_User extends XFCP_bdPaygate_XenForo_DataWriter_User
{
	protected function _postDelete()
	{
		$this->getModelFromCache('bdPaygate_Model_Purchase')->deleteUserRecords($this->get('user_id'));

		return parent::_postDelete();
	}
}