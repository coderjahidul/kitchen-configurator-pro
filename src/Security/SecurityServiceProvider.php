<?php
/**
 * Security service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

use KitchenConfiguratorPro\Container;
use KitchenConfiguratorPro\Repositories\ConfigurationHistoryRepository;
use KitchenConfiguratorPro\Services\ConfigurationAuditService;

/**
 * Registers security-related services.
 */
final class SecurityServiceProvider {

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
			ConfigurationSchemaValidator::class,
			static fn () => new ConfigurationSchemaValidator()
		);

		$this->container->singleton(
			RateLimiter::class,
			static fn () => new RateLimiter()
		);

		$this->container->singleton(
			ConfigurationHistoryRepository::class,
			static function () {
				global $wpdb;

				return new ConfigurationHistoryRepository( $wpdb );
			}
		);

		$this->container->singleton(
			ConfigurationAuditService::class,
			function () {
				return new ConfigurationAuditService(
					$this->container->get( ConfigurationHistoryRepository::class )
				);
			}
		);
	}
}
