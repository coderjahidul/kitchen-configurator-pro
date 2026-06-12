<?php
/**
 * Money value object.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Domain\ValueObjects;

/**
 * Immutable monetary amount stored as a decimal string.
 */
final class Money {

	/**
	 * Decimal amount (e.g. "450.00").
	 *
	 * @var string
	 */
	public readonly string $amount;

	/**
	 * ISO 4217 currency code.
	 *
	 * @var string
	 */
	public readonly string $currency;

	/**
	 * @param string $amount   Decimal amount.
	 * @param string $currency Currency code.
	 */
	public function __construct( string $amount, string $currency = 'EUR' ) {
		$this->amount   = self::normalize( $amount );
		$this->currency = $currency;
	}

	/**
	 * Create zero amount.
	 *
	 * @param string $currency Currency code.
	 * @return self
	 */
	public static function zero( string $currency = 'EUR' ): self {
		return new self( '0.00', $currency );
	}

	/**
	 * Create from numeric or string input.
	 *
	 * @param float|int|string $amount   Amount.
	 * @param string           $currency Currency code.
	 * @return self
	 */
	public static function from( float|int|string $amount, string $currency = 'EUR' ): self {
		return new self( (string) $amount, $currency );
	}

	/**
	 * Add two amounts.
	 *
	 * @param self $other Other amount.
	 * @return self
	 */
	public function add( self $other ): self {
		$this->assert_same_currency( $other );

		return new self( self::bc_add( $this->amount, $other->amount ), $this->currency );
	}

	/**
	 * Subtract amount.
	 *
	 * @param self $other Other amount.
	 * @return self
	 */
	public function subtract( self $other ): self {
		$this->assert_same_currency( $other );

		return new self( self::bc_sub( $this->amount, $other->amount ), $this->currency );
	}

	/**
	 * Multiply by a factor.
	 *
	 * @param float|int|string $factor Multiplier.
	 * @return self
	 */
	public function multiply( float|int|string $factor ): self {
		return new self( self::bc_mul( $this->amount, (string) $factor ), $this->currency );
	}

	/**
	 * Apply percentage of this amount.
	 *
	 * @param float|int|string $percent Percentage (e.g. 21 for 21%).
	 * @return self
	 */
	public function percentage( float|int|string $percent ): self {
		$factor = self::bc_div( (string) $percent, '100', 6 );

		return $this->multiply( $factor );
	}

	/**
	 * Whether amount is greater than zero.
	 *
	 * @return bool
	 */
	public function is_positive(): bool {
		return self::bc_comp( $this->amount, '0' ) > 0;
	}

	/**
	 * Whether amount is zero.
	 *
	 * @return bool
	 */
	public function is_zero(): bool {
		return self::bc_comp( $this->amount, '0' ) === 0;
	}

	/**
	 * Format for display.
	 *
	 * @return string
	 */
	public function format(): string {
		return $this->currency . ' ' . $this->amount;
	}

	/**
	 * Convert to array.
	 *
	 * @return array{amount: string, currency: string}
	 */
	public function to_array(): array {
		return array(
			'amount'   => $this->amount,
			'currency' => $this->currency,
		);
	}

	/**
	 * Normalize decimal string.
	 *
	 * @param string $amount Raw amount.
	 * @return string
	 */
	private static function normalize( string $amount ): string {
		return number_format( (float) $amount, 2, '.', '' );
	}

	/**
	 * Assert matching currency.
	 *
	 * @param self $other Other amount.
	 * @return void
	 */
	private function assert_same_currency( self $other ): void {
		if ( $this->currency !== $other->currency ) {
			throw new \InvalidArgumentException( 'Currency mismatch.' );
		}
	}

	/**
	 * BC addition with fallback.
	 *
	 * @param string $left  Left operand.
	 * @param string $right Right operand.
	 * @return string
	 */
	private static function bc_add( string $left, string $right ): string {
		if ( function_exists( 'bcadd' ) ) {
			return bcadd( $left, $right, 2 );
		}

		return number_format( (float) $left + (float) $right, 2, '.', '' );
	}

	/**
	 * BC subtraction with fallback.
	 *
	 * @param string $left  Left operand.
	 * @param string $right Right operand.
	 * @return string
	 */
	private static function bc_sub( string $left, string $right ): string {
		if ( function_exists( 'bcsub' ) ) {
			return bcsub( $left, $right, 2 );
		}

		return number_format( (float) $left - (float) $right, 2, '.', '' );
	}

	/**
	 * BC multiplication with fallback.
	 *
	 * @param string $left  Left operand.
	 * @param string $right Right operand.
	 * @return string
	 */
	private static function bc_mul( string $left, string $right ): string {
		if ( function_exists( 'bcmul' ) ) {
			return bcmul( $left, $right, 2 );
		}

		return number_format( (float) $left * (float) $right, 2, '.', '' );
	}

	/**
	 * BC division with fallback.
	 *
	 * @param string $left  Left operand.
	 * @param string $right Right operand.
	 * @param int    $scale Decimal scale.
	 * @return string
	 */
	private static function bc_div( string $left, string $right, int $scale = 2 ): string {
		if ( (float) $right === 0.0 ) {
			return '0';
		}

		if ( function_exists( 'bcdiv' ) ) {
			return bcdiv( $left, $right, $scale );
		}

		return number_format( (float) $left / (float) $right, $scale, '.', '' );
	}

	/**
	 * BC comparison with fallback.
	 *
	 * @param string $left  Left operand.
	 * @param string $right Right operand.
	 * @return int
	 */
	private static function bc_comp( string $left, string $right ): int {
		if ( function_exists( 'bccomp' ) ) {
			return bccomp( $left, $right, 2 );
		}

		return (float) $left <=> (float) $right;
	}
}
