<?php
/**
 * Configuration management service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Domain\Entities\Configuration;
use KitchenConfiguratorPro\Domain\Exceptions\NotFoundException;
use KitchenConfiguratorPro\Domain\Exceptions\ValidationException;
use KitchenConfiguratorPro\Repositories\ConfigurationRepository;
use KitchenConfiguratorPro\Security\ConfigurationSchemaValidator;
use KitchenConfiguratorPro\Security\RestAuth;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Creates, updates, and retrieves customer configurations with server-side pricing.
 */
final class ConfigurationService {

	/**
	 * Allowed configuration statuses.
	 *
	 * @var array<int, string>
	 */
	private const ALLOWED_STATUSES = array( 'draft', 'saved', 'quoted', 'ordered', 'archived' );

	/**
	 * @param ConfigurationRepository       $configurations Configuration repository.
	 * @param PricingEngine                 $pricing        Pricing engine.
	 * @param ConfigurationSchemaValidator  $schema         Payload schema validator.
	 * @param ConfigurationAuditService     $audit          Audit trail service.
	 */
	public function __construct(
		private readonly ConfigurationRepository $configurations,
		private readonly PricingEngine $pricing,
		private readonly ConfigurationSchemaValidator $schema,
		private readonly ConfigurationAuditService $audit
	) {
	}

	/**
	 * Create a new configuration with server-calculated pricing.
	 *
	 * @param array<string, mixed> $data       Request payload.
	 * @param int|null             $user_id    Owner user ID.
	 * @param string|null          $session_id Guest session ID.
	 * @return Configuration
	 *
	 * @throws ValidationException When validation fails.
	 */
	public function create( array $data, ?int $user_id = null, ?string $session_id = null ): Configuration {
		$input    = $this->sanitize_input( $data );
		$snapshot = $this->pricing->calculate( $input );

		$uuid = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : wp_generate_password( 36, false, false );

		$config_data = array(
			'uuid'                  => $uuid,
			'layout_id'             => $input->layout_id,
			'user_id'               => $user_id,
			'session_id'            => null !== $user_id ? null : $session_id,
			'title'                 => $input->title ?: __( 'Untitled Configuration', 'kitchen-configurator-pro' ),
			'configuration_json'    => wp_json_encode( $input->to_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}',
			'pricing_snapshot_json' => $snapshot->to_json(),
			'total_price'           => $snapshot->total->amount,
			'price_hash'            => $snapshot->price_hash->to_string(),
			'status'                => 'saved',
		);

		$entity = $this->configurations->create( $config_data );

		if ( null === $entity ) {
			throw new \RuntimeException( __( 'Failed to save configuration.', 'kitchen-configurator-pro' ) );
		}

		$this->audit->record(
			$entity->id,
			'created',
			$config_data['configuration_json'],
			$config_data['pricing_snapshot_json']
		);

		return $entity;
	}

