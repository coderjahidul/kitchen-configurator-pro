<?php
/**
 * Brand category landing — top sections.
 *
 * @package KitchenConfiguratorPro
 */

defined( 'ABSPATH' ) || exit;

/** @var array<string, mixed> $model */
$model = is_array( $model ?? null ) ? $model : array();

$breadcrumbs        = is_array( $model['breadcrumbs'] ?? null ) ? $model['breadcrumbs'] : array();
$navigation         = is_array( $model['navigation'] ?? null ) ? $model['navigation'] : array();
$hero               = is_array( $model['hero'] ?? null ) ? $model['hero'] : array();
$usps               = is_array( $model['usps'] ?? null ) ? $model['usps'] : array();
$video_tiles        = is_array( $model['video_tiles'] ?? null ) ? $model['video_tiles'] : array();
$spotlight_products = is_array( $model['spotlight_products'] ?? null ) ? $model['spotlight_products'] : array();
$popular_products   = is_array( $model['popular_products'] ?? null ) ? $model['popular_products'] : array();
$brand_label        = (string) ( $model['brand_label'] ?? '' );
$popular_heading    = (string) ( $model['popular_heading'] ?? '' );
$back_url           = (string) ( $model['back_url'] ?? '' );
$is_root            = ! empty( $model['is_root'] );
$nav_path           = KCP_PLUGIN_DIR . 'templates/woocommerce/partials/brand-landing-nav.php';
?>
<div class="kcp-brand-landing">
	<?php if ( ! empty( $breadcrumbs ) ) : ?>
		<nav class="kcp-brand-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'kitchen-configurator-pro' ); ?>">
			<div class="kcp-brand-breadcrumbs__list">
				<?php foreach ( $breadcrumbs as $index => $crumb ) : ?>
					<?php
					$label = (string) ( $crumb['label'] ?? '' );
					$url   = (string) ( $crumb['url'] ?? '' );
					$last  = ( $index === count( $breadcrumbs ) - 1 );
					?>
					<span class="kcp-brand-breadcrumbs__item">
						<?php if ( ! $last && '' !== $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php else : ?>
							<span aria-current="<?php echo $last ? 'page' : 'false'; ?>"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>
					</span>
				<?php endforeach; ?>
			</div>
		</nav>
	<?php endif; ?>

	<div class="kcp-brand-toolbar">
		<?php if ( '' !== $back_url ) : ?>
			<a class="kcp-brand-toolbar__back" href="<?php echo esc_url( $back_url ); ?>" aria-label="<?php esc_attr_e( 'Terug', 'kitchen-configurator-pro' ); ?>">
				<span aria-hidden="true">←</span>
			</a>
		<?php endif; ?>
		<?php if ( '' !== $brand_label ) : ?>
			<p class="kcp-brand-toolbar__title"><?php echo esc_html( $brand_label ); ?></p>
		<?php endif; ?>
	</div>

	<div class="kcp-brand-layout">
		<?php if ( is_readable( $nav_path ) ) : ?>
			<?php
			$modifier = 'desktop';
			include $nav_path;
			?>
		<?php endif; ?>

		<div class="kcp-brand-main">
			<a class="kcp-brand-hero-banner" href="<?php echo esc_url( (string) ( $hero['cta_url'] ?? '#kcp-brand-products' ) ); ?>">
				<div class="kcp-brand-hero-banner__visual<?php echo '' === (string) ( $hero['image_url'] ?? '' ) ? ' kcp-brand-hero-banner__visual--placeholder' : ''; ?>">
					<?php if ( '' !== (string) ( $hero['image_url'] ?? '' ) ) : ?>
						<img
							class="kcp-brand-hero-banner__image"
							src="<?php echo esc_url( (string) $hero['image_url'] ); ?>"
							alt=""
							loading="eager"
							decoding="async"
						/>
					<?php endif; ?>
					<?php if ( '' !== (string) ( $hero['badge'] ?? '' ) ) : ?>
						<span class="kcp-brand-hero-banner__badge"><?php echo esc_html( (string) $hero['badge'] ); ?></span>
					<?php endif; ?>
					<div class="kcp-brand-hero-banner__text">
						<h2 class="kcp-brand-hero-banner__title"><?php echo esc_html( (string) ( $hero['title'] ?? '' ) ); ?></h2>
						<span class="kcp-brand-hero-banner__cta"><?php echo esc_html( (string) ( $hero['cta_label'] ?? '' ) ); ?></span>
					</div>
				</div>
			</a>

			<?php if ( is_readable( $nav_path ) ) : ?>
				<?php
				$modifier = 'mobile';
				include $nav_path;
				?>
			<?php endif; ?>

			<?php if ( ! empty( $spotlight_products ) ) : ?>
				<section class="kcp-brand-spotlight" aria-label="<?php esc_attr_e( 'Uitgelichte producten', 'kitchen-configurator-pro' ); ?>">
					<div class="kcp-brand-spotlight__track">
						<?php
						foreach ( $spotlight_products as $product ) {
							if ( $product instanceof WC_Product ) {
								KitchenConfiguratorPro\Services\ShopBrandLandingService::render_featured_product_card( $product, $brand_label );
							}
						}
						if ( isset( $spotlight_products[0] ) && $spotlight_products[0] instanceof WC_Product ) {
							for ( $i = 0; $i < 2; $i++ ) {
								KitchenConfiguratorPro\Services\ShopBrandLandingService::render_featured_product_card( $spotlight_products[0], $brand_label );
							}
						}
						?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $usps ) ) : ?>
				<ul class="kcp-brand-usps">
					<?php foreach ( $usps as $usp ) : ?>
						<li>
							<span class="kcp-brand-usps__icon" aria-hidden="true"></span>
							<span><?php echo esc_html( (string) $usp ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( ! empty( $video_tiles ) ) : ?>
				<div class="kcp-brand-tiles">
					<?php foreach ( $video_tiles as $tile ) : ?>
						<a class="kcp-brand-tile" href="<?php echo esc_url( (string) ( $tile['url'] ?? '' ) ); ?>">
							<span class="kcp-brand-tile__media<?php echo '' === (string) ( $tile['image_url'] ?? '' ) ? ' kcp-brand-tile__media--placeholder' : ''; ?>">
								<?php if ( '' !== (string) ( $tile['image_url'] ?? '' ) ) : ?>
									<img src="<?php echo esc_url( (string) $tile['image_url'] ); ?>" alt="" loading="lazy" decoding="async" />
								<?php endif; ?>
								<span class="kcp-brand-tile__play" aria-hidden="true"></span>
							</span>
							<span class="kcp-brand-tile__label"><?php echo esc_html( (string) ( $tile['name'] ?? '' ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $popular_products ) ) : ?>
				<section id="kcp-brand-products" class="kcp-brand-popular">
					<h2 class="kcp-brand-popular__title"><?php echo esc_html( $popular_heading ); ?></h2>
					<div class="kcp-brand-popular__grid">
						<?php
						foreach ( $popular_products as $product ) {
							if ( $product instanceof WC_Product ) {
								KitchenConfiguratorPro\Services\ShopBrandLandingService::render_popular_product_card( $product, $brand_label );
							}
						}
						?>
					</div>
				</section>
			<?php elseif ( ! $is_root ) : ?>
				<div id="kcp-brand-products" class="kcp-brand-products-anchor">
					<h1 class="kcp-brand-landing__title"><?php echo esc_html( strtolower( (string) ( $model['heading'] ?? '' ) ) ); ?></h1>
				</div>
			<?php endif; ?>

			<?php
			$story_html  = (string) ( $model['story_html'] ?? '' );
			$story_title = (string) ( $model['story_title'] ?? '' );
			$back_label  = (string) ( $model['back_label'] ?? '' );
			?>

			<?php if ( $is_root && '' !== $story_title ) : ?>
				<div class="kcp-brand-story-anchor">
					<h1 class="kcp-brand-landing__title"><?php echo esc_html( $story_title ); ?></h1>
				</div>
			<?php endif; ?>

			<?php if ( '' !== trim( wp_strip_all_tags( $story_html ) ) ) : ?>
				<section class="kcp-brand-story">
					<div class="kcp-brand-story__content">
						<?php echo wp_kses_post( $story_html ); ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( '' !== $back_url ) : ?>
				<div class="kcp-brand-landing__back">
					<a class="kcp-brand-landing__back-link" href="<?php echo esc_url( $back_url ); ?>">
						<?php echo esc_html( $back_label ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
