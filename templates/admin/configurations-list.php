<?php
/**
 * Configurations list template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<int, mixed> $items
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Support\Arr;

$list_url = admin_url( 'admin.php?page=kcp-configurations' );

?>
<div class="wrap kcp-admin">
	<h1><?php esc_html_e( 'Customer Configurations', 'kitchen-configurator-pro' ); ?></h1>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Title', 'kitchen-configurator-pro' ); ?></th>
				<th><?php esc_html_e( 'UUID', 'kitchen-configurator-pro' ); ?></th>
				<th><?php esc_html_e( 'Status', 'kitchen-configurator-pro' ); ?></th>
				<th><?php esc_html_e( 'Total', 'kitchen-configurator-pro' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'kitchen-configurator-pro' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'kitchen-configurator-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $items ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No configurations yet.', 'kitchen-configurator-pro' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $items as $item ) : ?>
					<?php $row = Arr::to_array( $item ); ?>
					<tr>
						<td><strong><?php echo esc_html( (string) ( $row['title'] ?? '' ) ); ?></strong></td>
						<td><code><?php echo esc_html( (string) ( $row['uuid'] ?? '' ) ); ?></code></td>
						<td><?php echo esc_html( (string) ( $row['status'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $row['total_price'] ?? '0.00' ) ); ?></td>
						<td><?php echo esc_html( (string) ( $row['updated_at'] ?? '' ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'kcp-configurations', 'action' => 'view', 'id' => (int) ( $row['id'] ?? 0 ) ), admin_url( 'admin.php' ) ) ); ?>">
								<?php esc_html_e( 'View', 'kitchen-configurator-pro' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
