<?php
/**
 * Host-aware SEO profile selection.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/** Select a safe SEO profile without treating unknown sites as WDS. */
final class Site {
	/** Detect non-production hostnames that must remain noindex. */
	public static function is_staging(): bool {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return str_starts_with( $host, 'new.' ) || str_contains( $host, 'staging' ) || str_contains( $host, 'localhost' );
	}

	/** Return true when the WDS child theme is active. */
	public static function is_wds(): bool {
		$stylesheet = function_exists( 'get_stylesheet' ) ? (string) get_stylesheet() : '';
		$is_wds     = 'mrn-child-wds' === $stylesheet;

		/**
		 * Allow installations with a renamed WDS theme to opt into the legacy profile.
		 *
		 * @param bool   $is_wds     Whether the WDS profile should be used.
		 * @param string $stylesheet Active stylesheet slug.
		 */
		return (bool) apply_filters( 'mrn_seo_is_wds', $is_wds, $stylesheet );
	}

	/** Return true on the Zarsam storefront. */
	public static function is_zarsam(): bool {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return 'zarsamgold.ir' === $host || str_ends_with( $host, '.zarsamgold.ir' );
	}

	/** Return the public content language. */
	public static function language(): string {
		if ( self::is_zarsam() ) {
			return 'fa-IR';
		}
		if ( self::is_wds() ) {
			return 'en-CA';
		}

		$language = (string) get_bloginfo( 'language' );
		return $language ?: str_replace( '_', '-', get_locale() );
	}

	/** Return the Open Graph locale. */
	public static function og_locale(): string {
		if ( self::is_zarsam() ) {
			return 'fa_IR';
		}
		if ( self::is_wds() ) {
			return 'en_CA';
		}

		return str_replace( '-', '_', get_locale() );
	}

	/** Return a profile-specific or WordPress-managed social image. */
	public static function image_url(): string {
		if ( self::is_zarsam() ) {
			return get_stylesheet_directory_uri() . '/assets/images/zarsam-hero.webp';
		}
		if ( self::is_wds() ) {
			return get_stylesheet_directory_uri() . '/assets/images/about.webp';
		}
		if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
			return (string) get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
		}

		return (string) get_site_icon_url( 512 );
	}

	/** Return a profile-specific or WordPress-managed logo. */
	public static function logo_url(): string {
		if ( self::is_zarsam() ) {
			return get_stylesheet_directory_uri() . '/assets/images/zarsam-logo-official.webp';
		}
		if ( self::is_wds() ) {
			return get_stylesheet_directory_uri() . '/assets/images/logo.webp';
		}

		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
		$custom_logo    = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
		return $custom_logo ? (string) $custom_logo : (string) get_site_icon_url( 512 );
	}
}
