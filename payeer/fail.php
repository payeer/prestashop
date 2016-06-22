<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_GET['m_orderid'], 0, 32));
?>

<h3>Оплата заказа № <?php echo $order_id; ?> не прошла успешно</h3>
<br/>
<p class="cart_navigation">
	<a href='/' class="exclusive_large">Далее</a>
</p>

<?php
include(dirname(__FILE__).'/../../footer.php');
?>