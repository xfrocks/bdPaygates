<?php

class bdPaygate_XenResource_DataWriter_Category extends XFCP_bdPaygate_XenResource_DataWriter_Category
{
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$fields['xf_resource_category']['bdpaygate_allow_commercial_local'] = array(
			'type' => XenForo_DataWriter::TYPE_UINT,
			'default' => 0,
		);

		return $fields;
	}

	protected function _preSave()
	{
		if (isset($GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERADMIN_CATEGORY_SAVE]))
		{
			$GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERADMIN_CATEGORY_SAVE]->bdPaygate_actionSave($this);
		}

		return parent::_preSave();
	}

}
