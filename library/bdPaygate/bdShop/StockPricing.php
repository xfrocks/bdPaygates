<?php

class bdPaygate_bdShop_StockPricing extends bdShop_StockPricing_Abstract
{

    protected static $_currencies = false;

    public function getConfiguration()
    {
        return array(
            'title' => '[bd] Paygate',
            'currencies' => $this->_getProcessorModel()->getEnabledCurrencies(),
        );
    }

    public function formatCost($amount, $currency)
    {
        return $this->_getProcessorModel()->formatCost($amount, $currency);
    }

    public function generateHtml($amount, $currency, $comment, array $data, XenForo_View $view)
    {
        return $this->generateHtmlRecurrence(0, $amount, $currency, $comment, $data, $view);
    }

    public function generateHtmlRecurrence($days, $amount, $currency, $comment, array $data, XenForo_View $view)
    {
        $data[] = utf8_strtolower($currency);
        $data[] = $amount;

        $processorModel = $this->_getProcessorModel();
        $itemId = $processorModel->generateItemId('bdshop', XenForo_Visitor::getInstance(), $data);

        $processorNames = $processorModel->getProcessorNames();
        $processors = array();
        foreach ($processorNames as $processorId => $processorClass) {
            $processors[$processorId] = bdPaygate_Processor_Abstract::create($processorClass);
        }

        $recurringInterval = false;
        $recurringUnit = false;
        if ($days > 0) {
            if ($days % 360 == 0) {
                $recurringInterval = $days / 365;
                $recurringUnit = bdPaygate_Processor_Abstract::RECURRING_UNIT_YEAR;
            } elseif ($days % 30 == 0) {
                $recurringInterval = $days / 30;
                $recurringUnit = bdPaygate_Processor_Abstract::RECURRING_UNIT_MONTH;
            } else {
                $recurringInterval = $days;
                $recurringUnit = bdPaygate_Processor_Abstract::RECURRING_UNIT_DAY;
            }
        }

        return implode('', bdPaygate_Processor_Abstract::prepareForms($processors, $amount, $currency, $comment, $itemId, $recurringInterval, $recurringUnit, array(bdPaygate_Processor_Abstract::EXTRA_RETURN_URL => XenForo_Link::buildPublicLink('full:shop/thanks'))));
    }

    /**
     *
     * @return bdPaygate_Model_Processor
     */
    protected function _getProcessorModel()
    {
        return $this->getModelFromCache('bdPaygate_Model_Processor');
    }
}