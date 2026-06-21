<?php
/**
 * Admin dimension pricing field (width/height/depth surcharges).
 *
 * @package KitchenConfiguratorPro
 *
 * @var string $value JSON string stored in dimension_price_json.
 */

defined( 'ABSPATH' ) || exit;

$value = (string) ( $value ?? '' );
$rules = json_decode( $value, true );

if ( ! is_array( $rules ) ) {
	$rules = array();
}

$axes = array(
	'width'  => __( 'Width', 'kitchen-configurator-pro' ),
	'height' => __( 'Height', 'kitchen-configurator-pro' ),
	'depth'  => __( 'Depth', 'kitchen-configurator-pro' ),
);

?>
<div class="kcp-dimension-pricing">
	<div class="kcp-dimension-pricing__grid" role="group" aria-label="<?php esc_attr_e( 'Dimension pricing', 'kitchen-configurator-pro' ); ?>">
		<div class="kcp-dimension-pricing__head">
			<span class="kcp-dimension-pricing__col kcp-dimension-pricing__col--axis"><?php esc_html_e( 'Dimension', 'kitchen-configurator-pro' ); ?></span>
			<span class="kcp-dimension-pricing__col"><?php esc_html_e( 'Rate per mm', 'kitchen-configurator-pro' ); ?></span>
			<span class="kcp-dimension-pricing__col"><?php esc_html_e( 'Base (mm)', 'kitchen-configurator-pro' ); ?></span>
		</div>

		<?php foreach ( $axes as $axis => $axis_label ) : ?>
			<?php
			$axis_rule = is_array( $rules[ $axis ] ?? null ) ? $rules[ $axis ] : array();
			$rate      = (float) ( $axis_rule['rate_per_mm'] ?? $axis_rule['per_mm'] ?? 0 );
			$base      = (int) ( $axis_rule['base'] ?? 0 );
			?>
			<div class="kcp-dimension-pricing__row">
				<div class="kcp-dimension-pricing__axis">
					<?php echo esc_html( $axis_label ); ?>
				</div>
				<label class="kcp-dimension-pricing__field">
					<span class="screen-reader-text"><?php echo esc_html( $axis_label ); ?> — <?php esc_html_e( 'Rate per mm', 'kitchen-configurator-pro' ); ?></span>
					<input
						type="number"
						name="<?php echo esc_attr( 'dimension_pricing[' . $axis . '][rate_per_mm]' ); ?>"
						step="0.01"
						min="0"
						inputmode="decimal"
						value="<?php echo esc_attr( $rate > 0 ? (string) $rate : '' ); ?>"
						placeholder="0.00"
					/>
				</label>
				<label class="kcp-dimension-pricing__field">
					<span class="screen-reader-text"><?php echo esc_html( $axis_label ); ?> — <?php esc_html_e( 'Base (mm)', 'kitchen-configurator-pro' ); ?></span>
					<input
						type="number"
						name="<?php echo esc_attr( 'dimension_pricing[' . $axis . '][base]' ); ?>"
						step="1"
						min="0"
						inputmode="numeric"
						value="<?php echo esc_attr( $base > 0 ? (string) $base : '' ); ?>"
						placeholder="<?php esc_attr_e( 'Default', 'kitchen-configurator-pro' ); ?>"
					/>
				</label>
			</div>
		<?php endforeach; ?>
	</div>
</div>
