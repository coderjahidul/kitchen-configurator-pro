<?php
/**
 * Product card for shop archive loops.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$permalink = get_permalink( $product->get_id() );
$image_id  = $product->get_image_id();
?>
<li <?php wc_product_class( 'kcp-shop-card', $product ); ?>>
	<a class="kcp-shop-card__link" href="<?php echo esc_url( $permalink ); ?>">
		<div class="kcp-shop-card__media">
			<?php if ( $image_id ) : ?>
				<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'kcp-shop-card__image' ) ) ); ?>
			<?php else : ?>
				<span class="kcp-shop-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</div>

		<div class="kcp-shop-card__body">
			<span class="kcp-shop-card__brand" aria-hidden="true">K</span>
			<h2 class="kcp-shop-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<div class="kcp-shop-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		</div>
	</a>
</li>
