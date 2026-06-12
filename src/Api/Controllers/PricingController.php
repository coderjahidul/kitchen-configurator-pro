<?php
/**
 * Pricing REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api\Controllers;

use KitchenConfiguratorPro\Api\ApiResponse;
use KitchenConfiguratorPro\Api\RestController;
use KitchenConfiguratorPro\Security\RateLimiter;
use KitchenConfiguratorPro\Security\RestInputValidator;
use KitchenConfiguratorPro\Security\SecurityLogger;
use KitchenConfiguratorPro\Services\ConfigurationService;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /kcp/v1/pricing/calculate
 */
final class PricingController extends RestController {

	/**
	 * Maximum pricing requests per client per minute.
	 */
	private const RATE_LIMIT_MAX = 60;

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
			'/pricing/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate' ),
				'permission_callback' => '__return_true',
				'args'                => RestInputValidator::configuration_args( true ),
			)
		);
	}

	/**
	 * Calculate server-side pricing for a configuration payload.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function calculate( WP_REST_Request $request ): WP_REST_Response {
		/** @var RateLimiter $limiter */
		$limiter = $this->container->get( RateLimiter::class );
		$key     = RateLimiter::client_key( 'pricing_calculate' );

		if ( ! $limiter->attempt( $key, self::RATE_LIMIT_MAX, self::RATE_LIMIT_WINDOW ) ) {
			SecurityLogger::rate_limit_exceeded( 'pricing_calculate' );

			return ApiResponse::error(
				'kcp_rate_limit_exceeded',
				__( 'Too many pricing requests. Please wait and try again.', 'kitchen-configurator-pro' ),
				429
			);
		}

		try {
			/** @var PricingEngine $engine */
			$engine = $this->container->get( PricingEngine::class );
			/** @var ConfigurationService $config_service */
			$config_service = $this->container->get( ConfigurationService::class );

			$data     = $this->get_json_body( $request );
			$input    = $config_service->parse_input( $data );
			$snapshot = $engine->calculate( $input );

			return ApiResponse::success(
				$snapshot->to_array(),
				array(
					'calculated_at' => $snapshot->calculated_at,
				)
			);
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}
}
