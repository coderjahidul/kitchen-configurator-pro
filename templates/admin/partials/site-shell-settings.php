<?php
/**
 * Site shell settings fields.
 *
 * @package KitchenConfiguratorPro
 *
 * @var array<string, mixed> $site_shell
 */

defined( 'ABSPATH' ) || exit;

$site_shell = is_array( $site_shell ?? null ) ? $site_shell : \KitchenConfiguratorPro\Services\SiteShellSettingsService::defaults();
$contact_links = is_array( $site_shell['contact_links'] ?? null ) ? $site_shell['contact_links'] : array();
$trust_badges  = is_array( $site_shell['trust_badges'] ?? null ) ? $site_shell['trust_badges'] : array();
$footer_titles = is_array( $site_shell['footer_titles'] ?? null ) ? $site_shell['footer_titles'] : array();
$payment_icons = is_array( $site_shell['payment_icons'] ?? null ) ? $site_shell['payment_icons'] : array();
$available_icons = array(
	'ideal'      => 'iDEAL',
	'applepay'   => 'Apple Pay',
	'mastercard' => 'Mastercard',
	'bancontact' => 'Bancontact',
);

if ( empty( $contact_links ) ) {
	$contact_links = array( array( 'label' => '', 'url' => '' ) );
}
if ( empty( $trust_badges ) ) {
	$trust_badges = array( array( 'label' => '', 'url' => '', 'icon' => 'warranty' ) );
}
$menus_admin_url = admin_url( 'nav-menus.php' );
?>
<h2><?php esc_html_e( 'Header & footer shell', 'kitchen-configurator-pro' ); ?></h2>
<p class="description">
	<?php esc_html_e( 'Configure the configurator header and footer. Assign WordPress menus under Appearance → Menus to the Configurator menu locations for full control over navigation links.', 'kitchen-configurator-pro' ); ?>
	<?php esc_html_e( 'For image submenus like the reference site, add child items under a desktop menu item and set Submenu image / Submenu hover image on each child.', 'kitchen-configurator-pro' ); ?>
</p>

