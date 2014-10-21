<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/payeer.php');

$payeer = new Payeer();

if (isset($_POST["m_operation_id"]) && isset($_POST["m_sign"]))
{
	$m_key = Configuration::get('secret_key');
	$arHash = array($_POST['m_operation_id'],
		$_POST['m_operation_ps'],
		$_POST['m_operation_date'],
		$_POST['m_operation_pay_date'],
		$_POST['m_shop'],
		$_POST['m_orderid'],
		$_POST['m_amount'],
		$_POST['m_curr'],
		$_POST['m_desc'],
		$_POST['m_status'],
		$m_key
	);
	$sign_hash = strtoupper(hash('sha256', implode(":", $arHash)));
	
	$log_text = 
		"--------------------------------------------------------\n".
		"operation id		".$_POST["m_operation_id"]."\n".
		"operation ps		".$_POST["m_operation_ps"]."\n".
		"operation date		".$_POST["m_operation_date"]."\n".
		"operation pay date	".$_POST["m_operation_pay_date"]."\n".
		"shop				".$_POST["m_shop"]."\n".
		"order id			".$_POST["m_orderid"]."\n".
		"amount				".$_POST["m_amount"]."\n".
		"currency			".$_POST["m_curr"]."\n".
		"description		".base64_decode($_POST["m_desc"])."\n".
		"status				".$_POST["m_status"]."\n".
		"sign				".$_POST["m_sign"]."\n\n";
	
	if (Configuration::get('payeer_log') != '')
	{		
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/payeer.log', $log_text, FILE_APPEND);
	}
	
	if ($_POST["m_sign"] == $sign_hash && $_POST['m_status'] == "success")
	{
		echo $_POST['m_orderid']."|success";
	}
	else
	{
		$to = Configuration::get('email_error');
		$subject = "Error payment";
		$message = "Failed to make the payment through the system Payeer for the following reasons:\n\n";
		if ($_POST["m_sign"] != $sign_hash)
		{
			$message.=" - Do not match the digital signature\n";
		}
		
		if ($_POST['m_status'] != "success")
		{
			$message.=" - The payment status is not success\n";
		}
		
		$message.="\n".$log_text;
		$headers = "From: no-reply@".$_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
		mail($to, $subject, $message, $headers);
				
		echo $_POST['m_orderid']."|error";
	}
}
elseif ($_GET['m_orderid'])
{
	$payeer->validateOrder((int)($_GET['m_orderid']), _PS_OS_PAYMENT_, (float)($_GET['m_amount']), $payeer->displayName, NULL, array(), NULL, false, false);
	
	include(dirname(__FILE__).'/../../header.php');
	
	echo '<h3>Payment order No. ' . $_GET['m_orderid'] . ' successful</h3>';
	?>
	<br/>
	<p class="cart_navigation">
		<a href='/' class="exclusive_large">Далее</a>
	</p>
	<?
	include(dirname(__FILE__).'/../../footer.php');
}
else
{
	include(dirname(__FILE__).'/../../header.php');
	
	$m_url = Configuration::get('merchant_url');
	
	$m_shop = Configuration::get('merchant_id');

	$m_orderid = $cart->id;

	$m_amount = $cart->getOrderTotal(true, 3);

	$m_curr = Configuration::get('payeer_curr');

	$m_desc = base64_encode(Configuration::get('order_description') . ' # ' . $m_orderid);

	$m_key = Configuration::get('secret_key');

	$arHash = array(
		$m_shop,
		$m_orderid,
		$m_amount,
		$m_curr,
		$m_desc,
		$m_key
	);
	$sign = strtoupper(hash('sha256', implode(":", $arHash)));
	
	// проверка принадлежности ip списку доверенных ip
	$list_ip_str = str_replace(' ', '', Configuration::get('ip_filter'));
	
	if (!empty($list_ip_str)) 
	{
		$list_ip = explode(',', $list_ip_str);
		$this_ip = $_SERVER['REMOTE_ADDR'];
		$this_ip_field = explode('.', $this_ip);
		$list_ip_field = array();
		$i = 0;
		$valid_ip = FALSE;
		foreach ($list_ip as $ip)
		{
			$ip_field[$i] = explode('.', $ip);
			if ((($this_ip_field[0] ==  $ip_field[$i][0]) or ($ip_field[$i][0] == '*')) and
				(($this_ip_field[1] ==  $ip_field[$i][1]) or ($ip_field[$i][1] == '*')) and
				(($this_ip_field[2] ==  $ip_field[$i][2]) or ($ip_field[$i][2] == '*')) and
				(($this_ip_field[3] ==  $ip_field[$i][3]) or ($ip_field[$i][3] == '*')))
				{
					$valid_ip = TRUE;
					break;
				}
			$i++;
		}
	}
	else
	{
		$valid_ip = TRUE;
	}		
	?>

	<h3>Payment via Payeer</h3>
	
	<?
	if ($valid_ip)
	{
		?>
		<form action="<?=$m_url?>" method="get">	
			<input type="hidden" name="m_shop" value="<?=$m_shop?>">
			<input type="hidden" name="m_orderid" value="<?=$m_orderid?>" />
			<input type="hidden" name="m_amount" value="<?=$m_amount?>" />
			<input type="hidden" name="m_curr" value="<?=$m_curr?>" />
			<input type="hidden" name="m_desc" value="<?=$m_desc?>" />
			<input type="hidden" name="m_sign" value="<?=$sign?>" />

			<p>
				<img src="payeer.png" alt="Оплата через Payeer" style="float:left; margin: 0px 10px 5px 0px;" />
				You have chosen to pay via Payeer
				<br/><br />
			</p>
			<br/><br/>
			<p>
				<b>Please confirm the order by clicking the 'Confirm the order'</b>
			</p>
			
			<p class="cart_navigation">
				<input type="submit" name="m_process" value="Confirm the order" class="exclusive_large" />
			</p>

		</form>
		<?
	}
	else
	{
		$log_text = 
			"--------------------------------------------------------\n".
			"shop				".$m_shop."\n".
			"order id			".$m_orderid."\n".
			"amount				".$m_amount."\n".
			"currency			".$m_curr."\n".
			"description		".base64_decode($m_desc)."\n".
			"sign				".$sign."\n\n";
		
		$to = Configuration::get('email_error');
		$subject = "Error payment";
		$message = "Failed to make the payment through the system Payeer for the following reasons:\n\n";
		$message.=" - the ip address of the server is not trusted\n";
		$message.="   trusted ip: ".Configuration::get('ip_filter')."\n";
		$message.="   ip of the current server: ".$_SERVER['REMOTE_ADDR']."\n";
		$message.="\n".$log_text;
		$headers = "From: no-reply@".$_SERVER['HTTP_SERVER']."\r\nContent-type: text/plain; charset=utf-8 \r\n";
		mail($to, $subject, $message, $headers);

		?>
		<p>
			<img src="payeer.png" alt="Payment via Payeer" style="float:left; margin: 0px 10px 5px 0px;" />
			Unfortunately, it is impossible to pay through the payment system Payeer
			<br/><br/>
		</p>
		<br/><br/>
		<p>
			<b>The error notification of payment sent support</b>
		</p>
		<?
	}
	
	include(dirname(__FILE__).'/../../footer.php');
}

