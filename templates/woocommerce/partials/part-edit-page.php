<?php
/**
 * Full-page cart part editor.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $view Part edit view model.
 */

defined( 'ABSPATH' ) || exit;

$part_label    = (string) ( $view['part_label'] ?? '' );
$cart_key      = (string) ( $view['cart_key'] ?? '' );
$part_pos      = (int) ( $view['part_pos'] ?? 0 );
$cart_url      = (string) ( $view['cart_url'] ?? '' );
$image_url     = (string) ( $view['image_url'] ?? '' );
$info_lines    = is_array( $view['info_lines'] ?? null ) ? $view['info_lines'] : array();
$items         = is_array( $view['items'] ?? null ) ? $view['items'] : array();
$selected_item = (string) ( $view['selected_item'] ?? '' );
$price_label   = (string) ( $view['price_label'] ?? '0,-' );
$items_json    = (string) ( $view['items_json'] ?? '[]' );
$form_id       = 'kcp-part-edit-form';

?>
<div class="kcp-part-edit-page" data-kcp-part-edit>
	<header class="kcp-part-edit-page__header">
		<h1 class="kcp-part-edit-page__title"><?php echo esc_html( $part_label ); ?></h1>
		<a href="<?php echo esc_url( $cart_url ); ?>" class="kcp-part-edit-page__back">
			<?php esc_html_e( 'terug naar kasten', 'kitchen-configurator-pro' ); ?>
		</a>
	</header>

	<div class="kcp-part-edit-page__layout">
		<aside class="kcp-part-edit-page__media" aria-label="<?php esc_attr_e( 'Productafbeelding', 'kitchen-configurator-pro' ); ?>">
			<div class="kcp-part-edit-page__image-main">
				<?php if ( '' !== $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $part_label ); ?>" id="kcp-part-edit-image-main" loading="lazy">
				<?php endif; ?>
			</div>
			<?php if ( '' !== $image_url ) : ?>
				<button type="button" class="kcp-part-edit-page__image-thumb" aria-label="<?php esc_attr_e( 'Productafbeelding', 'kitchen-configurator-pro' ); ?>">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy">
				</button>
			<?php endif; ?>
		</aside>

		<section class="kcp-part-edit-page__config">
			<form id="<?php echo esc_attr( $form_id ); ?>" class="kcp-part-edit-page__form" method="post" action="">
				<input type="hidden" name="kcp_edit" value="1">
				<input type="hidden" name="kcp_cart_key" value="<?php echo esc_attr( $cart_key ); ?>">
				<input type="hidden" name="kcp_part_pos" value="<?php echo esc_attr( (string) $part_pos ); ?>">

				<div class="kcp-part-edit-page__section">
					<h2 class="kcp-part-edit-page__section-title"><?php esc_html_e( 'selecteer formaat', 'kitchen-configurator-pro' ); ?></h2>
					<label class="kcp-part-edit-page__field">
						<span class="screen-reader-text"><?php echo esc_html( $part_label ); ?></span>
						<select class="kcp-part-edit-page__select" name="kcp_part_item" id="kcp-part-item-select" data-kcp-part-item-select>
							<?php foreach ( $items as $item ) : ?>
								<?php
								$item_id    = sanitize_key( (string) ( $item['id'] ?? '' ) );
								$item_label = (string) ( $item['dropdown_label'] ?? $item['description'] ?? $item['label'] ?? $item_id );
								?>
								<option
									value="<?php echo esc_attr( $item_id ); ?>"
									data-price="<?php echo esc_attr( (string) (float) ( $item['price'] ?? 0 ) ); ?>"
									data-image-url="<?php echo esc_attr( (string) ( $item['image_url'] ?? '' ) ); ?>"
									<?php selected( $selected_item, $item_id ); ?>
								>
									<?php echo esc_html( $item_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<div class="kcp-part-edit-page__price">
					<span class="kcp-part-edit-page__price-label"><?php esc_html_e( 'Product wijzigen', 'kitchen-configurator-pro' ); ?></span>
					<strong class="kcp-part-edit-page__price-value" id="kcp-part-edit-price"><?php echo esc_html( $price_label ); ?></strong>
					<span class="kcp-part-edit-page__price-unit"><?php esc_html_e( 'per stuk', 'kitchen-configurator-pro' ); ?></span>
				</div>
			</form>
		</section>

		<aside class="kcp-part-edit-page__info">
			<h2 class="kcp-part-edit-page__info-title"><?php esc_html_e( 'productinformatie', 'kitchen-configurator-pro' ); ?></h2>
			<?php if ( ! empty( $info_lines ) ) : ?>
				<ul class="kcp-part-edit-page__info-list">
					<?php foreach ( $info_lines as $line ) : ?>
						<li><?php echo esc_html( (string) $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<button type="submit" class="kcp-part-edit-page__submit" form="<?php echo esc_attr( $form_id ); ?>">
				<?php esc_html_e( 'winkelwagen updaten', 'kitchen-configurator-pro' ); ?>
			</button>
		</aside>
	</div>

	<script type="application/json" id="kcp-part-edit-data"><?php echo $items_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON data block. ?></script>
</div>
