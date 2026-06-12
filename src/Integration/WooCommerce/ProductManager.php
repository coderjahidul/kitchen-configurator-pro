<?php
/**
 * WooCommerce container product manager.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

/**
 * Creates and manages the single WooCommerce container product.
 */
final class ProductManager {

	/**
	 * Option key for container product ID.
	 */
	public const OPTION_PRODUCT_ID = 'kcp_wc_product_id';

	/**
	 * Container product SKU.
	 */
	public const PRODUCT_SKU = 'kcp-kitchen-configuration';

	/**
	 * Ensure the container product exists and return its ID.
	 *
	 * @return int
	 *
	 * @throws \RuntimeException When product cannot be created.
	 */
	public function ensure_container_product(): int {
		$product_id = $this->get_product_id();

		if ( $product_id > 0 ) {
			return $product_id;
		}

		if ( ! class_exists( \WC_Product_Simple::class ) ) {
			throw new \RuntimeException( 'WooCommerce is not available.' );
		}

		$product = new \WC_Product_Simple();
		$product->set_name( __( 'Custom Kitchen Configuration', 'kitchen-configurator-pro' ) );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_virtual( true );
		$product->set_sold_individually( false );
		$product->set_sku( self::PRODUCT_SKU );
		$product->set_regular_price( '0' );
		$product->set_price( '0' );
		$product->set_description( __( 'Container product for Kitchen Configurator Pro custom configurations.', 'kitchen-configurator-pro' ) );
		$product->update_meta_data( '_kcp_container_product', 'yes' );

		$product_id = $product->save();

		if ( ! $product_id ) {
			throw new \RuntimeException( 'Failed to create WooCommerce container product.' );
		}

		update_option( self::OPTION_PRODUCT_ID, $product_id, false );

		return (int) $product_id;
	}

	/**
	 * Get container product ID.
	 *
	 * @return int
	 */
	public function get_product_id(): int {
		$product_id = (int) get_option( self::OPTION_PRODUCT_ID, 0 );

		if ( $product_id <= 0 ) {
			return 0;
		}

		$post = get_post( $product_id );

		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			return 0;
		}

		return $product_id;
	}

	/**
	 * Check if a product ID is the KCP container product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_container_product( int $product_id ): bool {
		return $product_id > 0 && $product_id === $this->get_product_id();
	}
}
