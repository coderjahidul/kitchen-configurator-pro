<?php
/**
 * KKF site footer shell.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $model
 */

defined( 'ABSPATH' ) || exit;

$footer_columns = is_array( $model['footer_columns'] ?? null ) ? $model['footer_columns'] : array();
$trust_badges   = is_array( $model['trust_badges'] ?? null ) ? $model['trust_badges'] : array();
$legal_links    = is_array( $model['legal_links'] ?? null ) ? $model['legal_links'] : array();
$payment_icons  = is_array( $model['payment_icons'] ?? null ) ? $model['payment_icons'] : array( 'ideal', 'applepay', 'mastercard', 'bancontact' );
$icon_map       = array(
	'ideal'       => array( 'file' => 'ideal.svg', 'label' => 'iDEAL' ),
	'applepay'    => array( 'file' => 'applepay.svg', 'label' => 'Apple Pay' ),
	'mastercard'  => array( 'file' => 'mastercard.svg', 'label' => 'Mastercard' ),
	'bancontact'  => array( 'file' => 'bancontact.svg', 'label' => 'Bancontact' ),
);
$badge_icon_map = array(
	'warranty'   => array( 'file' => 'warranty.svg', 'width' => 33, 'height' => 33 ),
	'reviews'    => array( 'file' => 'reviews.svg', 'width' => 41, 'height' => 42 ),
	'quality'    => array( 'file' => 'quality.svg', 'width' => 41, 'height' => 42 ),
	'brandstore' => array( 'file' => 'brandstore.svg', 'width' => 41, 'height' => 42 ),
);
$contact_icon_map = array(
	'whatsapp' => 'contact-whatsapp.svg',
	'phone'    => 'contact-phone.svg',
	'mail'     => 'contact-mail.svg',
	'location' => 'contact-location.svg',
);
?>
<footer class="kcp-shell-footer" id="kcp-shell-footer">
	<?php if ( ! empty( $payment_icons ) ) : ?>
	<div class="kcp-shell-footer__payments">
		<ul class="kcp-shell-footer__payment-icons">
			<?php foreach ( $payment_icons as $icon_key ) : ?>
				<?php
				$icon_key = sanitize_key( (string) $icon_key );
				if ( ! isset( $icon_map[ $icon_key ] ) ) {
					continue;
				}
				$icon = $icon_map[ $icon_key ];
				?>
				<li>
					<div class="kcp-shell-footer__payment-icon">
						<img
							src="<?php echo esc_url( KCP_PLUGIN_URL . 'assets/frontend/images/shell/' . $icon['file'] ); ?>"
							alt="<?php echo esc_attr( $icon['label'] ); ?>"
							loading="lazy"
							decoding="async"
						>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<div class="kcp-shell-footer__columns">
		<?php foreach ( $footer_columns as $column_index => $column ) : ?>
			<?php
			$links = is_array( $column['links'] ?? null ) ? $column['links'] : array();
			$title = (string) ( $column['title'] ?? '' );
			$is_contact_column = 3 === $column_index || false !== stripos( $title, 'contact' );
			?>
			<div class="kcp-shell-footer__column">
				<button type="button" class="kcp-shell-footer__column-toggle" aria-expanded="false">
					<h4><?php echo esc_html( $title ); ?></h4>
					<span class="kcp-shell-footer__plus" aria-hidden="true"></span>
				</button>
				<ul class="kcp-shell-footer__links">
					<?php foreach ( $links as $link ) : ?>
						<?php
						$label       = (string) ( $link['label'] ?? '' );
						$url         = (string) ( $link['url'] ?? '#' );
						$icon_key    = sanitize_key( (string) ( $link['icon'] ?? '' ) );
						$has_icon    = $is_contact_column && '' !== $icon_key && isset( $contact_icon_map[ $icon_key ] );
						$link_class  = $has_icon ? 'kcp-shell-footer__contact-link' : '';
						?>
						<li>
							<a class="<?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $has_icon && str_starts_with( $url, 'http' ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
								<?php if ( $has_icon ) : ?>
									<img
										class="kcp-shell-footer__contact-icon"
										src="<?php echo esc_url( KCP_PLUGIN_URL . 'assets/frontend/images/shell/' . $contact_icon_map[ $icon_key ] ); ?>"
										alt=""
										width="20"
										height="20"
										loading="lazy"
										decoding="async"
										aria-hidden="true"
									>
								<?php endif; ?>
								<span><?php echo esc_html( $label ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="kcp-shell-footer__badges">
		<?php foreach ( $trust_badges as $badge ) : ?>
			<?php
			$icon_key  = sanitize_key( (string) ( $badge['icon'] ?? 'quality' ) );
			$icon      = $badge_icon_map[ $icon_key ] ?? $badge_icon_map['quality'];
			$badge_alt = (string) ( $badge['label'] ?? '' );
			?>
			<div class="kcp-shell-footer__badge">
				<img
					class="kcp-shell-footer__badge-icon"
					src="<?php echo esc_url( KCP_PLUGIN_URL . 'assets/frontend/images/shell/' . $icon['file'] ); ?>"
					alt=""
					width="<?php echo esc_attr( (string) $icon['width'] ); ?>"
					height="<?php echo esc_attr( (string) $icon['height'] ); ?>"
					loading="lazy"
					decoding="async"
					aria-hidden="true"
				>
				<span><?php echo esc_html( $badge_alt ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="kcp-shell-footer__legal">
		<ul>
			<?php foreach ( $legal_links as $link ) : ?>
				<?php
				$url    = (string) ( $link['url'] ?? '#' );
				$target = (string) ( $link['target'] ?? '' );
				if ( '' === $target && str_starts_with( $url, 'http' ) && ! str_starts_with( $url, home_url() ) ) {
					$target = '_blank';
				}
				?>
				<li>
					<a
						href="<?php echo esc_url( $url ); ?>"
						<?php echo '' !== $target ? ' target="' . esc_attr( $target ) . '" rel="noopener noreferrer"' : ''; ?>
					>
						<?php echo esc_html( (string) ( $link['label'] ?? '' ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</footer>
