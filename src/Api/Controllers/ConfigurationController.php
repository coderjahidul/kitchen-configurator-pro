<?php
/**
 * Configuration REST controller.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api\Controllers;

use KitchenConfiguratorPro\Api\ApiResponse;
use KitchenConfiguratorPro\Api\RestController;
use KitchenConfiguratorPro\Domain\Exceptions\NotFoundException;
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Security\RestInputValidator;
use KitchenConfiguratorPro\Services\ConfigurationService;
use WP_REST_Request;
use WP_REST_Response;

/**
 * CRUD endpoints for customer configurations.
 */
final class ConfigurationController extends RestController {

	/**
	 * {@inheritDoc}
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/configurations',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'list_items' ),
					'permission_callback' => array( RestAuth::class, 'require_authenticated' ),
					'args'                => $this->list_args(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( RestAuth::class, 'require_mutation_auth' ),
					'args'                => $this->configuration_args(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/configurations/(?P<uuid>[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( RestAuth::class, 'require_authenticated' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( RestAuth::class, 'require_mutation_auth' ),
					'args'                => $this->configuration_args( false ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( RestAuth::class, 'require_mutation_auth' ),
				),
			)
		);
	}

	/**
	 * List configurations for the current user or guest session.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function list_items( WP_REST_Request $request ): WP_REST_Response {
		/** @var ConfigurationService $service */
		$service = $this->container->get( ConfigurationService::class );
		$context = $this->owner_context( $request );

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		$result = $service->list_for_owner(
			$context['user_id'],
			$context['session_id'],
			$page > 0 ? $page : 1,
			$per_page > 0 ? $per_page : 20
		);

		$items = array_map(
			static fn ( $config ) => $service->to_api_array( $config ),
			$result['items']
		);

		return ApiResponse::success(
			$items,
			array(
				'total'    => $result['total'],
				'page'     => $page > 0 ? $page : 1,
				'per_page' => $per_page > 0 ? $per_page : 20,
			)
		);
	}

	/**
	 * Create a new configuration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response {
		try {
			/** @var ConfigurationService $service */
			$service = $this->container->get( ConfigurationService::class );
			$context = $this->owner_context( $request );
			$data    = $this->get_json_body( $request );

			$config = $service->create( $data, $context['user_id'], $context['session_id'] );

			$meta = array();

			if ( null === $context['user_id'] && null !== $context['session_id'] ) {
				$meta['session_id'] = $context['session_id'];
			}

			return ApiResponse::success( $service->to_api_array( $config ), $meta, 201 );
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}

	/**
	 * Get a single configuration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function get_item( WP_REST_Request $request ): WP_REST_Response {
		/** @var ConfigurationService $service */
		$service = $this->container->get( ConfigurationService::class );
		$context = $this->owner_context( $request );
		$uuid    = (string) $request->get_param( 'uuid' );

		$config = $service->find_accessible( $uuid, $context['user_id'], $context['session_id'] );

		if ( null === $config ) {
			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		}

		return ApiResponse::success( $service->to_api_array( $config ) );
	}

	/**
	 * Update a configuration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function update( WP_REST_Request $request ): WP_REST_Response {
		try {
			/** @var ConfigurationService $service */
			$service = $this->container->get( ConfigurationService::class );
			$context = $this->owner_context( $request );
			$uuid    = (string) $request->get_param( 'uuid' );
			$data    = $this->get_json_body( $request );

			$config = $service->update( $uuid, $data, $context['user_id'], $context['session_id'] );

			return ApiResponse::success( $service->to_api_array( $config ) );
		} catch ( NotFoundException $exception ) {
			unset( $exception );

			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}

	/**
	 * Delete a configuration.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function delete( WP_REST_Request $request ): WP_REST_Response {
		try {
			/** @var ConfigurationService $service */
			$service = $this->container->get( ConfigurationService::class );
			$context = $this->owner_context( $request );
			$uuid    = (string) $request->get_param( 'uuid' );

			$service->delete( $uuid, $context['user_id'], $context['session_id'] );

			return ApiResponse::success(
				array(
					'deleted' => true,
					'uuid'    => $uuid,
				)
			);
		} catch ( NotFoundException $exception ) {
			unset( $exception );

			return ApiResponse::not_found( __( 'Configuration', 'kitchen-configurator-pro' ) );
		} catch ( \Throwable $exception ) {
			return $this->handle_exception( $exception );
		}
	}

	/**
	 * List query args.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function list_args(): array {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( RestInputValidator::class, 'validate_page' ),
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => 20,
				'sanitize_callback' => 'absint',
				'validate_callback' => array( RestInputValidator::class, 'validate_per_page' ),
			),
		);
	}

	/**
	 * Configuration body args.
	 *
	 * @param bool $require_layout Whether layout_id is required.
	 * @return array<string, array<string, mixed>>
	 */
	private function configuration_args( bool $require_layout = true ): array {
		return RestInputValidator::configuration_args( $require_layout );
	}
}
