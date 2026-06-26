<?php
/**
 * Single cabinet item row under a parent cabinet.
 *
 * @package KitchenConfiguratorPro
 *
 * @var int                  $item_index Item index.
 * @var array<string, mixed> $item       Item row data.
 * @var string               $field_id   Field DOM id prefix.
 */

defined( 'ABSPATH' ) || exit;

$item = is_array( $item ?? null ) ? $item : array();

?>
<div class="kcp-repeater__row kcp-repeater__row--cabinet-item">
	<input
		type="hidden"
		name="cabinet_items[<?php echo esc_attr( (string) $item_index ); ?>][id]"
		value="<?php echo esc_attr( (string) ( $item['id'] ?? '' ) ); ?>"
	/>

	<label>
		<span><?php esc_html_e( 'Name', 'kitchen-configurator-pro' ); ?> <span class="required">*</span></span>
		<input
			type="text"
			name="cabinet_items[<?php echo esc_attr( (string) $item_index ); ?>][name]"
			value="<?php echo esc_attr( (string) ( $item['name'] ?? '' ) ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Item name', 'kitchen-configurator-pro' ); ?>"
		/>
	</label>

	<label>
		<span><?php esc_html_e( 'Slug', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="text"
			name="cabinet_items[<?php echo esc_attr( (string) $item_index ); ?>][slug]"
			value="<?php echo esc_attr( (string) ( $item['slug'] ?? '' ) ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Auto from name', 'kitchen-configurator-pro' ); ?>"
		/>
	</label>

	<label>
		<span><?php esc_html_e( 'Base Price', 'kitchen-configurator-pro' ); ?></span>
		<input
			type="number"
			step="0.01"
			min="0"
			name="cabinet_items[<?php echo esc_attr( (string) $item_index ); ?>][base_price]"
			value="<?php echo esc_attr( (string) ( $item['base_price'] ?? '0' ) ); ?>"
		/>
	</label>

	<label class="kcp-repeater__checkbox">
		<input
			type="checkbox"
			name="cabinet_items[<?php echo esc_attr( (string) $item_index ); ?>][is_active]"
			value="1"
			<?php checked( ! isset( $item['is_active'] ) || ! empty( $item['is_active'] ) ); ?>
		/>
		<span><?php esc_html_e( 'Active', 'kitchen-configurator-pro' ); ?></span>
	</label>

	<div class="kcp-repeater__full kcp-repeater__image-field">
		<span class="kcp-field-label"><?php esc_html_e( 'Image', 'kitchen-configurator-pro' ); ?></span>
		<?php
		$name     = 'cabinet_items[' . (string) $item_index . '][image_url]';
		$value    = (string) ( $item['image_url'] ?? '' );
		$id       = $field_id . '-item-image-' . (string) $item_index;
		$modifier = 'compact';
		require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
		?>
	</div>

	<?php if ( ! empty( $item['id'] ) ) : ?>
		<p class="kcp-repeater__full description">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=kcp-cabinets&action=edit&id=' . (int) $item['id'] ) ); ?>">
				<?php esc_html_e( 'Edit full cabinet details', 'kitchen-configurator-pro' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<button type="button" class="button kcp-repeater__remove" aria-label="<?php esc_attr_e( 'Remove item', 'kitchen-configurator-pro' ); ?>">&times;</button>
</div>
