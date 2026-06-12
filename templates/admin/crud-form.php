<?php
/**
 * Generic CRUD form template.
 *
 * @package KitchenConfiguratorPro
 *
 * @var \KitchenConfiguratorPro\Admin\AbstractCrudPage $page
 * @var bool                                           $is_edit
 * @var int                                            $id
 * @var array<string, mixed>                           $values
 * @var array<string, array<string, mixed>>            $fields
 * @var array<int, array{type: string, message: string}> $notices
 * @var string                                         $list_url
 * @var string                                         $entity_label
 * @var string                                         $nonce_action
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="wrap kcp-admin">
	<h1>
		<?php
		echo esc_html(
			$is_edit
				? sprintf(
					/* translators: %s: entity label */
					__( 'Edit %s', 'kitchen-configurator-pro' ),
					$entity_label
				)
				: sprintf(
					/* translators: %s: entity label */
					__( 'Add %s', 'kitchen-configurator-pro' ),
					$entity_label
				)
		);
		?>
	</h1>

	<?php
	require KCP_PLUGIN_DIR . 'templates/admin/partials/admin-notice.php';
	?>

	<form method="post" action="" class="kcp-form">
		<?php wp_nonce_field( $nonce_action ); ?>
		<input type="hidden" name="kcp_action" value="<?php echo esc_attr( $is_edit ? 'update' : 'create' ); ?>" />
		<?php if ( $is_edit ) : ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( (string) $id ); ?>" />
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tbody>
				<?php foreach ( $fields as $field_key => $field ) : ?>
					<?php
					$type        = (string) ( $field['type'] ?? 'text' );
					$label       = (string) ( $field['label'] ?? $field_key );
					$description = (string) ( $field['description'] ?? '' );
					$value       = $values[ $field_key ] ?? ( $field['default'] ?? '' );
					$field_id    = 'kcp-field-' . esc_attr( $field_key );
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $field_id ); ?>">
								<?php echo esc_html( $label ); ?>
								<?php if ( ! empty( $field['required'] ) ) : ?>
									<span class="required">*</span>
								<?php endif; ?>
							</label>
						</th>
						<td>
							<?php if ( 'textarea' === $type || 'json' === $type ) : ?>
								<textarea
									name="<?php echo esc_attr( $field_key ); ?>"
									id="<?php echo esc_attr( $field_id ); ?>"
									class="large-text <?php echo 'json' === $type ? 'kcp-json-field' : ''; ?>"
									rows="<?php echo esc_attr( (string) ( $field['rows'] ?? 5 ) ); ?>"
									<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
								><?php echo esc_textarea( (string) $value ); ?></textarea>
							<?php elseif ( 'checkbox' === $type ) : ?>
								<label>
									<input
										type="checkbox"
										name="<?php echo esc_attr( $field_key ); ?>"
										id="<?php echo esc_attr( $field_id ); ?>"
										value="1"
										<?php checked( ! empty( $value ) ); ?>
									/>
									<?php echo esc_html( (string) ( $field['checkbox_label'] ?? __( 'Enabled', 'kitchen-configurator-pro' ) ) ); ?>
								</label>
							<?php elseif ( 'select' === $type ) : ?>
								<select name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_id ); ?>">
									<?php foreach ( (array) ( $field['options'] ?? array() ) as $option_value => $option_label ) : ?>
										<option value="<?php echo esc_attr( (string) $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>>
											<?php echo esc_html( (string) $option_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input
									type="<?php echo esc_attr( $type ); ?>"
									name="<?php echo esc_attr( $field_key ); ?>"
									id="<?php echo esc_attr( $field_id ); ?>"
									class="regular-text"
									value="<?php echo esc_attr( (string) $value ); ?>"
									<?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>
									<?php echo isset( $field['step'] ) ? 'step="' . esc_attr( (string) $field['step'] ) . '"' : ''; ?>
									<?php echo isset( $field['min'] ) ? 'min="' . esc_attr( (string) $field['min'] ) . '"' : ''; ?>
								/>
							<?php endif; ?>

							<?php if ( '' !== $description ) : ?>
								<p class="description"><?php echo esc_html( $description ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Save', 'kitchen-configurator-pro' ); ?>
			</button>
			<a href="<?php echo esc_url( $list_url ); ?>" class="button">
				<?php esc_html_e( 'Cancel', 'kitchen-configurator-pro' ); ?>
			</a>
		</p>
	</form>
</div>
