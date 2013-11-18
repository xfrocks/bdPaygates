<?php

class bdPaygate_Option
{
	public static function get($key, $subKey = null)
	{
		$options = XenForo_Application::getOptions();

		return $options->get(sprintf('bdPaygate_%s', $key), $subKey);
	}

	public static function renderEnabledCurrencies(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$value = $preparedOption['option_value'];
		$choices = array();

		$currencies = XenForo_Model::create('bdPaygate_Model_Processor')->getCurrencies();
		foreach ($currencies as $currencyCode => $currencyName)
		{
			$choices[] = array(
				'value' => $currencyCode,
				'label' => $currencyName,
				'selected' => !isset($value[$currencyCode]) OR !empty($value[$currencyCode]),
			);
		}

		$preparedOption['formatParams'] = $choices;

		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	public static function verifyEnabledCurrencies(array &$enabledCurrencies, XenForo_DataWriter $dw, $fieldName)
	{
		$currencies = XenForo_Model::create('bdPaygate_Model_Processor')->getCurrencies();
		$value = array();

		foreach (array_keys($currencies) as $currency)
		{
			if (in_array($currency, $enabledCurrencies))
			{
				$value[$currency] = 1;
			}
			else
			{
				$value[$currency] = 0;
			}
		}

		$enabledCurrencies = $value;
		return true;
	}

}
