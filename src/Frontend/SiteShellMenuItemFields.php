<?php
/**
 * Custom image fields for configurator nav menu items.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Frontend;

/**
 * Adds submenu image fields to the WordPress menu editor.
 */
final class SiteShellMenuItemFields {

	public const META_IMAGE       = '_kcp_menu_image';
	public const META_IMAGE_HOVER = '_kcp_menu_image_hover';

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'render_fields' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_fields' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * @param int      $item_id Menu item ID.
	 * @param \WP_Post $item    Menu item object.
	 */
	public function render_fields( int $item_id, $item ): void {
		unset( $item );

		$image       = (string) get_post_meta( $item_id, self::META_IMAGE, true );
		$image_hover = (string) get_post_meta( $item_id, self::META_IMAGE_HOVER, true );
		?>
		<p class="field-kcp-menu-image description description-wide kcp-nav-menu-field">
			<label><?php esc_html_e( 'Submenu image', 'kitchen-configurator-pro' ); ?></label>
			<?php
			$name     = 'menu-item-kcp-image[' . $item_id . ']';
			$value    = $image;
			$id       = 'edit-menu-item-kcp-image-' . $item_id;
			$modifier = 'compact';
			require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
			?>
			<span class="description"><?php esc_html_e( 'Square image shown in the desktop submenu tile.', 'kitchen-configurator-pro' ); ?></span>
		</p>
		<p class="field-kcp-menu-image-hover description description-wide kcp-nav-menu-field">
			<label><?php esc_html_e( 'Submenu hover image', 'kitchen-configurator-pro' ); ?></label>
			<?php
			$name     = 'menu-item-kcp-image-hover[' . $item_id . ']';
			$value    = $image_hover;
			$id       = 'edit-menu-item-kcp-image-hover-' . $item_id;
			$modifier = 'compact';
			require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
			?>
			<span class="description"><?php esc_html_e( 'Optional image swap on hover.', 'kitchen-configurator-pro' ); ?></span>
		</p>
		<?php
	}

	/**
	 * @param int $menu_id         Menu ID.
	 * @param int $menu_item_db_id Menu item post ID.
	 */
	public function save_fields( int $menu_id, int $menu_item_db_id ): void {
		unset( $menu_id );

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$images = isset( $_POST['menu-item-kcp-image'] ) && is_array( $_POST['menu-item-kcp-image'] )
			? wp_unslash( $_POST['menu-item-kcp-image'] )
			: array();
		$hovers = isset( $_POST['menu-item-kcp-image-hover'] ) && is_array( $_POST['menu-item-kcp-image-hover'] )
			? wp_unslash( $_POST['menu-item-kcp-image-hover'] )
			: array();

		if ( array_key_exists( $menu_item_db_id, $images ) ) {
			$url = esc_url_raw( (string) $images[ $menu_item_db_id ] );

			if ( '' === $url ) {
				delete_post_meta( $menu_item_db_id, self::META_IMAGE );
			} else {
				update_post_meta( $menu_item_db_id, self::META_IMAGE, $url );
			}
		}

		if ( array_key_exists( $menu_item_db_id, $hovers ) ) {
			$url = esc_url_raw( (string) $hovers[ $menu_item_db_id ] );

			if ( '' === $url ) {
				delete_post_meta( $menu_item_db_id, self::META_IMAGE_HOVER );
			} else {
				update_post_meta( $menu_item_db_id, self::META_IMAGE_HOVER, $url );
			}
		}
	}

	/**
	 * @param string $hook_suffix Admin page hook.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'nav-menus.php' !== $hook_suffix ) {
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

		$script = KCP_PLUGIN_DIR . 'assets/admin/js/nav-menu-fields.js';

		wp_enqueue_script(
			'kcp-nav-menu-fields',
			KCP_PLUGIN_URL . 'assets/admin/js/nav-menu-fields.js',
			array( 'jquery', 'kcp-product-preset-media', 'nav-menu' ),
			is_readable( $script ) ? (string) filemtime( $script ) : KCP_VERSION,
			true
		);
	}

	/**
	 * @param int $item_id Menu item post ID.
	 * @return array{image: string, image_hover: string}
	 */
	public static function get_item_images( int $item_id ): array {
		return array(
			'image'       => (string) get_post_meta( $item_id, self::META_IMAGE, true ),
			'image_hover' => (string) get_post_meta( $item_id, self::META_IMAGE_HOVER, true ),
		);
	}
}
