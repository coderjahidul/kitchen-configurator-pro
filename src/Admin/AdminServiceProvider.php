<?php
/**
 * Admin service provider.
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
use KitchenConfiguratorPro\Admin\Pages\MaterialsPage;
use KitchenConfiguratorPro\Admin\Pages\PricingRulesPage;
use KitchenConfiguratorPro\Admin\Pages\PlinthsPage;
use KitchenConfiguratorPro\Admin\Pages\ProductPresetsPage;
use KitchenConfiguratorPro\Admin\Pages\SettingsPage;
use KitchenConfiguratorPro\Admin\Pages\WorktopsPage;
use KitchenConfiguratorPro\Container;

/**
 * Registers admin services and boots admin layer.
 */
final class AdminServiceProvider {

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
	 * Register container bindings.
	 *
	 * @return void
	 */
	public function register(): void {
		$pages = array(
			DashboardPage::class,
			ProductPresetsPage::class,
			CabinetCategoriesPage::class,
			CabinetsPage::class,
			MaterialsPage::class,
			ColorsPage::class,
			HandlesPage::class,
			AccessoriesPage::class,
			WorktopsPage::class,
			PlinthsPage::class,
			PricingRulesPage::class,
			ConfigurationsPage::class,
			SettingsPage::class,
		);

		foreach ( $pages as $page_class ) {
			$this->container->singleton(
				$page_class,
				function () use ( $page_class ) {
					if ( SettingsPage::class === $page_class ) {
						return new SettingsPage();
					}

					return new $page_class( $this->container );
				}
			);
		}

		$this->container->singleton(
			Menu::class,
			function () {
				return new Menu( $this->container );
			}
		);

		$this->container->singleton(
			Assets::class,
			static function () {
				return new Assets();
			}
		);
	}

	/**
	 * Boot admin hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'admin_menu', array( $this->container->get( Menu::class ), 'register' ) );
		$this->container->get( Assets::class )->register();
	}
}
