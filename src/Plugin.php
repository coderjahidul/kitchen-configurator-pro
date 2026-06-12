<?php
/**
 * Main plugin orchestrator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro;

use KitchenConfiguratorPro\Admin\AdminServiceProvider;
use KitchenConfiguratorPro\Api\ApiServiceProvider;
use KitchenConfiguratorPro\CoreServiceProvider;
use KitchenConfiguratorPro\Database\Migrator;

/**
 * Plugin singleton.
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Whether the plugin has been booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Whether API services have been registered.
	 *
	 * @var bool
	 */
	private bool $api_registered = false;

	/**
	 * Whether admin services have been registered.
	 *
	 * @var bool
	 */
	private bool $admin_registered = false;

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->container = new Container();
	}

	/**
	 * Get plugin instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the service container.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		$this->register_core_services();
		$this->register_hooks();
	}

	/**
	 * Register core container bindings.
	 *
	 * @return void
	 */
	private function register_core_services(): void {
		$this->container->singleton(
			Migrator::class,
			static function (): Migrator {
				global $wpdb;

				return new Migrator( $wpdb );
			}
		);

		$core = new CoreServiceProvider( $this->container );
		$core->register();
	}

	/**
	 * Register admin layer when in dashboard.
	 *
	 * @return void
	 */
	private function register_admin(): void {
		if ( $this->admin_registered || ! is_admin() ) {
			return;
		}

		$this->admin_registered = true;

		$provider = new AdminServiceProvider( $this->container );
		$provider->register();
		$provider->boot();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 5 );
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Run after all plugins are loaded.
	 *
	 * @return void
	 */
	public function on_plugins_loaded(): void {
		$this->maybe_upgrade_database();
		$this->register_api();
		$this->register_admin();
	}

	/**
	 * Register REST API layer.
	 *
	 * @return void
	 */
	private function register_api(): void {
		if ( $this->api_registered ) {
			return;
		}

		$this->api_registered = true;

		$provider = new ApiServiceProvider( $this->container );
		$provider->register();
		$provider->boot();
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'kitchen-configurator-pro',
			false,
			dirname( KCP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Run pending migrations when the database version is behind.
	 *
	 * @return void
	 */
	private function maybe_upgrade_database(): void {
		$installed_version = get_option( 'kcp_db_version', '' );

		if ( version_compare( (string) $installed_version, KCP_DB_VERSION, '>=' ) ) {
			return;
		}

		try {
			$this->container->get( Migrator::class )->run();
		} catch ( \Throwable $exception ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'KCP migration failed: ' . $exception->getMessage() );
			}

			add_action(
				'admin_notices',
				static function () use ( $exception ): void {
					if ( ! current_user_can( 'manage_kcp' ) && ! current_user_can( 'manage_options' ) ) {
						return;
					}

					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html(
							sprintf(
								/* translators: %s: error message */
								__( 'Kitchen Configurator Pro database upgrade failed: %s', 'kitchen-configurator-pro' ),
								$exception->getMessage()
							)
						)
					);
				}
			);
		}
	}
}
