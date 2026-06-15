<?php
/**
 * WooCommerce cart integration handler.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Security\SecurityLogger;
use KitchenConfiguratorPro\Services\ProductBreakdownBuilder;

/**
 * Applies KCP pricing and displays configuration data in the cart.
 */
final class CartHandler {

	/**
	 * Cart item data keys.
	 */
	public const META_UUID     = 'kcp_config_uuid';
	public const META_HASH     = 'kcp_price_hash';
	public const META_TOTAL    = 'kcp_total_price';
	public const META_CONFIG   = 'kcp_configuration_json';
	public const META_PRICING  = 'kcp_pricing_snapshot_json';
	public const META_TITLE    = 'kcp_configuration_title';
	public const META_UNIQUE   = 'kcp_unique_key';

	/**
	 * @param ProductManager   $products Container product manager.
	 * @param CheckoutHandler  $checkout Checkout integrity validator.
	 */
	public function __construct(
		private readonly ProductManager $products,
		private readonly CheckoutHandler $checkout
	) {
	}

	/**
	 * Register WooCommerce cart hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 6 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_cart_item_prices' ), 99 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_name', array( $this, 'filter_cart_item_name' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'filter_cart_item_price' ), 10, 3 );
	}

	/**
	 * Block direct add-to-cart of the container product without KCP metadata.
	 *
	 * @param bool                 $passed         Validation result.
	 * @param int                  $product_id     Product ID.
	 * @param int                  $quantity       Quantity.
	 * @param int                  $variation_id   Variation ID.
	 * @param array<string, mixed> $variations     Variation attributes.
	 * @param array<string, mixed> $cart_item_data Cart item metadata.
	 * @return bool
	 */
	public function validate_add_to_cart(
		bool $passed,
		int $product_id,
		int $quantity,
		int $variation_id = 0,
		array $variations = array(),
		array $cart_item_data = array()
	): bool {
		unset( $quantity, $variation_id, $variations );

		if ( ! $passed || ! $this->products->is_container_product( $product_id ) ) {
			return $passed;
		}

		if ( ! empty( $cart_item_data[ self::META_UUID ] ) && ! empty( $cart_item_data[ self::META_HASH ] ) ) {
			return true;
		}

		SecurityLogger::log(
			'wc_unauthorized_add_to_cart',
			'Blocked direct add-to-cart of KCP container product.',
			array( 'product_id' => $product_id ),
			SecurityLogger::LEVEL_WARNING
		);

		wc_add_notice(
			__( 'This product cannot be added to the cart directly.', 'kitchen-configurator-pro' ),
			'error'
		);

		return false;
	}

	/**
	 * Override line item price from server-calculated snapshot.
	 *
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public function set_cart_item_prices( \WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_key => $cart_item ) {
			if ( ! isset( $cart_item['data'] ) ) {
				continue;
			}

			if ( self::is_product_breakdown_item( $cart_item ) ) {
				$cart_item['data']->set_price( (float) ( $cart_item[ ProductBreakdownBuilder::META_TOTAL ] ?? 0 ) );
				continue;
			}

			if ( ! self::is_kcp_cart_item( $cart_item ) ) {
				continue;
			}

			if ( ! $this->checkout->verify_cart_item( $cart_item ) ) {
				SecurityLogger::price_integrity_failed(
					'cart',
					array(
						'uuid' => (string) ( $cart_item[ self::META_UUID ] ?? '' ),
					)
				);

				$cart->remove_cart_item( $cart_key );
				wc_add_notice(
					__( 'A kitchen configuration in your cart is no longer valid and was removed. Please reconfigure and try again.', 'kitchen-configurator-pro' ),
					'error'
				);
				continue;
			}

			$cart_item['data']->set_price( (float) $cart_item[ self::META_TOTAL ] );
		}
	}

	/**
	 * Display configuration summary in cart.
	 *
	 * @param array<string, mixed> $item_data Cart item data rows.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return array<string, mixed>
	 */
	public function display_cart_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item[ self::META_UUID ] ) ) {
			return $item_data;
		}

		$title = (string) ( $cart_item[ self::META_TITLE ] ?? '' );

		if ( '' !== $title ) {
			$item_data[] = array(
				'key'   => __( 'Project', 'kitchen-configurator-pro' ),
				'value' => esc_html( $title ),
			);
		}

		$config = json_decode( (string) ( $cart_item[ self::META_CONFIG ] ?? '{}' ), true );
		$cabinet_count = is_array( $config['cabinets'] ?? null ) ? count( $config['cabinets'] ) : 0;

		if ( $cabinet_count > 0 ) {
			$item_data[] = array(
				'key'   => __( 'Cabinets', 'kitchen-configurator-pro' ),
				'value' => (string) $cabinet_count,
			);
		}

		$item_data[] = array(
			'key'   => __( 'Reference', 'kitchen-configurator-pro' ),
			'value' => esc_html( (string) $cart_item[ self::META_UUID ] ),
		);

		return $item_data;
	}

	/**
	 * Use configuration title as cart line item name.
	 *
	 * @param string               $name      Product name.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @param string               $cart_key  Cart item key.
	 * @return string
	 */
	public function filter_cart_item_name( string $name, array $cart_item, string $cart_key ): string {
		unset( $cart_key );

		if ( ! empty( $cart_item[ self::META_TITLE ] ) ) {
			return sprintf(
				/* translators: %s: configuration title */
				__( 'Kitchen: %s', 'kitchen-configurator-pro' ),
				(string) $cart_item[ self::META_TITLE ]
			);
		}

		return $name;
	}

	/**
	 * Format cart item price display.
	 *
	 * @param string               $price     Formatted price.
	 * @param array<string, mixed> $cart_item Cart item.
	 * @param string               $cart_key  Cart item key.
	 * @return string
	 */
	public function filter_cart_item_price( string $price, array $cart_item, string $cart_key ): string {
		unset( $cart_key );

		if ( self::is_product_breakdown_item( $cart_item ) && function_exists( 'wc_price' ) ) {
			return wc_price( (float) ( $cart_item[ ProductBreakdownBuilder::META_TOTAL ] ?? 0 ) );
		}

		if ( ! empty( $cart_item[ self::META_TOTAL ] ) && function_exists( 'wc_price' ) ) {
			return wc_price( (float) $cart_item[ self::META_TOTAL ] );
		}

		return $price;
	}

	/**
	 * Check if cart item is a KCP configuration.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return bool
	 */
	public static function is_kcp_cart_item( array $cart_item ): bool {
		return ! empty( $cart_item[ self::META_UUID ] )
			&& ! empty( $cart_item[ self::META_HASH ] )
			&& ! empty( $cart_item[ self::META_CONFIG ] );
	}

	/**
	 * Check if cart item is a storefront product breakdown group.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return bool
	 */
	public static function is_product_breakdown_item( array $cart_item ): bool {
		return ! empty( $cart_item[ ProductBreakdownBuilder::META_PARTS ] )
			&& is_array( $cart_item[ ProductBreakdownBuilder::META_PARTS ] );
	}
}
