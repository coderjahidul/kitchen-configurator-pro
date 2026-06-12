<?php
/**
 * Configuration entity.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Entities;

/**
 * Customer configuration entity.
 */
final class Configuration {

	/**
	 * @param int         $id                    Primary key.
	 * @param string      $uuid                  Public UUID.
	 * @param int|null    $project_id            Project ID.
	 * @param int         $layout_id             Layout ID.
	 * @param int|null    $user_id               User ID.
	 * @param string      $title                 Title.
	 * @param string      $configuration_json    Configuration JSON.
	 * @param string      $pricing_snapshot_json Pricing snapshot JSON.
	 * @param string      $total_price           Total price.
	 * @param string      $status                Status.
	 * @param string      $created_at            Created at.
	 * @param string      $updated_at            Updated at.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $uuid,
		public readonly ?int $project_id,
		public readonly int $layout_id,
		public readonly ?int $user_id,
		public readonly string $title,
		public readonly string $configuration_json,
		public readonly string $pricing_snapshot_json,
		public readonly string $total_price,
		public readonly string $status,
		public readonly string $created_at,
		public readonly string $updated_at
	) {
	}

	/**
	 * Create from database row.
	 *
	 * @param array<string, mixed> $row Database row.
	 * @return self
	 */
	public static function from_row( array $row ): self {
		return new self(
			(int) $row['id'],
			(string) $row['uuid'],
			isset( $row['project_id'] ) ? (int) $row['project_id'] : null,
			(int) $row['layout_id'],
			isset( $row['user_id'] ) ? (int) $row['user_id'] : null,
			(string) $row['title'],
			(string) $row['configuration_json'],
			(string) ( $row['pricing_snapshot_json'] ?? '' ),
			(string) $row['total_price'],
			(string) $row['status'],
			(string) $row['created_at'],
			(string) $row['updated_at']
		);
	}

	/**
	 * Convert to array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                    => $this->id,
			'uuid'                  => $this->uuid,
			'project_id'            => $this->project_id,
			'layout_id'             => $this->layout_id,
			'user_id'               => $this->user_id,
			'title'                 => $this->title,
			'configuration_json'    => $this->configuration_json,
			'pricing_snapshot_json' => $this->pricing_snapshot_json,
			'total_price'           => $this->total_price,
			'status'                => $this->status,
			'created_at'            => $this->created_at,
			'updated_at'            => $this->updated_at,
		);
	}
}
