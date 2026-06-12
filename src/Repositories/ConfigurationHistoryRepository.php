<?php
/**
 * Configuration history (audit trail) repository.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Repositories;

/**
 * Persists configuration change history for audit purposes.
 */
final class ConfigurationHistoryRepository extends AbstractRepository {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return 'kcp_configuration_history';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function map_row( array $row ): array {
		return $row;
	}

	/**
	 * Record an audit event.
	 *
	 * @param int                  $configuration_id Configuration ID.
	 * @param string               $action           Action name.
	 * @param string               $configuration_json Configuration JSON snapshot.
	 * @param string|null          $pricing_snapshot_json Pricing snapshot JSON.
	 * @param int|null             $actor_user_id    Acting user ID.
	 * @return bool
	 */
	public function record(
		int $configuration_id,
		string $action,
		string $configuration_json,
		?string $pricing_snapshot_json = null,
		?int $actor_user_id = null
	): bool {
		$result = $this->create(
			array(
				'configuration_id'      => $configuration_id,
				'configuration_json'    => $configuration_json,
				'pricing_snapshot_json' => $pricing_snapshot_json,
				'action'                => $action,
				'actor_user_id'         => $actor_user_id,
				'created_at'            => current_time( 'mysql', true ),
			)
		);

		return null !== $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function sanitize( array $data ): array {
		return array(
			'configuration_id'      => max( 0, (int) ( $data['configuration_id'] ?? 0 ) ),
			'configuration_json'    => (string) ( $data['configuration_json'] ?? '{}' ),
			'pricing_snapshot_json' => isset( $data['pricing_snapshot_json'] ) ? (string) $data['pricing_snapshot_json'] : null,
			'action'                => sanitize_key( (string) ( $data['action'] ?? 'unknown' ) ),
			'actor_user_id'         => isset( $data['actor_user_id'] ) ? (int) $data['actor_user_id'] : null,
			'created_at'            => sanitize_text_field( (string) ( $data['created_at'] ?? current_time( 'mysql', true ) ) ),
		);
	}
}
