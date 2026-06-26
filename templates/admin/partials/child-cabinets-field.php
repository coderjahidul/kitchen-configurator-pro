<?php
/**
 * Child cabinets multi-select field partial.
 *
 * @package KitchenConfiguratorPro
 *
 * @var string               $field_id
 * @var array<int, int>      $selected_child_ids
 * @var array<int, array{id: int, name: string, slug: string, category: string}> $available_cabinets
 * @var int                  $cabinet_id
 */

defined( 'ABSPATH' ) || exit;

$selected_child_ids = array_map( 'intval', (array) ( $selected_child_ids ?? array() ) );
$available_cabinets = (array) ( $available_cabinets ?? array() );

?>
<div class="kcp-child-cabinets-field" data-exclude-id="<?php echo esc_attr( (string) $cabinet_id ); ?>">
	<?php if ( empty( $available_cabinets ) ) : ?>
		<p class="description">
			<?php esc_html_e( 'No other cabinets available. Create more cabinets first, then assign them here.', 'kitchen-configurator-pro' ); ?>
		</p>
	<?php else : ?>
		<p class="description">
			<?php esc_html_e( 'Check one or more cabinets below, then click Save.', 'kitchen-configurator-pro' ); ?>
		</p>

		<input
			type="search"
			id="<?php echo esc_attr( $field_id ); ?>-filter"
			class="regular-text kcp-child-cabinets-field__filter"
			placeholder="<?php esc_attr_e( 'Filter cabinets…', 'kitchen-configurator-pro' ); ?>"
			autocomplete="off"
		/>

		<ul class="kcp-child-cabinets-field__options" id="<?php echo esc_attr( $field_id ); ?>-options">
			<?php foreach ( $available_cabinets as $cabinet ) : ?>
				<?php
				$child_id    = (int) ( $cabinet['id'] ?? 0 );
				$child_name  = (string) ( $cabinet['name'] ?? '' );
				$category    = (string) ( $cabinet['category'] ?? '' );
				$label       = $child_name . ( '' !== $category ? ' (' . $category . ')' : '' );
				$is_selected = in_array( $child_id, $selected_child_ids, true );
				$option_id   = $field_id . '-child-' . $child_id;
				?>
				<li
					class="kcp-child-cabinets-field__option"
					data-label="<?php echo esc_attr( strtolower( $label ) ); ?>"
				>
					<label for="<?php echo esc_attr( $option_id ); ?>">
						<input
							type="checkbox"
							id="<?php echo esc_attr( $option_id ); ?>"
							name="child_cabinet_ids[]"
							value="<?php echo esc_attr( (string) $child_id ); ?>"
							<?php checked( $is_selected ); ?>
						/>
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
