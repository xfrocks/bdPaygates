<?php

class bdPaygate_XenResource_ControllerAdmin_Category extends XFCP_bdPaygate_XenResource_ControllerAdmin_Category
{
	public function actionSave()
	{
		$GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERADMIN_CATEGORY_SAVE] = $this;

		return parent::actionSave();
	}

	public function bdPaygate_actionSave(XenResource_DataWriter_Category $dw)
	{
		$dw->set('bdpaygate_allow_commercial_local', $this->_input->filterSingle('bdpaygate_allow_commercial_local', XenForo_Input::UINT));

		unset($GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERADMIN_CATEGORY_SAVE]);
	}

	protected function _getCategoryAddEditResponse(array $category)
	{
		if (empty($category['resource_category_id']))
		{
			$category['bdpaygate_allow_commercial_local'] = 1;
		}

		return parent::_getCategoryAddEditResponse($category);
	}

}
