<?php
/**
 * Desktop submenu tile grid.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<int, array<string, mixed>> $children Submenu items.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $children ) ) {
	return;
}
?>
<div class="kcp-shell-nav__dropdown kcp-shell-nav__dropdown--tiles">
	<div class="kcp-shell-nav__dropdown-grid">
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
						<img
							class="kcp-shell-nav__dropdown-image"
							src="<?php echo esc_url( $image ); ?>"
							alt=""
							loading="lazy"
							decoding="async"
						/>
						<?php if ( '' !== $image_hover ) : ?>
							<img
								class="kcp-shell-nav__dropdown-image kcp-shell-nav__dropdown-image--hover"
								src="<?php echo esc_url( $image_hover ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
							/>
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
