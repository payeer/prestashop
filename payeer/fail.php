<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../header.php');
require_once(dirname(__FILE__) . '/payeer.php');

$payeer = new Payeer();
$order_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($_GET['m_orderid'], 0, 32));
?>

<h3><?php print($payeer->l('Payment of order', 'fail') . ' ' . $order_id . ' ' . $payeer->l('failed', 'fail')); ?></h3>
<br/>
<p class="cart_navigation">
	<a href='/' class="exclusive_large"><?php echo $payeer->l('OK', 'fail'); ?></a>
</p>

<?php
include(dirname(__FILE__).'/../../footer.php');
?>