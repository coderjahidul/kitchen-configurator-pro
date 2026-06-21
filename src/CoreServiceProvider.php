<?php
/**
 * Core service provider.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro;

use KitchenConfiguratorPro\Contracts\PricingCalculatorInterface;
use KitchenConfiguratorPro\Repositories\AccessoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetCategoryRepository;
use KitchenConfiguratorPro\Repositories\CabinetRepository;
use KitchenConfiguratorPro\Repositories\ColorRepository;
use KitchenConfiguratorPro\Repositories\ConfigurationRepository;
use KitchenConfiguratorPro\Repositories\HandleRepository;
use KitchenConfiguratorPro\Repositories\LayoutRepository;
use KitchenConfiguratorPro\Repositories\MaterialRepository;
use KitchenConfiguratorPro\Repositories\PlinthRepository;
use KitchenConfiguratorPro\Repositories\PricingRuleRepository;
use KitchenConfiguratorPro\Repositories\WorktopRepository;
use KitchenConfiguratorPro\Services\Pricing\Calculators\AccessoryCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\BasePriceCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\DimensionCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\HandleCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\MaterialCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\PlinthCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\RuleEngineCalculator;
use KitchenConfiguratorPro\Services\Pricing\Calculators\WorktopCalculator;
use KitchenConfiguratorPro\Services\Pricing\CatalogContextBuilder;
use KitchenConfiguratorPro\Services\Pricing\ConditionEvaluator;
use KitchenConfiguratorPro\Services\Pricing\PriceHashGenerator;
use KitchenConfiguratorPro\Services\Pricing\PricingEngine;
use KitchenConfiguratorPro\Services\CatalogService;
use KitchenConfiguratorPro\Services\ConfigurationAuditService;
use KitchenConfiguratorPro\Services\ConfigurationService;
use KitchenConfiguratorPro\Services\DesignZoneCatalogService;
use KitchenConfiguratorPro\Services\ValidationService;
use KitchenConfiguratorPro\Security\ConfigurationSchemaValidator;

/**
 * Registers repositories and pricing services for the entire plugin.
 */
final class CoreServiceProvider {

	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @param Container $container Service container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Register container bindings.
	 *
	 * @return void
	 */
	public function register(): void {
		$repositories = array(
			LayoutRepository::class,
			CabinetCategoryRepository::class,
			CabinetRepository::class,
			MaterialRepository::class,
			ColorRepository::class,
			HandleRepository::class,
			AccessoryRepository::class,
			WorktopRepository::class,
			PlinthRepository::class,
			PricingRuleRepository::class,
			ConfigurationRepository::class,
		);

		foreach ( $repositories as $repository_class ) {
			$this->container->singleton(
				$repository_class,
				static function () use ( $repository_class ) {
					global $wpdb;

					return new $repository_class( $wpdb );
				}
			);
		}

		$this->container->singleton( ConditionEvaluator::class, static fn () => new ConditionEvaluator() );
		$this->container->singleton( PriceHashGenerator::class, static fn () => new PriceHashGenerator() );

		$this->container->singleton(
			CatalogContextBuilder::class,
			function () {
				return new CatalogContextBuilder(
					$this->container->get( LayoutRepository::class ),
					$this->container->get( CabinetRepository::class ),
					$this->container->get( MaterialRepository::class ),
					$this->container->get( ColorRepository::class ),
					$this->container->get( HandleRepository::class ),
					$this->container->get( WorktopRepository::class ),
					$this->container->get( PlinthRepository::class )
				);
			}
		);

		$this->container->singleton(
			ValidationService::class,
			function () {
				return new ValidationService(
					$this->container->get( LayoutRepository::class ),
					$this->container->get( CabinetRepository::class ),
					$this->container->get( MaterialRepository::class ),
					$this->container->get( ColorRepository::class ),
					$this->container->get( HandleRepository::class ),
					$this->container->get( WorktopRepository::class ),
					$this->container->get( PlinthRepository::class )
				);
			}
		);

		$calculator_classes = array(
			BasePriceCalculator::class,
			DimensionCalculator::class,
			MaterialCalculator::class,
			HandleCalculator::class,
			AccessoryCalculator::class,
			WorktopCalculator::class,
			PlinthCalculator::class,
			RuleEngineCalculator::class,
		);

		foreach ( $calculator_classes as $calculator_class ) {
			$this->container->singleton(
				$calculator_class,
				function () use ( $calculator_class ) {
					if ( AccessoryCalculator::class === $calculator_class ) {
						return new AccessoryCalculator( $this->container->get( AccessoryRepository::class ) );
					}

					if ( RuleEngineCalculator::class === $calculator_class ) {
						return new RuleEngineCalculator(
							$this->container->get( PricingRuleRepository::class ),
							$this->container->get( ConditionEvaluator::class )
						);
					}

					return new $calculator_class();
				}
			);
		}

		$this->container->singleton(
			PricingEngine::class,
			function () use ( $calculator_classes ) {
				$calculators = array_map(
					fn ( string $class ): PricingCalculatorInterface => $this->container->get( $class ),
					$calculator_classes
				);

				return new PricingEngine(
					$this->container->get( ValidationService::class ),
					$this->container->get( CatalogContextBuilder::class ),
					$this->container->get( PriceHashGenerator::class ),
					$calculators
				);
			}
		);

		$this->container->singleton(
			DesignZoneCatalogService::class,
			function () {
				return new DesignZoneCatalogService(
					$this->container->get( ColorRepository::class ),
					$this->container->get( HandleRepository::class ),
					$this->container->get( CabinetRepository::class ),
					$this->container->get( PlinthRepository::class )
				);
			}
		);

		$this->container->singleton(
			CatalogService::class,
			function () {
				return new CatalogService(
					$this->container->get( LayoutRepository::class ),
					$this->container->get( CabinetCategoryRepository::class ),
					$this->container->get( CabinetRepository::class ),
					$this->container->get( MaterialRepository::class ),
					$this->container->get( ColorRepository::class ),
					$this->container->get( HandleRepository::class ),
					$this->container->get( AccessoryRepository::class ),
					$this->container->get( WorktopRepository::class ),
					$this->container->get( PlinthRepository::class )
				);
			}
		);

		$this->container->singleton(
			ConfigurationService::class,
			function () {
				return new ConfigurationService(
					$this->container->get( ConfigurationRepository::class ),
					$this->container->get( PricingEngine::class ),
					$this->container->get( ConfigurationSchemaValidator::class ),
					$this->container->get( ConfigurationAuditService::class )
				);
			}
		);
	}
}
