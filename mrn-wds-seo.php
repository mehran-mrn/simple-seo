<?php
/**
 * Plugin Name:       MRN SEO Profiles
 * Plugin URI:        https://mehranmarandi.ir
 * Description:       Lightweight technical and local SEO with universal, automatic XML sitemaps.
 * Version:           1.2.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Mehran Marandi
 * Author URI:        https://mehranmarandi.ir
 * Text Domain:       mrn-wds-seo
 * License:           GPL-2.0-or-later
 *
 * @package MRN\WDS\SEO
 */

defined( 'ABSPATH' ) || exit;

define( 'MRN_WDS_SEO_VERSION', '1.2.0' );
define( 'MRN_WDS_SEO_FILE', __FILE__ );
define( 'MRN_WDS_SEO_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'MRN\\WDS\\SEO\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$file = MRN_WDS_SEO_PATH . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( \MRN\WDS\SEO\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MRN\WDS\SEO\Plugin::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\MRN\WDS\SEO\Plugin::instance()->boot();
	},
	20
);

/**
 * Public theme integration: return normalized business settings.
 *
 * @return array<string, mixed>
 */
function mrn_wds_seo_business(): array {
	return \MRN\WDS\SEO\Business::get();
}

/**
 * Public theme integration: return the Google Maps directions URL.
 */
function mrn_wds_seo_directions_url(): string {
	return \MRN\WDS\SEO\Business::directions_url();
}

/**
 * Public theme integration: return the embeddable Google Map URL.
 */
function mrn_wds_seo_map_embed_url(): string {
	return \MRN\WDS\SEO\Business::map_embed_url();
}
