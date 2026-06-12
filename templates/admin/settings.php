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
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'kitchen-configurator-pro' ); ?>
			</button>
		</p>
	</form>
</div>
