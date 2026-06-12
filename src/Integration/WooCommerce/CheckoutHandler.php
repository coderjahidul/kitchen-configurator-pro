<?php
/**
 * WooCommerce checkout validation handler.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Security\SecurityLogger;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;

/**
 * Validates configuration price integrity before checkout completes.
 */
final class CheckoutHandler {

	/**
	 * @param PricingEngine $pricing_engine Pricing engine.
	 */
	public function __construct(
		private readonly PricingEngine $pricing_engine
	) {
	}

	/**
	 * Register checkout hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_cart_integrity' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_cart_integrity_with_cart' ), 10, 2 );
	}

	/**
	 * Validate all KCP cart items during checkout process.
	 *
	 * @return void
	 */
	public function validate_cart_integrity(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$this->validate_cart( WC()->cart );
	}

	/**
	 * Validate cart during checkout validation (Blocks + classic).
	 *
	 * @param array<string, mixed> $data   Posted checkout data.
	 * @param \WP_Error            $errors Validation errors.
	 * @return void
	 */
	public function validate_cart_integrity_with_cart( array $data, \WP_Error $errors ): void {
		unset( $data );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! CartHandler::is_kcp_cart_item( $cart_item ) ) {
				continue;
			}

			if ( ! $this->verify_cart_item( $cart_item ) ) {
				SecurityLogger::price_integrity_failed(
					'checkout_validation',
					array(
						'uuid' => (string) ( $cart_item[ CartHandler::META_UUID ] ?? '' ),
					)
				);

				$errors->add(
					'kcp_price_integrity',
					__( 'Your kitchen configuration price is no longer valid. Please return to the configurator and update your design.', 'kitchen-configurator-pro' )
				);
				return;
			}
		}
	}

	/**
	 * Validate cart and add WooCommerce notices on failure.
	 *
	 * @param \WC_Cart $cart Cart object.
	 * @return void
	 */
	private function validate_cart( \WC_Cart $cart ): void {
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! CartHandler::is_kcp_cart_item( $cart_item ) ) {
				continue;
			}

			if ( ! $this->verify_cart_item( $cart_item ) ) {
				SecurityLogger::price_integrity_failed(
					'checkout_process',
					array(
						'uuid' => (string) ( $cart_item[ CartHandler::META_UUID ] ?? '' ),
					)
				);

				wc_add_notice(
					__( 'Your kitchen configuration price is no longer valid. Please return to the configurator and update your design.', 'kitchen-configurator-pro' ),
					'error'
				);
				return;
			}
		}
	}

	/**
	 * Verify cart item price hash against live server calculation.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return bool
	 */
	public function verify_cart_item( array $cart_item ): bool {
		$config_json = (string) ( $cart_item[ CartHandler::META_CONFIG ] ?? '' );
		$stored_hash = (string) ( $cart_item[ CartHandler::META_HASH ] ?? '' );
		$stored_total = (string) ( $cart_item[ CartHandler::META_TOTAL ] ?? '' );

		if ( '' === $config_json || '' === $stored_hash ) {
			return false;
		}

		$data = json_decode( $config_json, true );

		if ( ! is_array( $data ) ) {
			return false;
		}

		try {
			$input    = ConfigurationInput::from_array( $data );
			$snapshot = $this->pricing_engine->calculate( $input );
		} catch ( \Throwable ) {
			return false;
		}

		if ( ! hash_equals( $snapshot->price_hash->to_string(), $stored_hash ) ) {
			return false;
		}

		return hash_equals(
			number_format( (float) $snapshot->total->amount, 2, '.', '' ),
			number_format( (float) $stored_total, 2, '.', '' )
		);
	}
}
