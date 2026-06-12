<?php
/**
 * Abstract REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Domain\Exceptions\KcpException;
use KitchenConfiguratorPro\Domain\Exceptions\NotFoundException;
use KitchenConfiguratorPro\Domain\Exceptions\PricingException;
use KitchenConfiguratorPro\Domain\Exceptions\ValidationException;
use KitchenConfiguratorPro\Security\RestAuth;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Base REST controller with shared helpers.
 */
abstract class RestController {

	/**
	 * REST API namespace.
	 */
	protected const NAMESPACE = 'kcp/v1';

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	protected Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register routes — implemented by subclasses.
	 *
	 * @return void
	 */
	abstract public function register_routes(): void;

	/**
	 * Resolve owner context from request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array{user_id: int|null, session_id: string|null}
	 */
	protected function owner_context( WP_REST_Request $request ): array {
		$user_id    = RestAuth::current_user_id();
		$session_id = RestAuth::resolve_session_id( $request );

		if ( null === $user_id && '' === $session_id ) {
			$session_id = RestAuth::generate_session_id();
		}

		return array(
			'user_id'    => $user_id,
			'session_id' => null !== $user_id ? null : $session_id,
		);
	}

	/**
	 * Parse JSON body as associative array.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string, mixed>
	 */
	protected function get_json_body( WP_REST_Request $request ): array {
		$params = $request->get_json_params();

		return is_array( $params ) ? $params : array();
	}

	/**
	 * Handle domain exceptions and return standardized errors.
	 *
	 * @param \Throwable $exception Caught exception.
	 * @return WP_REST_Response
	 */
	protected function handle_exception( \Throwable $exception ): WP_REST_Response {
		if ( $exception instanceof ValidationException ) {
			return ApiResponse::validation_error( $exception->errors() );
		}

		if ( $exception instanceof NotFoundException ) {
			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		}

		if ( $exception instanceof PricingException || $exception instanceof KcpException ) {
			return ApiResponse::error(
				'kcp_pricing_error',
				$exception->getMessage(),
				422
			);
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return ApiResponse::error(
				'kcp_internal_error',
				$exception->getMessage(),
				500
			);
		}

		return ApiResponse::error(
			'kcp_internal_error',
			__( 'An unexpected error occurred.', 'kitchen-configurator-pro' ),
			500
		);
	}
}
