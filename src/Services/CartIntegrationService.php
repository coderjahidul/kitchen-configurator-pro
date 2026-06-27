<?php
/**
 * Cart integration service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\Exceptions\NotFoundException;
use KitchenConfiguratorPro\Domain\Exceptions\ValidationException;
use KitchenConfiguratorPro\Integration\WooCommerce\CartHandler;
use KitchenConfiguratorPro\Integration\WooCommerce\ProductManager;

/**
 * Adds saved configurations to the WooCommerce cart with server-authoritative pricing.
 */
final class CartIntegrationService {

	/**
	 * @param ConfigurationService $configurations Configuration service.
	 * @param ProductManager       $products       Container product manager.
	 */
	public function __construct(
		private readonly ConfigurationService $configurations,
		private readonly ProductManager $products
	) {
	}

	/**
	 * Add a configuration to the WooCommerce cart.
	 *
	 * @param string      $uuid       Configuration UUID.
	 * @param int|null    $user_id    Requesting user ID.
	 * @param string|null $session_id Guest session ID.
	 * @param int         $quantity   Line item quantity.
	 * @return array{cart_item_key: string, cart_url: string, total_price: float}
	 *
	 * @throws NotFoundException When configuration is not found.
	 * @throws ValidationException When configuration cannot be added.
	 * @throws \RuntimeException When WooCommerce cart is unavailable.
	 */
	public function add_configuration( string $uuid, ?int $user_id = null, ?string $session_id = null, int $quantity = 1 ): array {
		$this->ensure_woocommerce_cart();

		$config = $this->configurations->prepare_for_cart( $uuid, $user_id, $session_id );
		$row    = $this->configurations->get_row_by_uuid( $uuid );

		if ( null === $row ) {
			throw new NotFoundException( __( 'Configuration not found.', 'kitchen-configurator-pro' ) );
		}

		$product_id = $this->products->ensure_container_product();

		$cart_item_data = array(
			CartHandler::META_UUID    => $uuid,
			CartHandler::META_HASH    => (string) ( $row['price_hash'] ?? '' ),
			CartHandler::META_TOTAL   => (string) ( $row['total_price'] ?? '0' ),
			CartHandler::META_CONFIG  => (string) ( $row['configuration_json'] ?? '{}' ),
			CartHandler::META_PRICING => (string) ( $row['pricing_snapshot_json'] ?? '{}' ),
			CartHandler::META_TITLE   => $config->title,
			CartHandler::META_UNIQUE  => $uuid . ':' . wp_generate_password( 8, false, false ),
		);

		$cart_item_key = WC()->cart->add_to_cart( $product_id, max( 1, $quantity ), 0, array(), $cart_item_data );

		if ( ! $cart_item_key ) {
			throw new \RuntimeException( __( 'Unable to add configuration to cart.', 'kitchen-configurator-pro' ) );
		}

		WC()->cart->calculate_totals();
		WC()->cart->set_session();

		if ( null !== WC()->session ) {
			WC()->session->set_customer_session_cookie( true );
		}

		$this->configurations->attach_cart_item( $uuid, $cart_item_key );

		return array(
			'cart_item_key' => $cart_item_key,
			'cart_url'      => wc_get_cart_url(),
			'total_price'   => (float) $row['total_price'],
		);
	}

	/**
	 * Ensure WooCommerce session and cart are available in REST/AJAX contexts.
	 *
	 * @return void
	 *
	 * @throws \RuntimeException When WooCommerce cart cannot be initialized.
	 */
	private function ensure_woocommerce_cart(): void {
		if ( ! function_exists( 'WC' ) ) {
			throw new \RuntimeException( __( 'WooCommerce cart is not available.', 'kitchen-configurator-pro' ) );
		}

		if ( null === WC()->cart || null === WC()->session ) {
			if ( function_exists( 'wc_load_cart' ) && did_action( 'woocommerce_init' ) ) {
				wc_load_cart();
			}
		}

		if ( null === WC()->cart ) {
			throw new \RuntimeException( __( 'WooCommerce cart is not available.', 'kitchen-configurator-pro' ) );
		}
	}
}
