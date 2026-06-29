<?php
/**
 * Plugin Name:       Kitchen Configurator Pro
 * Plugin URI:        https://example.com/kitchen-configurator-pro
 * Description:       Production kitchen cabinet configurator with WooCommerce integration.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            Your Company
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kitchen-configurator-pro
 * Domain Path:       /languages
 * WC requires at least: 9.0
 * WC tested up to:   9.5
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'KCP_VERSION', '1.0.0' );
define( 'KCP_DB_VERSION', '1.7.3' );
define( 'KCP_PLUGIN_FILE', __FILE__ );
define( 'KCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KCP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$kcp_autoloader = KCP_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $kcp_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__(
					'Kitchen Configurator Pro requires Composer dependencies. Run composer install in the plugin directory.',
					'kitchen-configurator-pro'
				)
			);
		}
	);

	return;
}

require_once $kcp_autoloader;

/**
 * Declare WooCommerce HPOS compatibility.
 */
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				KCP_PLUGIN_FILE,
				true
			);
		}
	}
);

register_activation_hook( __FILE__, array( \KitchenConfiguratorPro\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \KitchenConfiguratorPro\Deactivator::class, 'deactivate' ) );

/**
 * Bootstrap the plugin.
 *
 * @return \KitchenConfiguratorPro\Plugin
 */
function kcp_plugin(): \KitchenConfiguratorPro\Plugin {
	return \KitchenConfiguratorPro\Plugin::instance();
}

kcp_plugin()->boot();
