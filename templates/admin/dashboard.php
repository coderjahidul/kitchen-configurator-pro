<?php
/**
 * Admin dashboard template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $stats
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap kcp-admin">
	<h1><?php esc_html_e( 'Kitchen Configurator Pro', 'kitchen-configurator-pro' ); ?></h1>

	<div class="kcp-dashboard-cards">
		<div class="kcp-card">
			<h3><?php esc_html_e( 'Layouts', 'kitchen-configurator-pro' ); ?></h3>
			<p class="kcp-stat"><?php echo esc_html( (string) $stats['layouts'] ); ?></p>
		</div>
		<div class="kcp-card">
			<h3><?php esc_html_e( 'Cabinets', 'kitchen-configurator-pro' ); ?></h3>
			<p class="kcp-stat"><?php echo esc_html( (string) $stats['cabinets'] ); ?></p>
		</div>
		<div class="kcp-card">
			<h3><?php esc_html_e( 'Materials', 'kitchen-configurator-pro' ); ?></h3>
			<p class="kcp-stat"><?php echo esc_html( (string) $stats['materials'] ); ?></p>
		</div>
		<div class="kcp-card">
			<h3><?php esc_html_e( 'Accessories', 'kitchen-configurator-pro' ); ?></h3>
			<p class="kcp-stat"><?php echo esc_html( (string) $stats['accessories'] ); ?></p>
		</div>
		<div class="kcp-card">
			<h3><?php esc_html_e( 'Configurations', 'kitchen-configurator-pro' ); ?></h3>
			<p class="kcp-stat"><?php echo esc_html( (string) $stats['configurations'] ); ?></p>
		</div>
	</div>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Plugin Version', 'kitchen-configurator-pro' ); ?></th>
			<td><?php echo esc_html( (string) $stats['plugin_version'] ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Database Version', 'kitchen-configurator-pro' ); ?></th>
			<td><?php echo esc_html( (string) $stats['db_version'] ); ?></td>
		</tr>
	</table>
</div>
