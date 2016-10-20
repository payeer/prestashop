<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/payeer.php');

if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
{
	$err = false;
	$message = '';
	
	// logging

	$log_text = 
		"--------------------------------------------------------\n" .
		"operation id		" . $_POST['m_operation_id'] . "\n" .
		"operation ps		" . $_POST['m_operation_ps'] . "\n" .
		"operation date		" . $_POST['m_operation_date'] . "\n" .
		"operation pay date	" . $_POST['m_operation_pay_date'] . "\n" .
		"shop				" . $_POST['m_shop'] . "\n" .
		"order id			" . $_POST['m_orderid'] . "\n" .
		"amount				" . $_POST['m_amount'] . "\n" .
		"currency			" . $_POST['m_curr'] . "\n" .
		"description		" . base64_decode($_POST['m_desc']) . "\n" .
		"status				" . $_POST['m_status'] . "\n" .
		"sign				" . $_POST['m_sign'] . "\n\n";
	
	$log_file = Configuration::get('payeer_log');
	
	if (!empty($log_file))
	{
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . $log_file, $log_text, FILE_APPEND);
	}

	// digital signature verification and ip

	$sign_hash = strtoupper(hash('sha256', implode(":", array(
		$_POST['m_operation_id'],
		$_POST['m_operation_ps'],
		$_POST['m_operation_date'],
		$_POST['m_operation_pay_date'],
		$_POST['m_shop'],
		$_POST['m_orderid'],
		$_POST['m_amount'],
		$_POST['m_curr'],
		$_POST['m_desc'],
		$_POST['m_status'],
		Configuration::get('secret_key')
	))));
	
	$valid_ip = true;
	$sIP = str_replace(' ', '', Configuration::get('ip_filter'));
	
	if (!empty($sIP))
	{
		$arrIP = explode('.', $_SERVER['REMOTE_ADDR']);
		if (!preg_match('/(^|,)(' . $arrIP[0] . '|\*{1})(\.)' .
		'(' . $arrIP[1] . '|\*{1})(\.)' .
		'(' . $arrIP[2] . '|\*{1})(\.)' .
		'(' . $arrIP[3] . '|\*{1})($|,)/', $sIP))
		{
			$valid_ip = false;
		}
	}
	
	if (!$valid_ip)
	{
		$message .= " - the ip address of the server is not trusted\n" .
		"   trusted ip: " . $sIP . "\n" .
		"   the ip of the current server: " . $_SERVER['REMOTE_ADDR'] . "\n";
		$err = true;
	}

	if ($_POST['m_sign'] != $sign_hash)
	{
		$message .= " - do not match the digital signature\n";
		$err = true;
	}

	if (!$err)
	{
		// loading order
		
		$cart = new Cart(intval($_POST['m_orderid']));
		$order_curr = new Currency(intval($cart->id_currency));
		$order_curr = $order_curr->iso_code == 'RUR' ? 'RUB' : $order_curr->iso_code;
		$order_amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
		
		// check the amount and currency
	
		if ($_POST['m_amount'] != $order_amount)
		{
			$message .= " - wrong amount\n";
			$err = true;
		}

		if ($_POST['m_curr'] != $order_curr)
		{
			$message .= " - wrong currency\n";
			$err = true;
		}
		
		// проверка статуса
		
		if (!$err)
		{
			$payeer = new Payeer();
			
			switch ($_POST['m_status'])
			{
				case 'success':
					$payeer->validateOrder((int)($_POST['m_orderid']), 2, (float)($_POST['m_amount']), $payeer->displayName, NULL, array(), NULL, false, false);
					break;
					
				default:
					$message .= " - the payment status is not success\n";
					$payeer->validateOrder((int)($_POST['m_orderid']), 8, (float)($_POST['m_amount']), $payeer->displayName, NULL, array(), NULL, false, false);
					$err = true;
					break;
			}
		}
	}
	
	if ($err)
	{
		$to = Configuration::get('email_error');

		if (!empty($to))
		{
			$message = "Failed to make the payment through Payeer for the following reasons:\n\n" . $message . "\n" . $log_text;
			$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"Content-type: text/plain; charset=utf-8 \r\n";
			mail($to, 'Error payment', $message, $headers);
		}
		
		exit($_POST['m_orderid'] . '|error');
	}
	else
	{
		exit($_POST['m_orderid'] . '|success');
	}
}