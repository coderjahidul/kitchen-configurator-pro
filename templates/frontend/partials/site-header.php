<?php
/**
 * KKF site header shell.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $model
 */

defined( 'ABSPATH' ) || exit;

$mobile_primary  = is_array( $model['mobile_primary_nav'] ?? null ) ? $model['mobile_primary_nav'] : array();
$mobile_links    = is_array( $model['mobile_links'] ?? null ) ? $model['mobile_links'] : array();
$mobile_sections = is_array( $model['mobile_sections'] ?? null ) ? $model['mobile_sections'] : array();
$desktop_nav     = is_array( $model['desktop_nav'] ?? null ) ? $model['desktop_nav'] : array();
$breadcrumbs     = is_array( $model['breadcrumbs'] ?? null ) ? $model['breadcrumbs'] : array();
$cart_count      = (int) ( $model['cart_count'] ?? 0 );
$logo_url        = (string) ( $model['logo_url'] ?? '' );
$logo_alt        = (string) ( $model['logo_alt'] ?? get_bloginfo( 'name', 'display' ) );
?>
<div class="kcp-shell-mobile" id="kcp-shell-mobile" aria-hidden="true">
	<div class="kcp-shell-mobile__panel">
		<?php if ( ! empty( $mobile_primary ) ) : ?>
		<ul class="kcp-shell-mobile__primary">
			<?php foreach ( $mobile_primary as $item ) : ?>
				<li>
					<a
						class="<?php echo ! empty( $item['is_active'] ) ? 'is-active' : ''; ?>"
						href="<?php echo esc_url( (string) ( $item['url'] ?? '' ) ); ?>"
					>
						<?php echo esc_html( (string) ( $item['label'] ?? '' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<?php foreach ( $mobile_sections as $section ) : ?>
			<?php
			$children = is_array( $section['children'] ?? null ) ? $section['children'] : array();
			$label    = (string) ( $section['label'] ?? '' );
			$url      = (string) ( $section['url'] ?? '#' );

			if ( empty( $children ) ) :
				?>
				<div class="kcp-shell-mobile__section">
					<a class="kcp-shell-mobile__section-link" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				</div>
				<?php
				continue;
			endif;
			?>
			<div class="kcp-shell-mobile__section">
				<button type="button" class="kcp-shell-mobile__section-toggle" aria-expanded="false">
					<?php echo esc_html( (string) ( $section['label'] ?? '' ) ); ?>
					<span aria-hidden="true"></span>
				</button>
				<div class="kcp-shell-mobile__section-panel">
					<div class="kcp-shell-nav__dropdown-grid kcp-shell-nav__dropdown-grid--mobile">
						<?php foreach ( $children as $child ) : ?>
							<?php
							$label       = (string) ( $child['label'] ?? '' );
							$url         = (string) ( $child['url'] ?? '#' );
							$image       = (string) ( $child['image'] ?? '' );
							$image_hover = (string) ( $child['image_hover'] ?? '' );
							?>
							<a class="kcp-shell-nav__dropdown-tile" href="<?php echo esc_url( $url ); ?>">
								<span class="kcp-shell-nav__dropdown-media">
									<?php if ( '' !== $image ) : ?>
										<img class="kcp-shell-nav__dropdown-image" src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" decoding="async" />
										<?php if ( '' !== $image_hover ) : ?>
											<img class="kcp-shell-nav__dropdown-image kcp-shell-nav__dropdown-image--hover" src="<?php echo esc_url( $image_hover ); ?>" alt="" loading="lazy" decoding="async" />
										<?php endif; ?>
									<?php else : ?>
										<span class="kcp-shell-nav__dropdown-placeholder" aria-hidden="true"></span>
									<?php endif; ?>
								</span>
								<span class="kcp-shell-nav__dropdown-label"><?php echo esc_html( $label ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
		<?php if ( ! empty( $mobile_links ) ) : ?>
		<ul class="kcp-shell-mobile__links">
			<?php foreach ( $mobile_links as $link ) : ?>
				<li>
					<a href="<?php echo esc_url( (string) ( $link['url'] ?? '' ) ); ?>">
						<?php echo esc_html( (string) ( $link['label'] ?? '' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</div>

<nav class="kcp-shell-nav<?php echo empty( $model['announcement_enabled'] ) ? ' kcp-shell-nav--no-note' : ''; ?>" aria-label="<?php esc_attr_e( 'Hoofdnavigatie', 'kitchen-configurator-pro' ); ?>">
	<?php if ( ! empty( $model['announcement_enabled'] ) ) : ?>
	<div class="kcp-shell-nav__note">
		<a class="kcp-shell-nav__note-link" href="<?php echo esc_url( (string) ( $model['showroom_url'] ?? '' ) ); ?>">
			<span><?php echo esc_html( (string) ( $model['announcement_text'] ?? '' ) ); ?></span>
			<?php if ( ! empty( $model['announcement_cta'] ) ) : ?>
				<span class="kcp-shell-nav__note-cta">
					<?php echo esc_html( (string) $model['announcement_cta'] ); ?>
					<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none" aria-hidden="true">
						<path d="M2.02592 11.3846L12.0086 1.40195M12.0086 1.40195L12.0086 10.83M12.0086 1.40195L2.58051 1.40195" stroke="currentColor" stroke-width="1.66667" stroke-linecap="square"/>
					</svg>
				</span>
			<?php endif; ?>
		</a>
	</div>
	<?php endif; ?>

	<div class="kcp-shell-nav__wrapper">
		<div class="kcp-shell-nav__bg" aria-hidden="true"></div>

		<a
			class="kcp-shell-nav__logo"
			href="<?php echo esc_url( (string) ( $model['corporate_url'] ?? home_url( '/' ) ) ); ?>"
			aria-label="<?php esc_attr_e( 'Terug naar de Corporate website', 'kitchen-configurator-pro' ); ?>"
		>
			<?php if ( '' !== $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" class="kcp-shell-nav__logo-image" loading="eager" decoding="async" />
			<?php else : ?>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 63 64" fill="none" aria-hidden="true">
				<path d="M31.2727 0.654999C14.1091 0.654999 0 14.7639 0 32.0727C0 49.3816 14.1091 63.345 31.2727 63.345C48.5818 63.345 62.5455 49.2361 62.5455 32.0727C62.5455 14.9093 48.5818 0.654999 31.2727 0.654999ZM31.2727 59.8541C16 59.8541 3.63636 47.3452 3.63636 32.0727C3.63636 16.8002 16 4.29131 31.2727 4.29131C46.5455 4.29131 59.0545 16.8002 59.0545 32.0727C59.0545 47.3452 46.6909 59.8541 31.2727 59.8541Z" fill="currentColor"/>
				<path d="M41.5998 19.1274L38.5453 17.2366L28.7998 33.8181L23.1271 30.1818V17.2366H19.4907V46.9088H23.1271V34.5454L42.618 46.9088L44.5089 43.8543L31.8544 35.8545L41.5998 19.1274Z" fill="currentColor"/>
				<path d="M55.7091 0.654999H58.4727V1.23681H57.4545V4.29131H56.7273V1.38226H55.7091V0.654999ZM58.9091 0.654999H59.7818L60.8 3.41859L61.8182 0.654999H62.6909V4.29131H61.9636V3.7095C61.9636 3.41859 61.9636 2.98224 61.9636 2.25498L61.2364 4.29131H60.5091L59.6364 2.25498C59.6364 2.98224 59.6364 3.41859 59.6364 3.7095V4.29131H58.9091V0.654999Z" fill="currentColor"/>
			</svg>
			<?php endif; ?>
		</a>

		<div class="kcp-shell-nav__main">
			<?php foreach ( $desktop_nav as $section ) : ?>
				<?php
				$children = is_array( $section['children'] ?? null ) ? $section['children'] : array();
				$has_children = ! empty( $children );
				?>
				<div class="kcp-shell-nav__item<?php echo $has_children ? ' has-dropdown' : ''; ?>">
					<a class="kcp-shell-nav__link" href="<?php echo esc_url( (string) ( $section['url'] ?? '#' ) ); ?>">
						<?php echo esc_html( (string) ( $section['label'] ?? '' ) ); ?>
						<?php if ( $has_children ) : ?>
							<svg xmlns="http://www.w3.org/2000/svg" width="13" height="8" viewBox="0 0 13 8" fill="none" aria-hidden="true">
								<path d="M2.2557 0.580017L6.8457 5.17002L11.4357 0.580017L12.8457 2.00002L6.8457 8.00002L0.845703 2.00002L2.2557 0.580017Z" fill="currentColor"/>
							</svg>
						<?php endif; ?>
					</a>
					<?php if ( $has_children ) : ?>
						<?php require KCP_PLUGIN_DIR . 'templates/frontend/partials/nav-dropdown-tiles.php'; ?>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="kcp-shell-nav__actions">
			<a
				class="kcp-shell-nav__cart"
				href="<?php echo esc_url( (string) ( $model['cart_url'] ?? '#' ) ); ?>"
				aria-label="<?php esc_attr_e( 'Winkelwagen', 'kitchen-configurator-pro' ); ?>"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 19" fill="none" aria-hidden="true" class="kcp-shell-nav__cart-svg">
					<path d="M1.25 1.06641H4.25L6.5 13.8164H18.5M6.5 10.8164H18.1925C18.2792 10.8165 18.3633 10.7865 18.4304 10.7315C18.4975 10.6766 18.5434 10.6 18.5605 10.515L19.9105 3.765C19.9214 3.71057 19.92 3.6544 19.9066 3.60055C19.8931 3.54669 19.8679 3.4965 19.8327 3.45358C19.7975 3.41066 19.7532 3.3761 19.703 3.35237C19.6528 3.32865 19.598 3.31637 19.5425 3.31641H5M8 16.8164C8 17.2306 7.66421 17.5664 7.25 17.5664C6.83579 17.5664 6.5 17.2306 6.5 16.8164C6.5 16.4022 6.83579 16.0664 7.25 16.0664C7.66421 16.0664 8 16.4022 8 16.8164ZM18.5 16.8164C18.5 17.2306 18.1642 17.5664 17.75 17.5664C17.3358 17.5664 17 17.2306 17 16.8164C17 16.4022 17.3358 16.0664 17.75 16.0664C18.1642 16.0664 18.5 16.4022 18.5 16.8164Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<circle cx="7.5" cy="16.8164" r="1.5" fill="currentColor"/>
					<circle cx="17.5" cy="16.8164" r="1.5" fill="currentColor"/>
				</svg>
				<?php if ( $cart_count > 0 ) : ?>
					<span class="kcp-shell-nav__cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				<?php endif; ?>
			</a>

			<button
				type="button"
				class="kcp-shell-nav__menu-toggle"
				aria-expanded="false"
				aria-controls="kcp-shell-mobile"
				aria-label="<?php esc_attr_e( 'Menu openen', 'kitchen-configurator-pro' ); ?>"
			>
				<span class="kcp-shell-nav__menu-line kcp-shell-nav__menu-line--top" aria-hidden="true"></span>
				<span class="kcp-shell-nav__menu-line kcp-shell-nav__menu-line--bottom" aria-hidden="true"></span>
			</button>
		</div>
	</div>
</nav>

<div class="kcp-shell-subheader">
	<?php if ( ! empty( $breadcrumbs ) ) : ?>
		<nav class="kcp-shell-subheader__crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'kitchen-configurator-pro' ); ?>">
			<ul class="kcp-shell-subheader__crumb-list">
				<?php foreach ( $breadcrumbs as $index => $crumb ) : ?>
					<?php
					$label = (string) ( $crumb['label'] ?? '' );
					$url   = (string) ( $crumb['url'] ?? '' );
					$is_last = $index === count( $breadcrumbs ) - 1;
					?>
					<li>
						<?php if ( ! $is_last && '' !== $url ) : ?>
							<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
						<?php else : ?>
							<span aria-current="<?php echo $is_last ? 'page' : 'false'; ?>"><?php echo esc_html( $label ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php else : ?>
		<div class="kcp-shell-subheader__crumbs"></div>
	<?php endif; ?>
	<?php if ( ! empty( $model['show_theme_toggle'] ) ) : ?>
	<label class="kcp-shell-subheader__theme">
		<span><?php esc_html_e( 'liever een donkere weergave?', 'kitchen-configurator-pro' ); ?></span>
		<input type="checkbox" data-kcp-theme-toggle aria-label="<?php esc_attr_e( 'Donkere weergave', 'kitchen-configurator-pro' ); ?>">
		<span class="kcp-shell-subheader__switch" aria-hidden="true"></span>
	</label>
	<?php endif; ?>
</div>
