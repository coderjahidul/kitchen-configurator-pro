<?php
/**
 * REST API service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api;

use KitchenConfiguratorPro\Api\Controllers\CatalogController;
use KitchenConfiguratorPro\Api\Controllers\CartController;
use KitchenConfiguratorPro\Api\Controllers\ConfigurationController;
use KitchenConfiguratorPro\Api\Controllers\DesignController;
use KitchenConfiguratorPro\Api\Controllers\PricingController;
use KitchenConfiguratorPro\Container;

/**
 * Registers REST API routes and controllers.
 */
final class ApiServiceProvider {

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
		$controllers = array(
			CatalogController::class,
			ConfigurationController::class,
			PricingController::class,
			CartController::class,
			DesignController::class,
		);

		foreach ( $controllers as $controller_class ) {
			$this->container->singleton(
				$controller_class,
				fn () => new $controller_class( $this->container )
			);
		}
	}

	/**
	 * Boot REST routes.
	 *
	 * @return void
	 */
	public function boot(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$controllers = array(
			CatalogController::class,
			ConfigurationController::class,
			PricingController::class,
			CartController::class,
			DesignController::class,
		);

		foreach ( $controllers as $controller_class ) {
			$this->container->get( $controller_class )->register_routes();
		}
	}
}
