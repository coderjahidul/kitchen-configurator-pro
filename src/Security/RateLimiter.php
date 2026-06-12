<?php
/**
 * Transient-based rate limiter.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

/**
 * Throttles expensive or abuse-prone endpoints.
 */
final class RateLimiter {

	/**
	 * Transient key prefix.
	 */
	private const PREFIX = 'kcp_rate_';

	/**
	 * Check whether an action is allowed and increment the counter.
	 *
	 * @param string $key             Unique rate limit key.
	 * @param int    $max_attempts    Maximum attempts per window.
	 * @param int    $window_seconds  Window length in seconds.
	 * @return bool True when allowed, false when throttled.
	 */
	public function attempt( string $key, int $max_attempts, int $window_seconds ): bool {
		$transient_key = self::PREFIX . md5( $key );
		$record        = get_transient( $transient_key );

		if ( ! is_array( $record ) ) {
			set_transient(
				$transient_key,
				array(
					'count'      => 1,
					'expires_at' => time() + $window_seconds,
				),
				$window_seconds
			);

			return true;
		}

		$count = (int) ( $record['count'] ?? 0 );

		if ( $count >= $max_attempts ) {
			return false;
		}

		$record['count'] = $count + 1;
		$remaining       = max( 1, (int) ( $record['expires_at'] ?? time() ) - time() );

		set_transient( $transient_key, $record, $remaining );

		return true;
	}

	/**
	 * Build a rate limit key from endpoint and client identity.
	 *
	 * @param string $endpoint Endpoint name.
	 * @return string
	 */
	public static function client_key( string $endpoint ): string {
		$user_id = get_current_user_id();
		$session = RestAuth::resolve_session_id();

		if ( $user_id > 0 ) {
			return $endpoint . ':user:' . $user_id;
		}

		if ( '' !== $session ) {
			return $endpoint . ':session:' . $session;
		}

		$ip = ! empty( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) )
			: 'unknown';

		return $endpoint . ':ip:' . $ip;
	}
}
