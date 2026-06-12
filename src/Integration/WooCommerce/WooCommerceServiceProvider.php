<?php
/**
 * WooCommerce integration service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Services\CartIntegrationService;
use KitchenConfiguratorPro\Services\ConfigurationService;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;

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
			CartHandler::class,
			static fn () => new CartHandler()
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
	}
}