	/**
	 * Update an existing configuration and recalculate pricing.
	 *
	 * @param string               $uuid       Configuration UUID.
	 * @param array<string, mixed> $data       Request payload.
	 * @param int|null             $user_id    Requesting user ID.
	 * @param string|null          $session_id Guest session ID.
	 * @return Configuration
	 *
	 * @throws NotFoundException When configuration is not found or inaccessible.
	 * @throws ValidationException When validation fails.
	 */
	public function update( string $uuid, array $data, ?int $user_id = null, ?string $session_id = null ): Configuration {
		$existing = $this->find_accessible( $uuid, $user_id, $session_id );

		if ( null === $existing ) {
			throw new NotFoundException( __( 'Configuration not found.', 'kitchen-configurator-pro' ) );
		}

		if ( in_array( $existing->status, array( 'ordered', 'archived' ), true ) ) {
			throw new ValidationException(
				array( __( 'This configuration can no longer be edited.', 'kitchen-configurator-pro' ) )
			);
		}

		$input    = $this->sanitize_input( $data, $existing );
		$snapshot = $this->pricing->calculate( $input );

		$updated = $this->configurations->update(
			$existing->id,
			array(
				'layout_id'             => $input->layout_id,
				'title'                 => $input->title ?: $existing->title,
				'configuration_json'    => wp_json_encode( $input->to_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}',
				'pricing_snapshot_json' => $snapshot->to_json(),
				'total_price'           => $snapshot->total->amount,
				'price_hash'            => $snapshot->price_hash->to_string(),
				'status'                => 'saved',
			)
		);

		if ( null === $updated ) {
			throw new \RuntimeException( __( 'Failed to update configuration.', 'kitchen-configurator-pro' ) );
		}

		$this->audit->record(
			$updated->id,
			'updated',
			wp_json_encode( $input->to_array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ?: '{}',
			$snapshot->to_json()
		);

		return $updated;
	}

	/**
	 * Find configuration by UUID with ownership check.
	 *
	 * @param string      $uuid       Configuration UUID.
	 * @param int|null    $user_id    Requesting user ID.
	 * @param string|null $session_id Guest session ID.
	 * @return Configuration|null
	 */
	public function find_accessible( string $uuid, ?int $user_id = null, ?string $session_id = null ): ?Configuration {
		if ( ! RestAuth::is_valid_uuid( $uuid ) ) {
			return null;
		}

		$config = $this->configurations->find_by_uuid( $uuid );

		if ( null === $config ) {
			return null;
		}

		if ( current_user_can( 'manage_kcp' ) ) {
			return $config;
		}

		if ( null !== $user_id && $user_id > 0 && $config->user_id === $user_id ) {
			return $config;
		}

		if ( null !== $session_id && '' !== $session_id ) {
			$row_session = $this->get_session_id_for_config( $config->id );

			if ( null === $config->user_id && $row_session === $session_id ) {
				return $config;
			}
		}

		return null;
	}

	/**
	 * List configurations for current owner.
	 *
	 * @param int|null    $user_id    User ID.
	 * @param string|null $session_id Session ID.
	 * @param int         $page       Page number (1-based).
	 * @param int         $per_page   Items per page.
	 * @return array{items: array<int, Configuration>, total: int}
	 */
	public function list_for_owner( ?int $user_id, ?string $session_id, int $page = 1, int $per_page = 20 ): array {
		$page     = max( 1, $page );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		if ( null !== $user_id && $user_id > 0 ) {
			$items = $this->configurations->find_by_user( $user_id, $per_page, $offset );
			$total = $this->configurations->count_for_owner( $user_id );
		} elseif ( null !== $session_id && '' !== $session_id ) {
			$items = $this->configurations->find_by_session( $session_id, $per_page, $offset );
			$total = $this->configurations->count_for_owner( null, $session_id );
		} else {
			return array(
				'items' => array(),
				'total' => 0,
			);
		}

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Delete a configuration.
	 *
	 * @param string      $uuid       Configuration UUID.
	 * @param int|null    $user_id    Requesting user ID.
	 * @param string|null $session_id Guest session ID.
	 * @return bool
	 *
	 * @throws NotFoundException When not found or inaccessible.
	 */
	public function delete( string $uuid, ?int $user_id = null, ?string $session_id = null ): bool {
		$config = $this->find_accessible( $uuid, $user_id, $session_id );

		if ( null === $config ) {
			throw new NotFoundException( __( 'Configuration not found.', 'kitchen-configurator-pro' ) );
		}

		if ( 'ordered' === $config->status ) {
			throw new ValidationException(
				array( __( 'Ordered configurations cannot be deleted.', 'kitchen-configurator-pro' ) )
			);
		}

		$deleted = $this->configurations->delete( $config->id );

		if ( $deleted ) {
			$this->audit->record(
				$config->id,
				'deleted',
				$config->configuration_json,
				$config->pricing_snapshot_json
			);
		}

		return $deleted;
	}

	/**
	 * Recalculate pricing and lock configuration for cart/checkout.
	 *
	 * @param string      $uuid       Configuration UUID.
	 * @param int|null    $user_id    Requesting user ID.
	 * @param string|null $session_id Guest session ID.
	 * @return Configuration
	 *
	 * @throws NotFoundException When configuration is not found.
	 * @throws ValidationException When configuration cannot be purchased.
	 */
	public function prepare_for_cart( string $uuid, ?int $user_id = null, ?string $session_id = null ): Configuration {
		$existing = $this->find_accessible( $uuid, $user_id, $session_id );

		if ( null === $existing ) {
			throw new NotFoundException( __( 'Configuration not found.', 'kitchen-configurator-pro' ) );
		}

		if ( in_array( $existing->status, array( 'ordered', 'archived' ), true ) ) {
			throw new ValidationException(
				array( __( 'This configuration has already been ordered and cannot be added to cart again.', 'kitchen-configurator-pro' ) )
			);
		}

		$decoded = json_decode( $existing->configuration_json, true );
		$input   = ConfigurationInput::from_array( is_array( $decoded ) ? $decoded : array() );
		$snapshot = $this->pricing->calculate( $input );

		$updated = $this->configurations->update(
			$existing->id,
			array(
				'pricing_snapshot_json' => $snapshot->to_json(),
				'total_price'           => $snapshot->total->amount,
				'price_hash'            => $snapshot->price_hash->to_string(),
				'status'                => 'saved',
			)
		);

		if ( null === $updated ) {
			throw new \RuntimeException( __( 'Failed to prepare configuration for cart.', 'kitchen-configurator-pro' ) );
		}

		$this->audit->record(
			$updated->id,
			'cart_prepared',
			$existing->configuration_json,
			$snapshot->to_json()
		);

		return $updated;
	}

	/**
	 * Attach WooCommerce cart item key to configuration.
	 *
	 * @param string $uuid          Configuration UUID.
	 * @param string $cart_item_key Cart item key.
	 * @return void
	 */
	public function attach_cart_item( string $uuid, string $cart_item_key ): void {
		$config = $this->configurations->find_by_uuid( $uuid );

		if ( null === $config ) {
			return;
		}

		$this->configurations->update(
			$config->id,
			array(
				'wc_cart_item_key' => $cart_item_key,
			)
		);
	}

	/**
	 * Mark configuration as ordered.
	 *
	 * @param string $uuid     Configuration UUID.
	 * @param int    $order_id WooCommerce order ID.
	 * @return void
	 */
	public function mark_ordered( string $uuid, int $order_id ): void {
		$config = $this->configurations->find_by_uuid( $uuid );

		if ( null === $config ) {
			return;
		}

		$this->configurations->update(
			$config->id,
			array(
				'status'      => 'ordered',
				'wc_order_id' => $order_id,
			)
		);

		$this->audit->record(
			$config->id,
			'ordered',
			$config->configuration_json,
			$config->pricing_snapshot_json
		);
	}

	/**
	 * Get raw database row for a configuration UUID.
	 *
	 * @param string $uuid Configuration UUID.
	 * @return array<string, mixed>|null
	 */
	public function get_row_by_uuid( string $uuid ): ?array {
		return $this->configurations->find_row_by_uuid( $uuid );
	}

	/**
	 * Parse and sanitize raw request data into a configuration input DTO.
	 *
	 * @param array<string, mixed> $data Raw input.
	 * @return ConfigurationInput
	 */
	public function parse_input( array $data ): ConfigurationInput {
		return $this->sanitize_input( $data );
	}

	/**
	 * Format configuration for API response.
	 *
	 * @param Configuration $config Configuration entity.
	 * @return array<string, mixed>
	 */
	public function to_api_array( Configuration $config ): array {
		$configuration = json_decode( $config->configuration_json, true );
		$pricing       = json_decode( $config->pricing_snapshot_json, true );

		return array(
			'uuid'          => $config->uuid,
			'layout_id'     => $config->layout_id,
			'title'         => $config->title,
			'status'        => $config->status,
			'total_price'   => (float) $config->total_price,
			'configuration' => is_array( $configuration ) ? $configuration : array(),
			'pricing'       => is_array( $pricing ) ? $pricing : null,
			'created_at'    => $config->created_at,
			'updated_at'    => $config->updated_at,
		);
	}

	/**
	 * Sanitize and build configuration input DTO.
	 *
	 * @param array<string, mixed>  $data     Raw input.
	 * @param Configuration|null    $existing Existing configuration for partial updates.
	 * @return ConfigurationInput
	 */
	private function sanitize_input( array $data, ?Configuration $existing = null ): ConfigurationInput {
		$data = $this->schema->validate_and_strip( $data );

		$existing_config = array();

		if ( null !== $existing ) {
			$decoded = json_decode( $existing->configuration_json, true );
			$existing_config = is_array( $decoded ) ? $decoded : array();
		}

		$merged = array_merge(
			$existing_config,
			array(
				'schema_version' => sanitize_text_field( (string) Arr::get( $data, 'schema_version', Arr::get( $existing_config, 'schema_version', '1.0' ) ) ),
				'layout_id'      => (int) Arr::get( $data, 'layout_id', Arr::get( $existing_config, 'layout_id', 0 ) ),
				'title'          => sanitize_text_field( (string) Arr::get( $data, 'title', Arr::get( $existing_config, 'title', '' ) ) ),
				'cabinets'       => $this->sanitize_cabinets( Arr::get( $data, 'cabinets', Arr::get( $existing_config, 'cabinets', array() ) ) ),
				'global_options' => $this->sanitize_global_options( Arr::get( $data, 'global_options', Arr::get( $existing_config, 'global_options', array() ) ) ),
			)
		);

		return ConfigurationInput::from_array( $merged );
	}

	/**
	 * Sanitize cabinets array.
	 *
	 * @param mixed $cabinets Raw cabinets.
	 * @return array<int, array<string, mixed>>
	 */
	private function sanitize_cabinets( mixed $cabinets ): array {
		if ( ! is_array( $cabinets ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $cabinets as $cabinet ) {
			if ( ! is_array( $cabinet ) ) {
				continue;
			}

			$dimensions = Arr::get( $cabinet, 'dimensions', array() );
			$position   = Arr::get( $cabinet, 'position', array() );
			$accessories = Arr::get( $cabinet, 'accessories', array() );

			$sanitized[] = array(
				'cabinet_id'  => max( 0, (int) Arr::get( $cabinet, 'cabinet_id', 0 ) ),
				'material_id' => max( 0, (int) Arr::get( $cabinet, 'material_id', 0 ) ),
				'color_id'    => max( 0, (int) Arr::get( $cabinet, 'color_id', 0 ) ),
				'handle_id'   => max( 0, (int) Arr::get( $cabinet, 'handle_id', 0 ) ),
				'dimensions'  => array(
					'width'  => max( 0, (int) Arr::get( $dimensions, 'width', 0 ) ),
					'height' => max( 0, (int) Arr::get( $dimensions, 'height', 0 ) ),
					'depth'  => max( 0, (int) Arr::get( $dimensions, 'depth', 0 ) ),
				),
				'position'    => array(
					'x'        => (float) Arr::get( $position, 'x', 0 ),
					'y'        => (float) Arr::get( $position, 'y', 0 ),
					'rotation' => (int) Arr::get( $position, 'rotation', 0 ),
				),
				'accessories' => is_array( $accessories )
					? array_values( array_map( 'absint', $accessories ) )
					: array(),
			);
		}

		return $sanitized;
	}

	/**
	 * Sanitize global options.
	 *
	 * @param mixed $options Raw options.
	 * @return array<string, mixed>
	 */
	private function sanitize_global_options( mixed $options ): array {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$sanitized = array(
			'worktop_id'           => max( 0, (int) Arr::get( $options, 'worktop_id', 0 ) ),
			'worktop_material_id'  => max( 0, (int) Arr::get( $options, 'worktop_material_id', 0 ) ),
			'worktop_color_id'     => max( 0, (int) Arr::get( $options, 'worktop_color_id', 0 ) ),
			'worktop_length'       => max( 0, (int) Arr::get( $options, 'worktop_length', 0 ) ),
			'worktop_depth'        => max( 0, (int) Arr::get( $options, 'worktop_depth', 0 ) ),
			'plinth_id'            => max( 0, (int) Arr::get( $options, 'plinth_id', 0 ) ),
			'plinth_length'        => max( 0, (int) Arr::get( $options, 'plinth_length', 0 ) ),
			'plinth_height'        => max( 0, (int) Arr::get( $options, 'plinth_height', 0 ) ),
		);

		$accessories = Arr::get( $options, 'accessories', array() );

		if ( is_array( $accessories ) ) {
			$sanitized['accessories'] = array_values( array_map( 'absint', $accessories ) );
		}

		return $sanitized;
	}

	/**
	 * Read session_id from database row (not mapped on entity).
	 *
	 * @param int $config_id Configuration ID.
	 * @return string|null
	 */
	private function get_session_id_for_config( int $config_id ): ?string {
		global $wpdb;

		$table = \KitchenConfiguratorPro\Support\Helpers::table_name( 'kcp_configurations' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from trusted helper.
		$session = $wpdb->get_var(
			$wpdb->prepare( "SELECT session_id FROM {$table} WHERE id = %d", $config_id )
		);

		return is_string( $session ) && '' !== $session ? $session : null;
	}
}
