<?php
/**
 * Settings page template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed>                           $settings
 * @var array<int, array{type: string, message: string}> $notices
 */

defined( 'ABSPATH' ) || exit;

$shop_hero = is_array( $shop_hero ?? null ) ? $shop_hero : array();
$design_step = is_array( $design_step ?? null ) ? $design_step : array();

?>
<div class="wrap kcp-admin">
	<h1><?php esc_html_e( 'Kitchen Configurator Settings', 'kitchen-configurator-pro' ); ?></h1>

	<?php require KCP_PLUGIN_DIR . 'templates/admin/partials/admin-notice.php'; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'kcp_save_settings', 'kcp_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="kcp-currency"><?php esc_html_e( 'Currency', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="currency"
						id="kcp-currency"
						class="regular-text"
						value="<?php echo esc_attr( (string) ( $settings['currency'] ?? 'EUR' ) ); ?>"
						maxlength="3"
					/>
					<p class="description"><?php esc_html_e( 'ISO 4217 currency code (e.g. EUR).', 'kitchen-configurator-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-vat-rate"><?php esc_html_e( 'Display VAT Rate (%)', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="vat_rate"
						id="kcp-vat-rate"
						class="small-text"
						value="<?php echo esc_attr( (string) ( $settings['vat_rate'] ?? 0 ) ); ?>"
						min="0"
						step="0.01"
					/>
					<p class="description"><?php esc_html_e( 'Optional display VAT on pricing snapshots. WooCommerce handles checkout tax.', 'kitchen-configurator-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-quote-validity"><?php esc_html_e( 'Quote Validity (days)', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="quote_validity_days"
						id="kcp-quote-validity"
						class="small-text"
						value="<?php echo esc_attr( (string) ( $settings['quote_validity_days'] ?? 30 ) ); ?>"
						min="1"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-design-check-price"><?php esc_html_e( 'Design check price', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="design_check_price"
						id="kcp-design-check-price"
						class="small-text"
						value="<?php echo esc_attr( (string) ( $settings['design_check_price'] ?? 75 ) ); ?>"
						min="0"
						step="1"
					/>
					<p class="description"><?php esc_html_e( 'Price added to the cart when the customer selects design review on the cart page.', 'kitchen-configurator-pro' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Shop page hero', 'kitchen-configurator-pro' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Content shown at the top of the shop page, above the product grid.', 'kitchen-configurator-pro' ); ?></p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Show hero section', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<label for="kcp-shop-hero-enabled">
						<input
							type="checkbox"
							name="shop_hero_enabled"
							id="kcp-shop-hero-enabled"
							value="1"
							<?php checked( ! empty( $shop_hero['enabled'] ) ); ?>
						/>
						<?php esc_html_e( 'Enabled', 'kitchen-configurator-pro' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-shop-hero-heading"><?php esc_html_e( 'Heading', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						name="shop_hero_heading"
						id="kcp-shop-hero-heading"
						class="large-text"
						value="<?php echo esc_attr( (string) ( $shop_hero['heading'] ?? '' ) ); ?>"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-shop-hero-description"><?php esc_html_e( 'Description', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<textarea
						name="shop_hero_description"
						id="kcp-shop-hero-description"
						class="large-text"
						rows="3"
					><?php echo esc_textarea( (string) ( $shop_hero['description'] ?? '' ) ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Hero images', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<p class="description"><?php esc_html_e( 'Add multiple images to animate between them on the shop page. Order matches the sequence shown.', 'kitchen-configurator-pro' ); ?></p>
					<?php
					$hero_images = is_array( $shop_hero['image_urls'] ?? null ) ? $shop_hero['image_urls'] : array();

					if ( empty( $hero_images ) ) {
						$hero_images = array( '' );
					}
					?>
					<div class="kcp-repeater kcp-repeater--shop-hero-images" data-kcp-shop-hero-images>
						<div class="kcp-repeater__rows">
							<?php foreach ( $hero_images as $image_url ) : ?>
								<?php
								$value = (string) $image_url;
								require KCP_PLUGIN_DIR . 'templates/admin/partials/shop-hero-image-row.php';
								?>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button button-secondary" data-kcp-add-shop-hero-image>
							<?php esc_html_e( 'Add image', 'kitchen-configurator-pro' ); ?>
						</button>
						<template data-kcp-shop-hero-image-template>
							<?php
							$value = '';
							require KCP_PLUGIN_DIR . 'templates/admin/partials/shop-hero-image-row.php';
							?>
						</template>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kcp-shop-hero-image-interval"><?php esc_html_e( 'Image interval (seconds)', 'kitchen-configurator-pro' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						name="shop_hero_image_interval"
						id="kcp-shop-hero-image-interval"
						class="small-text"
						value="<?php echo esc_attr( (string) ( $shop_hero['image_interval'] ?? 4 ) ); ?>"
						min="2"
						step="1"
					/>
					<p class="description"><?php esc_html_e( 'Time each hero image stays visible before transitioning to the next.', 'kitchen-configurator-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Button 1', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<p>
						<label for="kcp-shop-hero-button-1-label"><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="text"
							name="shop_hero_button_1_label"
							id="kcp-shop-hero-button-1-label"
							class="regular-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['button_1']['label'] ?? '' ) ); ?>"
						/>
					</p>
					<p>
						<label for="kcp-shop-hero-button-1-url"><?php esc_html_e( 'URL', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="url"
							name="shop_hero_button_1_url"
							id="kcp-shop-hero-button-1-url"
							class="large-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['button_1']['url'] ?? '' ) ); ?>"
						/>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Button 2', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<p>
						<label for="kcp-shop-hero-button-2-label"><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="text"
							name="shop_hero_button_2_label"
							id="kcp-shop-hero-button-2-label"
							class="regular-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['button_2']['label'] ?? '' ) ); ?>"
						/>
					</p>
					<p>
						<label for="kcp-shop-hero-button-2-url"><?php esc_html_e( 'URL', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="url"
							name="shop_hero_button_2_url"
							id="kcp-shop-hero-button-2-url"
							class="large-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['button_2']['url'] ?? '' ) ); ?>"
						/>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Help link', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<p>
						<label for="kcp-shop-hero-help-link-label"><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="text"
							name="shop_hero_help_link_label"
							id="kcp-shop-hero-help-link-label"
							class="regular-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['help_link']['label'] ?? '' ) ); ?>"
						/>
					</p>
					<p>
						<label for="kcp-shop-hero-help-link-url"><?php esc_html_e( 'URL', 'kitchen-configurator-pro' ); ?></label><br />
						<input
							type="url"
							name="shop_hero_help_link_url"
							id="kcp-shop-hero-help-link-url"
							class="large-text"
							value="<?php echo esc_attr( (string) ( $shop_hero['help_link']['url'] ?? '' ) ); ?>"
						/>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Design step page', 'kitchen-configurator-pro' ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %s: shortcode example */
				esc_html__( 'Configure the "ontwerp jouw keuken" page. Add the shortcode %s to any page.', 'kitchen-configurator-pro' ),
				'<code>[kcp_design_step]</code>'
			);
			?>
		</p>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="kcp-design-step-breadcrumb"><?php esc_html_e( 'Top label', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="design_step_breadcrumb" id="kcp-design-step-breadcrumb" class="regular-text" value="<?php echo esc_attr( (string) ( $design_step['breadcrumb'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-heading"><?php esc_html_e( 'Heading', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="design_step_heading" id="kcp-design-step-heading" class="large-text" value="<?php echo esc_attr( (string) ( $design_step['heading'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-description"><?php esc_html_e( 'Description', 'kitchen-configurator-pro' ); ?></label></th>
				<td><textarea name="design_step_description" id="kcp-design-step-description" class="large-text" rows="3"><?php echo esc_textarea( (string) ( $design_step['description'] ?? '' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Base cabinet image', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<?php
					$name = 'design_step_base_image_url';
					$value = (string) ( $design_step['base_image_url'] ?? '' );
					$id = 'kcp-design-step-base-image';
					$modifier = 'large';
					require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-back-url"><?php esc_html_e( 'Back URL', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="url" name="design_step_back_url" id="kcp-design-step-back-url" class="large-text" value="<?php echo esc_attr( (string) ( $design_step['back_url'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-back-label"><?php esc_html_e( 'Back label', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="design_step_back_label" id="kcp-design-step-back-label" class="regular-text" value="<?php echo esc_attr( (string) ( $design_step['back_label'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-skip-url"><?php esc_html_e( 'Skip URL', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="url" name="design_step_skip_url" id="kcp-design-step-skip-url" class="large-text" value="<?php echo esc_attr( (string) ( $design_step['skip_url'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-skip-label"><?php esc_html_e( 'Skip label', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="design_step_skip_label" id="kcp-design-step-skip-label" class="regular-text" value="<?php echo esc_attr( (string) ( $design_step['skip_label'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-cabinet-select-url"><?php esc_html_e( 'Select cabinets URL', 'kitchen-configurator-pro' ); ?></label></th>
				<td>
					<input type="url" name="design_step_cabinet_select_url" id="kcp-design-step-cabinet-select-url" class="large-text" value="<?php echo esc_attr( (string) ( $design_step['cabinet_select_url'] ?? '' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Page with [kcp_cabinet_select] shortcode. Powers the bottom "selecteer kasten" button.', 'kitchen-configurator-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-design-step-cabinet-select-label"><?php esc_html_e( 'Select cabinets label', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="design_step_cabinet_select_label" id="kcp-design-step-cabinet-select-label" class="regular-text" value="<?php echo esc_attr( (string) ( $design_step['cabinet_select_label'] ?? '' ) ); ?>" /></td>
			</tr>
		</table>

		<?php
		$design_zones = is_array( $design_step['zones'] ?? null ) ? $design_step['zones'] : array();
		foreach ( $design_zones as $zone_index => $zone ) :
			require KCP_PLUGIN_DIR . 'templates/admin/partials/design-zone-row.php';
		endforeach;
		?>

		<h2><?php esc_html_e( 'Select cabinets step', 'kitchen-configurator-pro' ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %s: shortcode */
				esc_html__( 'Configure the "selecteer kasten" page. Add %s to any page.', 'kitchen-configurator-pro' ),
				'<code>[kcp_cabinet_select]</code>'
			);
			?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="kcp-cabinet-select-heading"><?php esc_html_e( 'Heading', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="text" name="cabinet_select_heading" id="kcp-cabinet-select-heading" class="large-text" value="<?php echo esc_attr( (string) ( $cabinet_select_step['heading'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-cabinet-select-description"><?php esc_html_e( 'Description', 'kitchen-configurator-pro' ); ?></label></th>
				<td><textarea name="cabinet_select_description" id="kcp-cabinet-select-description" class="large-text" rows="2"><?php echo esc_textarea( (string) ( $cabinet_select_step['description'] ?? '' ) ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Preview image', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<?php
					$name = 'cabinet_select_preview_image_url';
					$value = (string) ( $cabinet_select_step['preview_image_url'] ?? '' );
					$id = 'kcp-cabinet-select-preview-image';
					$modifier = 'large';
					require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-cabinet-select-back-url"><?php esc_html_e( 'Back to design URL', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="url" name="cabinet_select_back_url" id="kcp-cabinet-select-back-url" class="large-text" value="<?php echo esc_attr( (string) ( $cabinet_select_step['back_url'] ?? '' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="kcp-cabinet-select-design-edit-url"><?php esc_html_e( 'Edit design URL (wijzigen)', 'kitchen-configurator-pro' ); ?></label></th>
				<td><input type="url" name="cabinet_select_design_edit_url" id="kcp-cabinet-select-design-edit-url" class="large-text" value="<?php echo esc_attr( (string) ( $cabinet_select_step['design_edit_url'] ?? '' ) ); ?>" /></td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'kitchen-configurator-pro' ); ?>
			</button>
		</p>
	</form>
</div>
