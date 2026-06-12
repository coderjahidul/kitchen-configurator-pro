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
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Services\ConfigurationService;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;
use WP_REST_Request;
use WP_REST_Response;

/**
 * POST /kcp/v1/pricing/calculate
 */
final class PricingController extends RestController {

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
				'permission_callback' => array( RestAuth::class, 'allow_public_read' ),
				'args'                => $this->configuration_args(),
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
		try {
			/** @var PricingEngine $engine */
			$engine = $this->container->get( PricingEngine::class );
			/** @var ConfigurationService $config_service */
			$config_service = $this->container->get( ConfigurationService::class );

			$data   = $this->get_json_body( $request );
			$input  = $config_service->parse_input( $data );
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

	/**
	 * Shared configuration schema args.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function configuration_args(): array {
		return array(
			'layout_id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'title'     => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'cabinets'  => array(
				'type'     => 'array',
				'required' => true,
			),
		);
	}
}
