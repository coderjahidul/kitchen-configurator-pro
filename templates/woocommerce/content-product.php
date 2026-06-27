<?php
/**
 * Product card for shop archive loops.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Services\ShopBrandLandingService;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$permalink = get_permalink( $product->get_id() );
$images    = ShopBrandLandingService::get_product_gallery_ids( $product );
$price     = \KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter::format_archive_price_html( $product );
?>
<li <?php wc_product_class( 'kcp-shop-card', $product ); ?>>
	<a class="kcp-shop-card__link" href="<?php echo esc_url( $permalink ); ?>">
		<div class="kcp-shop-card__media<?php echo count( $images ) > 1 ? ' kcp-shop-card__media--dual' : ''; ?>">
			<?php foreach ( $images as $index => $image_id ) : ?>
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'woocommerce_thumbnail',
					false,
					array(
						'class'    => 'kcp-shop-card__image' . ( 0 === $index ? ' is-primary' : ' is-secondary' ),
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);
				?>
			<?php endforeach; ?>
			<?php if ( empty( $images ) ) : ?>
				<span class="kcp-shop-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</div>

		<div class="kcp-shop-card__body">
			<h2 class="kcp-shop-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<div class="kcp-shop-card__meta">
				<span class="kcp-shop-card__price"><?php echo esc_html( $price ); ?></span>
				<span class="kcp-shop-card__stock"><?php echo esc_html( ShopBrandLandingService::get_stock_label( $product ) ); ?></span>
			</div>
		</div>
	</a>
</li>
