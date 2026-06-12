<?php
/**
 * Validation exception.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\Exceptions;

/**
 * Thrown when configuration validation fails.
 */
final class ValidationException extends KcpException {

	/**
	 * @param array<int, string> $errors Validation error messages.
	 */
	public function __construct(
		private readonly array $errors
	) {
		parent::__construct( implode( ' ', $errors ) );
	}

	/**
	 * Get validation errors.
	 *
	 * @return array<int, string>
	 */
	public function errors(): array {
		return $this->errors;
	}
}
