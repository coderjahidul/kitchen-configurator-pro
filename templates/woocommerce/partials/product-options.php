<?php
/**
 * Single product option selectors.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $options
 * @var array<int, mixed>    $option_groups
 * @var float                $base_price
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter;

$specs              = is_array( $options['specs'] ?? null ) ? $options['specs'] : array();
$option_groups      = is_array( $option_groups ?? null ) ? $option_groups : array();
$wc_variations_mode = ! empty( $wc_variations_mode );
$color_attribute    = (string) ( $color_attr ?? $options['color_attribute'] ?? '' );
$height_attribute   = (string) ( $height_attr ?? $options['height_attribute'] ?? '' );

if ( empty( $option_groups ) ) {
	$legacy_groups = array();

	if ( ! empty( $colors ?? array() ) ) {
		$legacy_groups[] = array(
			'id'           => 'color',
			'label'        => __( 'kies je frontkleur', 'kitchen-configurator-pro' ),
			'default_item' => (string) ( $default_color ?? '' ),
			'items'        => $colors,
		);
	}

	if ( ! empty( $heights ?? array() ) ) {
		$legacy_groups[] = array(
			'id'           => 'height',
			'label'        => __( 'kies je hoogte', 'kitchen-configurator-pro' ),
			'default_item' => (string) ( $default_height ?? '' ),
			'items'        => $heights,
		);
	}

	$option_groups = $legacy_groups;
}

?>
<div
	class="kcp-product-options"
	data-base-price="<?php echo esc_attr( (string) $base_price ); ?>"
	<?php if ( $wc_variations_mode ) : ?>
		data-wc-variations="1"
		data-color-attribute="<?php echo esc_attr( $color_attribute ); ?>"
		data-height-attribute="<?php echo esc_attr( $height_attribute ); ?>"
	<?php endif; ?>
>
	<?php if ( ! empty( $specs['dimensions'] ) || ! empty( $specs['includes'] ) ) : ?>
		<div class="kcp-product-specs">
			<?php if ( ! empty( $specs['dimensions'] ) ) : ?>
				<div class="kcp-product-specs__block">
					<strong><?php esc_html_e( 'afmeting:', 'kitchen-configurator-pro' ); ?></strong>
					<ul>
						<?php foreach ( (array) $specs['dimensions'] as $line ) : ?>
							<li><?php echo esc_html( (string) $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $specs['includes'] ) ) : ?>
				<div class="kcp-product-specs__block">
					<strong><?php esc_html_e( 'zelf toevoegen:', 'kitchen-configurator-pro' ); ?></strong>
					<ul>
						<?php foreach ( (array) $specs['includes'] as $line ) : ?>
							<li><?php echo esc_html( (string) $line ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php foreach ( $option_groups as $group ) : ?>
		<?php
		$group_id     = sanitize_key( (string) ( $group['id'] ?? '' ) );
		$group_label  = (string) ( $group['label'] ?? ucfirst( $group_id ) );
		$group_items  = is_array( $group['items'] ?? null ) ? $group['items'] : array();
		$default_item = sanitize_key( (string) ( $group['default_item'] ?? ( $group_items[0]['id'] ?? '' ) ) );

		if ( '' === $group_id || empty( $group_items ) ) {
			continue;
		}
		?>
		<div class="kcp-option-group" data-kcp-group-id="<?php echo esc_attr( $group_id ); ?>">
			<h3 class="kcp-option-group__title"><?php echo esc_html( $group_label ); ?></h3>
			<div class="kcp-option-list" role="list">
				<?php foreach ( $group_items as $item ) : ?>
					<?php
					$item_id   = sanitize_key( (string) ( $item['id'] ?? '' ) );
					$label     = (string) ( $item['label'] ?? '' );
					$note      = (string) ( $item['note'] ?? '' );
					$image     = esc_url( (string) ( $item['image_url'] ?? '' ) );
					$hex       = sanitize_hex_color( (string) ( $item['hex_code'] ?? '' ) ) ?: '';
					$modifier  = (float) ( $item['price_modifier'] ?? 0 );
					$active    = $item_id === $default_item;
					$has_thumb = '' !== $image || '' !== $hex;
					$bar_class = 'kcp-option-bar' . ( $has_thumb ? ' kcp-option-bar--color' : ' kcp-option-bar--height' );
					?>
					<button
						type="button"
						class="<?php echo esc_attr( $bar_class ); ?><?php echo $active ? ' kcp-option-bar--active' : ''; ?>"
						data-kcp-option-group="<?php echo esc_attr( $group_id ); ?>"
						data-kcp-option-id="<?php echo esc_attr( $item_id ); ?>"
						data-kcp-option-modifier="<?php echo esc_attr( (string) $modifier ); ?>"
						<?php if ( $wc_variations_mode && 'color' === $group_id && '' !== $color_attribute ) : ?>
							data-kcp-wc-attribute="<?php echo esc_attr( $color_attribute ); ?>"
							data-kcp-wc-value="<?php echo esc_attr( $item_id ); ?>"
						<?php endif; ?>
						<?php if ( $wc_variations_mode && 'height' === $group_id && '' !== $height_attribute ) : ?>
							data-kcp-wc-attribute="<?php echo esc_attr( $height_attribute ); ?>"
							data-kcp-wc-value="<?php echo esc_attr( $item_id ); ?>"
						<?php endif; ?>
						aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
					>
						<?php if ( $has_thumb ) : ?>
							<span class="kcp-option-bar__thumb">
								<?php if ( '' !== $image ) : ?>
									<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
								<?php else : ?>
									<span class="kcp-option-bar__swatch" style="background-color: <?php echo esc_attr( $hex ); ?>;"></span>
								<?php endif; ?>
							</span>
							<span class="kcp-option-bar__content">
								<span class="kcp-option-bar__label"><?php echo esc_html( $label ); ?></span>
								<?php if ( '' !== $note ) : ?>
									<span class="kcp-option-bar__note">(<?php echo esc_html( $note ); ?>)</span>
								<?php endif; ?>
							</span>
						<?php else : ?>
							<span class="kcp-option-bar__label"><?php echo esc_html( $label ); ?></span>
							<span class="kcp-option-bar__price" hidden></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<div class="kcp-product-options__price">
		<span class="kcp-price kcp-live-price"><?php echo esc_html( ShopPresenter::format_dutch_price( $base_price ) ); ?></span>
	</div>
</div>
