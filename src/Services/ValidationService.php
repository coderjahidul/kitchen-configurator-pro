<?php
/**
 * Configuration validation service.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Services;

use KitchenConfiguratorPro\Domain\DTO\ConfigurationInput;
use KitchenConfiguratorPro\Domain\Entities\Cabinet;
use KitchenConfiguratorPro\Domain\Exceptions\ValidationException;
use KitchenConfiguratorPro\Domain\ValueObjects\Dimensions;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;
use KitchenConfiguratorPro\Repositories\WorktopRepository;
use KitchenConfiguratorPro\Support\Arr;

/**
 * Validates configuration input against catalog constraints.
 */
final class ValidationService {

	/**
	 * @param LayoutRepository   $layouts   Layout repository.
	 * @param CabinetRepository  $cabinets  Cabinet repository.
	 * @param MaterialRepository $materials Material repository.
	 * @param ColorRepository    $colors    Color repository.
	 * @param HandleRepository   $handles   Handle repository.
	 * @param WorktopRepository  $worktops  Worktop repository.
	 * @param PlinthRepository   $plinths   Plinth repository.
	 */
	public function __construct(
		private readonly LayoutRepository $layouts,
		private readonly CabinetRepository $cabinets,
		private readonly MaterialRepository $materials,
		private readonly ColorRepository $colors,
		private readonly HandleRepository $handles,
		private readonly WorktopRepository $worktops,
		private readonly PlinthRepository $plinths
	) {
	}

	/**
	 * Validate configuration input.
	 *
	 * @param ConfigurationInput $input Configuration input.
	 * @return void
	 *
	 * @throws ValidationException When validation fails.
	 */
	public function validate_configuration( ConfigurationInput $input ): void {
		$errors = array();

		if ( $input->layout_id <= 0 ) {
			$errors[] = __( 'Layout is required.', 'kitchen-configurator-pro' );
		} else {
			$layout = $this->layouts->find( $input->layout_id );

			if ( null === $layout || ! $layout->is_active ) {
				$errors[] = __( 'Selected layout is invalid or inactive.', 'kitchen-configurator-pro' );
			}
		}

		if ( empty( $input->cabinets ) ) {
			$errors[] = __( 'At least one cabinet is required.', 'kitchen-configurator-pro' );
		}

		foreach ( $input->cabinets as $index => $cabinet_item ) {
			if ( ! is_array( $cabinet_item ) ) {
				$errors[] = sprintf(
					/* translators: %d: cabinet index */
					__( 'Cabinet item %d is invalid.', 'kitchen-configurator-pro' ),
					$index + 1
				);
				continue;
			}

			$this->validate_cabinet_item( $cabinet_item, $index, $errors );
		}

		$this->validate_global_options( $input->global_options, $errors );

		if ( ! empty( $errors ) ) {
			throw new ValidationException( $errors );
		}
	}

