<?php
/**
 * Frontend configurator assets.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Enqueues frontend CSS and ES module JavaScript.
 */
final class Assets {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_module_type' ), 10, 3 );
	}

	/**
	 * Enqueue configurator assets when shortcode is present.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! Shortcode::is_rendered() && ! Shortcode::post_has_shortcode() ) {
			return;
		}

		wp_enqueue_style(
			'kcp-configurator',
			KCP_PLUGIN_URL . 'assets/frontend/css/configurator.css',
			array(),
			KCP_VERSION
		);

		wp_enqueue_script(
			'kcp-configurator',
			KCP_PLUGIN_URL . 'assets/frontend/js/main.js',
			array(),
			KCP_VERSION,
			true
		);

		wp_script_add_data( 'kcp-configurator', 'type', 'module' );

		$settings = get_option(
			'kcp_settings',
			array(
				'currency' => 'EUR',
			)
		);

		wp_localize_script(
			'kcp-configurator',
			'kcpConfigurator',
			array(
				'apiUrl'             => esc_url_raw( rest_url( 'kcp/v1' ) ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'isLoggedIn'         => is_user_logged_in(),
				'currency'           => sanitize_text_field( (string) ( $settings['currency'] ?? 'EUR' ) ),
				'pluginUrl'          => KCP_PLUGIN_URL,
				'woocommerceActive'  => class_exists( 'WooCommerce' ),
				'cartUrl'            => function_exists( 'wc_get_cart_url' ) ? esc_url_raw( wc_get_cart_url() ) : '',
				'i18n'               => array(
					'loading'           => __( 'Loading…', 'kitchen-configurator-pro' ),
					'error'             => __( 'Something went wrong. Please try again.', 'kitchen-configurator-pro' ),
					'save'              => __( 'Save Configuration', 'kitchen-configurator-pro' ),
					'saved'             => __( 'Configuration saved.', 'kitchen-configurator-pro' ),
					'updated'           => __( 'Configuration updated.', 'kitchen-configurator-pro' ),
					'next'              => __( 'Next', 'kitchen-configurator-pro' ),
					'back'              => __( 'Back', 'kitchen-configurator-pro' ),
					'total'             => __( 'Total', 'kitchen-configurator-pro' ),
					'subtotal'          => __( 'Subtotal', 'kitchen-configurator-pro' ),
					'tax'               => __( 'VAT', 'kitchen-configurator-pro' ),
					'calculating'       => __( 'Calculating price…', 'kitchen-configurator-pro' ),
					'selectLayout'      => __( 'Choose your kitchen layout', 'kitchen-configurator-pro' ),
					'addCabinet'        => __( 'Add cabinet', 'kitchen-configurator-pro' ),
					'removeCabinet'     => __( 'Remove', 'kitchen-configurator-pro' ),
					'projectTitle'      => __( 'Project title', 'kitchen-configurator-pro' ),
					'myProjects'          => __( 'My saved configurations', 'kitchen-configurator-pro' ),
					'noProjects'          => __( 'No saved configurations yet.', 'kitchen-configurator-pro' ),
					'loadProject'         => __( 'Load', 'kitchen-configurator-pro' ),
					'newProject'          => __( 'New configuration', 'kitchen-configurator-pro' ),
					'stepLayout'          => __( 'Layout', 'kitchen-configurator-pro' ),
					'stepCabinets'        => __( 'Cabinets', 'kitchen-configurator-pro' ),
					'stepFinishes'        => __( 'Finishes', 'kitchen-configurator-pro' ),
					'stepExtras'          => __( 'Extras', 'kitchen-configurator-pro' ),
					'stepSummary'         => __( 'Summary', 'kitchen-configurator-pro' ),
					'noCabinets'          => __( 'Add at least one cabinet to continue.', 'kitchen-configurator-pro' ),
					'cabinet'             => __( 'Cabinet', 'kitchen-configurator-pro' ),
					'material'            => __( 'Material', 'kitchen-configurator-pro' ),
					'color'               => __( 'Color', 'kitchen-configurator-pro' ),
					'handle'              => __( 'Handle', 'kitchen-configurator-pro' ),
					'accessories'         => __( 'Accessories', 'kitchen-configurator-pro' ),
					'worktop'             => __( 'Worktop', 'kitchen-configurator-pro' ),
					'plinth'              => __( 'Plinth', 'kitchen-configurator-pro' ),
					'width'               => __( 'Width (mm)', 'kitchen-configurator-pro' ),
					'height'              => __( 'Height (mm)', 'kitchen-configurator-pro' ),
					'depth'               => __( 'Depth (mm)', 'kitchen-configurator-pro' ),
					'length'              => __( 'Length (mm)', 'kitchen-configurator-pro' ),
					'addToCart'           => __( 'Add to Cart', 'kitchen-configurator-pro' ),
					'addingToCart'        => __( 'Adding to cart…', 'kitchen-configurator-pro' ),
					'saveBeforeCart'      => __( 'Save your configuration before adding to cart.', 'kitchen-configurator-pro' ),
				),
			)
		);
	}

	/**
	 * Add type="module" to configurator script tag.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @return string
	 */
	public function add_module_type( string $tag, string $handle, string $src ): string {
		if ( 'kcp-configurator' !== $handle ) {
			return $tag;
		}

		return sprintf(
			'<script type="module" src="%s" id="%s-js"></script>',
			esc_url( $src ),
			esc_attr( $handle )
		);
	}
}
