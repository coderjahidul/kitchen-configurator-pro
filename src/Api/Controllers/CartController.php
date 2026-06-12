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
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Services\CartIntegrationService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /kcp/v1/cart/add
 */
final class CartController extends RestController {

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
					'uuid' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
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

		$uuid = (string) $request->get_param( 'uuid' );

		if ( ! RestAuth::is_valid_uuid( $uuid ) ) {
			return ApiResponse::error(
				'kcp_invalid_uuid',
				__( 'Invalid configuration UUID.', 'kitchen-configurator-pro' ),
				400
			);
		}

		try {
			/** @var CartIntegrationService $cart_service */
			$cart_service = $this->container->get( CartIntegrationService::class );
			$context      = $this->owner_context( $request );

			$result = $cart_service->add_configuration(
				$uuid,
				$context['user_id'],
				$context['session_id']
			);

			return ApiResponse::success(
				$result,
				array(
					'redirect' => $result['cart_url'],
				),
				201
			);
		} catch ( NotFoundException $exception ) {
			unset( $exception );

			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}
}
