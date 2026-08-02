<?php
/**
 * Plugin composition root.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin's small set of independent services.
 */
final class Plugin {
	private const VERSION_OPTION = 'mrn_seo_profiles_version';

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Whether hooks have already been registered.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Return the plugin singleton.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all WordPress hooks once.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		( new Settings() )->hooks();
		( new Meta() )->hooks();
		( new Schema() )->hooks();
		( new Sitemap() )->hooks();
		if ( Site::is_wds() ) {
			( new Redirects() )->hooks();
		}
		add_action( 'init', array( self::class, 'maybe_upgrade' ), 999 );
	}

	/**
	 * Install defaults and sitemap routes.
	 */
	public static function activate(): void {
		if ( false === get_option( Business::OPTION, false ) ) {
			add_option( Business::OPTION, Business::defaults(), '', false );
		}
		Sitemap::add_rewrite_rules();
		Sitemap::clear_cache();
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, MRN_WDS_SEO_VERSION, false );
	}

	/** Run one-time migrations after a plugin update. */
	public static function maybe_upgrade(): void {
		if ( MRN_WDS_SEO_VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		self::migrate_unknown_site_defaults();
		Sitemap::add_rewrite_rules();
		Sitemap::clear_cache();
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, MRN_WDS_SEO_VERSION, false );
	}

	/** Replace legacy WDS defaults accidentally stored on an unrelated site. */
	private static function migrate_unknown_site_defaults(): void {
		if ( Site::is_wds() || Site::is_zarsam() ) {
			return;
		}

		$current = get_option( Business::OPTION, array() );
		if (
			is_array( $current )
			&& 'WDS Driving School' === ( $current['name'] ?? '' )
			&& 'wdsdrivingschool@gmail.com' === ( $current['email'] ?? '' )
		) {
			update_option( Business::OPTION, Business::defaults(), false );
		}
	}

	/**
	 * Remove cached rewrite rules on deactivation.
	 */
	public static function deactivate(): void {
		Sitemap::clear_cache();
		flush_rewrite_rules( false );
	}

	/**
	 * Detect full SEO plugins that would emit duplicate markup.
	 */
	public static function competing_plugin_active(): bool {
		return defined( 'WPSEO_VERSION' )
			|| defined( 'RANK_MATH_VERSION' )
			|| defined( 'AIOSEO_VERSION' )
			|| defined( 'SEOPRESS_VERSION' )
			|| class_exists( 'All_in_One_SEO_Pack' );
	}
}
