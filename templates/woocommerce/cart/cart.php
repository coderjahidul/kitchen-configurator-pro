<?php
/**
 * KKF-style cart page.
 *
 * @package KitchenConfiguratorPro
 * @version 1.3.0
 */

defined( 'ABSPATH' ) || exit;

use KitchenConfiguratorPro\Integration\WooCommerce\CartPresenter;
use KitchenConfiguratorPro\Integration\WooCommerce\ShopPresenter;

/** @var CartPresenter $presenter */
$presenter        = kcp_plugin()->container()->get( CartPresenter::class );
$groups           = $presenter->get_display_groups();
$summary          = $presenter->get_configuration_summary();
$plinth_lines     = $presenter->get_plinth_lines();
$delivery_weeks   = $presenter->get_delivery_weeks();
$item_count       = $presenter->get_item_count();
$cart_total       = $presenter->get_formatted_total();
$empty_cart_url   = $presenter->get_empty_cart_url();
$shop_url         = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$checkout_url     = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : $shop_url;
$first_edit_url   = ! empty( $groups[0]['edit_url'] ) ? (string) $groups[0]['edit_url'] : $shop_url;

do_action( 'woocommerce_before_cart' );
?>

<div class="kcp-cart">
	<header class="kcp-cart__header">
		<div class="kcp-cart__title-wrap">
			<h1 class="kcp-cart__title"><?php esc_html_e( 'mijn winkelwagen', 'kitchen-configurator-pro' ); ?></h1>
			<?php if ( $item_count > 0 ) : ?>
				<a href="<?php echo esc_url( $empty_cart_url ); ?>" class="kcp-cart__title-trash" aria-label="<?php esc_attr_e( 'Winkelwagen legen', 'kitchen-configurator-pro' ); ?>">
					<i class="fa fa-trash" aria-hidden="true"></i>
				</a>
			<?php endif; ?>
		</div>
		<?php if ( $item_count > 0 ) : ?>
			<div class="kcp-cart__header-actions">
				<a href="<?php echo esc_url( $shop_url ); ?>" class="kcp-cart__save">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 2l2.4 4.8L20 7.5l-3.5 3.4.8 5.1L12 13.8 6.7 16l.8-5.1L4 7.5l5.6-.7z"/></svg>
					<?php esc_html_e( 'winkelwagen opslaan', 'kitchen-configurator-pro' ); ?>
				</a>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="kcp-cart__add-cabinet">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
					<?php esc_html_e( 'nieuwe kast toevoegen', 'kitchen-configurator-pro' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $summary ) ) : ?>
		<section class="kcp-cart-summary" aria-label="<?php esc_attr_e( 'Configuratie overzicht', 'kitchen-configurator-pro' ); ?>">
			<ul class="kcp-cart-summary__list">
				<?php foreach ( $summary as $row ) : ?>
					<li class="kcp-cart-summary__item">
						<span class="kcp-cart-summary__label"><?php echo esc_html( (string) ( $row['label'] ?? '' ) ); ?></span>
						<span class="kcp-cart-summary__value"><?php echo esc_html( (string) ( $row['value'] ?? '' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="kcp-cart-summary__footer">
				<a href="<?php echo esc_url( $first_edit_url ); ?>" class="kcp-cart-summary__link"><?php esc_html_e( 'wijzigen', 'kitchen-configurator-pro' ); ?></a>
				<a href="<?php echo esc_url( $shop_url ); ?>" class="kcp-cart-summary__link"><?php esc_html_e( 'kast toevoegen', 'kitchen-configurator-pro' ); ?></a>
			</div>
		</section>
	<?php endif; ?>

	<form class="woocommerce-cart-form kcp-cart__form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="kcp-cart__products">
			<?php foreach ( $groups as $group_index => $group ) : ?>
				<?php
				$cart_key         = (string) ( $group['cart_key'] ?? '' );
				$group_title      = (string) ( $group['group_title'] ?? '' );
				$preview_image    = (string) ( $group['preview_image'] ?? '' );
				$edit_url         = (string) ( $group['edit_url'] ?? '' );
				$remove_url       = (string) ( $group['remove_url'] ?? '' );
				$empty_group_url  = '' !== $cart_key ? $presenter->get_empty_group_url( $cart_key ) : '';
				$parts            = is_array( $group['parts'] ?? null ) ? $group['parts'] : array();
				$has_breakdown    = count( $parts ) > 0;
				$show_drawings    = '' !== $preview_image || '' !== $group_title;
				?>
				<section class="kcp-cart-product" data-kcp-group="<?php echo esc_attr( (string) $group_index ); ?>">
					<?php if ( $show_drawings ) : ?>
						<div class="kcp-cart-product__drawings">
							<div class="kcp-cart-product__drawings-head">
								<h2 class="kcp-cart-product__drawings-title"><?php esc_html_e( 'tekeningen', 'kitchen-configurator-pro' ); ?></h2>
								<button type="button" class="kcp-cart-product__refresh" aria-label="<?php esc_attr_e( 'Tekeningen vernieuwen', 'kitchen-configurator-pro' ); ?>">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
								</button>
							</div>
							<div class="kcp-cart-hero">
								<div class="kcp-cart-hero__aside">
									<strong class="kcp-cart-hero__title"><?php echo esc_html( $group_title ); ?></strong>
									<?php if ( '' !== $edit_url ) : ?>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="kcp-cart-hero__edit">
											<?php esc_html_e( 'bekijk en bewerk', 'kitchen-configurator-pro' ); ?>
										</a>
									<?php endif; ?>
								</div>
								<?php if ( '' !== $preview_image ) : ?>
									<div class="kcp-cart-hero__image">
										<img src="<?php echo esc_url( $preview_image ); ?>" alt="<?php echo esc_attr( $group_title ); ?>" loading="lazy">
									</div>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $has_breakdown ) : ?>
						<div class="kcp-cart-breakdown">
							<header class="kcp-cart-breakdown__header">
								<h3 class="kcp-cart-breakdown__title"><?php echo esc_html( $group_title ); ?></h3>
								<div class="kcp-cart-breakdown__actions">
									<?php if ( '' !== $empty_group_url ) : ?>
										<a href="<?php echo esc_url( $empty_group_url ); ?>" class="kcp-cart-breakdown__action" data-kcp-confirm-empty>
											<?php esc_html_e( 'Groep leegmaken', 'kitchen-configurator-pro' ); ?>
										</a>
									<?php endif; ?>
									<?php if ( '' !== $remove_url ) : ?>
										<a href="<?php echo esc_url( $remove_url ); ?>" class="kcp-cart-breakdown__action" data-kcp-confirm-remove-group>
											<?php esc_html_e( 'Groep verwijderen', 'kitchen-configurator-pro' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</header>

							<div class="kcp-cart-breakdown__list" role="list">
								<?php foreach ( $parts as $part ) : ?>
									<?php
									$label         = (string) ( $part['label'] ?? '' );
									$description   = (string) ( $part['description'] ?? '' );
									$price         = (float) ( $part['price'] ?? 0 );
									$image_url     = (string) ( $part['image_url'] ?? '' );
									$duplicate_url = (string) ( $part['duplicate_url'] ?? '' );
									$part_remove   = (string) ( $part['remove_url'] ?? '' );
									$part_edit     = (string) ( $part['edit_url'] ?? '' );
									?>
									<article class="kcp-cart-part" role="listitem">
										<div class="kcp-cart-part__controls">
											<span class="kcp-cart-part__drag" aria-hidden="true">
												<svg width="12" height="16" viewBox="0 0 12 16" fill="currentColor"><circle cx="3" cy="3" r="1.2"/><circle cx="9" cy="3" r="1.2"/><circle cx="3" cy="8" r="1.2"/><circle cx="9" cy="8" r="1.2"/><circle cx="3" cy="13" r="1.2"/><circle cx="9" cy="13" r="1.2"/></svg>
											</span>
											<span class="kcp-cart-part__reorder" aria-hidden="true">
												<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9l-4 3 4 3M16 15l4-3-4-3"/></svg>
											</span>
										</div>

										<div class="kcp-cart-part__thumb">
											<?php if ( '' !== $image_url ) : ?>
												<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy">
											<?php else : ?>
												<span class="kcp-cart-part__thumb-placeholder" aria-hidden="true"></span>
											<?php endif; ?>
										</div>

										<div class="kcp-cart-part__content">
											<div class="kcp-cart-part__headline">
												<h4 class="kcp-cart-part__label"><?php echo esc_html( $label ); ?></h4>
												<span class="kcp-cart-part__price"><?php echo esc_html( ShopPresenter::format_dutch_price( $price ) ); ?></span>
											</div>
											<?php if ( '' !== $description ) : ?>
												<p class="kcp-cart-part__description"><?php echo esc_html( $description ); ?></p>
											<?php endif; ?>
											<?php if ( '' !== $part_edit ) : ?>
												<a href="<?php echo esc_url( $part_edit ); ?>" class="kcp-cart-part__wijzig">
													<?php esc_html_e( 'wijzigen', 'kitchen-configurator-pro' ); ?>
												</a>
											<?php endif; ?>
										</div>

										<div class="kcp-cart-part__actions">
											<?php if ( '' !== $duplicate_url ) : ?>
												<a href="<?php echo esc_url( $duplicate_url ); ?>" class="kcp-cart-part__duplicate">
													<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
													<?php esc_html_e( 'dupliceren', 'kitchen-configurator-pro' ); ?>
												</a>
											<?php endif; ?>
											<?php if ( '' !== $part_remove ) : ?>
												<a
													href="<?php echo esc_url( $part_remove ); ?>"
													class="kcp-cart-part__remove"
													aria-label="<?php esc_attr_e( 'Verwijder artikel', 'kitchen-configurator-pro' ); ?>"
												>×</a>
											<?php endif; ?>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $plinth_lines ) ) : ?>
			<section class="kcp-cart-extras">
				<h3 class="kcp-cart-extras__title"><?php esc_html_e( 'meerprijs plint(en)', 'kitchen-configurator-pro' ); ?></h3>
				<div class="kcp-cart-extras__card">
					<?php foreach ( $plinth_lines as $plinth ) : ?>
						<div class="kcp-cart-extras__row">
							<div class="kcp-cart-extras__copy">
								<strong class="kcp-cart-extras__name"><?php echo esc_html( (string) ( $plinth['label'] ?? '' ) ); ?></strong>
								<?php if ( '' !== (string) ( $plinth['unit_label'] ?? '' ) ) : ?>
									<span class="kcp-cart-extras__unit"><?php echo esc_html( (string) $plinth['unit_label'] ); ?></span>
								<?php endif; ?>
							</div>
							<span class="kcp-cart-extras__subtotal">
								<?php
								echo esc_html(
									ShopPresenter::format_dutch_price( (float) ( $plinth['subtotal'] ?? 0 ) )
									. ' ' . __( 'subtotaal', 'kitchen-configurator-pro' )
								);
								?>
							</span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="kcp-cart-delivery">
			<label class="kcp-cart-delivery__label" for="kcp-delivery-week"><?php esc_html_e( 'selecteer een leverweek', 'kitchen-configurator-pro' ); ?></label>
			<select id="kcp-delivery-week" class="kcp-cart-delivery__select" name="kcp_delivery_week">
				<option value=""><?php esc_html_e( 'selecteer een leverweek', 'kitchen-configurator-pro' ); ?></option>
				<?php foreach ( $delivery_weeks as $week ) : ?>
					<option value="<?php echo esc_attr( (string) ( $week['id'] ?? '' ) ); ?>">
						<?php echo esc_html( (string) ( $week['label'] ?? '' ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</section>

		<section class="kcp-cart-extra-security">
			<h3 class="kcp-cart-extra-security__title"><?php esc_html_e( 'extra zekerheid', 'kitchen-configurator-pro' ); ?></h3>
			<div class="kcp-cart-design-check">
				<div class="kcp-cart-design-check__inner">
					<span class="kcp-cart-design-check__badge"><?php esc_html_e( 'Extra zekerheid', 'kitchen-configurator-pro' ); ?></span>
					<h4 class="kcp-cart-design-check__title"><?php esc_html_e( 'laat jouw ontwerp controleren', 'kitchen-configurator-pro' ); ?></h4>
					<p class="kcp-cart-design-check__text">
						<?php esc_html_e( 'Onze experts controleren jouw keukenontwerp en geven advies voordat je bestelt.', 'kitchen-configurator-pro' ); ?>
					</p>
					<div class="kcp-cart-design-check__choices">
						<label class="kcp-cart-design-check__choice">
							<input type="radio" name="kcp_design_check" value="yes">
							<span><?php esc_html_e( 'ja dat wil ik', 'kitchen-configurator-pro' ); ?></span>
						</label>
						<label class="kcp-cart-design-check__choice">
							<input type="radio" name="kcp_design_check" value="no" checked>
							<span><?php esc_html_e( 'nee dat wil ik niet', 'kitchen-configurator-pro' ); ?></span>
						</label>
					</div>
					<span class="kcp-cart-design-check__price">+ 75,-</span>
				</div>
			</div>
		</section>

		<?php do_action( 'woocommerce_cart_contents' ); ?>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<div class="kcp-cart__total-wrap">
		<span class="kcp-cart__total-label"><?php esc_html_e( 'totaalbedrag', 'kitchen-configurator-pro' ); ?></span>
		<strong class="kcp-cart__total-value">€ <?php echo esc_html( $cart_total ); ?></strong>
	</div>

	<footer class="kcp-cart__footer">
		<a href="<?php echo esc_url( $shop_url ); ?>" class="kcp-cart__save kcp-cart__save--footer">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 2l2.4 4.8L20 7.5l-3.5 3.4.8 5.1L12 13.8 6.7 16l.8-5.1L4 7.5l5.6-.7z"/></svg>
			<?php esc_html_e( 'winkelwagen opslaan', 'kitchen-configurator-pro' ); ?>
		</a>
		<div class="kcp-cart__footer-actions">
			<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="kcp-cart__action kcp-cart__action--secondary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
				<?php esc_html_e( 'plan een afspraak', 'kitchen-configurator-pro' ); ?>
			</a>
			<a href="<?php echo esc_url( $checkout_url ); ?>" class="kcp-cart__action kcp-cart__action--primary">
				<?php esc_html_e( 'bestellen', 'kitchen-configurator-pro' ); ?>
			</a>
		</div>
	</footer>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
