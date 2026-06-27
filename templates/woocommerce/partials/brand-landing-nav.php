<?php
/**
 * Brand category sidebar navigation.
 *
 * @package KitchenConfiguratorPro
 */

defined( 'ABSPATH' ) || exit;

/** @var array<int, array<string, mixed>> $navigation */
$navigation = is_array( $navigation ?? null ) ? $navigation : array();
/** @var string $modifier */
$modifier = is_string( $modifier ?? null ) ? $modifier : '';
?>
<?php if ( ! empty( $navigation ) ) : ?>
	<nav class="kcp-brand-sidebar<?php echo '' !== $modifier ? ' kcp-brand-sidebar--' . esc_attr( $modifier ) : ''; ?>" aria-label="<?php esc_attr_e( 'Assortiment', 'kitchen-configurator-pro' ); ?>">
		<?php foreach ( $navigation as $section ) : ?>
			<?php
			$section_name   = (string) ( $section['name'] ?? '' );
			$section_url    = (string) ( $section['url'] ?? '' );
			$section_active = ! empty( $section['is_active'] );
			$children       = is_array( $section['children'] ?? null ) ? $section['children'] : array();
			$has_children   = ! empty( $children );
			?>
			<div class="kcp-brand-sidebar__section<?php echo $section_active ? ' is-active' : ''; ?><?php echo $has_children ? ' has-children' : ''; ?>">
				<div class="kcp-brand-sidebar__header">
					<a class="kcp-brand-sidebar__section-link" href="<?php echo esc_url( $section_url ); ?>">
						<?php echo esc_html( $section_name ); ?>
					</a>
					<?php if ( $has_children ) : ?>
						<button
							type="button"
							class="kcp-brand-sidebar__toggle"
							aria-expanded="<?php echo $section_active ? 'true' : 'false'; ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'Toggle %s', 'kitchen-configurator-pro' ), $section_name ) ); ?>"
						>
							<span aria-hidden="true"></span>
						</button>
					<?php endif; ?>
				</div>
				<?php if ( $has_children ) : ?>
					<div class="kcp-brand-sidebar__children<?php echo $section_active ? ' is-open' : ''; ?>">
						<?php foreach ( $children as $child ) : ?>
							<a
								class="kcp-brand-sidebar__child-link<?php echo ! empty( $child['is_active'] ) ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( (string) ( $child['url'] ?? '' ) ); ?>"
							>
								<?php echo esc_html( (string) ( $child['name'] ?? '' ) ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</nav>
<?php endif; ?>
