<?php
/**
 * Registers WordPress menu locations for the site shell.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Nav menu locations used by the KKF header/footer shell.
 */
final class SiteShellMenuRegistry {

	public const HEADER_PRIMARY   = 'kcp-shell-header-primary';
	public const HEADER_DESKTOP   = 'kcp-shell-header-desktop';
	public const HEADER_SECONDARY = 'kcp-shell-header-secondary';
	public const FOOTER_1         = 'kcp-shell-footer-1';
	public const FOOTER_2         = 'kcp-shell-footer-2';
	public const FOOTER_3         = 'kcp-shell-footer-3';
	public const FOOTER_4         = 'kcp-shell-footer-4';
	public const FOOTER_LEGAL     = 'kcp-shell-footer-legal';

	/**
	 * Register menu locations.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_locations' ) );
		add_action( 'admin_init', array( $this, 'register_locations' ) );
	}

	/**
	 * @return void
	 */
	public function register_locations(): void {
		register_nav_menus(
			array(
				self::HEADER_PRIMARY   => __( 'Configurator header (mobile primary)', 'kitchen-configurator-pro' ),
				self::HEADER_DESKTOP   => __( 'Configurator header (desktop)', 'kitchen-configurator-pro' ),
				self::HEADER_SECONDARY => __( 'Configurator header (mobile secondary)', 'kitchen-configurator-pro' ),
				self::FOOTER_1         => __( 'Configurator footer column 1', 'kitchen-configurator-pro' ),
				self::FOOTER_2         => __( 'Configurator footer column 2', 'kitchen-configurator-pro' ),
				self::FOOTER_3         => __( 'Configurator footer column 3', 'kitchen-configurator-pro' ),
				self::FOOTER_4         => __( 'Configurator footer column 4', 'kitchen-configurator-pro' ),
				self::FOOTER_LEGAL     => __( 'Configurator footer (legal links)', 'kitchen-configurator-pro' ),
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function footer_locations(): array {
		return array(
			self::FOOTER_1 => __( 'webshop', 'kitchen-configurator-pro' ),
			self::FOOTER_2 => __( 'opstellingen', 'kitchen-configurator-pro' ),
			self::FOOTER_3 => __( 'configurator', 'kitchen-configurator-pro' ),
			self::FOOTER_4 => __( 'contact', 'kitchen-configurator-pro' ),
		);
	}
}
