<?php
/**
 * Configurations admin page (read-only).
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\ConfigurationRepository;
use KitchenConfiguratorPro\Security\CapabilityManager;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Read-only list of customer configurations.
 */
final class ConfigurationsPage {

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
	 * Menu slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'kcp-configurations';
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kitchen-configurator-pro' ) );
		}

		/** @var ConfigurationRepository $repository */
		$repository = $this->container->get( ConfigurationRepository::class );
		$action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( (string) $_GET['action'] ) ) : 'list';

		if ( 'view' === $action ) {
			$this->render_view();
			return;
		}

		$items = $repository->find_all( array(), 'updated_at', 'DESC' );

		$path = KCP_PLUGIN_DIR . 'templates/admin/configurations-list.php';
		include $path;
	}

	/**
	 * Render single configuration view.
	 *
	 * @return void
	 */
	private function render_view(): void {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

		if ( $id <= 0 ) {
			wp_die( esc_html__( 'Invalid configuration ID.', 'kitchen-configurator-pro' ) );
		}

		/** @var ConfigurationRepository $repository */
		$repository = $this->container->get( ConfigurationRepository::class );
		$item       = $repository->find( $id );

		if ( null === $item ) {
			wp_die( esc_html__( 'Configuration not found.', 'kitchen-configurator-pro' ) );
		}

		$config = Arr::to_array( $item );
		$path   = KCP_PLUGIN_DIR . 'templates/admin/configuration-view.php';
		include $path;
	}
}
