<?php
/**
 * Security and audit event logger.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

/**
 * Logs critical security events and validation failures.
 */
final class SecurityLogger {

	/**
	 * Logger source identifier.
	 */
	private const LOG_SOURCE = 'kitchen-configurator-pro';

	/**
	 * Log levels used by WooCommerce logger.
	 */
	public const LEVEL_ERROR   = 'error';
	public const LEVEL_WARNING = 'warning';
	public const LEVEL_INFO    = 'info';

	/**
	 * Log a security-related event.
	 *
	 * @param string               $event   Event name.
	 * @param string               $message Human-readable message.
	 * @param array<string, mixed> $context Additional context.
	 * @param string               $level   Log level.
	 * @return void
	 */
	public static function log( string $event, string $message, array $context = array(), string $level = self::LEVEL_WARNING ): void {
		$payload = array_merge(
			array(
				'event'     => sanitize_key( $event ),
				'timestamp' => gmdate( 'c' ),
				'user_id'   => get_current_user_id(),
				'ip'        => self::client_ip(),
			),
			$context
		);

		$line = sprintf(
			'[%1$s] %2$s %3$s',
			$payload['event'],
			$message,
			wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}'
		);

		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();
			$logger->log( $level, $line, array( 'source' => self::LOG_SOURCE ) );
			return;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'KCP Security: ' . $line );
		}
	}

	/**
	 * Log REST authentication failure.
	 *
	 * @param string $reason Failure reason.
	 * @return void
	 */
	public static function auth_failed( string $reason ): void {
		self::log(
			'rest_auth_failed',
			'REST authentication failed.',
			array( 'reason' => sanitize_text_field( $reason ) ),
			self::LEVEL_WARNING
		);
	}

	/**
	 * Log validation failure.
	 *
	 * @param string               $context Validation context.
	 * @param array<int, string>   $errors  Error messages.
	 * @return void
	 */
	public static function validation_failed( string $context, array $errors ): void {
		self::log(
			'validation_failed',
			'Input validation failed.',
			array(
				'context' => sanitize_text_field( $context ),
				'errors'  => array_map( 'sanitize_text_field', $errors ),
			),
			self::LEVEL_INFO
		);
	}

	/**
	 * Log price integrity failure.
	 *
	 * @param string               $stage   Pipeline stage (cart, checkout, etc.).
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public static function price_integrity_failed( string $stage, array $context = array() ): void {
		self::log(
			'price_integrity_failed',
			'Price integrity check failed.',
			array_merge(
				array( 'stage' => sanitize_key( $stage ) ),
				$context
			),
			self::LEVEL_ERROR
		);
	}

	/**
	 * Log rate limit exceeded.
	 *
	 * @param string $endpoint Endpoint identifier.
	 * @return void
	 */
	public static function rate_limit_exceeded( string $endpoint ): void {
		self::log(
			'rate_limit_exceeded',
			'Rate limit exceeded.',
			array( 'endpoint' => sanitize_key( $endpoint ) ),
			self::LEVEL_WARNING
		);
	}

	/**
	 * Resolve client IP for logging (respects proxies when available).
	 *
	 * @return string
	 */
	private static function client_ip(): string {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}
}
