<?php
/**
 * Frontend service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

use KitchenConfiguratorPro\Container;

/**
 * Registers frontend shortcode and assets.
 */
final class FrontendServiceProvider {

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
			Shortcode::class,
			static fn () => new Shortcode()
		);

		$this->container->singleton(
			DesignShortcode::class,
			static fn () => new DesignShortcode()
		);

		$this->container->singleton(
			Assets::class,
			static fn () => new Assets()
		);

		$this->container->singleton(
			DesignAssets::class,
			static fn () => new DesignAssets()
		);
	}

	/**
	 * Boot frontend hooks.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->container->get( Shortcode::class )->register();
		$this->container->get( DesignShortcode::class )->register();
		$this->container->get( Assets::class )->register();
		$this->container->get( DesignAssets::class )->register();
	}
}
