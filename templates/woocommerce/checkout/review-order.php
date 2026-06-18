<?php
/**
 * KKF-style checkout shipping review.
 *
 * @package KitchenConfiguratorPro
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<table class="shop_table woocommerce-checkout-review-order-table kcp-checkout-shipping">
	<tbody>
		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>
		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>
	</tbody>
	<tfoot>
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<?php wc_cart_totals_shipping_html(); ?>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>
	</tfoot>
</table>
