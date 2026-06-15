<?php
/**
 * Admin image picker field (WordPress media library).
 *
 * @package KitchenConfiguratorPro
 *
 * @var string $name     Input name attribute.
 * @var string $value    Image URL value.
 * @var string $id       Optional input ID.
 * @var string $modifier Optional size modifier: compact|large.
 */

defined( 'ABSPATH' ) || exit;

$name     = (string) ( $name ?? '' );
$value    = (string) ( $value ?? '' );
$id       = (string) ( $id ?? '' );
$modifier = (string) ( $modifier ?? '' );
$has_image = '' !== $value;

$classes = array( 'kcp-image-picker' );

if ( '' !== $modifier ) {
	$classes[] = 'kcp-image-picker--' . sanitize_html_class( $modifier );
}

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-kcp-image-picker>
	<input
		type="hidden"
		class="kcp-image-picker__input"
		name="<?php echo esc_attr( $name ); ?>"
		<?php if ( '' !== $id ) : ?>
			id="<?php echo esc_attr( $id ); ?>"
		<?php endif; ?>
		value="<?php echo esc_attr( $value ); ?>"
	/>
	<div class="kcp-image-picker__preview<?php echo $has_image ? '' : ' is-empty'; ?>">
		<?php if ( $has_image ) : ?>
			<img src="<?php echo esc_url( $value ); ?>" alt="" />
		<?php else : ?>
			<span class="kcp-image-picker__placeholder"><?php esc_html_e( 'No image selected', 'kitchen-configurator-pro' ); ?></span>
		<?php endif; ?>
	</div>
	<div class="kcp-image-picker__actions">
		<button type="button" class="button kcp-image-picker__select">
			<?php esc_html_e( 'Select image', 'kitchen-configurator-pro' ); ?>
		</button>
		<button type="button" class="button-link kcp-image-picker__remove"<?php echo $has_image ? '' : ' hidden'; ?>>
			<?php esc_html_e( 'Remove', 'kitchen-configurator-pro' ); ?>
		</button>
	</div>
</div>
