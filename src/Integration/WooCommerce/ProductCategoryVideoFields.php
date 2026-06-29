<?php
/**
 * Video upload field on WooCommerce product category admin screens.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Integration\WooCommerce;

use KitchenConfiguratorPro\Services\ProductCategoryVideoService;

/**
 * Adds an optional video attachment field to all product categories.
 */
final class ProductCategoryVideoFields {

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
			'kcp-product-category-video-fields',
			KCP_PLUGIN_URL . 'assets/admin/js/product-category-video-fields.js',
			array( 'jquery' ),
			KCP_VERSION,
			true
		);

		wp_localize_script(
			'kcp-product-category-video-fields',
			'kcpProductCategoryVideoFields',
			array(
				'selectTitle'  => __( 'Select category video', 'kitchen-configurator-pro' ),
				'selectButton' => __( 'Use video', 'kitchen-configurator-pro' ),
				'emptyLabel'   => __( 'No video selected', 'kitchen-configurator-pro' ),
			)
		);
	}

	/**
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function render_add_fields( string $taxonomy ): void {
		unset( $taxonomy );

		$this->render_fields( 0, '', '', false );
	}

	/**
	 * @param \WP_Term $term Category term.
	 */
	public function render_edit_fields( \WP_Term $term ): void {
		$video_id  = ProductCategoryVideoService::get_video_id( (int) $term->term_id );
		$video_url = $video_id > 0 ? ProductCategoryVideoService::get_video_url( (int) $term->term_id ) : '';
		$tile_label = ProductCategoryVideoService::get_promo_tile_label( (int) $term->term_id );

		$this->render_fields( $video_id, $video_url, $tile_label, true );
	}

	/**
	 * @param int $term_id Term ID.
	 */
	public function save_fields( int $term_id ): void {
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		if ( ! isset( $_POST['kcp_category_video_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['kcp_category_video_nonce'] ) ), 'kcp_category_video_save' ) ) {
			return;
		}

		ProductCategoryVideoService::save_video_id(
			$term_id,
			max( 0, (int) ( $_POST['kcp_category_video_id'] ?? 0 ) )
		);

		ProductCategoryVideoService::save_promo_tile_label(
			$term_id,
			sanitize_text_field( wp_unslash( (string) ( $_POST['kcp_promo_tile_label'] ?? '' ) ) )
		);
	}

	/**
	 * @param int    $video_id   Attachment ID.
	 * @param string $video_url  Attachment URL for preview.
	 * @param string $tile_label Promo tile label.
	 * @param bool   $is_edit    Whether this is the edit form.
	 */
	private function render_fields( int $video_id, string $video_url, string $tile_label, bool $is_edit ): void {
		$path = KCP_PLUGIN_DIR . 'templates/admin/partials/product-category-video-field.php';

		if ( ! is_readable( $path ) ) {
			return;
		}

		include $path;
	}
}
