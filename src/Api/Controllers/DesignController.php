<?php
/**
 * Design step REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api\Controllers;

use KitchenConfiguratorPro\Api\ApiResponse;
use KitchenConfiguratorPro\Api\RestController;
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Services\DesignStepService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * GET /kcp/v1/design-step
 */
final class DesignController extends RestController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/design-step',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_design_step' ),
				'permission_callback' => array( RestAuth::class, 'allow_public_read' ),
			)
		);
	}

	/**
	 * Return public design step configuration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_design_step( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		return ApiResponse::success( DesignStepService::get_public_config() );
	}
}
