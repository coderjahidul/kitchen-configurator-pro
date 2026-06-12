<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'KCP_PLUGIN_DIR' ) ) {
	define( 'KCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

$autoloader = KCP_PLUGIN_DIR . 'vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

/**
 * Allow themes or mu-plugins to preserve KCP data on uninstall.
 *
 * @param bool $drop_tables Whether to drop custom tables.
 */
$drop_tables = (bool) apply_filters( 'kcp_uninstall_drop_tables', true );

if ( $drop_tables ) {
	global $wpdb;

	if ( class_exists( \KitchenConfiguratorPro\Database\Migrations\Migration_1_1_0::class ) ) {
		\KitchenConfiguratorPro\Database\Migrations\Migration_1_1_0::set_db( $wpdb );
		\KitchenConfiguratorPro\Database\Migrations\Migration_1_1_0::down();
	}

	if ( class_exists( \KitchenConfiguratorPro\Database\Migrations\Migration_1_0_0::class ) ) {
		\KitchenConfiguratorPro\Database\Migrations\Migration_1_0_0::set_db( $wpdb );
		\KitchenConfiguratorPro\Database\Migrations\Migration_1_0_0::down();
	}
}

$options = array(
	'kcp_db_version',
	'kcp_wc_product_id',
	'kcp_settings',
	'kcp_catalog_cache_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

$role = get_role( 'administrator' );

if ( $role ) {
	$role->remove_cap( 'manage_kcp' );
}
