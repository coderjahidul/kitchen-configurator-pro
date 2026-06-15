<?php
/**
 * Embeds configurator on WooCommerce single product pages.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;

/**
 * Renders linked configurator presets on WooCommerce product pages.
 */
final class ProductConfiguratorPresenter {

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
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_configurator' ), 12 );
	}

	/**
	 * Render configurator for products linked in KCP admin.
	 *
	 * @return void
	 */
	public function render_configurator(): void {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_the_ID();

		if ( $product_id <= 0 ) {
			return;
		}

		/** @var ProductPresetRepository $presets */
		$presets = $this->container->get( ProductPresetRepository::class );
		$preset  = $presets->find_by_wc_product_id( $product_id );

		if ( null === $preset || ! $preset->is_active ) {
			return;
		}

		if ( $preset->has_storefront_options() ) {
			return;
		}

		echo '<div class="kcp-product-configurator">';
		echo do_shortcode( '[kitchen_configurator product_id="' . $product_id . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}
}
