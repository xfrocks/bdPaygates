<?php

class bdPaygate_XenForo_Model_UserUpgrade extends XFCP_bdPaygate_XenForo_Model_UserUpgrade
{
    public function prepareUserUpgrade(array $upgrade)
    {
        $upgrade = parent::prepareUserUpgrade($upgrade);

        /** @var bdPaygate_Model_Processor $processorModel */
        $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
        $currencies = $processorModel->getCurrencies();
        if (!empty($currencies[$upgrade['cost_currency']])) {
            $currencyName = $currencies[$upgrade['cost_currency']];

            $cost = "$upgrade[cost_amount] $currencyName";

            if ($upgrade['costPhrase'] instanceof XenForo_Phrase) {
                $upgrade['costPhrase']->setParams(array('cost' => $cost));
            } else {
                $upgrade['costPhrase'] = $cost;
            }
        }

        if (!empty($upgrade['record']['extra'])) {
            // this is an active user upgrade record
            $extra = unserialize($upgrade['record']['extra']);

            if (!empty($extra['bdPaygate_processorClass'])
                && !empty($extra['bdPaygate_subscriptionId'])
            ) {
                // this is a subscription
                $getSubscriptionLinkFunc = array($extra['bdPaygate_processorClass'], 'getSubscriptionLink');
                if (is_callable($getSubscriptionLinkFunc)) {
                    $upgrade['bdPaygate_subscriptionLink'] = call_user_func($getSubscriptionLinkFunc,
                        $extra['bdPaygate_subscriptionId']);
                }
            }
        }

        return $upgrade;
    }

}
