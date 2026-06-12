<?php
/**
 * Pricing rules admin page.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Admin\Pages;

use KitchenConfiguratorPro\Admin\AbstractCrudPage;
use KitchenConfiguratorPro\Domain\Enums\PricingRuleType;
use KitchenConfiguratorPro\Repositories\PricingRuleRepository;

/**
 * CRUD admin page for pricing rules.
 */
final class PricingRulesPage extends AbstractCrudPage {

	/**
	 * {@inheritDoc}
	 */
	public function slug(): string {
		return 'kcp-pricing-rules';
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label(): string {
		return __( 'Pricing Rule', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function entity_label_plural(): string {
		return __( 'Pricing Rules', 'kitchen-configurator-pro' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function repository_class(): string {
		return PricingRuleRepository::class;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function list_columns(): array {
		return array(
			'name'      => __( 'Name', 'kitchen-configurator-pro' ),
			'rule_type' => __( 'Type', 'kitchen-configurator-pro' ),
			'priority'  => __( 'Priority', 'kitchen-configurator-pro' ),
			'is_active' => __( 'Active', 'kitchen-configurator-pro' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function form_fields(): array {
		return array(
			'name'             => array( 'type' => 'text', 'label' => __( 'Name', 'kitchen-configurator-pro' ), 'required' => true ),
			'rule_type'        => array(
				'type'     => 'select',
				'label'    => __( 'Rule Type', 'kitchen-configurator-pro' ),
				'required' => true,
				'options'  => PricingRuleType::labels(),
			),
			'entity_type'      => array(
				'type'    => 'select',
				'label'   => __( 'Entity Type', 'kitchen-configurator-pro' ),
				'options' => array(
					''         => __( '— Global —', 'kitchen-configurator-pro' ),
					'cabinet'  => __( 'Cabinet', 'kitchen-configurator-pro' ),
					'material' => __( 'Material', 'kitchen-configurator-pro' ),
					'layout'   => __( 'Layout', 'kitchen-configurator-pro' ),
				),
			),
			'entity_id'        => array( 'type' => 'number', 'label' => __( 'Entity ID', 'kitchen-configurator-pro' ), 'min' => 0 ),
			'conditions_json'  => array(
				'type'        => 'json',
				'label'       => __( 'Conditions (JSON)', 'kitchen-configurator-pro' ),
				'required'    => true,
				'default'     => '{}',
				'rows'        => 8,
				'description' => __( 'When this rule applies.', 'kitchen-configurator-pro' ),
			),
			'calculation_json' => array(
				'type'        => 'json',
				'label'       => __( 'Calculation (JSON)', 'kitchen-configurator-pro' ),
				'required'    => true,
				'default'     => '{}',
				'rows'        => 8,
				'description' => __( 'How to calculate the price adjustment.', 'kitchen-configurator-pro' ),
			),
			'priority'         => array( 'type' => 'number', 'label' => __( 'Priority', 'kitchen-configurator-pro' ), 'default' => 100, 'min' => 0 ),
			'valid_from'       => array( 'type' => 'datetime-local', 'label' => __( 'Valid From', 'kitchen-configurator-pro' ) ),
			'valid_until'      => array( 'type' => 'datetime-local', 'label' => __( 'Valid Until', 'kitchen-configurator-pro' ) ),
			'is_active'        => array( 'type' => 'checkbox', 'label' => __( 'Active', 'kitchen-configurator-pro' ), 'default' => 1 ),
		);
	}
}
