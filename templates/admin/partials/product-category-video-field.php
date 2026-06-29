<?php
/**
 * Video field on WooCommerce product category screens.
 *
 * @package KitchenConfiguratorPro
 *
 * @var bool   $is_edit
 * @var int    $video_id
 * @var string $video_url
 * @var string $tile_label
 */

defined( 'ABSPATH' ) || exit;

$wrapper_tag = $is_edit ? 'tr' : 'div';
$label_tag   = $is_edit ? 'th' : 'label';
$field_tag   = $is_edit ? 'td' : 'div';
$row_class   = $is_edit ? 'form-field kcp-category-video-fields__row' : 'form-field kcp-category-video-fields__row';

echo '<' . esc_attr( $wrapper_tag ) . ' class="' . esc_attr( $row_class ) . '">';
echo '<' . esc_attr( $label_tag ) . ' scope="row">';
echo '<label>' . esc_html__( 'Shop promo tile', 'kitchen-configurator-pro' ) . '</label>';
echo '</' . esc_attr( $label_tag ) . '>';
echo '<' . esc_attr( $field_tag ) . '>';

wp_nonce_field( 'kcp_category_video_save', 'kcp_category_video_nonce' );
?>
<p>
	<label for="kcp-promo-tile-label"><?php esc_html_e( 'Tile label', 'kitchen-configurator-pro' ); ?></label><br />
	<input
		type="text"
		class="large-text"
		id="kcp-promo-tile-label"
		name="kcp_promo_tile_label"
		value="<?php echo esc_attr( (string) ( $tile_label ?? '' ) ); ?>"
		placeholder="<?php esc_attr_e( 'e.g. het complete Quooker assortiment', 'kitchen-configurator-pro' ); ?>"
	/>
</p>
<div class="kcp-category-video-picker" data-kcp-category-video-picker>
	<input type="hidden" name="kcp_category_video_id" value="<?php echo esc_attr( (string) $video_id ); ?>" data-kcp-category-video-input />
	<div class="kcp-category-video-picker__preview<?php echo '' === $video_url ? ' is-empty' : ''; ?>" data-kcp-category-video-preview>
		<?php if ( '' !== $video_url ) : ?>
			<video src="<?php echo esc_url( $video_url ); ?>" muted playsinline preload="metadata"></video>
		<?php else : ?>
			<span class="kcp-category-video-picker__placeholder"><?php esc_html_e( 'No video selected', 'kitchen-configurator-pro' ); ?></span>
		<?php endif; ?>
	</div>
	<p>
		<button type="button" class="button" data-kcp-category-video-select><?php esc_html_e( 'Select video', 'kitchen-configurator-pro' ); ?></button>
		<button type="button" class="button-link" data-kcp-category-video-remove<?php echo $video_id > 0 ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'kitchen-configurator-pro' ); ?></button>
	</p>
	<p class="description"><?php esc_html_e( 'Shown on the shop page promo tiles for parent categories. Uses the category thumbnail as the poster image.', 'kitchen-configurator-pro' ); ?></p>
</div>
<?php
echo '</' . esc_attr( $field_tag ) . '>';
echo '</' . esc_attr( $wrapper_tag ) . '>';
