<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/payeer.php');

$payeer = new Payeer();

include(dirname(__FILE__).'/../../header.php');

$context = Context::getContext();

$currency = $context->currency;

$m_url = Configuration::get('merchant_url');

$m_shop = Configuration::get('merchant_id');

$m_orderid = $cart->id;

$m_amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');

$m_curr = $currency->iso_code;

$m_desc = base64_encode(Configuration::get('order_description'));

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
?>

<h3>Оплата через Payeer</h3>

<form action="<? echo $m_url; ?>" method="GET">	
	<input type="hidden" name="m_shop" value="<? echo $m_shop; ?>" />
	<input type="hidden" name="m_orderid" value="<? echo $m_orderid; ?>" />
	<input type="hidden" name="m_amount" value="<? echo $m_amount; ?>" />
	<input type="hidden" name="m_curr" value="<? echo $m_curr; ?>" />
	<input type="hidden" name="m_desc" value="<? echo $m_desc; ?>" />
	<input type="hidden" name="m_sign" value="<? echo $sign; ?>" />

	<p>
		<img src="payeer.png" alt="Оплата через Payeer" style="float:left; margin: 0px 10px 5px 0px;" />
		Вы выбрали оплату через Payeer
		<br/><br />
	</p>
	<br/><br/>
	<p>
		<b>Пожалуйста, подтвердите заказ, нажав кнопку 'Подтверждаю заказ'</b>
	</p>
	
	<p class="cart_navigation">
		<input type="submit" name="m_process" value="Подтверждаю заказ" class="exclusive_large" />
	</p>

</form>
<?php

include(dirname(__FILE__).'/../../footer.php');