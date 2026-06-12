<?php
/**
 * Admin dashboard page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\AccessoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ConfigurationRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Security\CapabilityManager;

/**
 * Plugin admin dashboard.
 */
final class DashboardPage {

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
		return 'kitchen-configurator-pro';
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kitchen-configurator-pro' ) );
		}

		$stats = array(
			'layouts'        => $this->container->get( LayoutRepository::class )->count(),
			'cabinets'       => $this->container->get( CabinetRepository::class )->count(),
			'materials'      => $this->container->get( MaterialRepository::class )->count(),
			'accessories'    => $this->container->get( AccessoryRepository::class )->count(),
			'configurations' => $this->container->get( ConfigurationRepository::class )->count(),
			'db_version'     => get_option( 'kcp_db_version', '—' ),
			'plugin_version' => KCP_VERSION,
		);

		$path = KCP_PLUGIN_DIR . 'templates/admin/dashboard.php';

		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'Dashboard template not found.', 'kitchen-configurator-pro' ) );
		}

		include $path;
	}
}
