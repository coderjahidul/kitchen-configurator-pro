<?php
/**
 * Single configuration view template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $config
 */

defined( 'ABSPATH' ) || exit;

$list_url = admin_url( 'admin.php?page=kcp-configurations' );

?>
<div class="wrap kcp-admin">
	<h1><?php echo esc_html( (string) ( $config['title'] ?? __( 'Configuration', 'kitchen-configurator-pro' ) ) ); ?></h1>

	<p>
		<a href="<?php echo esc_url( $list_url ); ?>" class="button">
			<?php esc_html_e( 'Back to list', 'kitchen-configurator-pro' ); ?>
		</a>
	</p>

	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'UUID', 'kitchen-configurator-pro' ); ?></th>
			<td><code><?php echo esc_html( (string) ( $config['uuid'] ?? '' ) ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Status', 'kitchen-configurator-pro' ); ?></th>
			<td><?php echo esc_html( (string) ( $config['status'] ?? '' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Total Price', 'kitchen-configurator-pro' ); ?></th>
			<td><?php echo esc_html( (string) ( $config['total_price'] ?? '0.00' ) ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Layout ID', 'kitchen-configurator-pro' ); ?></th>
			<td><?php echo esc_html( (string) ( $config['layout_id'] ?? '' ) ); ?></td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Configuration JSON', 'kitchen-configurator-pro' ); ?></h2>
	<pre class="kcp-json-view"><?php echo esc_html( (string) ( $config['configuration_json'] ?? '{}' ) ); ?></pre>

	<?php if ( ! empty( $config['pricing_snapshot_json'] ) ) : ?>
		<h2><?php esc_html_e( 'Pricing Snapshot JSON', 'kitchen-configurator-pro' ); ?></h2>
		<pre class="kcp-json-view"><?php echo esc_html( (string) $config['pricing_snapshot_json'] ); ?></pre>
	<?php endif; ?>
</div>
