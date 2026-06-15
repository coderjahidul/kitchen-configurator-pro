<?php
/**
 * KKF-style empty cart page.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

do_action( 'woocommerce_cart_is_empty' );
?>

<div class="kcp-cart kcp-cart--empty">
	<header class="kcp-cart__header">
		<div class="kcp-cart__title-wrap">
			<h1 class="kcp-cart__title"><?php esc_html_e( 'mijn winkelwagen', 'kitchen-configurator-pro' ); ?></h1>
			<span class="kcp-cart__count">0</span>
		</div>
	</header>

	<div class="kcp-cart__empty">
		<h2><?php esc_html_e( 'jouw winkelwagen is leeg', 'kitchen-configurator-pro' ); ?></h2>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="kcp-cart__action kcp-cart__action--primary">
			<?php esc_html_e( 'selecteer kasten', 'kitchen-configurator-pro' ); ?>
		</a>
	</div>
</div>
