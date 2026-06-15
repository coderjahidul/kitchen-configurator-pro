<?php
/**
 * WooCommerce integration service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Repositories\ProductPresetRepository;
use KitchenConfiguratorPro\Services\CartIntegrationService;
use KitchenConfiguratorPro\Services\ConfigurationService;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;
use KitchenConfiguratorPro\Services\ProductStorefrontOptionsBuilder;
use KitchenConfiguratorPro\Services\WooVariationOptionsBuilder;

/**
 * Registers WooCommerce integration services and hooks.
 */
final class WooCommerceServiceProvider {

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
		$this->container->singleton(
			ProductManager::class,
			static fn () => new ProductManager()
		);

		$this->container->singleton(
			CheckoutHandler::class,
			function () {
				return new CheckoutHandler(
					$this->container->get( PricingEngine::class )
				);
			}
		);

		$this->container->singleton(
			CartHandler::class,
			function () {
				return new CartHandler(
					$this->container->get( ProductManager::class ),
					$this->container->get( CheckoutHandler::class )
				);
			}
		);

		$this->container->singleton(
			OrderHandler::class,
			function () {
				return new OrderHandler(
					$this->container->get( ConfigurationService::class )
				);
			}
		);

		$this->container->singleton(
			OrderMetaDisplay::class,
			static fn () => new OrderMetaDisplay()
		);

		$this->container->singleton(
			CartIntegrationService::class,
			function () {
				return new CartIntegrationService(
					$this->container->get( ConfigurationService::class ),
					$this->container->get( ProductManager::class )
				);
			}
		);

		$this->container->singleton(
			ProductPresetRepository::class,
			static function () {
				global $wpdb;

				return new ProductPresetRepository( $wpdb );
			}
		);

		$this->container->singleton(
			ProductStorefrontOptionsBuilder::class,
			function () {
				return new ProductStorefrontOptionsBuilder(
					$this->container->get( LayoutRepository::class ),
					$this->container->get( MaterialRepository::class ),
					$this->container->get( ColorRepository::class ),
					$this->container->get( CabinetRepository::class )
				);
			}
		);

		$this->container->singleton(
			WooVariationOptionsBuilder::class,
			static fn () => new WooVariationOptionsBuilder()
		);

		$this->container->singleton(
			ShopPresenter::class,
			static fn () => new ShopPresenter()
		);

		$this->container->singleton(
			ProductOptionsPresenter::class,
			function () {
				return new ProductOptionsPresenter( $this->container );
			}
		);

		$this->container->singleton(
			ProductConfiguratorPresenter::class,
			function () {
				return new ProductConfiguratorPresenter( $this->container );
			}
		);
	}

	/**
	 * Boot WooCommerce hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->container->get( CartHandler::class )->register();
		$this->container->get( CheckoutHandler::class )->register();
		$this->container->get( OrderHandler::class )->register();
		$this->container->get( OrderMetaDisplay::class )->register();
		$this->container->get( ShopPresenter::class )->register();
		$this->container->get( ProductOptionsPresenter::class )->register();
		$this->container->get( ProductConfiguratorPresenter::class )->register();
	}
}
