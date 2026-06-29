<?php
/**
 * WooCommerce shop and single product presentation.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Integration\WooCommerce\ProductOptionsPresenter;
use KitchenConfiguratorPro\Services\ShopBrandLandingService;
use KitchenConfiguratorPro\Services\ShopHeroService;
use KitchenConfiguratorPro\Services\ShopPromoService;
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
		add_filter( 'wc_get_template_part', array( $this, 'filter_product_card_template' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_filter( 'loop_shop_columns', array( $this, 'shop_columns' ) );
		add_filter( 'woocommerce_get_price_html', array( $this, 'format_price_html' ), 20, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_shop_hero' ), 20 );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_brand_landing_top' ), 22 );
		add_action( 'woocommerce_before_main_content', array( $this, 'render_shop_header' ), 25 );
		add_action( 'woocommerce_after_main_content', array( $this, 'render_shop_promo' ), 4 );
		add_action( 'woocommerce_after_main_content', array( $this, 'render_brand_landing_bottom' ), 5 );
		add_filter( 'astra_the_title_enabled', array( $this, 'disable_astra_archive_title' ) );
		add_filter( 'astra_apply_hero_header_banner', array( $this, 'disable_astra_hero_banner' ) );
		add_filter( 'woocommerce_show_page_title', array( $this, 'hide_woocommerce_page_title' ) );
		add_action( 'pre_get_posts', array( $this, 'hide_root_brand_product_loop' ) );

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

		if ( ShopBrandLandingService::is_active() ) {
			$classes[] = 'kcp-brand-landing-active';
		}

		if ( ShopBrandLandingService::is_root_brand_page() ) {
			$classes[] = 'kcp-brand-root-page';
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

		if ( ShopBrandLandingService::is_active() ) {
			wp_enqueue_script(
				'kcp-shop-brand',
				KCP_PLUGIN_URL . 'assets/frontend/js/shop-brand.js',
				array(),
				KCP_VERSION,
				true
			);
		}

		if ( ShopPromoService::is_enabled() && ( is_shop() || ShopBrandLandingService::is_active() ) ) {
			wp_enqueue_script(
				'kcp-shop-promo',
				KCP_PLUGIN_URL . 'assets/frontend/js/shop-promo.js',
				array(),
				KCP_VERSION,
				true
			);
		}

		if ( is_shop() ) {
			$hero_images = ShopHeroService::get_settings()['image_urls'] ?? array();

			if ( is_array( $hero_images ) && count( $hero_images ) > 1 ) {
				wp_enqueue_script(
					'kcp-shop-hero',
					KCP_PLUGIN_URL . 'assets/frontend/js/shop-hero.js',
					array(),
					KCP_VERSION,
					true
				);
			}
		}

		if ( ! is_product() ) {
			return;
		}

		/** @var ProductOptionsPresenter $options_presenter */
		$options_presenter = kcp_plugin()->container()->get( ProductOptionsPresenter::class );

		if ( $options_presenter->is_part_edit_request() ) {
			wp_enqueue_style(
				'kcp-part-edit',
				KCP_PLUGIN_URL . 'assets/frontend/css/part-edit.css',
				array( 'kcp-shop' ),
				KCP_VERSION
			);

			wp_enqueue_script(
				'kcp-part-edit',
				KCP_PLUGIN_URL . 'assets/frontend/js/part-edit.js',
				array(),
				KCP_VERSION,
				true
			);

			return;
		}

		wp_enqueue_script(
			'kcp-product-options',
			KCP_PLUGIN_URL . 'assets/frontend/js/product-options.js',
			array( 'jquery' ),
			KCP_VERSION,
			true
		);
	}

	/**
	 * Render the configurable shop hero section.
	 *
	 * @return void
	 */
	public function render_shop_hero(): void {
		if ( ! is_shop() || ! ShopHeroService::is_enabled() ) {
			return;
		}

		$hero = ShopHeroService::get_settings();
		$path = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/shop-hero.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * Force plugin product cards in WooCommerce loops.
	 *
	 * @param string $template Template path.
	 * @param string $slug     Template slug.
	 * @param string $name     Template name.
	 * @return string
	 */
	public function filter_product_card_template( string $template, string $slug, string $name ): string {
		if ( 'content' !== $slug || 'product' !== $name || ! $this->is_shop_context() || is_product() ) {
			return $template;
		}

		$override = KCP_PLUGIN_DIR . 'templates/woocommerce/content-product.php';

		return file_exists( $override ) ? $override : $template;
	}

	/**
	 * Disable the Astra archive banner title on brand landing pages.
	 *
	 * @param bool $enabled Whether the title is enabled.
	 * @return bool
	 */
	public function disable_astra_archive_title( bool $enabled ): bool {
		if ( ShopBrandLandingService::is_active() || is_shop() ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Disable the Astra hero/archive banner on brand landing pages.
	 *
	 * @param bool $apply Whether to apply the hero banner.
	 * @return bool
	 */
	public function disable_astra_hero_banner( bool $apply ): bool {
		if ( ShopBrandLandingService::is_active() || is_shop() ) {
			return false;
		}

		return $apply;
	}

	/**
	 * Hide default WooCommerce archive title on brand landing pages.
	 *
	 * @param bool $show Whether to show the page title.
	 * @return bool
	 */
	public function hide_woocommerce_page_title( bool $show ): bool {
		if ( ShopBrandLandingService::is_active() || is_shop() ) {
			return false;
		}

		return $show;
	}

	/**
	 * Hide the default WooCommerce product loop on root brand landing pages.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	public function hide_root_brand_product_loop( \WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( ! ShopBrandLandingService::is_root_brand_page() ) {
			return;
		}

		$query->set( 'post__in', array( 0 ) );
	}

	/**
	 * Render the top brand landing sections.
	 *
	 * @return void
	 */
	public function render_brand_landing_top(): void {
		if ( ! ShopBrandLandingService::is_active() ) {
			return;
		}

		$model = ShopBrandLandingService::get_view_model();
		$path  = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/brand-landing-top.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * Render USP bar and promotional tiles after the shop product grid.
	 *
	 * @return void
	 */
	public function render_shop_promo(): void {
		if ( ! is_shop() ) {
			return;
		}

		ShopPromoService::render();
	}

	/**
	 * Render brand story and back link after the product loop.
	 *
	 * @return void
	 */
	public function render_brand_landing_bottom(): void {
		if ( ! ShopBrandLandingService::is_active() ) {
			return;
		}

		$model = ShopBrandLandingService::get_view_model();
		$path  = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/brand-landing-bottom.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * Render the shop section header.
	 *
	 * @return void
	 */
	public function render_shop_header(): void {
		if ( ShopBrandLandingService::is_active() ) {
			return;
		}

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

		$amount = $this->resolve_storefront_price( $product );

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
	 * Resolve storefront price text for archive cards.
	 */
	public static function format_archive_price_html( \WC_Product $product ): string {
		$presenter = new self();
		$amount    = $presenter->resolve_storefront_price( $product );

		if ( $amount > 0 ) {
			return self::format_dutch_price( $amount );
		}

		return '';
	}

	/**
	 * Resolve the storefront amount for shop/archive price output.
	 *
	 * Mirrors single-product live price defaults for KCP-rendered variable products.
	 *
	 * @param \WC_Product $product Product object.
	 * @return float
	 */
	private function resolve_storefront_price( \WC_Product $product ): float {
		$amount = (float) wc_get_price_to_display( $product );

		if ( ! $product->is_type( 'variable' ) ) {
			return $amount;
		}

		/** @var WooVariationOptionsBuilder $builder */
		$builder = kcp_plugin()->container()->get( WooVariationOptionsBuilder::class );

		if ( ! $builder->can_render( $product ) ) {
			return $amount;
		}

		$options = $builder->build( $product );
		$base    = (float) ( $options['base_price'] ?? $amount );
		$groups  = is_array( $options['option_groups'] ?? null ) ? $options['option_groups'] : array();

		if ( empty( $groups ) ) {
			return $base;
		}

		foreach ( $groups as $group ) {
			$base += $this->resolve_default_group_modifier( is_array( $group ) ? $group : array() );
		}

		return max( 0.0, $base );
	}

	/**
	 * Resolve the selected default modifier for a storefront option group.
	 *
	 * @param array<string, mixed> $group Option group.
	 * @return float
	 */
	private function resolve_default_group_modifier( array $group ): float {
		$items = is_array( $group['items'] ?? null ) ? $group['items'] : array();

		if ( empty( $items ) ) {
			return 0.0;
		}

		$default_id = sanitize_key( (string) ( $group['default_item'] ?? '' ) );

		if ( '' === $default_id ) {
			$default_id = sanitize_key( (string) ( $items[0]['id'] ?? '' ) );
		}

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( sanitize_key( (string) ( $item['id'] ?? '' ) ) === $default_id ) {
				return (float) ( $item['price_modifier'] ?? 0 );
			}
		}

		return 0.0;
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
