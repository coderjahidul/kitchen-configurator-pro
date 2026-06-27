<?php
/**
 * Brand landing fields on WooCommerce product category admin screens.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Services\ShopBrandLandingSettingsService;

/**
 * Adds editable brand landing content to top-level product categories.
 */
final class BrandCategoryFields {

	/**
	 * Register admin hooks.
	 */
	public function register(): void {
		add_action( 'product_cat_add_form_fields', array( $this, 'render_add_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'render_edit_fields' ) );
		add_action( 'created_product_cat', array( $this, 'save_fields' ) );
		add_action( 'edited_product_cat', array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * @param string $hook Admin hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( (string) $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'product_cat' !== $taxonomy ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'kcp-admin',
			KCP_PLUGIN_URL . 'assets/admin/css/admin.css',
			array(),
			KCP_VERSION
		);

		wp_enqueue_script(
			'kcp-brand-category-fields',
			KCP_PLUGIN_URL . 'assets/admin/js/brand-category-fields.js',
			array( 'jquery' ),
			KCP_VERSION,
			true
		);

		wp_localize_script(
			'kcp-brand-category-fields',
			'kcpBrandCategoryFields',
			array(
				'selectTitle'  => __( 'Select hero image', 'kitchen-configurator-pro' ),
				'selectButton' => __( 'Use image', 'kitchen-configurator-pro' ),
				'emptyLabel'   => __( 'No image selected', 'kitchen-configurator-pro' ),
			)
		);
	}

	/**
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function render_add_fields( string $taxonomy ): void {
		unset( $taxonomy );

		$this->render_fields( ShopBrandLandingSettingsService::defaults(), false );
	}

	/**
	 * @param \WP_Term $term Category term.
	 */
	public function render_edit_fields( \WP_Term $term ): void {
		if ( 0 !== (int) $term->parent ) {
			return;
		}

		$this->render_fields( ShopBrandLandingSettingsService::get_for_term( $term ), true, $term );
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public function save_fields( int $term_id ): void {
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		if ( ! isset( $_POST['kcp_brand_landing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['kcp_brand_landing_nonce'] ) ), 'kcp_brand_landing_save' ) ) {
			return;
		}

		$term = get_term( $term_id, 'product_cat' );

		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		$parent = isset( $_POST['parent'] ) ? (int) $_POST['parent'] : (int) $term->parent;

		if ( $parent > 0 ) {
			delete_term_meta( $term_id, ShopBrandLandingSettingsService::META_KEY );

			return;
		}

		ShopBrandLandingSettingsService::save_for_term(
			$term_id,
			ShopBrandLandingSettingsService::sanitize_post( wp_unslash( $_POST ) )
		);
	}

	/**
	 * @param array<string, mixed> $settings Field values.
	 * @param bool                 $is_edit  Whether this is the edit form.
	 * @param \WP_Term|null        $term     Category term on edit.
	 */
	private function render_fields( array $settings, bool $is_edit, ?\WP_Term $term = null ): void {
		$path = KCP_PLUGIN_DIR . 'templates/admin/partials/brand-landing-category-fields.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		$hero_image_id  = (int) ( $settings['hero_image_id'] ?? 0 );
		$hero_image_url = $hero_image_id > 0 ? (string) wp_get_attachment_image_url( $hero_image_id, 'medium' ) : '';
		$usps           = is_array( $settings['usps'] ?? null ) ? $settings['usps'] : array();

		include $path;
	}
}
