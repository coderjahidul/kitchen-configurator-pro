<?php
/**
 * Admin shop promo tile row.
 *
 * @package KitchenConfiguratorPro
 *
 * @var int                 $index Row index.
 * @var array<string,mixed> $tile  Tile values.
 */

defined( 'ABSPATH' ) || exit;

$index = (int) ( $index ?? 0 );
$tile  = is_array( $tile ?? null ) ? $tile : array();

?>
<div class="kcp-repeater__row kcp-repeater__row--shop-promo-tile">
	<div class="kcp-repeater__grid">
		<p>
			<label><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label><br />
			<input
				type="text"
				class="large-text"
				name="shop_promo_tiles[<?php echo esc_attr( (string) $index ); ?>][label]"
				value="<?php echo esc_attr( (string) ( $tile['label'] ?? '' ) ); ?>"
			/>
		</p>
		<p>
			<label><?php esc_html_e( 'URL', 'kitchen-configurator-pro' ); ?></label><br />
			<input
				type="url"
				class="large-text"
				name="shop_promo_tiles[<?php echo esc_attr( (string) $index ); ?>][url]"
				value="<?php echo esc_attr( (string) ( $tile['url'] ?? '' ) ); ?>"
			/>
		</p>
		<p>
			<label><?php esc_html_e( 'Badge text', 'kitchen-configurator-pro' ); ?></label><br />
			<input
				type="text"
				class="regular-text"
				name="shop_promo_tiles[<?php echo esc_attr( (string) $index ); ?>][badge_text]"
				value="<?php echo esc_attr( (string) ( $tile['badge_text'] ?? '' ) ); ?>"
			/>
		</p>
		<p>
			<label><?php esc_html_e( 'Poster image', 'kitchen-configurator-pro' ); ?></label>
			<?php
			$name     = 'shop_promo_tiles[' . $index . '][image_url]';
			$value    = (string) ( $tile['image_url'] ?? '' );
			$id       = '';
			$modifier = 'compact';
			require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
			?>
		</p>
		<p>
			<label><?php esc_html_e( 'Badge logo', 'kitchen-configurator-pro' ); ?></label>
			<?php
			$name     = 'shop_promo_tiles[' . $index . '][badge_image_url]';
			$value    = (string) ( $tile['badge_image_url'] ?? '' );
			$id       = '';
			$modifier = 'compact';
			require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
			?>
		</p>
		<p>
			<label><?php esc_html_e( 'Video URL', 'kitchen-configurator-pro' ); ?></label><br />
			<input
				type="url"
				class="large-text"
				name="shop_promo_tiles[<?php echo esc_attr( (string) $index ); ?>][video_url]"
				value="<?php echo esc_attr( (string) ( $tile['video_url'] ?? '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'Optional MP4 from media library', 'kitchen-configurator-pro' ); ?>"
			/>
		</p>
	</div>
</div>