	/**
	 * Validate a single cabinet item.
	 *
	 * @param array<string, mixed> $item   Cabinet item.
	 * @param int                  $index  Zero-based index.
	 * @param array<int, string>   $errors Error collector.
	 * @return void
	 */
	private function validate_cabinet_item( array $item, int $index, array &$errors ): void {
		$cabinet_id = (int) Arr::get( $item, 'cabinet_id', 0 );
		$position   = $index + 1;

		if ( $cabinet_id <= 0 ) {
			$errors[] = sprintf(
				/* translators: %d: cabinet position */
				__( 'Cabinet %d: cabinet_id is required.', 'kitchen-configurator-pro' ),
				$position
			);

			return;
		}

		$cabinet = $this->cabinets->find( $cabinet_id );

		if ( null === $cabinet || ! $cabinet->is_active ) {
			$errors[] = sprintf(
				/* translators: %d: cabinet position */
				__( 'Cabinet %d: selected cabinet is invalid or inactive.', 'kitchen-configurator-pro' ),
				$position
			);

			return;
		}

		$dimensions = Dimensions::from_array(
			is_array( $item['dimensions'] ?? null ) ? $item['dimensions'] : array()
		);

		try {
			$this->validate_dimensions( $cabinet, $dimensions );
		} catch ( ValidationException $exception ) {
			foreach ( $exception->errors() as $message ) {
				$errors[] = sprintf(
					/* translators: 1: cabinet position, 2: error message */
					__( 'Cabinet %1$d: %2$s', 'kitchen-configurator-pro' ),
					$position,
					$message
				);
			}
		}

		$material_id = (int) Arr::get( $item, 'material_id', 0 );

		if ( $material_id > 0 ) {
			$material = $this->materials->find( $material_id );

			if ( null === $material || ! $material->is_active ) {
				$errors[] = sprintf(
					/* translators: %d: cabinet position */
					__( 'Cabinet %d: selected material is invalid or inactive.', 'kitchen-configurator-pro' ),
					$position
				);
			}
		}

		$color_id = (int) Arr::get( $item, 'color_id', 0 );

		if ( $color_id > 0 ) {
			$color = $this->colors->find( $color_id );

			if ( null === $color || ! $color->is_active ) {
				$errors[] = sprintf(
					/* translators: %d: cabinet position */
					__( 'Cabinet %d: selected color is invalid or inactive.', 'kitchen-configurator-pro' ),
					$position
				);
			} elseif ( $material_id > 0 && $color->material_id !== $material_id ) {
				$errors[] = sprintf(
					/* translators: %d: cabinet position */
					__( 'Cabinet %d: color does not belong to the selected material.', 'kitchen-configurator-pro' ),
					$position
				);
			}
		}

		$handle_id = (int) Arr::get( $item, 'handle_id', 0 );

		if ( $handle_id > 0 ) {
			$handle = $this->handles->find( $handle_id );

			if ( null === $handle || ! $handle->is_active ) {
				$errors[] = sprintf(
					/* translators: %d: cabinet position */
					__( 'Cabinet %d: selected handle is invalid or inactive.', 'kitchen-configurator-pro' ),
					$position
				);
			}
		}
	}

	/**
	 * Validate global options.
	 *
	 * @param array<string, mixed> $options Global options.
	 * @param array<int, string>   $errors  Error collector.
	 * @return void
	 */
	private function validate_global_options( array $options, array &$errors ): void {
		$worktop_id = (int) Arr::get( $options, 'worktop_id', 0 );

		if ( $worktop_id > 0 ) {
			$worktop = $this->worktops->find( $worktop_id );

			if ( null === $worktop || ! $worktop->is_active ) {
				$errors[] = __( 'Selected worktop is invalid or inactive.', 'kitchen-configurator-pro' );
			} else {
				$this->validate_worktop_dimensions( $worktop, $options, $errors );
			}

			$this->validate_finish_material( $options, 'worktop_material_id', 'worktop_color_id', $errors );
		}

		$plinth_id = (int) Arr::get( $options, 'plinth_id', 0 );

		if ( $plinth_id > 0 ) {
			$plinth = $this->plinths->find( $plinth_id );

			if ( null === $plinth || ! $plinth->is_active ) {
				$errors[] = __( 'Selected plinth is invalid or inactive.', 'kitchen-configurator-pro' );
			} else {
				$this->validate_plinth_dimensions( $plinth, $options, $errors );
			}
		}
	}

	/**
	 * Validate worktop dimensions.
	 *
	 * @param \KitchenConfiguratorPro\Domain\Entities\Worktop $worktop Worktop entity.
	 * @param array<string, mixed>                            $options Global options.
	 * @param array<int, string>                              $errors  Error collector.
	 * @return void
	 */
	private function validate_worktop_dimensions( $worktop, array $options, array &$errors ): void {
		$length = (int) Arr::get( $options, 'worktop_length', $worktop->default_length );
		$depth  = (int) Arr::get( $options, 'worktop_depth', $worktop->default_depth );

		if ( $length < $worktop->min_length || $length > $worktop->max_length ) {
			$errors[] = __( 'Worktop length is out of allowed range.', 'kitchen-configurator-pro' );
		}

		if ( $depth < $worktop->min_depth || $depth > $worktop->max_depth ) {
			$errors[] = __( 'Worktop depth is out of allowed range.', 'kitchen-configurator-pro' );
		}
	}

