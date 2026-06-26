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

		if ( 'kitchen-configurator_page_kcp-cabinets' === $hook_suffix ) {
			wp_enqueue_media();

			$media_script = KCP_PLUGIN_DIR . 'assets/admin/js/product-preset-media.js';

			wp_enqueue_script(
				'kcp-product-preset-media',
				KCP_PLUGIN_URL . 'assets/admin/js/product-preset-media.js',
				array( 'jquery' ),
				is_readable( $media_script ) ? (string) filemtime( $media_script ) : KCP_VERSION,
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

			$child_script = KCP_PLUGIN_DIR . 'assets/admin/js/cabinet-items-repeater.js';

			wp_enqueue_script(
				'kcp-cabinet-items-repeater',
				KCP_PLUGIN_URL . 'assets/admin/js/cabinet-items-repeater.js',
				array( 'kcp-product-preset-media' ),
				is_readable( $child_script ) ? (string) filemtime( $child_script ) : KCP_VERSION,
				true
			);
		}

		if ( in_array( $hook_suffix, array( 'kitchen-configurator_page_kcp-products', 'kitchen-configurator_page_kcp-settings', 'kitchen-configurator_page_kcp-handles', 'kitchen-configurator_page_kcp-colors', 'kitchen-configurator_page_kcp-plinths' ), true ) ) {
			wp_enqueue_media();

			$media_script = KCP_PLUGIN_DIR . 'assets/admin/js/product-preset-media.js';

			wp_enqueue_script(
				'kcp-product-preset-media',
				KCP_PLUGIN_URL . 'assets/admin/js/product-preset-media.js',
				array( 'jquery' ),
				is_readable( $media_script ) ? (string) filemtime( $media_script ) : KCP_VERSION,
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
		}

		if ( 'kitchen-configurator_page_kcp-settings' === $hook_suffix ) {
			$hero_script = KCP_PLUGIN_DIR . 'assets/admin/js/shop-hero-settings.js';

			wp_enqueue_script(
				'kcp-shop-hero-settings',
				KCP_PLUGIN_URL . 'assets/admin/js/shop-hero-settings.js',
				array( 'kcp-product-preset-media' ),
				is_readable( $hero_script ) ? (string) filemtime( $hero_script ) : KCP_VERSION,
				true
			);
		}

		if ( 'kitchen-configurator_page_kcp-products' === $hook_suffix ) {
			$form_script = KCP_PLUGIN_DIR . 'assets/admin/js/product-preset-form.js';

			wp_enqueue_script(
				'kcp-product-preset-form',
				KCP_PLUGIN_URL . 'assets/admin/js/product-preset-form.js',
				array( 'kcp-product-preset-media' ),
				is_readable( $form_script ) ? (string) filemtime( $form_script ) : KCP_VERSION,
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
			'kitchen-configurator_page_kcp-plinths',
			'kitchen-configurator_page_kcp-accessories',
			'kitchen-configurator_page_kcp-pricing-rules',
			'kitchen-configurator_page_kcp-configurations',
			'kitchen-configurator_page_kcp-settings',
			'kitchen-configurator_page_kcp-products',
		);

		return in_array( $hook_suffix, $screens, true );
	}
}
