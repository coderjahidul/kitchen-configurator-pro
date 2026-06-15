<?php
/**
 * Visual product preset form.
 *
 * @package KitchenConfiguratorPro
 *
 * @var \KitchenConfiguratorPro\Admin\Pages\ProductPresetsPage $page
 * @var bool                                                    $is_edit
 * @var int                                                     $id
 * @var array<string, mixed>                                    $values
 * @var array<string, mixed>                                    $preset
 * @var array<string, array<string, string>>                    $wc_product_options
 * @var array<int, array<string, mixed>>                        $notices
 * @var string                                                  $list_url
 * @var string                                                  $entity_label
 * @var string                                                  $nonce_action
 */

defined( 'ABSPATH' ) || exit;

$summary_rows  = is_array( $preset['summary'] ?? null ) ? $preset['summary'] : array();
$part_groups   = is_array( $preset['part_groups'] ?? null ) ? $preset['part_groups'] : array();
$option_groups = is_array( $preset['option_groups'] ?? null ) ? $preset['option_groups'] : array();
$option_types  = is_array( $option_type_labels ?? null ) ? $option_type_labels : array();
$part_types    = is_array( $part_type_labels ?? null ) ? $part_type_labels : array();

if ( empty( $part_groups ) ) {
	$part_groups = array(
		array(
			'id'           => '',
			'type'         => 'panels',
			'label'        => '',
			'description'  => '',
			'image_url'    => '',
			'price'        => 0,
			'editable'     => false,
			'default_item' => '',
			'items'        => array(
				array(
					'id'          => '',
					'value'       => '',
					'price'       => 0,
					'description' => '',
					'image_url'   => '',
				),
			),
		),
	);
}

if ( empty( $option_groups ) ) {
	$option_groups = array(
		array(
			'id'           => 'color',
			'type'         => 'color',
			'label'        => '',
			'default_item' => '',
			'items'        => array(
				array(
					'id'          => '',
					'value'       => '',
					'price'       => 0,
					'description' => '',
					'image_url'   => '',
				),
			),
		),
	);
}

if ( empty( $summary_rows ) ) {
	$summary_rows = array( array( 'label' => '', 'value' => '' ) );
}

