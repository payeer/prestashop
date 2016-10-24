<?php

if (!defined('_PS_VERSION_'))
	exit;

class Payeer extends PaymentModule 
{
    public function __construct() 
	{
        $this->name = 'payeer';
        $this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'Payeer';
		$this->currencies = false;
		$this->displayName = $this->l('Payeer');
		$this->description = $this->l('Payment via Payeer');
		parent::__construct();
	}

	public function install()
	{
		if (!parent::install() 
			OR !$this->registerHook('payment') 
			OR !$this->registerHook('paymentReturn') 
			OR !$this->registerHook('updateOrderStatus'))
		{
			return false;
		}
		
		return true;
	}

	private function _displayForm()
	{
		$m_url = (Configuration::get('merchant_url') == '' ? 'https://payeer.com/merchant/' : Configuration::get('merchant_url'));
		
		$this->_html .= '
		<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">
			<fieldset >
				<legend><img src="' . __PS_BASE_URI__ . 'modules/payeer/logo.png" alt="" />' . $this->l('Settings') . '</legend>
				<label>' . $this->l('URL merchant (specify https://payeer.com/merchant/):') . '</label>
				<div class="margin-form">
					<input type="text" name="merchant_url" value="' . $m_url . '" style="width: 300px;"  />
				</div>
				<div style="clear: both;"></div>
				<label>' . $this->l('The ID of the store:') . '</label>
				<div class="margin-form">
					<input type="text" name="merchant_id" value="' . Configuration::get('merchant_id') . '" style="width: 300px;"  />
				</div>
				<div style="clear: both;"></div>
				<label>' . $this->l('Secret key:') . '</label>
				<div class="margin-form">
					<input type="text" name="secret_key" value="' . Configuration::get('secret_key') . '" style="width: 300px;" />
				</div>
				<div style="clear: both;"></div>
				<label>' . $this->l('IP filter:') . '</label>
				<div class="margin-form">
					<input type="text" name="ip_filter" value="' . Configuration::get('ip_filter') . '" style="width: 300px;" />
				</div>
				<div style="clear: both;"></div>
				<label>' . $this->l('Email for errors:') . '</label>
				<div class="margin-form">
					<input type="text" name="email_error" value="' . Configuration::get('email_error') . '" style="width: 300px;" />
				</div>
				<label>' . $this->l('The log path orders (for example, /payeer_orders.log):') . '</label>
				<div class="margin-form">
					<input type="text" name="payeer_log" value="' . Configuration::get('payeer_log') . '" style="width: 300px;" />
				</div>
				<center><input type="submit" class="button" name="btnSubmit" value="' . $this->l('Update') .'" style="margin-top: 25px;" /></center>
			</fieldset>
		</form>';
	}

	private function _postProcess()
	{
		if (isset($_POST['btnSubmit']))
		{
			if ($merchant_url = Tools::GetValue('merchant_url'))
			{
				if (empty($merchant_url))
				{
					Configuration::updateValue('merchant_url', 'https://payeer.com/merchant/');
				}
				else
				{
					Configuration::updateValue('merchant_url', $merchant_url);
				}
			}
			
			if ($merchant_id = Tools::GetValue('merchant_id'))
			{
				Configuration::updateValue('merchant_id', $merchant_id);
			}
			
			if ($secret_key = Tools::GetValue('secret_key'))
			{
				Configuration::updateValue('secret_key', $secret_key);
			}
			
			if ($ip_filter = Tools::GetValue('ip_filter'))
			{
				Configuration::updateValue('ip_filter', $ip_filter);
			}
			
			if ($email_error = Tools::GetValue('email_error'))
			{
				Configuration::updateValue('email_error', $email_error);
			}
			
			if ($payeer_log = Tools::GetValue('payeer_log'))
			{
				Configuration::updateValue('payeer_log', $payeer_log);
			}
			
			$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
		}
	}

	public function getContent()
	{
		$this->_html = '<h2>' . $this->displayName . '</h2>';

		if (!empty($_POST))
		{
			$this->_postProcess();
		}
		else
			$this->_html .= '<br />';

		$this->_displayForm();

		return $this->_html;
	}
	
	public function hookPayment($params)
	{
		if (!$this->active)
		{
			return;
		}
		
		global $smarty;

		$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getHttpHost(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
		));

		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookUpdateOrderStatus($params)
	{
	    return true;
    }
}