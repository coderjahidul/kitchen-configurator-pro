<?php
/**
 * Admin design zone row.
 *
 * @package KitchenConfiguratorPro
 *
 * @var int|string           $zone_index Zone index.
 * @var array<string, mixed> $zone Zone values.
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Services\DesignZoneCatalogService;

$zone_index = (string) ( $zone_index ?? '0' );
$zone       = is_array( $zone ?? null ) ? $zone : array();
$zone_id    = (string) ( $zone['id'] ?? '' );
$admin_page = DesignZoneCatalogService::admin_page_for_zone( $zone_id );
$catalog_url = '' !== $admin_page
	? admin_url( 'admin.php?page=' . $admin_page )
	: '';

?>
<fieldset class="kcp-panel kcp-design-zone">
	<legend class="kcp-design-zone__title"><?php echo esc_html( (string) ( $zone['label'] ?? $zone_id ) ); ?></legend>
	<input type="hidden" name="design_step_zones[<?php echo esc_attr( $zone_index ); ?>][id]" value="<?php echo esc_attr( $zone_id ); ?>" />

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></label></th>
			<td>
				<input
					type="text"
					class="regular-text"
					name="design_step_zones[<?php echo esc_attr( $zone_index ); ?>][label]"
					value="<?php echo esc_attr( (string) ( $zone['label'] ?? '' ) ); ?>"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php esc_html_e( 'Hotspot top (%)', 'kitchen-configurator-pro' ); ?></label></th>
			<td>
				<input
					type="number"
					class="small-text"
					min="0"
					max="100"
					step="0.1"
					name="design_step_zones[<?php echo esc_attr( $zone_index ); ?>][top]"
					value="<?php echo esc_attr( (string) ( $zone['top'] ?? 50 ) ); ?>"
				/>
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php esc_html_e( 'Hotspot left (%)', 'kitchen-configurator-pro' ); ?></label></th>
			<td>
				<input
					type="number"
					class="small-text"
					min="0"
					max="100"
					step="0.1"
					name="design_step_zones[<?php echo esc_attr( $zone_index ); ?>][left]"
					value="<?php echo esc_attr( (string) ( $zone['left'] ?? 50 ) ); ?>"
				/>
			</td>
		</tr>
		<?php if ( '' !== $catalog_url ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Options', 'kitchen-configurator-pro' ); ?></th>
				<td>
					<p class="description">
						<?php
						printf(
							/* translators: %s: admin catalog page link */
							esc_html__( 'Options for this zone are loaded from the catalog. Manage them on the %s page.', 'kitchen-configurator-pro' ),
							'<a href="' . esc_url( $catalog_url ) . '">' . esc_html__( 'catalog', 'kitchen-configurator-pro' ) . '</a>'
						);
						?>
					</p>
				</td>
			</tr>
		<?php endif; ?>
	</table>
</fieldset>
