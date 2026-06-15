<?php
/**
 * Storefront color and height selectors on single product pages.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Domain\Entities\ProductPreset;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;
use KitchenConfiguratorPro\Services\ProductStorefrontOptionsBuilder;

/**
 * Renders KKF-style product option bars from preset configuration.
 */
final class ProductOptionsPresenter {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_options' ), 21 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_hidden_fields' ), 5 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
	}

	/**
	 * Render product specs, color, and height selectors.
	 *
	 * @return void
	 */
	public function render_options(): void {
		if ( ! is_product() ) {
			return;
		}

		$preset = $this->current_preset();

		if ( null === $preset || ! $this->options_builder()->can_render( $preset ) ) {
			return;
		}

		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

		$options        = $this->options_builder()->build( $preset );
		$base_price     = (float) wc_get_price_to_display( $product );
		$colors         = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights        = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$default_color  = (string) ( $options['default_color'] ?? ( $colors[0]['id'] ?? '' ) );
		$default_height = (string) ( $options['default_height'] ?? ( $heights[0]['id'] ?? '' ) );

		include KCP_PLUGIN_DIR . 'templates/woocommerce/partials/product-options.php';
	}

	/**
	 * Output hidden cart fields for selected options.
	 *
	 * @return void
	 */
	public function render_hidden_fields(): void {
		$preset = $this->current_preset();

		if ( null === $preset || ! $this->options_builder()->can_render( $preset ) ) {
			return;
		}

		$options        = $this->options_builder()->build( $preset );
		$colors         = is_array( $options['colors'] ?? null ) ? $options['colors'] : array();
		$heights        = is_array( $options['heights'] ?? null ) ? $options['heights'] : array();
		$default_color  = (string) ( $options['default_color'] ?? ( $colors[0]['id'] ?? '' ) );
		$default_height = (string) ( $options['default_height'] ?? ( $heights[0]['id'] ?? '' ) );

		printf(
			'<input type="hidden" name="kcp_color" id="kcp-selected-color" value="%s" />',
			esc_attr( $default_color )
		);
		printf(
			'<input type="hidden" name="kcp_height" id="kcp-selected-height" value="%s" />',
			esc_attr( $default_height )
		);
	}

	/**
	 * Persist selected options on the cart line item.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id     Product ID.
	 * @return array<string, mixed>
	 */
	public function add_cart_item_data( array $cart_item_data, int $product_id ): array {
		$preset = $this->get_preset_for_product( $product_id );

		if ( null === $preset || ! $this->options_builder()->can_render( $preset ) ) {
			return $cart_item_data;
		}

		$color  = isset( $_POST['kcp_color'] ) ? sanitize_key( wp_unslash( (string) $_POST['kcp_color'] ) ) : '';
		$height = isset( $_POST['kcp_height'] ) ? sanitize_key( wp_unslash( (string) $_POST['kcp_height'] ) ) : '';

		if ( '' !== $color ) {
			$cart_item_data['kcp_color'] = $color;
		}

		if ( '' !== $height ) {
			$cart_item_data['kcp_height'] = $height;
		}

		return $cart_item_data;
	}

	/**
	 * Get preset for the current single product page.
	 *
	 * @return ProductPreset|null
	 */
	private function current_preset(): ?ProductPreset {
		if ( ! is_product() ) {
			return null;
		}

		return $this->get_preset_for_product( get_the_ID() );
	}

	/**
	 * Get preset for a WooCommerce product ID.
	 *
	 * @param int $product_id Product ID.
	 * @return ProductPreset|null
	 */
	private function get_preset_for_product( int $product_id ): ?ProductPreset {
		if ( $product_id <= 0 ) {
			return null;
		}

		/** @var ProductPresetRepository $presets */
		$presets = $this->container->get( ProductPresetRepository::class );
		$preset  = $presets->find_by_wc_product_id( $product_id );

		if ( null === $preset || ! $preset->is_active ) {
			return null;
		}

		return $preset;
	}

	/**
	 * @return ProductStorefrontOptionsBuilder
	 */
	private function options_builder(): ProductStorefrontOptionsBuilder {
		/** @var ProductStorefrontOptionsBuilder $builder */
		$builder = $this->container->get( ProductStorefrontOptionsBuilder::class );

		return $builder;
	}
}
