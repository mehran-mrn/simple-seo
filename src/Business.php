<?php
/**
 * Central, user-configurable site data.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

/** Provides the single source of truth for SEO settings. */
final class Business {
	public const OPTION = 'mrn_seo_settings';

	/** @return array<string, mixed> */
	public static function defaults(): array {
		$locale_parts = preg_split( '/[-_]/', get_locale() );
		$country      = is_array( $locale_parts ) && isset( $locale_parts[1] ) ? strtoupper( $locale_parts[1] ) : '';
		$name         = (string) get_bloginfo( 'name' );

		return array(
			'name'                        => $name,
			'legal_name'                  => $name,
			'manager'                     => '',
			'manager_job_title'           => 'Owner',
			'manager_schema_property'     => 'founder',
			'phone'                       => '',
			'phone_e164'                  => '',
			'email'                       => (string) get_option( 'admin_email', '' ),
			'street_address'              => '',
			'address_locality'            => '',
			'address_region'              => '',
			'postal_code'                 => '',
			'address_country'             => $country,
			'latitude'                    => '0',
			'longitude'                   => '0',
			'service_areas'               => '',
			'google_business_url'         => '',
			'price_range'                 => '',
			'schema_type'                 => 'Organization',
			'area_schema_type'            => 'Place',
			'language'                    => (string) get_bloginfo( 'language' ),
			'og_locale'                   => str_replace( '-', '_', (string) get_bloginfo( 'language' ) ),
			'home_label'                  => 'Home',
			'logo_url'                    => '',
			'social_image_url'            => '',
			'social_profiles'             => '',
			'home_title'                  => '',
			'home_description'            => '',
			'default_description'         => '',
			'page_metadata'               => '',
			'redirects'                   => '',
			'faq_page_slug'               => '',
			'faq_items'                   => '',
			'staging_host_patterns'       => 'staging,localhost,.test,.local',
			'noindex_search'              => '1',
			'noindex_404'                 => '1',
			'noindex_date_archives'       => '0',
			'noindex_author_archives'     => '0',
			'noindex_category_archives'   => '0',
			'noindex_tag_archives'        => '0',
			'sitemap_excluded_post_types' => '',
			'sitemap_excluded_taxonomies' => '',
			'sitemap_include_authors'     => '1',
			'sitemap_max_urls'            => '1000',
		);
	}

	/** @return array<string, mixed> */
	public static function get(): array {
		$defaults = self::defaults();
		$value    = get_option( self::OPTION, array() );
		$value    = is_array( $value ) ? array_intersect_key( $value, $defaults ) : array();
		return wp_parse_args( $value, $defaults );
	}

	/** Return a comma-separated setting as a normalized list. */
	public static function list_setting( string $key ): array {
		$data = self::get();
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) ( $data[ $key ] ?? '' ) ) ) ) );
	}

	public static function full_address(): string {
		$data   = self::get();
		$region = trim( (string) $data['address_region'] . ' ' . (string) $data['postal_code'] );
		return implode( ', ', array_filter( array( $data['street_address'], $data['address_locality'], $region, $data['address_country'] ) ) );
	}

	public static function directions_url(): string {
		$address = self::full_address();
		return $address ? add_query_arg(
			array(
				'api'         => '1',
				'destination' => $address,
			),
			'https://www.google.com/maps/dir/'
		) : '';
	}

	public static function map_embed_url(): string {
		$data = self::get();
		if ( ! (float) $data['latitude'] || ! (float) $data['longitude'] ) {
			return '';
		}
		return add_query_arg(
			array(
				'q'      => $data['latitude'] . ',' . $data['longitude'],
				'z'      => '16',
				'output' => 'embed',
			),
			'https://www.google.com/maps'
		);
	}

	/** @return array<int, string> */
	public static function service_areas(): array {
		return self::list_setting( 'service_areas' );
	}
}
