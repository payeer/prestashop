<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');

$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_GET['m_orderid'], 0, 32));
?>

<h3>Payment of order № <?php echo $order_id; ?> successful</h3>
<br/>
<p class="cart_navigation">
	<a href='/' class="exclusive_large">Next</a>
</p>

<?php
include(dirname(__FILE__).'/../../footer.php');
?>