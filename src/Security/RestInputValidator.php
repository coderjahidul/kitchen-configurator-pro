<?php
/**
 * REST API input validation callbacks.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

/**
 * Shared validate_callback helpers for REST route args.
 */
final class RestInputValidator {

	/**
	 * Validate positive integer ID.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_positive_int( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request );

		if ( ! is_numeric( $value ) || (int) $value < 0 ) {
			return new \WP_Error(
				'kcp_invalid_param',
				sprintf(
					/* translators: %s: parameter name */
					__( 'Invalid value for parameter "%s".', 'kitchen-configurator-pro' ),
					$param
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate required positive layout ID.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_layout_id( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request, $param );

		if ( ! is_numeric( $value ) || (int) $value <= 0 ) {
			return new \WP_Error(
				'kcp_invalid_layout',
				__( 'A valid layout_id is required.', 'kitchen-configurator-pro' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate cabinets array structure.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_cabinets( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request, $param );

		if ( ! is_array( $value ) ) {
			return new \WP_Error(
				'kcp_invalid_cabinets',
				__( 'Cabinets must be an array.', 'kitchen-configurator-pro' ),
				array( 'status' => 400 )
			);
		}

		if ( count( $value ) > ConfigurationSchemaValidator::MAX_CABINETS ) {
			return new \WP_Error(
				'kcp_too_many_cabinets',
				sprintf(
					/* translators: %d: max cabinets */
					__( 'A maximum of %d cabinets is allowed.', 'kitchen-configurator-pro' ),
					ConfigurationSchemaValidator::MAX_CABINETS
				),
				array( 'status' => 400 )
			);
		}

		foreach ( $value as $index => $cabinet ) {
			if ( ! is_array( $cabinet ) ) {
				return new \WP_Error(
					'kcp_invalid_cabinet',
					sprintf(
						/* translators: %d: cabinet index */
						__( 'Cabinet at index %d must be an object.', 'kitchen-configurator-pro' ),
						(int) $index
					),
					array( 'status' => 400 )
				);
			}
		}

		return true;
	}

	/**
	 * Validate global options array.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_global_options( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request, $param );

		if ( null === $value ) {
			return true;
		}

		if ( ! is_array( $value ) ) {
			return new \WP_Error(
				'kcp_invalid_global_options',
				__( 'Global options must be an array.', 'kitchen-configurator-pro' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate configuration UUID.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_uuid( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request );

		if ( ! is_string( $value ) || ! RestAuth::is_valid_uuid( $value ) ) {
			return new \WP_Error(
				'kcp_invalid_uuid',
				sprintf(
					/* translators: %s: parameter name */
					__( 'Invalid UUID for parameter "%s".', 'kitchen-configurator-pro' ),
					$param
				),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate pagination page number.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_page( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request, $param );

		if ( ! is_numeric( $value ) || (int) $value < 1 || (int) $value > 10000 ) {
			return new \WP_Error(
				'kcp_invalid_page',
				__( 'Invalid page number.', 'kitchen-configurator-pro' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Validate pagination per_page value.
	 *
	 * @param mixed             $value   Value.
	 * @param \WP_REST_Request  $request Request.
	 * @param string            $param   Parameter name.
	 * @return bool|\WP_Error
	 */
	public static function validate_per_page( mixed $value, \WP_REST_Request $request, string $param ): bool|\WP_Error {
		unset( $request, $param );

		if ( ! is_numeric( $value ) || (int) $value < 1 || (int) $value > 100 ) {
			return new \WP_Error(
				'kcp_invalid_per_page',
				__( 'per_page must be between 1 and 100.', 'kitchen-configurator-pro' ),
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Shared configuration body args for REST routes.
	 *
	 * @param bool $require_layout Whether layout_id is required.
	 * @return array<string, array<string, mixed>>
	 */
	public static function configuration_args( bool $require_layout = true ): array {
		$args = array(
			'schema_version' => array(
				'type'              => 'string',
				'default'           => '1.0',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'title'          => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'cabinets'       => array(
				'type'              => 'array',
				'required'          => $require_layout,
				'validate_callback' => array( self::class, 'validate_cabinets' ),
			),
			'global_options' => array(
				'type'              => 'array',
				'validate_callback' => array( self::class, 'validate_global_options' ),
			),
		);

		$args['layout_id'] = array(
			'type'              => 'integer',
			'required'          => $require_layout,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( self::class, 'validate_layout_id' ),
		);

		return $args;
	}
}
