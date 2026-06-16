<?php
/**
 * Plugin settings admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Security\CapabilityManager;

/**
 * Plugin settings page.
 */
final class SettingsPage {

	/**
	 * Menu slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return 'kcp-settings';
	}

	/**
	 * Render and handle settings.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'kitchen-configurator-pro' ) );
		}

		$notices  = array();
		$settings = get_option(
			'kcp_settings',
			array(
				'currency'            => 'EUR',
				'vat_rate'            => 0,
				'quote_validity_days' => 30,
				'design_check_price'  => 75,
			)
		);

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['kcp_settings_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['kcp_settings_nonce'] ) ), 'kcp_save_settings' ) ) {
				$notices[] = array(
					'type'    => 'error',
					'message' => __( 'Security check failed.', 'kitchen-configurator-pro' ),
				);
			} else {
				$settings = array_merge(
					$settings,
					array(
						'currency'            => sanitize_text_field( wp_unslash( (string) ( $_POST['currency'] ?? 'EUR' ) ) ),
						'vat_rate'            => max( 0, (float) ( $_POST['vat_rate'] ?? 0 ) ),
						'quote_validity_days' => max( 1, (int) ( $_POST['quote_validity_days'] ?? 30 ) ),
						'design_check_price'  => max( 0, (float) ( $_POST['design_check_price'] ?? 75 ) ),
					)
				);

				update_option( 'kcp_settings', $settings, false );

				$notices[] = array(
					'type'    => 'success',
					'message' => __( 'Settings saved successfully.', 'kitchen-configurator-pro' ),
				);
			}
		}

		$path = KCP_PLUGIN_DIR . 'templates/admin/settings.php';
		include $path;
	}
}
