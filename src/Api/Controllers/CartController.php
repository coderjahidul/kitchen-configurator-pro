<?php
/**
 * Cart REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api\Controllers;

use KitchenConfiguratorPro\Api\ApiResponse;
use KitchenConfiguratorPro\Api\RestController;
use KitchenConfiguratorPro\Domain\Exceptions\NotFoundException;
use KitchenConfiguratorPro\Security\RateLimiter;
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Security\RestInputValidator;
use KitchenConfiguratorPro\Security\SecurityLogger;
use KitchenConfiguratorPro\Services\CartIntegrationService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /kcp/v1/cart/add
 */
final class CartController extends RestController {

	/**
	 * Maximum cart add requests per client per minute.
	 */
	private const RATE_LIMIT_MAX = 20;

	/**
	 * Rate limit window in seconds.
	 */
	private const RATE_LIMIT_WINDOW = 60;

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/cart/add',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_to_cart' ),
				'permission_callback' => array( RestAuth::class, 'require_mutation_auth' ),
				'args'                => array(
					'uuid'     => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => array( RestInputValidator::class, 'validate_uuid' ),
					),
					'quantity' => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'validate_callback' => array( RestInputValidator::class, 'validate_quantity' ),
					),
				),
			)
		);
	}

	/**
	 * Add configuration to WooCommerce cart.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function add_to_cart( WP_REST_Request $request ): WP_REST_Response {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return ApiResponse::error(
				'kcp_woocommerce_missing',
				__( 'WooCommerce is required for cart integration.', 'kitchen-configurator-pro' ),
				503
			);
		}

		/** @var RateLimiter $limiter */
		$limiter = $this->container->get( RateLimiter::class );
		$key     = RateLimiter::client_key( 'cart_add' );

		if ( ! $limiter->attempt( $key, self::RATE_LIMIT_MAX, self::RATE_LIMIT_WINDOW ) ) {
			SecurityLogger::rate_limit_exceeded( 'cart_add' );

			return ApiResponse::error(
				'kcp_rate_limit_exceeded',
				__( 'Too many cart requests. Please wait and try again.', 'kitchen-configurator-pro' ),
				429
			);
		}

		$uuid     = (string) $request->get_param( 'uuid' );
		$quantity = max( 1, (int) $request->get_param( 'quantity' ) );

		try {
			/** @var CartIntegrationService $cart_service */
			$cart_service = $this->container->get( CartIntegrationService::class );
			$context      = $this->owner_context( $request );

			$result = $cart_service->add_configuration(
				$uuid,
				$context['user_id'],
				$context['session_id'],
				$quantity
			);

			$redirect = 'yes' === get_option( 'woocommerce_cart_redirect_after_add', 'no' )
				? $result['cart_url']
				: '';

			return ApiResponse::success(
				$result,
				array(
					'redirect'   => $redirect,
					'cart_count'   => function_exists( 'WC' ) && WC()->cart
						? WC()->cart->get_cart_contents_count()
						: 0,
					'cart_hash'    => function_exists( 'WC' ) && WC()->cart
						? WC()->cart->get_cart_hash()
						: '',
				),
				201
			);
		} catch ( NotFoundException $exception ) {
			unset( $exception );

			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		} catch ( \RuntimeException $exception ) {
			return ApiResponse::error(
				'kcp_cart_error',
				$exception->getMessage(),
				422
			);
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}
}
