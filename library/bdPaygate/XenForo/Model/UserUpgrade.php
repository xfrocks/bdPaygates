<?php

class bdPaygate_XenForo_Model_UserUpgrade extends XFCP_bdPaygate_XenForo_Model_UserUpgrade
{
	public function prepareUserUpgrade(array $upgrade)
	{
		$upgrade = parent::prepareUserUpgrade($upgrade);

		$currencies = $this->getModelFromCache('bdPaygate_Model_Processor')->getCurrencies();
		if (!empty($currencies[$upgrade['cost_currency']]))
		{
			$currencyName = $currencies[$upgrade['cost_currency']];

			$cost = "$upgrade[cost_amount] $currencyName";

			if ($upgrade['costPhrase'] instanceof XenForo_Phrase)
			{
				$upgrade['costPhrase']->setParams(array('cost' => $cost));
			}
			else
			{
				$upgrade['costPhrase'] = $cost;
			}
		}

		return $upgrade;
	}

}
