<?php
/**
 * KKF-style thank you page.
 *
 * @package KitchenConfiguratorPro
 * @version 1.0.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order kcp-thankyou">

	<?php if ( $order ) : ?>

		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="kcp-thankyou__content kcp-thankyou__content--failed">
				<h1 class="kcp-thankyou__title">
					<span><?php esc_html_e( 'betaling', 'kitchen-configurator-pro' ); ?></span>
					<span><?php esc_html_e( 'mislukt', 'kitchen-configurator-pro' ); ?></span>
				</h1>
				<div class="kcp-thankyou__message">
					<p><?php esc_html_e( 'Helaas kan je bestelling niet worden verwerkt. Probeer het opnieuw.', 'kitchen-configurator-pro' ); ?></p>
				</div>
				<div class="kcp-thankyou__actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="kcp-thankyou__button">
						<?php esc_html_e( 'opnieuw betalen', 'kitchen-configurator-pro' ); ?>
					</a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="kcp-thankyou__button kcp-thankyou__button--secondary">
							<?php esc_html_e( 'mijn account', 'kitchen-configurator-pro' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		<?php else : ?>

			<div class="kcp-thankyou__content">
				<h1 class="kcp-thankyou__title">
					<span><?php esc_html_e( 'bestelling', 'kitchen-configurator-pro' ); ?></span>
					<span><?php esc_html_e( 'succesvol', 'kitchen-configurator-pro' ); ?></span>
					<span><?php esc_html_e( 'afgerond', 'kitchen-configurator-pro' ); ?></span>
				</h1>
				<div class="kcp-thankyou__message">
					<p><?php esc_html_e( 'Bedankt voor jouw bestelling, het aftellen kan beginnen!', 'kitchen-configurator-pro' ); ?></p>
					<p><?php esc_html_e( 'Je ontvangt van ons een bevestiging per mail met alle informatie die je nodig hebt.', 'kitchen-configurator-pro' ); ?></p>
				</div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

	<?php else : ?>

		<div class="kcp-thankyou__content">
			<h1 class="kcp-thankyou__title">
				<span><?php esc_html_e( 'bestelling', 'kitchen-configurator-pro' ); ?></span>
				<span><?php esc_html_e( 'succesvol', 'kitchen-configurator-pro' ); ?></span>
				<span><?php esc_html_e( 'afgerond', 'kitchen-configurator-pro' ); ?></span>
			</h1>
			<div class="kcp-thankyou__message">
				<p><?php esc_html_e( 'Bedankt voor jouw bestelling, het aftellen kan beginnen!', 'kitchen-configurator-pro' ); ?></p>
				<p><?php esc_html_e( 'Je ontvangt van ons een bevestiging per mail met alle informatie die je nodig hebt.', 'kitchen-configurator-pro' ); ?></p>
			</div>
		</div>

	<?php endif; ?>

</div>
