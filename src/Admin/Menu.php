<?php
/**
 * Admin menu registration.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin;

use KitchenConfiguratorPro\Admin\Pages\AccessoriesPage;
use KitchenConfiguratorPro\Admin\Pages\CabinetCategoriesPage;
use KitchenConfiguratorPro\Admin\Pages\CabinetsPage;
use KitchenConfiguratorPro\Admin\Pages\ColorsPage;
use KitchenConfiguratorPro\Admin\Pages\ConfigurationsPage;
use KitchenConfiguratorPro\Admin\Pages\DashboardPage;
use KitchenConfiguratorPro\Admin\Pages\HandlesPage;
use KitchenConfiguratorPro\Admin\Pages\LayoutsPage;
use KitchenConfiguratorPro\Admin\Pages\MaterialsPage;
use KitchenConfiguratorPro\Admin\Pages\PricingRulesPage;
use KitchenConfiguratorPro\Admin\Pages\PlinthsPage;
use KitchenConfiguratorPro\Admin\Pages\SettingsPage;
use KitchenConfiguratorPro\Admin\Pages\WorktopsPage;
use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Security\CapabilityManager;

/**
 * Registers the KCP admin menu and submenus.
 */
final class Menu {

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
	 * Register admin menus.
	 *
	 * @return void
	 */
	public function register(): void {
		$capability = CapabilityManager::CAP_MANAGE;

		add_menu_page(
			__( 'Kitchen Configurator', 'kitchen-configurator-pro' ),
			__( 'Kitchen Configurator', 'kitchen-configurator-pro' ),
			$capability,
			'kitchen-configurator-pro',
			array( $this->container->get( DashboardPage::class ), 'render' ),
			'dashicons-layout',
			56
		);

		$pages = array(
			array( DashboardPage::class, __( 'Dashboard', 'kitchen-configurator-pro' ), 'kitchen-configurator-pro' ),
			array( LayoutsPage::class, __( 'Layouts', 'kitchen-configurator-pro' ), 'kcp-layouts' ),
			array( CabinetCategoriesPage::class, __( 'Cabinet Categories', 'kitchen-configurator-pro' ), 'kcp-cabinet-categories' ),
			array( CabinetsPage::class, __( 'Cabinets', 'kitchen-configurator-pro' ), 'kcp-cabinets' ),
			array( MaterialsPage::class, __( 'Materials', 'kitchen-configurator-pro' ), 'kcp-materials' ),
			array( ColorsPage::class, __( 'Colors', 'kitchen-configurator-pro' ), 'kcp-colors' ),
			array( HandlesPage::class, __( 'Handles', 'kitchen-configurator-pro' ), 'kcp-handles' ),
			array( AccessoriesPage::class, __( 'Accessories', 'kitchen-configurator-pro' ), 'kcp-accessories' ),
			array( WorktopsPage::class, __( 'Worktops', 'kitchen-configurator-pro' ), 'kcp-worktops' ),
			array( PlinthsPage::class, __( 'Plinths', 'kitchen-configurator-pro' ), 'kcp-plinths' ),
			array( PricingRulesPage::class, __( 'Pricing Rules', 'kitchen-configurator-pro' ), 'kcp-pricing-rules' ),
			array( ConfigurationsPage::class, __( 'Configurations', 'kitchen-configurator-pro' ), 'kcp-configurations' ),
			array( SettingsPage::class, __( 'Settings', 'kitchen-configurator-pro' ), 'kcp-settings' ),
		);

		foreach ( $pages as $page ) {
			/** @var class-string $class */
			$class = $page[0];

			add_submenu_page(
				'kitchen-configurator-pro',
				$page[1],
				$page[1],
				$capability,
				$page[2],
				array( $this->container->get( $class ), 'render' )
			);
		}
	}
}
