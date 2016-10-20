<?php
include(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(dirname(__FILE__) . '/payeer.php');
include(dirname(__FILE__) . '/../../header.php');

$payeer = new Payeer();
$context = Context::getContext();
$m_url = Configuration::get('merchant_url');
$m_shop = Configuration::get('merchant_id');
$m_orderid = $cart->id;
$m_amount = number_format($cart->getOrderTotal(true, 3), 2, '.', '');
$m_curr = $context->currency->iso_code;
$m_desc = base64_encode('Payment of order №' . $m_orderid);
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

<h3>Payeer payment</h3>

<form action="<?php echo $m_url; ?>" method="GET">	
	<input type="hidden" name="m_shop" value="<?php echo $m_shop; ?>" />
	<input type="hidden" name="m_orderid" value="<?php echo $m_orderid; ?>" />
	<input type="hidden" name="m_amount" value="<?php echo $m_amount; ?>" />
	<input type="hidden" name="m_curr" value="<?php echo $m_curr; ?>" />
	<input type="hidden" name="m_desc" value="<?php echo $m_desc; ?>" />
	<input type="hidden" name="m_sign" value="<?php echo $sign; ?>" />
	<p>
		<img src="payeer.png" alt="Payment via Payeer" style="float:left; margin: 0px 10px 5px 0px;" />
		You have chosen a payment system Payeer
		<br/><br/>
	</p><br/><br/>
	<p>
		<b>Please confirm the order by clicking 'Confirm order'</b>
	</p>
	<p class="cart_navigation">
		<input type="submit" name="m_process" value="Confirm order" class="exclusive_large" />
	</p>
</form>

<?php
include(dirname(__FILE__) . '/../../footer.php');
?>