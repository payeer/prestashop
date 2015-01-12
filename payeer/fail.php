<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
?>

<h3>Оплата заказа № <? echo $_GET['m_orderid']; ?> не прошла успешно</h3>
<br/>
<p class="cart_navigation">
	<a href='/' class="exclusive_large">Далее</a>
</p>

<?php
include(dirname(__FILE__).'/../../footer.php');
?>