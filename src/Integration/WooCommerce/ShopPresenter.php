<?php
/**
 * WooCommerce shop and single product presentation.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Services\WooVariationOptionsBuilder;

/**
 * Styles shop archive and single product pages to match the KKF storefront layout.
 */
final class ShopPresenter {

	/**
	 * Register WordPress and WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_locate_template', array( $this, 'locate_template' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'loop_shop_columns', array( $this, 'shop_columns' ) );
		add_filter( 'woocommerce_get_price_html', array( $this, 'format_price_html' ), 20, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_shop_header' ), 25 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_product_label' ), 4 );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'open_cart_anchor' ), 1 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'close_cart_anchor' ), 99 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'add_to_cart_text' ) );
		add_filter( 'woocommerce_post_class', array( $this, 'woocommerce_post_class' ), 20, 2 );

		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
		remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

		add_action( 'woocommerce_single_product_summary', array( $this, 'render_trust_badges' ), 35 );
		add_action( 'wp_footer', array( $this, 'render_sticky_bar' ) );
	}

	/**
	 * Load plugin WooCommerce template overrides.
	 *
	 * @param string $template      Default template path.
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 * @return string
	 */
	public function locate_template( string $template, string $template_name, string $template_path ): string {
		unset( $template_path );

		if ( ! $this->is_shop_context() ) {
			return $template;
		}

		if ( 'single-product/add-to-cart/variable.php' === $template_name ) {
			global $product;

			if ( ! $product instanceof \WC_Product ) {
				return $template;
			}

			/** @var WooVariationOptionsBuilder $builder */
			$builder = kcp_plugin()->container()->get( WooVariationOptionsBuilder::class );

			if ( ! $builder->can_render( $product ) ) {
				return $template;
			}
		}

		$override = KCP_PLUGIN_DIR . 'templates/woocommerce/' . $template_name;

		return file_exists( $override ) ? $override : $template;
	}

	/**
	 * Add storefront classes to single product wrappers.
	 *
	 * @param array<int, string> $classes CSS classes.
	 * @param \WC_Product        $product Product.
	 * @return array<int, string>
	 */
	public function woocommerce_post_class( array $classes, \WC_Product $product ): array {
		unset( $product );

		if ( is_product() ) {
			$classes[] = 'kcp-single-product';
		}

		return $classes;
	}

	/**
	 * Add body classes on shop pages.
	 *
	 * @param array<int, string> $classes Body classes.
	 * @return array<int, string>
	 */
	public function body_class( array $classes ): array {
		if ( $this->is_shop_context() ) {
			$classes[] = 'kcp-shop-active';
		}

		return $classes;
	}

	/**
	 * Force a four-column shop grid on desktop.
	 *
	 * @return int
	 */
	public function shop_columns(): int {
		return 4;
	}

