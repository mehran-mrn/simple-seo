<?php
/**
 * Plugin Name:       MRN SEO
 * Plugin URI:        https://mehranmarandi.ir
 * Description:       Lightweight technical and local SEO with configurable metadata, schema, redirects, and XML sitemaps.
 * Version:           2.1.1
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Mehran Marandi
 * Author URI:        https://mehranmarandi.ir
 * Text Domain:       mrn-seo
 * License:           GPL-2.0-or-later
 *
 * @package MRN\SEO
 */

defined( 'ABSPATH' ) || exit;

define( 'MRN_SEO_VERSION', '2.1.1' );
define( 'MRN_SEO_FILE', __FILE__ );
define( 'MRN_SEO_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'MRN\\SEO\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$file = MRN_SEO_PATH . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( \MRN\SEO\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \MRN\SEO\Plugin::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		\MRN\SEO\Plugin::instance()->boot();
	},
	20
);

/** Return normalized business and SEO settings for theme integrations. */
function mrn_seo_settings(): array {
	return \MRN\SEO\Business::get();
}

/** Return the configured Google Maps directions URL. */
function mrn_seo_directions_url(): string {
	return \MRN\SEO\Business::directions_url();
}

/** Return the configured embeddable Google Map URL. */
function mrn_seo_map_embed_url(): string {
	return \MRN\SEO\Business::map_embed_url();
}
