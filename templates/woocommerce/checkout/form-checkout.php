<?php
/**
 * KKF-style checkout form.
 *
 * @package KitchenConfiguratorPro
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$billing_fields = $checkout->get_checkout_fields( 'billing' );
$order_fields   = $checkout->get_checkout_fields( 'order' );
$default_country = function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_base_country() : 'NL';
$billing_country = $checkout->get_value( 'billing_country' );
$billing_country = '' !== $billing_country ? $billing_country : $default_country;

unset( $billing_fields['billing_country'] );

$render_field = static function ( string $key, array &$fields, string $label = '' ) use ( $checkout ): void {
	if ( empty( $fields[ $key ] ) ) {
		return;
	}

	$field = $fields[ $key ];

	if ( '' !== $label ) {
		$field['label'] = $label;
	}

	$field['class']       = array( 'form-row-wide', 'kcp-checkout-field' );
	$field['label_class'] = array( 'kcp-checkout-field__label' );
	$field['input_class'] = array( 'kcp-checkout-field__input' );

	woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
	unset( $fields[ $key ] );
};
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout kcp-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
	<input type="hidden" name="billing_country" id="billing_country" value="<?php echo esc_attr( $billing_country ); ?>">
	<input type="hidden" name="shipping_country" id="shipping_country" value="<?php echo esc_attr( $billing_country ); ?>">
	<h1 class="kcp-checkout__title"><?php esc_html_e( 'my details', 'kitchen-configurator-pro' ); ?></h1>

	<?php if ( $checkout->get_checkout_fields() ) : ?>
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="kcp-checkout__details" id="customer_details">
			<section class="kcp-checkout__panel kcp-checkout__panel--personal">
				<h2 class="kcp-checkout__section-title"><?php esc_html_e( 'Personal data', 'kitchen-configurator-pro' ); ?></h2>
				<?php
				$render_field( 'billing_first_name', $billing_fields, __( 'First name', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_last_name', $billing_fields, __( 'Surname', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_email', $billing_fields, __( 'Email address', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_phone', $billing_fields, __( 'Phone number', 'kitchen-configurator-pro' ) );
				?>
			</section>

			<section class="kcp-checkout__panel kcp-checkout__panel--billing">
				<h2 class="kcp-checkout__section-title"><?php esc_html_e( 'Billing address', 'kitchen-configurator-pro' ); ?></h2>
				<?php
				$render_field( 'billing_postcode', $billing_fields, __( 'Postal code', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_address_2', $billing_fields, __( 'House number', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_address_1', $billing_fields, __( 'Street name', 'kitchen-configurator-pro' ) );
				$render_field( 'billing_city', $billing_fields, __( 'Place of residence', 'kitchen-configurator-pro' ) );
				?>

				<label class="kcp-checkout-same-address">
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" value="1" disabled checked />
					<span><?php esc_html_e( 'Delivery address is the same as the billing address', 'kitchen-configurator-pro' ); ?></span>
				</label>
			</section>
		</div>

		<?php if ( ! empty( $billing_fields ) ) : ?>
			<div class="kcp-checkout__extra-fields">
				<?php foreach ( $billing_fields as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	<?php endif; ?>

	<?php if ( ! empty( $order_fields ) ) : ?>
		<section class="kcp-checkout__comments">
			<h2 class="kcp-checkout__section-title"><?php esc_html_e( 'Comments', 'kitchen-configurator-pro' ); ?></h2>
			<?php foreach ( $order_fields as $key => $field ) : ?>
				<?php
				$field['label']       = false;
				$field['placeholder'] = __( 'Any comments...', 'kitchen-configurator-pro' );
				$field['class']       = array( 'form-row-wide', 'kcp-checkout-field', 'kcp-checkout-field--comments' );
				$field['input_class'] = array( 'kcp-checkout-field__input' );
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				?>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<section class="kcp-checkout__delivery" data-kcp-checkout-details>
		<h2 class="kcp-checkout__section-title"><?php esc_html_e( 'Method of delivery', 'kitchen-configurator-pro' ); ?></h2>
		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php woocommerce_order_review(); ?>
		</div>
	</section>

	<section class="kcp-checkout__terms" data-kcp-checkout-details>
		<h2 class="kcp-checkout__section-title"><?php esc_html_e( 'General Terms and Conditions', 'kitchen-configurator-pro' ); ?></h2>
		<?php wc_get_template( 'checkout/terms.php' ); ?>
	</section>

	<div class="kcp-checkout__actions" data-kcp-checkout-details>
		<a class="kcp-checkout__back" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'go back', 'kitchen-configurator-pro' ); ?></a>
		<button type="button" class="button alt kcp-checkout__submit" data-kcp-show-payment><?php esc_html_e( 'proceed to payment', 'kitchen-configurator-pro' ); ?></button>
	</div>

	<section class="kcp-checkout__payment-step" data-kcp-checkout-payment>
		<?php woocommerce_checkout_payment(); ?>
	</section>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
