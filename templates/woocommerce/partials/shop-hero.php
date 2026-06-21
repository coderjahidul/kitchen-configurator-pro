<?php
/**
 * Shop page hero section.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $hero Hero settings.
 */

defined( 'ABSPATH' ) || exit;

$button_1   = is_array( $hero['button_1'] ?? null ) ? $hero['button_1'] : array();
$button_2   = is_array( $hero['button_2'] ?? null ) ? $hero['button_2'] : array();
$help_link  = is_array( $hero['help_link'] ?? null ) ? $hero['help_link'] : array();
$buttons    = array();
$image_urls = is_array( $hero['image_urls'] ?? null ) ? $hero['image_urls'] : array();
$interval   = max( 2, (int) ( $hero['image_interval'] ?? 4 ) ) * 1000;

foreach ( array( $button_1, $button_2 ) as $button ) {
	$label = trim( (string) ( $button['label'] ?? '' ) );
	$url   = trim( (string) ( $button['url'] ?? '' ) );

	if ( '' === $label || '' === $url ) {
		continue;
	}

	$buttons[] = array(
		'label' => $label,
		'url'   => $url,
	);
}

$help_label = trim( (string) ( $help_link['label'] ?? '' ) );
$help_url   = trim( (string) ( $help_link['url'] ?? '' ) );

$image_urls = array_values(
	array_filter(
		array_map(
			static fn( $url ): string => esc_url_raw( (string) $url ),
			$image_urls
		)
	)
);

?>
<section class="kcp-shop-hero">
	<div class="kcp-shop-hero__content">
		<?php if ( '' !== (string) ( $hero['heading'] ?? '' ) ) : ?>
			<h2 class="kcp-shop-hero__title"><?php echo esc_html( (string) $hero['heading'] ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== (string) ( $hero['description'] ?? '' ) ) : ?>
			<p class="kcp-shop-hero__description"><?php echo esc_html( (string) $hero['description'] ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $buttons ) ) : ?>
			<div class="kcp-shop-hero__actions">
				<?php foreach ( $buttons as $button ) : ?>
					<a class="kcp-shop-hero__button" href="<?php echo esc_url( $button['url'] ); ?>">
						<?php echo esc_html( $button['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $help_label && '' !== $help_url ) : ?>
			<a class="kcp-shop-hero__help" href="<?php echo esc_url( $help_url ); ?>">
				<span class="kcp-shop-hero__help-icon" aria-hidden="true">?</span>
				<?php echo esc_html( $help_label ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $image_urls ) ) : ?>
		<div class="kcp-shop-hero__media<?php echo count( $image_urls ) > 1 ? ' kcp-shop-hero__media--animated' : ''; ?>">
			<div
				class="kcp-shop-hero__slideshow"
				<?php if ( count( $image_urls ) > 1 ) : ?>
					data-interval="<?php echo esc_attr( (string) $interval ); ?>"
					aria-live="polite"
				<?php endif; ?>
			>
				<?php foreach ( $image_urls as $index => $image_url ) : ?>
					<div class="kcp-shop-hero__slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
						<img
							class="kcp-shop-hero__image"
							src="<?php echo esc_url( $image_url ); ?>"
							alt=""
							<?php echo 0 === $index ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
							decoding="async"
						/>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
