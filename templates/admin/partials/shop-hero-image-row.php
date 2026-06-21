<?php
/**
 * Admin shop hero image row.
 *
 * @package KitchenConfiguratorPro
 *
 * @var string $value Image URL value.
 */

defined( 'ABSPATH' ) || exit;

$value = (string) ( $value ?? '' );

?>
<div class="kcp-repeater__row kcp-repeater__row--shop-hero-image">
	<div class="kcp-repeater__full kcp-repeater__image-field">
		<?php
		$name     = 'shop_hero_images[]';
		$id       = '';
		$modifier = 'compact';
		require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
		?>
	</div>
	<button type="button" class="button kcp-repeater__remove" aria-label="<?php esc_attr_e( 'Remove image', 'kitchen-configurator-pro' ); ?>">&times;</button>
</div>
