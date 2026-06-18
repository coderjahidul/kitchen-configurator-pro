<?php
/**
 * KKF-style checkout payment section.
 *
 * @package KitchenConfiguratorPro
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment kcp-checkout-payment">
	<h1 class="kcp-checkout-payment__title"><?php esc_html_e( 'mijn betaling', 'kitchen-configurator-pro' ); ?></h1>

	<div class="kcp-checkout-payment__layout">
		<div class="kcp-checkout-payment__main">
			<h2 class="kcp-checkout-payment__subtitle"><?php esc_html_e( 'betaalmogelijkheden', 'kitchen-configurator-pro' ); ?></h2>

			<?php if ( WC()->cart && WC()->cart->needs_payment() ) : ?>
				<ul class="wc_payment_methods payment_methods methods kcp-checkout-payment__methods">
					<?php
					if ( ! empty( $available_gateways ) ) {
						foreach ( $available_gateways as $gateway ) {
							wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
						}
					} else {
						echo '<li>';
						wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
						echo '</li>';
					}
					?>
				</ul>
			<?php endif; ?>
		</div>

		<aside class="kcp-checkout-payment__summary" aria-label="<?php esc_attr_e( 'Payment summary', 'kitchen-configurator-pro' ); ?>">
			<div class="kcp-payment-summary__row">
				<span><?php esc_html_e( 'Producten', 'kitchen-configurator-pro' ); ?></span>
				<strong><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_cart_subtotal() : wc_price( 0 ) ); ?></strong>
			</div>
			<div class="kcp-payment-summary__row">
				<span><?php esc_html_e( 'Verzendkosten:', 'kitchen-configurator-pro' ); ?></span>
				<strong><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_cart_shipping_total() : wc_price( 0 ) ); ?></strong>
			</div>
			<?php if ( WC()->cart && count( WC()->cart->get_fees() ) > 0 ) : ?>
				<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
					<div class="kcp-payment-summary__row">
						<span><?php echo esc_html( $fee->name ); ?></span>
						<strong><?php wc_cart_totals_fee_html( $fee ); ?></strong>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="kcp-payment-summary__row">
					<span><?php esc_html_e( 'Transactiekosten:', 'kitchen-configurator-pro' ); ?></span>
					<strong><?php echo wp_kses_post( wc_price( 0 ) ); ?></strong>
				</div>
			<?php endif; ?>
			<div class="kcp-payment-summary__row kcp-payment-summary__row--total">
				<span><?php esc_html_e( 'Totaal te betalen:', 'kitchen-configurator-pro' ); ?></span>
				<strong><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_total() : wc_price( 0 ) ); ?></strong>
			</div>
		</aside>
	</div>

	<div class="form-row place-order kcp-checkout-payment__place-order">
		<noscript>
			<?php
			printf(
				esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ),
				'<em>',
				'</em>'
			);
			?>
			<br><button type="submit" class="button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
		</noscript>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<div class="kcp-checkout__actions">
			<button type="button" class="kcp-checkout__back kcp-checkout__back--payment" data-kcp-show-details><?php esc_html_e( 'Ga terug naar mijn gegevens', 'kitchen-configurator-pro' ); ?></button>
			<?php
			echo apply_filters(
				'woocommerce_order_button_html',
				'<button type="submit" class="button alt kcp-checkout__submit' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr__( 'Bestelling afronden', 'kitchen-configurator-pro' ) . '" data-value="' . esc_attr__( 'Bestelling afronden', 'kitchen-configurator-pro' ) . '">' . esc_html__( 'Bestelling afronden', 'kitchen-configurator-pro' ) . '</button>'
			); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>

		<div class="kcp-checkout-payment__note">
			<h2><?php esc_html_e( '*betalen vanuit bouwdepot', 'kitchen-configurator-pro' ); ?></h2>
			<p><?php esc_html_e( 'je ontvangt van ons een factuur die je bij jouw hypotheekverstrekker kunt declareren', 'kitchen-configurator-pro' ); ?></p>
		</div>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
