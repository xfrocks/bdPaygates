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

		if (!empty($upgrade['record']['extra']))
		{
			// this is an active user upgrade record
			$extra = unserialize($upgrade['record']['extra']);

			if (!empty($extra['bdPaygate_processorClass']) AND !empty($extra['bdPaygate_subscriptionId']))
			{
				// this is a subscription
				if (is_callable(array(
					$extra['bdPaygate_processorClass'],
					'getSubscriptionLink'
				)))
				{
					$upgrade['bdPaygate_subscriptionLink'] = call_user_func(array(
						$extra['bdPaygate_processorClass'],
						'getSubscriptionLink'
					), $extra['bdPaygate_subscriptionId']);
				}
			}
		}

		return $upgrade;
	}

}
