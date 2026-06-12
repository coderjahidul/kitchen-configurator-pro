<?php
/**
 * Frontend configurator template shell.
 *
 * @package KitchenConfiguratorPro
 *
 * @var string $uuid  Optional configuration UUID to load.
 * @var string $title Optional default project title.
 */

defined( 'ABSPATH' ) || exit;

?>
<div
	id="kcp-configurator-root"
	class="kcp-configurator"
	data-uuid="<?php echo esc_attr( $uuid ); ?>"
	data-title="<?php echo esc_attr( $title ); ?>"
>
	<div class="kcp-configurator__loader" aria-live="polite">
		<span class="kcp-configurator__spinner" aria-hidden="true"></span>
		<span><?php esc_html_e( 'Loading configurator…', 'kitchen-configurator-pro' ); ?></span>
	</div>
</div>
