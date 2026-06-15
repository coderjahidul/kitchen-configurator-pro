<?php
/**
 * Product option group card (e.g. Color, Height, Width).
 *
 * @package KitchenConfiguratorPro
 *
 * @var int                  $group_index Group index.
 * @var array<string, mixed> $group       Group row data.
 * @var array<string, string> $type_options Option type labels keyed by slug.
 */

defined( 'ABSPATH' ) || exit;

$group       = is_array( $group ?? null ) ? $group : array();
$type_options = is_array( $type_options ?? null ) ? $type_options : array();
$items       = is_array( $group['items'] ?? null ) ? $group['items'] : array();

if ( empty( $items ) ) {
	$items = array(
		array(
			'id'          => '',
			'value'       => '',
			'price'       => 0,
			'description' => '',
			'image_url'   => '',
		),
	);
}

$group_type = sanitize_key( (string) ( $group['type'] ?? 'custom' ) );

?>
<fieldset class="kcp-option-group kcp-repeater__row">
	<div class="kcp-option-group__header">
		<div class="kcp-option-group__meta">
			<label>
				<span><?php esc_html_e( 'Option type', 'kitchen-configurator-pro' ); ?></span>
				<select name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][type]" class="kcp-option-group__type">
					<?php foreach ( $type_options as $type_slug => $type_label ) : ?>
						<option value="<?php echo esc_attr( (string) $type_slug ); ?>" <?php selected( $group_type, (string) $type_slug ); ?>>
							<?php echo esc_html( (string) $type_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span><?php esc_html_e( 'Group label', 'kitchen-configurator-pro' ); ?></span>
				<input
					type="text"
					name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][label]"
					value="<?php echo esc_attr( (string) ( $group['label'] ?? '' ) ); ?>"
					class="regular-text"
					placeholder="<?php esc_attr_e( 'e.g. Front color', 'kitchen-configurator-pro' ); ?>"
				/>
			</label>
			<label>
				<span><?php esc_html_e( 'Group ID', 'kitchen-configurator-pro' ); ?></span>
				<input
					type="text"
					name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][id]"
					value="<?php echo esc_attr( (string) ( $group['id'] ?? '' ) ); ?>"
					placeholder="color"
				/>
			</label>
			<label>
				<span><?php esc_html_e( 'Default item ID', 'kitchen-configurator-pro' ); ?></span>
				<input
					type="text"
					name="kcp_preset[option_groups][<?php echo esc_attr( (string) $group_index ); ?>][default_item]"
					value="<?php echo esc_attr( (string) ( $group['default_item'] ?? '' ) ); ?>"
					placeholder="light-oak"
				/>
			</label>
		</div>
		<button type="button" class="button-link kcp-repeater__remove kcp-repeater__remove--text kcp-option-group__remove" aria-label="<?php esc_attr_e( 'Remove option group', 'kitchen-configurator-pro' ); ?>">
			<?php esc_html_e( 'Remove group', 'kitchen-configurator-pro' ); ?>
		</button>
	</div>

	<div class="kcp-option-group__body">
		<h4 class="kcp-option-group__items-title"><?php esc_html_e( 'Option items', 'kitchen-configurator-pro' ); ?></h4>
		<div class="kcp-repeater kcp-repeater--nested" data-kcp-repeater="option_items">
			<div class="kcp-repeater__rows">
				<?php foreach ( $items as $item_index => $item ) : ?>
					<?php
					require KCP_PLUGIN_DIR . 'templates/admin/partials/option-item-row.php';
					?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-secondary kcp-repeater__add" data-kcp-add="option_items">
				<?php esc_html_e( 'Add item', 'kitchen-configurator-pro' ); ?>
			</button>
		</div>
	</div>
</fieldset>
