<?php
/**
 * WooCommerce order handler.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Services\ConfigurationService;

/**
 * Persists configuration snapshots to order item meta and updates configuration status.
 */
final class OrderHandler {

	/**
	 * Order item meta keys.
	 */
	public const META_UUID    = '_kcp_configuration_uuid';
	public const META_CONFIG  = '_kcp_configuration_json';
	public const META_PRICING = '_kcp_pricing_snapshot_json';
	public const META_HASH    = '_kcp_price_hash';
	public const META_TOTAL   = '_kcp_total_price';

	/**
	 * @param ConfigurationService $configurations Configuration service.
	 */
	public function __construct(
		private readonly ConfigurationService $configurations
	) {
	}

	/**
	 * Register order hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'persist_order_line_meta' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'mark_configurations_ordered' ), 10, 3 );
	}

	/**
	 * Persist KCP data on order line items.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array<string, mixed>   $values        Cart item values.
	 * @param \WC_Order              $order         Order object.
	 * @return void
	 */
	public function persist_order_line_meta( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
		unset( $cart_item_key, $order );

		if ( empty( $values[ CartHandler::META_UUID ] ) ) {
			return;
		}

		$item->add_meta_data( self::META_UUID, sanitize_text_field( (string) $values[ CartHandler::META_UUID ] ), true );
		$item->add_meta_data( self::META_CONFIG, (string) ( $values[ CartHandler::META_CONFIG ] ?? '{}' ), true );
		$item->add_meta_data( self::META_PRICING, (string) ( $values[ CartHandler::META_PRICING ] ?? '{}' ), true );
		$item->add_meta_data( self::META_HASH, sanitize_text_field( (string) ( $values[ CartHandler::META_HASH ] ?? '' ) ), true );
		$item->add_meta_data( self::META_TOTAL, (string) ( $values[ CartHandler::META_TOTAL ] ?? '0' ), true );
	}

	/**
	 * Mark configurations as ordered after checkout.
	 *
	 * @param int                  $order_id Order ID.
	 * @param array<string, mixed> $posted_data Posted checkout data.
	 * @param \WC_Order|null       $order Order object.
	 * @return void
	 */
	public function mark_configurations_ordered( int $order_id, array $posted_data, ?\WC_Order $order ): void {
		unset( $posted_data );

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$uuid = (string) $item->get_meta( self::META_UUID, true );

			if ( '' === $uuid ) {
				continue;
			}

			$this->configurations->mark_ordered( $uuid, $order_id );
		}
	}
}
