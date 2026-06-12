<?php
/**
 * Lightweight dependency injection container.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro;

use Closure;
use InvalidArgumentException;

/**
 * Simple service container with bind and singleton support.
 */
final class Container {

	/**
	 * Factory bindings.
	 *
	 * @var array<string, callable(self): mixed>
	 */
	private array $bindings = array();

	/**
	 * Singleton instance cache.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = array();

	/**
	 * Register a transient binding.
	 *
	 * @param string               $id      Service identifier (typically class name).
	 * @param callable(self):mixed $factory Factory closure.
	 * @return void
	 */
	public function bind( string $id, callable $factory ): void {
		unset( $this->instances[ $id ] );
		$this->bindings[ $id ] = Closure::fromCallable( $factory );
	}

	/**
	 * Register a singleton binding.
	 *
	 * @param string               $id      Service identifier.
	 * @param callable(self):mixed $factory Factory closure.
	 * @return void
	 */
	public function singleton( string $id, callable $factory ): void {
		$this->bindings[ $id ] = Closure::fromCallable( $factory );
	}

	/**
	 * Resolve a service from the container.
	 *
	 * @param string $id Service identifier.
	 * @return mixed
	 *
	 * @throws InvalidArgumentException When the service is not registered.
	 */
	public function get( string $id ): mixed {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->bindings[ $id ] ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Service "%s" is not registered in the container.', $id )
			);
		}

		$service = ( $this->bindings[ $id ] )( $this );

		$this->instances[ $id ] = $service;

		return $service;
	}

	/**
	 * Check whether a service is registered.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->bindings[ $id ] ) || isset( $this->instances[ $id ] );
	}

	/**
	 * Remove a cached singleton instance.
	 *
	 * @param string $id Service identifier.
	 * @return void
	 */
	public function forget( string $id ): void {
		unset( $this->instances[ $id ] );
	}
}
