<?php

class bdPaygate_XenForo_DataWriter_UserUpgrade extends XFCP_bdPaygate_XenForo_DataWriter_UserUpgrade
{
	protected function _getFields()
	{
		$fields = parent::_getFields();

		$currencies = $this->getModelFromCache('bdPaygate_Model_Processor')->getCurrencies();
		foreach (array_keys($currencies) as $currency)
		{
			$fields['xf_user_upgrade']['cost_currency']['allowedValues'][] = $currency;
		}

		return $fields;
	}

}