<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><?php esc_html_e( 'Announcement bar', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="site_shell_announcement_enabled" value="1" <?php checked( ! empty( $site_shell['announcement_enabled'] ) ); ?> />
				<?php esc_html_e( 'Enabled', 'kitchen-configurator-pro' ); ?>
			</label>
			<p>
				<input type="text" class="large-text" name="site_shell_announcement_text" value="<?php echo esc_attr( (string) ( $site_shell['announcement_text'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Announcement text', 'kitchen-configurator-pro' ); ?>" />
			</p>
			<p>
				<input type="text" class="regular-text" name="site_shell_announcement_cta" value="<?php echo esc_attr( (string) ( $site_shell['announcement_cta'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'CTA text', 'kitchen-configurator-pro' ); ?>" />
			</p>
			<p>
				<input type="url" class="large-text" name="site_shell_announcement_url" value="<?php echo esc_attr( (string) ( $site_shell['announcement_url'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'CTA URL', 'kitchen-configurator-pro' ); ?>" />
			</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="kcp-shell-corporate-url"><?php esc_html_e( 'Logo link URL', 'kitchen-configurator-pro' ); ?></label></th>
		<td><input type="url" class="large-text" id="kcp-shell-corporate-url" name="site_shell_corporate_url" value="<?php echo esc_attr( (string) ( $site_shell['corporate_url'] ?? '' ) ); ?>" /></td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Custom logo', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<?php
			$name     = 'site_shell_logo_url';
			$value    = (string) ( $site_shell['logo_url'] ?? '' );
			$id       = 'kcp-shell-logo-url';
			$modifier = 'large';
			require KCP_PLUGIN_DIR . 'templates/admin/partials/image-picker-field.php';
			?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Dark mode toggle', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="site_shell_show_theme_toggle" value="1" <?php checked( ! empty( $site_shell['show_theme_toggle'] ) ); ?> />
				<?php esc_html_e( 'Show theme switch in subheader', 'kitchen-configurator-pro' ); ?>
			</label>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Payment icons', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<?php foreach ( $available_icons as $icon_key => $icon_label ) : ?>
				<label style="display:inline-block;margin-right:1rem;">
					<input type="checkbox" name="site_shell_payment_icons[]" value="<?php echo esc_attr( $icon_key ); ?>" <?php checked( in_array( $icon_key, $payment_icons, true ) ); ?> />
					<?php echo esc_html( $icon_label ); ?>
				</label>
			<?php endforeach; ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Dynamic category fallbacks', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<p>
				<label for="kcp-shell-webshop-cat"><?php esc_html_e( 'Webshop parent category slug', 'kitchen-configurator-pro' ); ?></label><br />
				<input type="text" class="regular-text" id="kcp-shell-webshop-cat" name="site_shell_webshop_category_slug" value="<?php echo esc_attr( (string) ( $site_shell['webshop_category_slug'] ?? '' ) ); ?>" />
			</p>
			<p>
				<label for="kcp-shell-opstellingen-cat"><?php esc_html_e( 'Opstellingen parent category slug', 'kitchen-configurator-pro' ); ?></label><br />
				<input type="text" class="regular-text" id="kcp-shell-opstellingen-cat" name="site_shell_opstellingen_category_slug" value="<?php echo esc_attr( (string) ( $site_shell['opstellingen_category_slug'] ?? '' ) ); ?>" />
			</p>
			<p class="description"><?php esc_html_e( 'Used when no footer/header menu is assigned. Leave empty to use top-level WooCommerce categories.', 'kitchen-configurator-pro' ); ?></p>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Footer column titles', 'kitchen-configurator-pro' ); ?></th>
		<td>
			<?php foreach ( array( 1, 2, 3, 4 ) as $col ) : ?>
				<p>
					<input type="text" class="regular-text" name="site_shell_footer_title_<?php echo (int) $col; ?>" value="<?php echo esc_attr( (string) ( $footer_titles[ 'col_' . $col ] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Column %d title', 'kitchen-configurator-pro' ), $col ) ); ?>" />
				</p>
			<?php endforeach; ?>
		</td>
	</tr>
</table>

<h3><?php esc_html_e( 'Contact links (footer fallback)', 'kitchen-configurator-pro' ); ?></h3>
<table class="widefat striped">
	<thead><tr><th><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></th><th><?php esc_html_e( 'URL', 'kitchen-configurator-pro' ); ?></th><th><?php esc_html_e( 'Icon key', 'kitchen-configurator-pro' ); ?></th></tr></thead>
	<tbody>
		<?php foreach ( $contact_links as $row ) : ?>
			<tr>
				<td><input type="text" class="large-text" name="site_shell_contact_label[]" value="<?php echo esc_attr( (string) ( $row['label'] ?? '' ) ); ?>" /></td>
				<td><input type="url" class="large-text" name="site_shell_contact_url[]" value="<?php echo esc_attr( (string) ( $row['url'] ?? '' ) ); ?>" /></td>
				<td><input type="text" class="regular-text" name="site_shell_contact_icon[]" value="<?php echo esc_attr( (string) ( $row['icon'] ?? '' ) ); ?>" placeholder="whatsapp, phone, mail, location" /></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<td><input type="text" class="large-text" name="site_shell_contact_label[]" value="" /></td>
			<td><input type="url" class="large-text" name="site_shell_contact_url[]" value="" /></td>
			<td><input type="text" class="regular-text" name="site_shell_contact_icon[]" value="" placeholder="whatsapp, phone, mail, location" /></td>
		</tr>
	</tbody>
</table>

<h3><?php esc_html_e( 'Trust badges', 'kitchen-configurator-pro' ); ?></h3>
<table class="widefat striped">
	<thead><tr><th><?php esc_html_e( 'Label', 'kitchen-configurator-pro' ); ?></th><th><?php esc_html_e( 'Icon key', 'kitchen-configurator-pro' ); ?></th></tr></thead>
	<tbody>
		<?php foreach ( $trust_badges as $row ) : ?>
			<tr>
				<td><input type="text" class="large-text" name="site_shell_trust_label[]" value="<?php echo esc_attr( (string) ( $row['label'] ?? '' ) ); ?>" /></td>
				<td><input type="text" class="regular-text" name="site_shell_trust_icon[]" value="<?php echo esc_attr( (string) ( $row['icon'] ?? 'default' ) ); ?>" /></td>
			</tr>
		<?php endforeach; ?>
		<tr>
			<td><input type="text" class="large-text" name="site_shell_trust_label[]" value="" /></td>
			<td><input type="text" class="regular-text" name="site_shell_trust_icon[]" value="default" /></td>
		</tr>
	</tbody>
</table>

<h3><?php esc_html_e( 'Legal links', 'kitchen-configurator-pro' ); ?></h3>
<p class="description">
	<?php
	printf(
		/* translators: %s: WordPress menus admin URL */
		esc_html__( 'Manage footer legal links under %1$sAppearance → Menus%2$s and assign a menu to %3$sConfigurator footer (legal links)%4$s.', 'kitchen-configurator-pro' ),
		'<a href="' . esc_url( $menus_admin_url ) . '">',
		'</a>',
		'<strong>',
		'</strong>'
	);
	?>
</p>
<p class="description">
	<?php esc_html_e( 'Example items: keukenkastenfabriek, algemene voorwaarden, garantie voorwaarden, privacy & cookies. If no menu is assigned, the built-in defaults are used.', 'kitchen-configurator-pro' ); ?>
</p>
