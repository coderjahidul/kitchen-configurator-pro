<?php
/**
 * Single product content layout.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'kcp-single-product', $product ); ?>>
	<div class="kcp-single-product__hero">
		<div class="kcp-single-product__gallery">
			<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
		</div>

		<div class="summary entry-summary kcp-single-product__summary">
			<?php do_action( 'woocommerce_single_product_summary' ); ?>
		</div>
	</div>

	<div class="kcp-single-product__details">
		<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
	</div>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
