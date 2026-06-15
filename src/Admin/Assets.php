<?php
/**
 * Admin assets enqueue.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin;

/**
 * Enqueues admin CSS and JavaScript.
 */
final class Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue assets on KCP admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		if ( ! $this->is_kcp_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'kcp-admin',
			KCP_PLUGIN_URL . 'assets/admin/css/admin.css',
			array(),
			KCP_VERSION
		);

		wp_enqueue_script(
			'kcp-admin',
			KCP_PLUGIN_URL . 'assets/admin/js/admin.js',
			array(),
			KCP_VERSION,
			true
		);

		if ( 'kitchen-configurator_page_kcp-products' === $hook_suffix ) {
			wp_enqueue_media();

			wp_enqueue_script(
				'kcp-product-preset-media',
				KCP_PLUGIN_URL . 'assets/admin/js/product-preset-media.js',
				array( 'jquery' ),
				KCP_VERSION,
				true
			);

			wp_localize_script(
				'kcp-product-preset-media',
				'kcpProductPresetMedia',
				array(
					'selectTitle'  => __( 'Select image', 'kitchen-configurator-pro' ),
					'selectButton' => __( 'Use image', 'kitchen-configurator-pro' ),
					'emptyLabel'   => __( 'No image selected', 'kitchen-configurator-pro' ),
				)
			);

			wp_enqueue_script(
				'kcp-product-preset-form',
				KCP_PLUGIN_URL . 'assets/admin/js/product-preset-form.js',
				array( 'kcp-product-preset-media' ),
				KCP_VERSION,
				true
			);
		}
	}

	/**
	 * Check if current screen belongs to KCP.
	 *
	 * @param string $hook_suffix Admin hook suffix.
	 * @return bool
	 */
	private function is_kcp_screen( string $hook_suffix ): bool {
		$screens = array(
			'toplevel_page_kitchen-configurator-pro',
			'kitchen-configurator_page_kcp-layouts',
			'kitchen-configurator_page_kcp-cabinet-categories',
			'kitchen-configurator_page_kcp-cabinets',
			'kitchen-configurator_page_kcp-materials',
			'kitchen-configurator_page_kcp-colors',
			'kitchen-configurator_page_kcp-handles',
			'kitchen-configurator_page_kcp-accessories',
			'kitchen-configurator_page_kcp-pricing-rules',
			'kitchen-configurator_page_kcp-configurations',
			'kitchen-configurator_page_kcp-settings',
			'kitchen-configurator_page_kcp-products',
		);

		return in_array( $hook_suffix, $screens, true );
	}
}
