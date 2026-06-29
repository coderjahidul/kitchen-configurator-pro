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
			CabinetSelectShortcode::class,
			static fn () => new CabinetSelectShortcode()
		);

		$this->container->singleton(
			CabinetGroupShortcode::class,
			static fn () => new CabinetGroupShortcode()
		);

		$this->container->singleton(
			CabinetListShortcode::class,
			static fn () => new CabinetListShortcode()
		);

		$this->container->singleton(
			CabinetDetailShortcode::class,
			static fn () => new CabinetDetailShortcode()
		);

		$this->container->singleton(
			CabinetRouter::class,
			static fn () => new CabinetRouter()
		);

		$this->container->singleton(
			CabinetListAssets::class,
			static fn () => new CabinetListAssets()
		);

		$this->container->singleton(
			CabinetDetailAssets::class,
			static fn () => new CabinetDetailAssets()
		);

		$this->container->singleton(
			CabinetGroupAssets::class,
			static fn () => new CabinetGroupAssets()
		);

		$this->container->singleton(
			CabinetSelectAssets::class,
			static fn () => new CabinetSelectAssets()
		);

		$this->container->singleton(
			Assets::class,
			static fn () => new Assets()
		);

		$this->container->singleton(
			DesignAssets::class,
			static fn () => new DesignAssets()
		);

		$this->container->singleton(
			SiteShellPresenter::class,
			static fn () => new SiteShellPresenter()
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
		$this->container->get( CabinetSelectShortcode::class )->register();
		$this->container->get( CabinetGroupShortcode::class )->register();
		$this->container->get( CabinetListShortcode::class )->register();
		$this->container->get( CabinetDetailShortcode::class )->register();
		$this->container->get( CabinetRouter::class )->register();
		$this->container->get( SiteShellPresenter::class )->register();
		$this->container->get( Assets::class )->register();
		$this->container->get( DesignAssets::class )->register();
		$this->container->get( CabinetSelectAssets::class )->register();
		$this->container->get( CabinetGroupAssets::class )->register();
		$this->container->get( CabinetListAssets::class )->register();
		$this->container->get( CabinetDetailAssets::class )->register();
	}
}
