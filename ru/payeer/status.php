<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/payeer.php');

if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
{
	$err = false;
	$message = '';
	
	// запись логов

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

	// проверка цифровой подписи и ip

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
		$message .= " - ip-адрес сервера не является доверенным\n" .
		"   доверенные ip: " . $sIP . "\n" .
		"   ip текущего сервера: " . $_SERVER['REMOTE_ADDR'] . "\n";
		$err = true;
	}

	if ($_POST['m_sign'] != $sign_hash)
	{
		$message .= " - не совпадают цифровые подписи\n";
		$err = true;
	}

	if (!$err)
	{
		// загрузка заказа
		
		$cart = new Cart(intval($_POST['m_orderid']));
		$order_curr = new Currency(intval($cart->id_currency));
		$order_curr = $order_curr->iso_code == 'RUR' ? 'RUB' : $order_curr->iso_code;
		$order_amount = number_format($cart->getOrderTotal(true, Cart::BOTH), 2, '.', '');
		
		// проверка суммы и валюты
	
		if ($_POST['m_amount'] != $order_amount)
		{
			$message .= " - неправильная сумма\n";
			$err = true;
		}

		if ($_POST['m_curr'] != $order_curr)
		{
			$message .= " - неправильная валюта\n";
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
					$message .= " - статус платежа не является success\n";
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
			$message = "Не удалось провести платёж через систему Payeer по следующим причинам:\n\n" . $message . "\n" . $log_text;
			$headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" . 
			"Content-type: text/plain; charset=utf-8 \r\n";
			mail($to, 'Ошибка оплаты', $message, $headers);
		}
		
		exit($_POST['m_orderid'] . '|error');
	}
	else
	{
		exit($_POST['m_orderid'] . '|success');
	}
}