<?php
/**
 * Configuration audit trail service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Repositories\ConfigurationHistoryRepository;
use KitchenConfiguratorPro\Security\SecurityLogger;

/**
 * Records configuration lifecycle events to the audit table.
 */
final class ConfigurationAuditService {

	/**
	 * @param ConfigurationHistoryRepository $history History repository.
	 */
	public function __construct(
		private readonly ConfigurationHistoryRepository $history
	) {
	}

	/**
	 * Record a configuration audit event.
	 *
	 * @param int         $configuration_id Configuration ID.
	 * @param string      $action           Action identifier.
	 * @param string      $configuration_json Configuration JSON.
	 * @param string|null $pricing_json     Pricing snapshot JSON.
	 * @return void
	 */
	public function record(
		int $configuration_id,
		string $action,
		string $configuration_json,
		?string $pricing_json = null
	): void {
		$user_id = get_current_user_id();

		$recorded = $this->history->record(
			$configuration_id,
			$action,
			$configuration_json,
			$pricing_json,
			$user_id > 0 ? $user_id : null
		);

		if ( ! $recorded ) {
			SecurityLogger::log(
				'audit_write_failed',
				'Failed to write configuration audit record.',
				array(
					'configuration_id' => $configuration_id,
					'action'           => $action,
				),
				SecurityLogger::LEVEL_ERROR
			);
		}
	}
}