	/**
	 * Validate plinth dimensions.
	 *
	 * @param \KitchenConfiguratorPro\Domain\Entities\Plinth $plinth  Plinth entity.
	 * @param array<string, mixed>                           $options Global options.
	 * @param array<int, string>                             $errors  Error collector.
	 * @return void
	 */
	private function validate_plinth_dimensions( $plinth, array $options, array &$errors ): void {
		$length = (int) Arr::get( $options, 'plinth_length', $plinth->default_length );
		$height = (int) Arr::get( $options, 'plinth_height', $plinth->default_height );

		if ( $length < $plinth->min_length || $length > $plinth->max_length ) {
			$errors[] = __( 'Plinth length is out of allowed range.', 'kitchen-configurator-pro' );
		}

		if ( $height < $plinth->min_height || $height > $plinth->max_height ) {
			$errors[] = __( 'Plinth height is out of allowed range.', 'kitchen-configurator-pro' );
		}
	}

	/**
	 * Validate finish material and color pair.
	 *
	 * @param array<string, mixed> $options     Global options.
	 * @param string               $material_key Material option key.
	 * @param string               $color_key    Color option key.
	 * @param array<int, string>   $errors       Error collector.
	 * @return void
	 */
	private function validate_finish_material( array $options, string $material_key, string $color_key, array &$errors ): void {
		$material_id = (int) Arr::get( $options, $material_key, 0 );

		if ( $material_id <= 0 ) {
			return;
		}

		$material = $this->materials->find( $material_id );

		if ( null === $material || ! $material->is_active ) {
			$errors[] = __( 'Selected worktop finish material is invalid or inactive.', 'kitchen-configurator-pro' );

			return;
		}

		$color_id = (int) Arr::get( $options, $color_key, 0 );

		if ( $color_id <= 0 ) {
			return;
		}

		$color = $this->colors->find( $color_id );

		if ( null === $color || ! $color->is_active ) {
			$errors[] = __( 'Selected worktop finish color is invalid or inactive.', 'kitchen-configurator-pro' );
		} elseif ( $color->material_id !== $material_id ) {
			$errors[] = __( 'Worktop finish color does not belong to the selected material.', 'kitchen-configurator-pro' );
		}
	}

	/**
	 * Validate cabinet dimensions.
	 *
	 * @param Cabinet    $cabinet    Cabinet entity.
	 * @param Dimensions $dimensions Dimensions to validate.
	 * @return void
	 *
	 * @throws ValidationException When dimensions are invalid.
	 */
	public function validate_dimensions( Cabinet $cabinet, Dimensions $dimensions ): void {
		$min = new Dimensions( $cabinet->min_width, $cabinet->min_height, $cabinet->min_depth );
		$max = new Dimensions( $cabinet->max_width, $cabinet->max_height, $cabinet->max_depth );

		if ( ! $dimensions->is_within_range( $min, $max ) ) {
			throw new ValidationException(
				array(
					sprintf(
						/* translators: 1: min width, 2: max width, 3: min height, 4: max height, 5: min depth, 6: max depth */
						__( 'Dimensions must be within %1$d–%2$d (W) × %3$d–%4$d (H) × %5$d–%6$d (D) mm.', 'kitchen-configurator-pro' ),
						$cabinet->min_width,
						$cabinet->max_width,
						$cabinet->min_height,
						$cabinet->max_height,
						$cabinet->min_depth,
						$cabinet->max_depth
					),
				)
			);
		}

		foreach (
			array(
				'width'  => array( $dimensions->width, $cabinet->width_step ),
				'height' => array( $dimensions->height, $cabinet->height_step ),
				'depth'  => array( $dimensions->depth, $cabinet->depth_step ),
			) as $axis => $pair
		) {
			list( $value, $step ) = $pair;

			if ( $step > 1 && 0 !== $value % $step ) {
				throw new ValidationException(
					array(
						sprintf(
							/* translators: 1: dimension axis, 2: step size */
							__( '%1$s must be in increments of %2$d mm.', 'kitchen-configurator-pro' ),
							ucfirst( $axis ),
							$step
						),
					)
				);
			}
		}
	}
}
