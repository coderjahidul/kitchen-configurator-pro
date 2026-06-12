<?php
/**
 * WordPress capability management.
 *
 * @package KitchenConfiguratorPro
 */

declare(strict_types=1);

namespace KitchenConfiguratorPro\Security;

/**
 * Registers and manages plugin capabilities.
 */
final class CapabilityManager {

	/**
	 * Primary admin capability for KCP management.
	 */
	public const CAP_MANAGE = 'manage_kcp';

	/**
	 * Register capabilities on administrator role.
	 *
	 * @return void
	 */
	public static function register(): void {
		$role = get_role( 'administrator' );

		if ( $role ) {
			$role->add_cap( self::CAP_MANAGE );
		}
	}

	/**
	 * Assert current user can manage KCP admin features.
	 *
	 * @return void
	 */
	public static function require_manage(): void {
		if ( current_user_can( self::CAP_MANAGE ) ) {
			return;
		}

		wp_die(
			esc_html__( 'You do not have permission to access this page.', 'kitchen-configurator-pro' ),
			esc_html__( 'Permission Denied', 'kitchen-configurator-pro' ),
			array( 'response' => 403 )
		);
	}
}
