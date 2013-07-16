<?php

class bdPaygate_Processor_PayPal extends bdPaygate_Processor_Abstract
{
	public function isAvailable()
	{
		$paypalAccount = XenForo_Application::getOptions()->get('payPalPrimaryAccount');

		return !empty($paypalAccount);
	}

	public function getSupportedCurrencies()
	{
		return array(
			bdPaygate_Processor_Abstract::CURRENCY_USD,
			bdPaygate_Processor_Abstract::CURRENCY_CAD,
			bdPaygate_Processor_Abstract::CURRENCY_AUD,
			bdPaygate_Processor_Abstract::CURRENCY_GBP,
			bdPaygate_Processor_Abstract::CURRENCY_EUR,
		);
	}

	public function isRecurringSupported()
	{
		return true;
	}

	public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
	{
		$amount = false;
		$currency = false;

		return $this->validateCallback2($request, $transactionId, $paymentStatus, $transactionDetails, $itemId, $amount, $currency);
	}

	public function validateCallback2(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId, &$amount, &$currency)
	{
		$input = new XenForo_Input($request);
		$filtered = $input->filter(array(
			'test_ipn' => XenForo_Input::UINT,
			'business' => XenForo_Input::STRING,
			'receiver_email' => XenForo_Input::STRING,
			'txn_type' => XenForo_Input::STRING,
			'txn_id' => XenForo_Input::STRING,
			'parent_txn_id' => XenForo_Input::STRING,
			'mc_currency' => XenForo_Input::STRING,
			'mc_gross' => XenForo_Input::UNUM,
			'payment_status' => XenForo_Input::STRING,
			'custom' => XenForo_Input::STRING,
		));

		$transactionId = (!empty($filtered['txn_id']) ? ('paypal_' . $filtered['txn_id']) : '');
		$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;
		$transactionDetails = array_merge($_POST, $filtered);
		$itemId = $filtered['custom'];
		$amount = $filtered['mc_gross'];
		$currency = $filtered['mc_currency'];
		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');

		try
		{
			if ($filtered['test_ipn'] && $this->_sandboxMode())
			{
				$validator = XenForo_Helper_Http::getClient('https://www.sandbox.paypal.com/cgi-bin/webscr');
			}
			else
			{
				$validator = XenForo_Helper_Http::getClient('http://www.paypal.com/cgi-bin/webscr');
			}
			$validator->setParameterPost('cmd', '_notify-validate');
			$validator->setParameterPost($_POST);
			$validatorResponse = $validator->request('POST');

			if (!$validatorResponse || $validatorResponse->getBody() != 'VERIFIED' || $validatorResponse->getStatus() != 200)
			{
				if (!empty($validatorResponse))
				{
					$transactionDetails['validator'] = $validator->getUri(true);
					$transactionDetails['validator_status'] = $validatorResponse->getStatus();
					$transactionDetails['validator_response'] = $validatorResponse->getBody();
				}

				$this->_setError('Request not validated');
				return false;
			}
		}
		catch (Zend_Http_Client_Exception $e)
		{
			$this->_setError('Connection to PayPal failed');
			return false;
		}

		if (strtolower($filtered['business']) != strtolower(XenForo_Application::get('options')->payPalPrimaryAccount) && strtolower($filtered['receiver_email']) != strtolower(XenForo_Application::get('options')->payPalPrimaryAccount))
		{
			$this->_setError('Invalid business or receiver_email');
			return false;
		}

		switch ($filtered['txn_type'])
		{
			case 'web_accept':
			case 'subscr_payment':
				$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
				break;
		}

		if ($filtered['payment_status'] == 'Refunded' || $filtered['payment_status'] == 'Reversed')
		{
			$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
			
			if (!empty($filtered['parent_txn_id']))
			{
				$transactionDetails[bdPaygate_Processor_Abstract::TRANSACTION_DETAILS_REJECTED_TID] = 'paypal_' . $filtered['parent_txn_id'];
			}
		}

		return true;
	}

	public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$this->_assertAmount($amount);
		$this->_assertCurrency($currency);
		$this->_assertItem($itemName, $itemId);
		$this->_assertRecurring($recurringInterval, $recurringUnit);

		$formAction = $this->_sandboxMode() ? 'https://www.sandbox.paypal.com/cgi-bin/websrc' : 'https://www.paypal.com/cgi-bin/websrc';
		$callToAction = new XenForo_Phrase('bdpaygate_paypal_call_to_action');
		$paypalAccount = XenForo_Application::getOptions()->get('payPalPrimaryAccount');
		$returnUrl = $this->_generateReturnUrl($extraData);
		$callbackUrl = $this->_generateCallbackUrl($extraData);

		// convert variables to PayPal format
		$currencyPP = utf8_strtoupper($currency);
		$recurringUnitPP = '';
		switch ($recurringUnit)
		{
			case bdPaygate_Processor_Abstract::RECURRING_UNIT_DAY:
				$recurringUnitPP = 'D';
				break;
			case bdPaygate_Processor_Abstract::RECURRING_UNIT_MONTH:
				$recurringUnitPP = 'M';
				break;
			case bdPaygate_Processor_Abstract::RECURRING_UNIT_YEAR:
				$recurringUnitPP = 'Y';
				break;
		}

		if ($recurringInterval !== false AND $recurringUnit !== false)
		{
			// recurring payment
			$form = <<<EOF
<form action="{$formAction}" method="POST">
	<input type="hidden" name="cmd" value="_xclick-subscriptions" />
	<input type="hidden" name="a3" value="{$amount}" />
	<input type="hidden" name="p3" value="{$recurringInterval}" />
	<input type="hidden" name="t3" value="{$recurringUnitPP}" />
	<input type="hidden" name="src" value="1" />
	<input type="hidden" name="sra" value="1" />

	<input type="submit" value="{$callToAction}" class="button" />

	<input type="hidden" name="business" value="{$paypalAccount}" />
	<input type="hidden" name="currency_code" value="{$currencyPP}" />
	<input type="hidden" name="item_name" value="$itemName" />
	<input type="hidden" name="quantity" value="1" />
	<input type="hidden" name="no_note" value="1" />
	<input type="hidden" name="no_shipping" value="1" />
	<input type="hidden" name="custom" value="$itemId" />

	<input type="hidden" name="charset" value="utf-8" />

	<input type="hidden" name="return" value="{$returnUrl}" />
	<input type="hidden" name="notify_url" value="{$callbackUrl}" />
</form>
EOF;
		}
		else
		{
			// one time payment
			$form = <<<EOF
<form action="{$formAction}" method="POST">
	<input type="hidden" name="cmd" value="_xclick" />
	<input type="hidden" name="amount" value="{$amount}" />

	<input type="submit" value="{$callToAction}" class="button" />

	<input type="hidden" name="business" value="{$paypalAccount}" />
	<input type="hidden" name="currency_code" value="{$currencyPP}" />
	<input type="hidden" name="item_name" value="$itemName" />
	<input type="hidden" name="quantity" value="1" />
	<input type="hidden" name="no_note" value="1" />
	<input type="hidden" name="no_shipping" value="1" />
	<input type="hidden" name="custom" value="$itemId" />

	<input type="hidden" name="charset" value="utf-8" />

	<input type="hidden" name="return" value="{$returnUrl}" />
	<input type="hidden" name="notify_url" value="{$callbackUrl}" />
</form>
EOF;
		}

		return $form;
	}

}
