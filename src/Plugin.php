<?php
/**
 * Plugin composition root.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private const VERSION_OPTION   = 'mrn_seo_version';
	private static ?self $instance = null;
	private bool $booted           = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;
		( new Settings() )->hooks();
		( new Meta() )->hooks();
		( new Schema() )->hooks();
		( new Sitemap() )->hooks();
		( new Redirects() )->hooks();
		add_action( 'init', array( self::class, 'maybe_upgrade' ), 999 );
	}

	public static function activate(): void {
		if ( false === get_option( Business::OPTION, false ) ) {
			add_option( Business::OPTION, Business::defaults(), '', false );
		}
		self::refresh_runtime();
	}

	public static function maybe_upgrade(): void {
		if ( MRN_SEO_VERSION !== get_option( self::VERSION_OPTION ) ) {
			self::refresh_runtime();
		}
	}

	private static function refresh_runtime(): void {
		Sitemap::add_rewrite_rules();
		Sitemap::clear_cache();
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, MRN_SEO_VERSION, false );
	}

	public static function deactivate(): void {
		Sitemap::clear_cache();
		flush_rewrite_rules( false );
	}

	public static function competing_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| class_exists( 'All_in_One_SEO_Pack' );
	}
}
