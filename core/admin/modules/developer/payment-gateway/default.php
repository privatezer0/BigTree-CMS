<?
	include BigTree::path("admin/modules/developer/payment-gateway/_common.php");
?>
<h1><span class="payment"></span>Payment Gateway</h1>

<div class="table">
	<summary><h2>Currently Using<small><?=$currently?></small></h2></summary>
	<section>
		<p>Choose a service below to configure your payment gateway settings.</p>
		<a class="box_select" href="authorize/">
			<span class="authorize"></span>
			<p>Authorize.Net</p>
		</a>
		<a class="box_select" href="paypal/">
			<span class="paypal"></span>
			<p>PayPal Payments Pro</p>
		</a>
		<a class="box_select" href="payflow/">
			<span class="payflow"></span>
			<p>PayPal Payflow Gateway</p>
		</a>
		<a class="box_select" href="linkpoint/">
			<span class="linkpoint"></span>
			<p>First Data / LinkPoint</p>
		</a>
	</section>
</div>