<?php
/**
 * Single product color and height selectors.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $options
 * @var array<int, mixed>    $colors
 * @var array<int, mixed>    $heights
 * @var string               $default_color
 * @var string               $default_height
 * @var float                $base_price
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter;

$specs = is_array( $options['specs'] ?? null ) ? $options['specs'] : array();
$wc_variations_mode = ! empty( $wc_variations_mode );
$color_attribute    = (string) ( $color_attr ?? $options['color_attribute'] ?? '' );
$height_attribute   = (string) ( $height_attr ?? $options['height_attribute'] ?? '' );

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

	<?php if ( ! empty( $colors ) ) : ?>
		<div class="kcp-option-group">
			<h3 class="kcp-option-group__title"><?php esc_html_e( 'kies je frontkleur', 'kitchen-configurator-pro' ); ?></h3>
			<div class="kcp-option-list" role="list">
				<?php foreach ( $colors as $color ) : ?>
					<?php
					$id       = sanitize_key( (string) ( $color['id'] ?? '' ) );
					$label    = (string) ( $color['label'] ?? '' );
					$note     = (string) ( $color['note'] ?? '' );
					$image    = esc_url( (string) ( $color['image_url'] ?? '' ) );
					$hex      = sanitize_hex_color( (string) ( $color['hex_code'] ?? '' ) ) ?: '';
					$modifier = (float) ( $color['price_modifier'] ?? 0 );
					$active   = $id === $default_color;
					?>
					<button
						type="button"
						class="kcp-option-bar kcp-option-bar--color<?php echo $active ? ' kcp-option-bar--active' : ''; ?>"
						data-kcp-option-group="color"
						data-kcp-option-id="<?php echo esc_attr( $id ); ?>"
						data-kcp-option-modifier="<?php echo esc_attr( (string) $modifier ); ?>"
						<?php if ( $wc_variations_mode && '' !== $color_attribute ) : ?>
							data-kcp-wc-attribute="<?php echo esc_attr( $color_attribute ); ?>"
							data-kcp-wc-value="<?php echo esc_attr( $id ); ?>"
						<?php endif; ?>
						aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
					>
						<span class="kcp-option-bar__thumb">
							<?php if ( '' !== $image ) : ?>
								<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
							<?php elseif ( '' !== $hex ) : ?>
								<span class="kcp-option-bar__swatch" style="background-color: <?php echo esc_attr( $hex ); ?>;"></span>
							<?php else : ?>
								<span class="kcp-option-bar__swatch kcp-option-bar__swatch--empty" aria-hidden="true"></span>
							<?php endif; ?>
						</span>
						<span class="kcp-option-bar__content">
							<span class="kcp-option-bar__label"><?php echo esc_html( $label ); ?></span>
							<?php if ( '' !== $note ) : ?>
								<span class="kcp-option-bar__note">(<?php echo esc_html( $note ); ?>)</span>
							<?php endif; ?>
						</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $heights ) ) : ?>
		<div class="kcp-option-group">
			<h3 class="kcp-option-group__title"><?php esc_html_e( 'kies je hoogte', 'kitchen-configurator-pro' ); ?></h3>
			<div class="kcp-option-list" role="list">
				<?php foreach ( $heights as $height ) : ?>
					<?php
					$id       = sanitize_key( (string) ( $height['id'] ?? '' ) );
					$label    = (string) ( $height['label'] ?? '' );
					$modifier = (float) ( $height['price_modifier'] ?? 0 );
					$active   = $id === $default_height;
					?>
					<button
						type="button"
						class="kcp-option-bar kcp-option-bar--height<?php echo $active ? ' kcp-option-bar--active' : ''; ?>"
						data-kcp-option-group="height"
						data-kcp-option-id="<?php echo esc_attr( $id ); ?>"
						data-kcp-option-modifier="<?php echo esc_attr( (string) $modifier ); ?>"
						<?php if ( $wc_variations_mode && '' !== $height_attribute ) : ?>
							data-kcp-wc-attribute="<?php echo esc_attr( $height_attribute ); ?>"
							data-kcp-wc-value="<?php echo esc_attr( $id ); ?>"
						<?php endif; ?>
						aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
					>
						<span class="kcp-option-bar__label"><?php echo esc_html( $label ); ?></span>
						<?php if ( $modifier > 0 ) : ?>
							<span class="kcp-option-bar__price">+<?php echo esc_html( ShopPresenter::format_dutch_price( $modifier ) ); ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<div class="kcp-product-options__price">
		<span class="kcp-price kcp-live-price"><?php echo esc_html( ShopPresenter::format_dutch_price( $base_price ) ); ?></span>
	</div>
</div>
