<?php
/**
 * Generic site context helpers.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Site {
	public static function is_staging(): bool {
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		foreach ( Business::list_setting( 'staging_host_patterns' ) as $pattern ) {
			if ( '' !== $pattern && str_contains( $host, strtolower( $pattern ) ) ) {
				return true;
			}
		}
		return false;
	}

	public static function language(): string {
		$data = Business::get();
		return (string) ( $data['language'] ? $data['language'] : get_bloginfo( 'language' ) );
	}

	public static function og_locale(): string {
		$data = Business::get();
		return (string) ( $data['og_locale'] ? $data['og_locale'] : str_replace( '-', '_', self::language() ) );
	}

	public static function image_url(): string {
		if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
			return (string) get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
		}
		$data = Business::get();
		return (string) ( $data['social_image_url'] ? $data['social_image_url'] : get_site_icon_url( 512 ) );
	}

	public static function logo_url(): string {
		$data = Business::get();
		if ( $data['logo_url'] ) {
			return (string) $data['logo_url'];
		}
		$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
		$custom_logo    = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
		return $custom_logo ? (string) $custom_logo : (string) get_site_icon_url( 512 );
	}
}