	/**
	 * Enqueue storefront styles.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! $this->is_shop_context() ) {
			return;
		}

		wp_enqueue_style(
			'kcp-shop',
			KCP_PLUGIN_URL . 'assets/frontend/css/shop.css',
			array(),
			KCP_VERSION
		);

		if ( is_product() ) {
			wp_enqueue_script(
				'kcp-product-options',
				KCP_PLUGIN_URL . 'assets/frontend/js/product-options.js',
				array( 'jquery', 'wc-add-to-cart-variation' ),
				KCP_VERSION,
				true
			);
		}
	}

	/**
	 * Render the shop section header.
	 *
	 * @return void
	 */
	public function render_shop_header(): void {
		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		$title = is_shop()
			? __( 'populaire opstellingen', 'kitchen-configurator-pro' )
			: woocommerce_page_title( false );

		?>
		<div class="kcp-shop-header">
			<h1 class="kcp-shop-header__title"><?php echo esc_html( is_string( $title ) ? $title : '' ); ?></h1>
			<?php if ( is_shop() ) : ?>
				<span class="kcp-shop-header__meta"><?php esc_html_e( 'bekijk alle populaire opstellingen', 'kitchen-configurator-pro' ); ?></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the category label above the product title.
	 *
	 * @return void
	 */
	public function render_product_label(): void {
		if ( ! is_product() ) {
			return;
		}

		echo '<span class="kcp-single-product__brand" aria-hidden="true">K</span>';
	}

	/**
	 * Localize add-to-cart button label on storefront pages.
	 *
	 * @param string $text Button label.
	 * @return string
	 */
	public function add_to_cart_text( string $text ): string {
		if ( ! is_product() || ! $this->is_shop_context() ) {
			return $text;
		}

		return __( 'voeg toe aan winkelwagen', 'kitchen-configurator-pro' );
	}

	/**
	 * Open anchor wrapper for sticky add-to-cart link.
	 *
	 * @return void
	 */
	public function open_cart_anchor(): void {
		echo '<div id="kcp-product-cart" class="kcp-single-product__cart">';
	}

	/**
	 * Close anchor wrapper for sticky add-to-cart link.
	 *
	 * @return void
	 */
	public function close_cart_anchor(): void {
		echo '</div>';
	}

	/**
	 * Render trust badges on the single product page.
	 *
	 * @return void
	 */
	public function render_trust_badges(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( $product instanceof \WC_Product && $this->product_uses_kcp_options( $product ) ) {
			return;
		}

		$badges = array(
			__( 'op voorraad', 'kitchen-configurator-pro' ),
			__( 'real-life te zien in onze winkel', 'kitchen-configurator-pro' ),
			__( 'volledig voor-gemonteerde kasten', 'kitchen-configurator-pro' ),
			__( '5 jaar garantie', 'kitchen-configurator-pro' ),
		);

		echo '<ul class="kcp-product-badges">';

		foreach ( $badges as $badge ) {
			printf( '<li>%s</li>', esc_html( $badge ) );
		}

		echo '</ul>';
	}

	/**
	 * Render sticky add-to-cart bar on single product pages.
	 *
	 * @return void
	 */
	public function render_sticky_bar(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( $this->product_uses_kcp_options( $product ) ) {
			return;
		}

		?>
		<div class="kcp-product-sticky" aria-hidden="false">
			<div class="kcp-product-sticky__inner">
				<div class="kcp-product-sticky__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<a href="#kcp-product-cart" class="kcp-product-sticky__button button alt">
					<?php esc_html_e( 'Voeg toe aan winkelwagen', 'kitchen-configurator-pro' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Format WooCommerce prices in Dutch storefront style.
	 *
	 * @param string          $price   Price HTML.
	 * @param \WC_Product|null $product Product object.
	 * @return string
	 */
	public function format_price_html( string $price, $product ): string {
		if ( ! $this->is_shop_context() || '' === $price || ! $product instanceof \WC_Product ) {
			return $price;
		}

		$amount = (float) wc_get_price_to_display( $product );

		if ( $amount <= 0 ) {
			return $price;
		}

		return '<span class="kcp-price">' . esc_html( self::format_dutch_price( $amount ) ) . '</span>';
	}

	/**
	 * Format a numeric amount as Dutch storefront price.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function format_dutch_price( float $amount ): string {
		return number_format( $amount, 0, ',', '.' ) . ',-';
	}

	/**
	 * Whether the current request is a WooCommerce storefront page.
	 *
	 * @return bool
	 */
	private function is_shop_context(): bool {
		return function_exists( 'is_woocommerce' )
			&& ( is_woocommerce() || is_shop() || is_product() || is_product_category() || is_product_tag() );
	}

	/**
	 * Whether the product uses KCP pill selectors (preset or native variations).
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	private function product_uses_kcp_options( \WC_Product $product ): bool {
		/** @var WooVariationOptionsBuilder $builder */
		$builder = kcp_plugin()->container()->get( WooVariationOptionsBuilder::class );

		return $builder->can_render( $product );
	}
}
