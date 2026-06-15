<?php
/**
 * Single product tabs as accordion panels.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( empty( $product_tabs ) ) {
	return;
}
?>
<div class="woocommerce-tabs wc-tabs-wrapper kcp-product-tabs">
	<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
		<details class="kcp-product-tabs__item" <?php echo 0 === array_search( $key, array_keys( $product_tabs ), true ) ? 'open' : ''; ?>>
			<summary class="kcp-product-tabs__title">
				<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
			</summary>
			<div class="kcp-product-tabs__panel woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?> panel entry-content wc-tab" id="tab-<?php echo esc_attr( $key ); ?>">
				<?php
				if ( isset( $product_tab['callback'] ) ) {
					call_user_func( $product_tab['callback'], $key, $product_tab );
				}
				?>
			</div>
		</details>
	<?php endforeach; ?>

	<?php do_action( 'woocommerce_product_after_tabs' ); ?>
</div>
