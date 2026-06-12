<?php
/**
 * Standardized REST API response helpers.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Api;

use WP_REST_Response;
use WP_Error;

/**
 * Builds consistent success and error REST responses.
 */
final class ApiResponse {

	/**
	 * Success response envelope.
	 *
	 * @param mixed                $data   Response payload.
	 * @param array<string, mixed> $meta   Optional metadata.
	 * @param int                  $status HTTP status code.
	 * @return WP_REST_Response
	 */
	public static function success( mixed $data, array $meta = array(), int $status = 200 ): WP_REST_Response {
		$body = array(
			'success' => true,
			'data'    => $data,
		);

		if ( ! empty( $meta ) ) {
			$body['meta'] = $meta;
		}

		return new WP_REST_Response( $body, $status );
	}

	/**
	 * Error response envelope.
	 *
	 * @param string               $code    Machine-readable error code.
	 * @param string               $message Human-readable message.
	 * @param int                  $status  HTTP status code.
	 * @param array<string, mixed> $details Additional error details.
	 * @return WP_REST_Response
	 */
	public static function error( string $code, string $message, int $status = 400, array $details = array() ): WP_REST_Response {
		$error = array(
			'code'    => $code,
			'message' => $message,
		);

		if ( ! empty( $details ) ) {
			$error['details'] = $details;
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $error,
			),
			$status
		);
	}

	/**
	 * Validation error from exception messages.
	 *
	 * @param array<int, string> $errors Validation messages.
	 * @return WP_REST_Response
	 */
	public static function validation_error( array $errors ): WP_REST_Response {
		return self::error(
			'kcp_validation_failed',
			__( 'Configuration validation failed.', 'kitchen-configurator-pro' ),
			422,
			array( 'errors' => $errors )
		);
	}

	/**
	 * Not found error.
	 *
	 * @param string $resource Resource name.
	 * @return WP_REST_Response
	 */
	public static function not_found( string $resource = 'Resource' ): WP_REST_Response {
		return self::error(
			'kcp_not_found',
			sprintf(
				/* translators: %s: resource name */
				__( '%s not found.', 'kitchen-configurator-pro' ),
				$resource
			),
			404
		);
	}

	/**
	 * Forbidden error.
	 *
	 * @return WP_REST_Response
	 */
	public static function forbidden(): WP_REST_Response {
		return self::error(
			'kcp_forbidden',
			__( 'You do not have permission to access this resource.', 'kitchen-configurator-pro' ),
			403
		);
	}

	/**
	 * Unauthorized error.
	 *
	 * @return WP_REST_Response
	 */
	public static function unauthorized(): WP_REST_Response {
		return self::error(
			'kcp_unauthorized',
			__( 'Authentication required.', 'kitchen-configurator-pro' ),
			401
		);
	}
}
