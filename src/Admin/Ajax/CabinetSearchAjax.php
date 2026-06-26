<?php
/**
 * AJAX handler for cabinet search in admin child-cabinets field.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Ajax;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\CabinetRelationRepository;
use KitchenConfiguratorPro\Security\CapabilityManager;

/**
 * Provides searchable cabinet results for the admin multi-select field.
 */
final class CabinetSearchAjax {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_kcp_search_cabinets', array( $this, 'handle' ) );
	}

	/**
	 * Handle cabinet search request.
	 *
	 * @return void
	 */
	public function handle(): void {
		if ( ! current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'kitchen-configurator-pro' ) ),
				403
			);
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'kcp_search_cabinets' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'kitchen-configurator-pro' ) ),
				403
			);
		}

		$query      = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : '';
		$exclude_id = isset( $_GET['exclude_id'] ) ? (int) $_GET['exclude_id'] : 0;

		/** @var CabinetRelationRepository $repo */
		$repo    = $this->container->get( CabinetRelationRepository::class );
		$results = $repo->search_cabinets( $query, $exclude_id, 50 );

		wp_send_json_success( array( 'items' => $results ) );
	}
}
