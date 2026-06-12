<?php
/**
 * Configuration JSON structure validator.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

use KitchenConfiguratorPro\Domain\Exceptions\ValidationException;

/**
 * Validates and strips untrusted configuration payloads before processing.
 */
final class ConfigurationSchemaValidator {

	/**
	 * Supported schema versions.
	 *
	 * @var array<int, string>
	 */
	public const ALLOWED_SCHEMA_VERSIONS = array( '1.0' );

	/**
	 * Maximum cabinets per configuration.
	 */
	public const MAX_CABINETS = 50;

	/**
	 * Maximum accessories per cabinet or globally.
	 */
	public const MAX_ACCESSORIES = 30;

	/**
	 * Maximum title length.
	 */
	public const MAX_TITLE_LENGTH = 200;

	/**
	 * Maximum raw JSON payload size in bytes.
	 */
	public const MAX_PAYLOAD_BYTES = 524288;

	/**
	 * Fields that must never be accepted from clients.
	 *
	 * @var array<int, string>
	 */
	private const FORBIDDEN_ROOT_KEYS = array(
		'total_price',
		'price_hash',
		'pricing_snapshot_json',
		'pricing',
		'status',
		'uuid',
		'user_id',
		'session_id',
		'wc_order_id',
		'wc_cart_item_key',
		'created_at',
		'updated_at',
	);

	/**
	 * Validate payload structure and strip forbidden keys.
	 *
	 * @param array<string, mixed> $data Raw request data.
	 * @return array<string, mixed> Sanitized structure.
	 *
	 * @throws ValidationException When structure is invalid.
	 */
	public function validate_and_strip( array $data ): array {
		$errors = $this->collect_errors( $data );

		if ( ! empty( $errors ) ) {
			SecurityLogger::validation_failed( 'configuration_payload', $errors );
			throw new ValidationException( $errors );
		}

		return $this->strip_forbidden_keys( $data );
	}

	/**
	 * Validate raw JSON string size and decode safety.
	 *
	 * @param string $json Raw JSON.
	 * @return array<string, mixed>
	 *
	 * @throws ValidationException When JSON is invalid or too large.
	 */
	public function decode_json( string $json ): array {
		if ( strlen( $json ) > self::MAX_PAYLOAD_BYTES ) {
			throw new ValidationException(
				array( __( 'Configuration payload exceeds maximum allowed size.', 'kitchen-configurator-pro' ) )
			);
		}

		$decoded = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			throw new ValidationException(
				array( __( 'Invalid configuration JSON.', 'kitchen-configurator-pro' ) )
			);
		}

		return $this->validate_and_strip( $decoded );
	}

	/**
	 * Collect validation errors without throwing.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @return array<int, string>
	 */
	public function collect_errors( array $data ): array {
		$errors = array();

		foreach ( self::FORBIDDEN_ROOT_KEYS as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$errors[] = sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" is not allowed in configuration input.', 'kitchen-configurator-pro' ),
					$key
				);
			}
		}

		$encoded_size = strlen( wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '' );

		if ( $encoded_size > self::MAX_PAYLOAD_BYTES ) {
			$errors[] = __( 'Configuration payload exceeds maximum allowed size.', 'kitchen-configurator-pro' );
		}

		$schema = (string) ( $data['schema_version'] ?? '1.0' );

		if ( ! in_array( $schema, self::ALLOWED_SCHEMA_VERSIONS, true ) ) {
			$errors[] = __( 'Unsupported configuration schema version.', 'kitchen-configurator-pro' );
		}

		$title = (string) ( $data['title'] ?? '' );

		if ( strlen( $title ) > self::MAX_TITLE_LENGTH ) {
			$errors[] = sprintf(
				/* translators: %d: max characters */
				__( 'Title must not exceed %d characters.', 'kitchen-configurator-pro' ),
				self::MAX_TITLE_LENGTH
			);
		}

		$cabinets = $data['cabinets'] ?? null;

		if ( null !== $cabinets && ! is_array( $cabinets ) ) {
			$errors[] = __( 'Cabinets must be an array.', 'kitchen-configurator-pro' );
		} elseif ( is_array( $cabinets ) && count( $cabinets ) > self::MAX_CABINETS ) {
			$errors[] = sprintf(
				/* translators: %d: max cabinets */
				__( 'A maximum of %d cabinets is allowed.', 'kitchen-configurator-pro' ),
				self::MAX_CABINETS
			);
		}

		if ( is_array( $cabinets ) ) {
			foreach ( $cabinets as $index => $cabinet ) {
				if ( ! is_array( $cabinet ) ) {
					$errors[] = sprintf(
						/* translators: %d: cabinet index */
						__( 'Cabinet at index %d must be an object.', 'kitchen-configurator-pro' ),
						(int) $index
					);
					continue;
				}

				$accessories = $cabinet['accessories'] ?? array();

				if ( is_array( $accessories ) && count( $accessories ) > self::MAX_ACCESSORIES ) {
					$errors[] = sprintf(
						/* translators: %d: cabinet index */
						__( 'Too many accessories on cabinet %d.', 'kitchen-configurator-pro' ),
						(int) $index
					);
				}
			}
		}

		$global = $data['global_options'] ?? null;

		if ( null !== $global && ! is_array( $global ) ) {
			$errors[] = __( 'Global options must be an array.', 'kitchen-configurator-pro' );
		} elseif ( is_array( $global ) ) {
			$global_accessories = $global['accessories'] ?? array();

			if ( is_array( $global_accessories ) && count( $global_accessories ) > self::MAX_ACCESSORIES ) {
				$errors[] = __( 'Too many global accessories.', 'kitchen-configurator-pro' );
			}
		}

		return $errors;
	}

	/**
	 * Remove forbidden keys from payload.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @return array<string, mixed>
	 */
	private function strip_forbidden_keys( array $data ): array {
		foreach ( self::FORBIDDEN_ROOT_KEYS as $key ) {
			unset( $data[ $key ] );
		}

		return $data;
	}
}
