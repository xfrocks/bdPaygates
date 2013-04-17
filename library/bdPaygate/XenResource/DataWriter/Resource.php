<?php
class bdPaygate_XenResource_DataWriter_Resource extends XFCP_bdPaygate_XenResource_DataWriter_Resource
{
	protected function _preSave()
	{
		if (isset($GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE]))
		{
			$GLOBALS[bdPaygate_Constant::GLOBALS_XFRM_CONTROLLERPUBLIC_RESOURCE_SAVE]->bdPaygate_actionSave($this);
		}

		$response = parent::_preSave();

		$errors = $this->getErrors();
		if (!empty($errors['currency'])
		AND $errors['currency'] instanceof XenForo_Phrase
		AND $errors['currency']->getPhraseName() === 'please_complete_all_commercial_resource_related_fields')
		{
			// this error happened because we set `price` and `currency` but didn't set `external_purchase_url`
			
			// unset it first
			unset($this->_errors['currency']);
			
			// and do our own check
			$commercialParts = (floatval($this->get('price')) ? 1 : 0) + ($this->get('currency') ? 1 : 0);
			if ($commercialParts > 0 && $commercialParts < 2)
			{
				$this->error(new XenForo_Phrase('please_complete_all_commercial_resource_related_fields'), 'currency');
			}
		}

		return $response;
	}
}