?>
<div class="wrap kcp-admin kcp-product-preset-form">
	<div class="kcp-product-preset-form__head">
		<h1>
			<?php
			echo esc_html(
				$is_edit
					? sprintf(
						/* translators: %s: entity label */
						__( 'Edit %s', 'kitchen-configurator-pro' ),
						$entity_label
					)
					: sprintf(
						/* translators: %s: entity label */
						__( 'Add %s', 'kitchen-configurator-pro' ),
						$entity_label
					)
			);
			?>
		</h1>
		<p class="kcp-product-preset-form__intro"><?php esc_html_e( 'Configure how this WooCommerce product appears on the shop and cart pages.', 'kitchen-configurator-pro' ); ?></p>
	</div>

	<?php require KCP_PLUGIN_DIR . 'templates/admin/partials/admin-notice.php'; ?>

	<form method="post" action="" class="kcp-form kcp-product-preset-form__form">
		<?php wp_nonce_field( $nonce_action ); ?>
		<input type="hidden" name="kcp_action" value="<?php echo esc_attr( $is_edit ? 'update' : 'create' ); ?>" />
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>" />
		<?php endif; ?>

		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'General', 'kitchen-configurator-pro' ); ?></h2>
			</header>
			<div class="kcp-panel__body">
		<table class="form-table kcp-form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="kcp-wc-product"><?php esc_html_e( 'WooCommerce Product', 'kitchen-configurator-pro' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<select name="wc_product_id" id="kcp-wc-product" class="regular-text" required>
							<?php foreach ( $wc_product_options as $option_value => $option_label ) : ?>
								<option value="<?php echo esc_attr( (string) $option_value ); ?>" <?php selected( (string) ( $values['wc_product_id'] ?? '' ), (string) $option_value ); ?>>
									<?php echo esc_html( $option_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kcp-preset-name"><?php esc_html_e( 'Preset Name', 'kitchen-configurator-pro' ); ?></label></th>
					<td>
						<input type="text" name="name" id="kcp-preset-name" class="regular-text" value="<?php echo esc_attr( (string) ( $values['name'] ?? '' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional admin label. Defaults to the WooCommerce product name.', 'kitchen-configurator-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Active', 'kitchen-configurator-pro' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="is_active" value="1" <?php checked( ! empty( $values['is_active'] ) ); ?> />
							<?php esc_html_e( 'Enable this product preset', 'kitchen-configurator-pro' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
			</div>
		</section>

		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Cart display', 'kitchen-configurator-pro' ); ?></h2>
				<p class="kcp-panel__description"><?php esc_html_e( 'Hero image and group title on the cart page.', 'kitchen-configurator-pro' ); ?></p>
			</header>
			<div class="kcp-panel__body">
		<table class="form-table kcp-form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="kcp-group-title"><?php esc_html_e( 'Group title', 'kitchen-configurator-pro' ); ?></label></th>
					<td><input type="text" name="kcp_preset[group_title]" id="kcp-group-title" class="regular-text" value="<?php echo esc_attr( (string) ( $preset['group_title'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'kastenwand', 'kitchen-configurator-pro' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="kcp-subtitle"><?php esc_html_e( 'Subtitle', 'kitchen-configurator-pro' ); ?></label></th>
					<td><input type="text" name="kcp_preset[subtitle]" id="kcp-subtitle" class="regular-text" value="<?php echo esc_attr( (string) ( $preset['subtitle'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'basiselement', 'kitchen-configurator-pro' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Preview image', 'kitchen-configurator-pro' ); ?></th>
					<td>
						<?php
						$name     = 'kcp_preset[preview_image]';
						$value    = (string) ( $preset['preview_image'] ?? '' );
						$id       = 'kcp-preview-image';
						$modifier = 'large';
						require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Storefront block', 'kitchen-configurator-pro' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="kcp_preset[show_storefront]" value="1" <?php checked( ! empty( $preset['show_storefront'] ) ); ?> />
							<?php esc_html_e( 'Show preset selectors on the single product page', 'kitchen-configurator-pro' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>
			</div>
		</section>

		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Cart summary', 'kitchen-configurator-pro' ); ?></h2>
				<p class="kcp-panel__description"><?php esc_html_e( 'Shown in the configuration overview card on the cart page.', 'kitchen-configurator-pro' ); ?></p>
			</header>
			<div class="kcp-panel__body">
		<div class="kcp-repeater" data-kcp-repeater="summary">
			<div class="kcp-repeater__rows">
				<?php foreach ( $summary_rows as $index => $row ) : ?>
					<div class="kcp-repeater__row kcp-repeater__row--summary">
						<input type="text" name="kcp_preset[summary][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( (string) ( $row['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Label', 'kitchen-configurator-pro' ); ?>" />
						<input type="text" name="kcp_preset[summary][<?php echo esc_attr( (string) $index ); ?>][value]" value="<?php echo esc_attr( (string) ( $row['value'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Value', 'kitchen-configurator-pro' ); ?>" class="regular-text" />
						<button type="button" class="button kcp-repeater__remove" aria-label="<?php esc_attr_e( 'Remove row', 'kitchen-configurator-pro' ); ?>">&times;</button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-secondary kcp-repeater__add" data-kcp-add="summary"><?php esc_html_e( 'Add summary row', 'kitchen-configurator-pro' ); ?></button>
		</div>
			</div>
		</section>

		<section class="kcp-panel kcp-panel--highlight">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Cart breakdown parts', 'kitchen-configurator-pro' ); ?> <span class="required">*</span></h2>
				<p class="kcp-panel__description"><?php esc_html_e( 'Each part is one cart breakdown row. Add part items inside for optional variants (e.g. per-height pricing).', 'kitchen-configurator-pro' ); ?></p>
			</header>
			<div class="kcp-panel__body">
				<div class="kcp-repeater kcp-repeater--part-groups" data-kcp-repeater="part_groups">
					<div class="kcp-repeater__rows">
						<?php foreach ( $part_groups as $group_index => $group ) : ?>
							<?php
							$type_options = $part_types;
							require KCP_PLUGIN_DIR . 'templates/admin/partials/part-group-card.php';
							?>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button button-primary kcp-repeater__add" data-kcp-add="part_groups">
						<?php esc_html_e( 'Add new part', 'kitchen-configurator-pro' ); ?>
					</button>
				</div>
			</div>
		</section>

		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Product options', 'kitchen-configurator-pro' ); ?></h2>
				<p class="kcp-panel__description"><?php esc_html_e( 'Add option groups such as color, height, width, or custom selectors. Each item supports ID, value, price, image, and description.', 'kitchen-configurator-pro' ); ?></p>
			</header>
			<div class="kcp-panel__body">
				<div class="kcp-repeater kcp-repeater--option-groups" data-kcp-repeater="option_groups">
					<div class="kcp-repeater__rows">
						<?php foreach ( $option_groups as $group_index => $group ) : ?>
							<?php
							$type_options = $option_types;
							require KCP_PLUGIN_DIR . 'templates/admin/partials/option-group-card.php';
							?>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button button-primary kcp-repeater__add" data-kcp-add="option_groups">
						<?php esc_html_e( 'Add new option', 'kitchen-configurator-pro' ); ?>
					</button>
				</div>
			</div>
		</section>

		<div class="kcp-panel-grid">
		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Product specs', 'kitchen-configurator-pro' ); ?></h2>
			</header>
			<div class="kcp-panel__body">
		<table class="form-table kcp-form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="kcp-spec-dimensions"><?php esc_html_e( 'Dimensions', 'kitchen-configurator-pro' ); ?></label></th>
					<td>
						<textarea name="kcp_preset[spec_dimensions]" id="kcp-spec-dimensions" class="large-text" rows="4"><?php echo esc_textarea( (string) ( $preset['spec_dimensions'] ?? '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One dimension per line.', 'kitchen-configurator-pro' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kcp-spec-includes"><?php esc_html_e( 'Includes', 'kitchen-configurator-pro' ); ?></label></th>
					<td>
						<textarea name="kcp_preset[spec_includes]" id="kcp-spec-includes" class="large-text" rows="4"><?php echo esc_textarea( (string) ( $preset['spec_includes'] ?? '' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One item per line.', 'kitchen-configurator-pro' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
			</div>
		</section>

		<section class="kcp-panel">
			<header class="kcp-panel__header">
				<h2 class="kcp-panel__title"><?php esc_html_e( 'Plinth surcharge', 'kitchen-configurator-pro' ); ?></h2>
			</header>
			<div class="kcp-panel__body">
		<table class="form-table kcp-form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="kcp-plinth-label"><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label></th>
					<td><input type="text" name="kcp_preset[plinth_label]" id="kcp-plinth-label" class="regular-text" value="<?php echo esc_attr( (string) ( $preset['plinth_label'] ?? '' ) ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="kcp-plinth-unit"><?php esc_html_e( 'Unit price label', 'kitchen-configurator-pro' ); ?></label></th>
					<td><input type="text" name="kcp_preset[plinth_unit_label]" id="kcp-plinth-unit" class="regular-text" value="<?php echo esc_attr( (string) ( $preset['plinth_unit_label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( '1,20 per 10mm breedte', 'kitchen-configurator-pro' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="kcp-plinth-subtotal"><?php esc_html_e( 'Subtotal', 'kitchen-configurator-pro' ); ?></label></th>
					<td><input type="number" step="0.01" min="0" name="kcp_preset[plinth_subtotal]" id="kcp-plinth-subtotal" value="<?php echo esc_attr( (string) ( $preset['plinth_subtotal'] ?? 0 ) ); ?>" /></td>
				</tr>
			</tbody>
		</table>
			</div>
		</section>
		</div>

		<div class="kcp-form-actions">
			<button type="submit" class="button button-primary button-hero"><?php echo esc_html( $is_edit ? __( 'Update Product', 'kitchen-configurator-pro' ) : __( 'Create Product', 'kitchen-configurator-pro' ) ); ?></button>
			<a href="<?php echo esc_url( $list_url ); ?>" class="button button-large"><?php esc_html_e( 'Cancel', 'kitchen-configurator-pro' ); ?></a>
		</div>
	</form>
</div>
