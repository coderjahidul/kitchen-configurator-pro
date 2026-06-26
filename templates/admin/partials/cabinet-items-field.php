<?php
/**
 * Cabinet items repeater under a parent cabinet.
 *
 * @package KitchenConfiguratorPro
 *
 * @var string                              $field_id
 * @var array<int, array<string, mixed>>    $cabinet_items
 * @var int                                 $cabinet_id
 */

defined( 'ABSPATH' ) || exit;

$cabinet_items = (array) ( $cabinet_items ?? array() );

if ( empty( $cabinet_items ) ) {
	$cabinet_items = array( array() );
}

?>
<div class="kcp-cabinet-items-field" data-cabinet-id="<?php echo esc_attr( (string) $cabinet_id ); ?>">
	<div class="kcp-repeater kcp-repeater--cabinet-items" data-kcp-repeater="cabinet_items">
		<div class="kcp-repeater__rows">
			<?php foreach ( $cabinet_items as $item_index => $item ) : ?>
				<?php
				$item = is_array( $item ) ? $item : array();
				require KCP_PLUGIN_DIR . 'templates/admin/partials/cabinet-item-row.php';
				?>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button button-secondary kcp-repeater__add" data-kcp-add="cabinet_items">
			<?php esc_html_e( 'Add New Item', 'kitchen-configurator-pro' ); ?>
		</button>
	</div>
</div>
