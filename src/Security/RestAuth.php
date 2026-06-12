<?php
/**
 * REST API authentication and authorization helpers.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

use WP_REST_Request;
use WP_Error;

/**
 * Handles REST permission checks for KCP endpoints.
 */
final class RestAuth {

	/**
	 * Session header name for guest clients.
	 */
	public const SESSION_HEADER = 'X-KCP-Session-Id';

	/**
	 * Cookie name for guest session.
	 */
	public const SESSION_COOKIE = 'kcp_session_id';

	/**
	 * Allow public read-only access (catalog).
	 *
	 * @return bool
	 */
	public static function allow_public_read(): bool {
		return true;
	}

	/**
	 * Require REST nonce for mutating requests.
	 *
	 * Logged-in users must send X-WP-Nonce. Guests must send X-KCP-Session-Id.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool|WP_Error
	 */
	public static function require_mutation_auth( WP_REST_Request $request ): bool|WP_Error {
		if ( current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			return true;
		}

		if ( is_user_logged_in() ) {
			if ( self::verify_wp_rest_nonce( $request ) ) {
				return true;
			}

			SecurityLogger::auth_failed( 'invalid_wp_rest_nonce' );

			return new WP_Error(
				'kcp_unauthorized',
				__( 'Authentication required.', 'kitchen-configurator-pro' ),
				array( 'status' => 401 )
			);
		}

		if ( self::has_valid_guest_session( $request ) ) {
			return true;
		}

		SecurityLogger::auth_failed( 'missing_guest_session' );

		return new WP_Error(
			'kcp_unauthorized',
			__( 'A valid guest session is required.', 'kitchen-configurator-pro' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Require authenticated user or guest session for read access to private resources.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool|WP_Error
	 */
	public static function require_authenticated( WP_REST_Request $request ): bool|WP_Error {
		if ( current_user_can( CapabilityManager::CAP_MANAGE ) ) {
			return true;
		}

		if ( is_user_logged_in() ) {
			if ( self::verify_wp_rest_nonce( $request ) ) {
				return true;
			}

			SecurityLogger::auth_failed( 'invalid_wp_rest_nonce_read' );

			return new WP_Error(
				'kcp_unauthorized',
				__( 'Authentication required.', 'kitchen-configurator-pro' ),
				array( 'status' => 401 )
			);
		}

		if ( self::has_valid_guest_session( $request ) ) {
			return true;
		}

		SecurityLogger::auth_failed( 'missing_guest_session_read' );

		return new WP_Error(
			'kcp_unauthorized',
			__( 'A valid guest session is required.', 'kitchen-configurator-pro' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Verify WordPress REST nonce from header or query param.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public static function verify_wp_rest_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( empty( $nonce ) || ! is_string( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Check guest session header or cookie.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return bool
	 */
	public static function has_valid_guest_session( WP_REST_Request $request ): bool {
		$session_id = self::resolve_session_id( $request );

		return self::is_valid_session_id( $session_id );
	}

	/**
	 * Resolve session ID from request header or cookie.
	 *
	 * @param WP_REST_Request|null $request REST request.
	 * @return string
	 */
	public static function resolve_session_id( ?WP_REST_Request $request = null ): string {
		if ( $request instanceof WP_REST_Request ) {
			$header = $request->get_header( self::SESSION_HEADER );

			if ( is_string( $header ) && self::is_valid_session_id( $header ) ) {
				return sanitize_text_field( $header );
			}
		}

		if ( isset( $_COOKIE[ self::SESSION_COOKIE ] ) ) {
			$cookie = sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::SESSION_COOKIE ] ) );

			if ( self::is_valid_session_id( $cookie ) ) {
				return $cookie;
			}
		}

		return '';
	}

	/**
	 * Generate a new guest session ID.
	 *
	 * @return string
	 */
	public static function generate_session_id(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return wp_generate_password( 32, false, false );
	}

	/**
	 * Validate session ID format.
	 *
	 * @param string $session_id Session ID.
	 * @return bool
	 */
	public static function is_valid_session_id( string $session_id ): bool {
		if ( '' === $session_id ) {
			return false;
		}

		return (bool) preg_match( '/^[a-zA-Z0-9\-_]{16,64}$/', $session_id );
	}

	/**
	 * Validate UUID v4 format.
	 *
	 * @param string $uuid UUID string.
	 * @return bool
	 */
	public static function is_valid_uuid( string $uuid ): bool {
		return (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
			$uuid
		);
	}

	/**
	 * Get current user ID or null for guests.
	 *
	 * @return int|null
	 */
	public static function current_user_id(): ?int {
		$user_id = get_current_user_id();

		return $user_id > 0 ? $user_id : null;
	}
}
