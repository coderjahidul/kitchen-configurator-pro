<?php
/**
 * Single product option item row.
 *
 * @package KitchenConfiguratorPro
 *
 * @var int                  $group_index Group index.
 * @var int                  $item_index  Item index.
 * @var array<string, mixed> $item        Item row data.
 */

defined( 'ABSPATH' ) || exit;

$item = is_array( $item ?? null ) ? $item : array();

?>
<div class="kcp-repeater__row kcp-repeater__row--option-item">
	<label>
		<span><?php esc_html_e( 'ID', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="text"
			name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][items][<?php echo esc_attr( (string) $item_index ); ?>][id]"
			value="<?php echo esc_attr( (string) ( $item['id'] ?? '' ) ); ?>"
			placeholder="light-oak"
		/>
	</label>
	<label>
		<span><?php esc_html_e( 'Value', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="text"
			name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][items][<?php echo esc_attr( (string) $item_index ); ?>][value]"
			value="<?php echo esc_attr( (string) ( $item['value'] ?? '' ) ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Display label', 'kitchen-configurator-pro' ); ?>"
		/>
	</label>
	<label>
		<span><?php esc_html_e( 'Price', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="number"
			step="0.01"
			name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][items][<?php echo esc_attr( (string) $item_index ); ?>][price]"
			value="<?php echo esc_attr( (string) ( $item['price'] ?? 0 ) ); ?>"
		/>
	</label>
	<label class="kcp-repeater__full">
		<span><?php esc_html_e( 'Description', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="text"
			name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][items][<?php echo esc_attr( (string) $item_index ); ?>][description]"
			value="<?php echo esc_attr( (string) ( $item['description'] ?? '' ) ); ?>"
			class="large-text"
		/>
	</label>
	<div class="kcp-repeater__full kcp-repeater__image-field">
		<span class="kcp-field-label"><?php esc_html_e( 'Image', 'kitchen-configurator-pro' ); ?></span>
		<?php
		$name     = 'kcp_preset[option_groups][' . (string) $group_index . '][items][' . (string) $item_index . '][image_url]';
		$value    = (string) ( $item['image_url'] ?? '' );
		$id       = '';
		$modifier = 'compact';
		require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
		?>
	</div>
	<button type="button" class="button kcp-repeater__remove" aria-label="<?php esc_attr_e( 'Remove option item', 'kitchen-configurator-pro' ); ?>">&times;</button>
</div>
