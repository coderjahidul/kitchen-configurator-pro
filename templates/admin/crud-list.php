<?php
/**
 * Generic CRUD list template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var \KitchenConfiguratorPro\Admin\AbstractCrudPage $page
 * @var array<int, mixed>                            $items
 * @var array<string, string>                        $columns
 * @var array<int, array{type: string, message: string}> $notices
 * @var string                                       $add_url
 * @var string                                       $entity_label
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Support\Arr;

?>
<div class="wrap kcp-admin">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $page->entity_label_plural() ); ?>
	</h1>
	<a href="<?php echo esc_url( $add_url ); ?>" class="page-title-action">
		<?php
		printf(
			/* translators: %s: entity label */
			esc_html__( 'Add %s', 'kitchen-configurator-pro' ),
			esc_html( $entity_label )
		);
		?>
	</a>
	<hr class="wp-header-end">

	<?php
	require KCP_PLUGIN_DIR . 'templates/admin/partials/admin-notice.php';
	?>

	<table class="wp-list-table widefat fixed striped table-view-list">
		<thead>
			<tr>
				<?php foreach ( $columns as $column_key => $column_label ) : ?>
					<th scope="col"><?php echo esc_html( $column_label ); ?></th>
				<?php endforeach; ?>
				<th scope="col"><?php esc_html_e( 'Actions', 'kitchen-configurator-pro' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $items ) ) : ?>
				<tr>
					<td colspan="<?php echo esc_attr( (string) ( count( $columns ) + 1 ) ); ?>">
						<?php esc_html_e( 'No records found.', 'kitchen-configurator-pro' ); ?>
					</td>
				</tr>
			<?php else : ?>
				<?php foreach ( $items as $item ) : ?>
					<?php
					$row = Arr::to_array( $item );
					$id  = (int) ( $row['id'] ?? 0 );
					?>
					<tr>
						<?php foreach ( $columns as $column_key => $column_label ) : ?>
							<td>
								<?php
								if ( 'name' === $column_key && $id > 0 ) {
									printf(
										'<strong><a href="%1$s">%2$s</a></strong>',
										esc_url( $page->form_url( $id ) ),
										esc_html( (string) ( $row[ $column_key ] ?? '' ) )
									);
								} else {
									echo wp_kses_post( $page->format_column( $column_key, $row ) );
								}
								?>
							</td>
						<?php endforeach; ?>
						<td class="kcp-actions">
							<a href="<?php echo esc_url( $page->form_url( $id ) ); ?>">
								<?php esc_html_e( 'Edit', 'kitchen-configurator-pro' ); ?>
							</a>
							|
							<a href="<?php echo esc_url( $page->delete_url( $id ) ); ?>" class="kcp-delete-link">
								<?php esc_html_e( 'Delete', 'kitchen-configurator-pro' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
