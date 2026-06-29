<?php
/**
 * Shop page USP bar and promotional tiles.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $promo Promo settings.
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Services\ShopPromoService;

$usps  = is_array( $promo['usps'] ?? null ) ? $promo['usps'] : array();
$tiles = is_array( $promo['tiles'] ?? null ) ? $promo['tiles'] : array();

if ( empty( $usps ) && empty( $tiles ) ) {
	return;
}

?>
<section class="kcp-shop-promo" aria-label="<?php esc_attr_e( 'Shop highlights', 'kitchen-configurator-pro' ); ?>">
	<?php if ( ! empty( $usps ) ) : ?>
		<div class="kcp-shop-usps">
			<?php foreach ( $usps as $usp ) : ?>
				<?php
				$label = trim( (string) ( $usp['label'] ?? '' ) );
				$icon  = sanitize_key( (string) ( $usp['icon'] ?? 'cabinet' ) );

				if ( '' === $label ) {
					continue;
				}

				$icon_url = (string) ( $usp['icon_url'] ?? ShopPromoService::get_icon_url( $icon ) );
				?>
				<div class="kcp-shop-usps__item">
					<img class="kcp-shop-usps__icon" src="<?php echo esc_url( $icon_url ); ?>" alt="" loading="lazy" decoding="async" />
					<span class="kcp-shop-usps__label"><?php echo esc_html( $label ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $tiles ) ) : ?>
		<div class="kcp-shop-promo-tiles">
			<?php foreach ( $tiles as $tile ) : ?>
				<?php
				$label           = trim( (string) ( $tile['label'] ?? '' ) );
				$url             = trim( (string) ( $tile['url'] ?? '' ) );
				$image_url       = trim( (string) ( $tile['image_url'] ?? '' ) );
				$video_url       = trim( (string) ( $tile['video_url'] ?? '' ) );
				$badge_text      = trim( (string) ( $tile['badge_text'] ?? '' ) );
				$badge_image_url = trim( (string) ( $tile['badge_image_url'] ?? '' ) );
				$has_media       = '' !== $video_url || '' !== $image_url;
				$tag             = '' !== $url ? 'a' : 'div';
				$href_attr       = '' !== $url ? ' href="' . esc_url( $url ) . '"' : '';

				if ( '' === $label && ! $has_media ) {
					continue;
				}
				?>

				<<?php echo esc_attr( $tag ); ?> class="kcp-shop-promo-tile"<?php echo $href_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<div class="kcp-shop-promo-tile__media<?php echo $has_media ? ' kcp-shop-promo-tile__media--has-image' : ' kcp-shop-promo-tile__media--placeholder'; ?>">
						<?php if ( '' !== $image_url ) : ?>
							<img
								class="kcp-shop-promo-tile__poster"
								src="<?php echo esc_url( $image_url ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
							/>
						<?php endif; ?>

						<?php if ( '' !== $video_url ) : ?>
							<video
								class="kcp-shop-promo-tile__video"
								src="<?php echo esc_url( $video_url ); ?>"
								muted
								loop
								playsinline
								preload="none"
								hidden
							></video>
						<?php endif; ?>

						<?php if ( '' !== $badge_image_url || '' !== $badge_text ) : ?>
							<div class="kcp-shop-promo-tile__badge">
								<?php if ( '' !== $badge_image_url ) : ?>
									<img src="<?php echo esc_url( $badge_image_url ); ?>" alt="<?php echo esc_attr( $badge_text ); ?>" loading="lazy" decoding="async" />
								<?php else : ?>
									<span><?php echo esc_html( $badge_text ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( '' !== $label ) : ?>
							<div class="kcp-shop-promo-tile__label"><?php echo esc_html( $label ); ?></div>
						<?php endif; ?>
					</div>
				</<?php echo esc_attr( $tag ); ?>>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
