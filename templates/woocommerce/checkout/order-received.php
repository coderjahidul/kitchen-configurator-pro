<?php
/**
 * KKF-style order received fallback message.
 *
 * Used when login or email verification is required before showing order details.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="kcp-thankyou__content kcp-thankyou__content--fallback">
	<h1 class="kcp-thankyou__title">
		<span><?php esc_html_e( 'bestelling', 'kitchen-configurator-pro' ); ?></span>
		<span><?php esc_html_e( 'succesvol', 'kitchen-configurator-pro' ); ?></span>
		<span><?php esc_html_e( 'afgerond', 'kitchen-configurator-pro' ); ?></span>
	</h1>
	<div class="kcp-thankyou__message">
		<p><?php esc_html_e( 'Bedankt voor jouw bestelling, het aftellen kan beginnen!', 'kitchen-configurator-pro' ); ?></p>
		<p><?php esc_html_e( 'Je ontvangt van ons een bevestiging per mail met alle informatie die je nodig hebt.', 'kitchen-configurator-pro' ); ?></p>
	</div>
</div>
