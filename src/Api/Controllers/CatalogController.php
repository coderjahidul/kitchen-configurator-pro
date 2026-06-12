<?php
/**
 * Catalog REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api\Controllers;

use KitchenConfiguratorPro\Api\ApiResponse;
use KitchenConfiguratorPro\Api\RestController;
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Services\CatalogService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * GET /kcp/v1/catalog
 */
final class CatalogController extends RestController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/catalog',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_catalog' ),
				'permission_callback' => array( RestAuth::class, 'allow_public_read' ),
			)
		);
	}

	/**
	 * Return full active catalog.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_catalog( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		/** @var CatalogService $catalog */
		$catalog  = $this->container->get( CatalogService::class );
		$response = $catalog->get_full_catalog();

		return ApiResponse::success(
			$response->to_array(),
			array(
				'cache_version' => (int) get_option( 'kcp_catalog_cache_version', 1 ),
			)
		);
	}
}
