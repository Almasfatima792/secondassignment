<?php
/**
 * @version 3.0.0
 */
?>
<span class="wc-stripe-card-icons-container">
	<?php foreach($cards as $id):?>
		<img class="wc-stripe-card-icon"
		src="<?php echo wc_stripe()->assets_url() . 'img/cards/' . $id . '.svg'?>" />
	<?php endforeach;?>
</span>