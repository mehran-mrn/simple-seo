<?php
/**
 * Central business data.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the single source of truth for public business information.
 */
final class Business {
	public const OPTION = 'mrn_wds_seo_settings';

	/**
	 * Default, production-safe business data.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		if ( Site::is_zarsam() ) {
			return array(
				'name'                => 'فروشگاه طلا و جواهر زرسام',
				'legal_name'          => 'زرسام',
				'manager'             => 'سهیل قربانعلی پور',
				'phone'               => '۰۹۱۲۰۳۹۱۱۳۶',
				'phone_e164'          => '+989120391136',
				'email'               => '',
				'street_address'      => '',
				'address_locality'    => '',
				'address_region'      => '',
				'postal_code'         => '',
				'address_country'     => 'IR',
				'latitude'            => '0',
				'longitude'           => '0',
				'service_areas'       => 'ایران',
				'google_business_url' => '',
				'google_place_id'     => '',
				'price_range'         => '$$$',
			);
		}

		if ( Site::is_wds() ) {
			return array(
				'name'                => 'WDS Driving School',
				'legal_name'          => 'WDS Driving School',
				'manager'             => '',
				'phone'               => '+1 (647) 606-1213',
				'phone_e164'          => '+16476061213',
				'email'               => 'wdsdrivingschool@gmail.com',
				'street_address'      => '60 Granton Dr',
				'address_locality'    => 'Richmond Hill',
				'address_region'      => 'ON',
				'postal_code'         => 'L4B 2N6',
				'address_country'     => 'CA',
				'latitude'            => '43.8569221',
				'longitude'           => '-79.3886688',
				'service_areas'       => 'Richmond Hill, Newmarket, Scarborough',
				'google_business_url' => 'https://share.google/drWhtOYaoG6KalWJF',
				'google_place_id'     => '/g/11z7lgm34p',
				'price_range'         => '$$',
			);
		}

		$locale_parts = preg_split( '/[-_]/', get_locale() );
		$country      = is_array( $locale_parts ) && isset( $locale_parts[1] ) ? strtoupper( $locale_parts[1] ) : '';
		$name         = (string) get_bloginfo( 'name' );

		return array(
			'name'                => $name,
			'legal_name'          => $name,
			'manager'             => '',
			'phone'               => '',
			'phone_e164'          => '',
			'email'               => (string) get_option( 'admin_email', '' ),
			'street_address'      => '',
			'address_locality'    => '',
			'address_region'      => '',
			'postal_code'         => '',
			'address_country'     => $country,
			'latitude'            => '0',
			'longitude'           => '0',
			'service_areas'       => '',
			'google_business_url' => '',
			'google_place_id'     => '',
			'price_range'         => '',
		);
	}

	/**
	 * Return settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$defaults = self::defaults();
		$value    = get_option( self::OPTION, array() );
		$value    = is_array( $value ) ? array_intersect_key( $value, $defaults ) : array();
		return wp_parse_args( $value, $defaults );
	}

	/**
	 * Return the configured postal address.
	 */
	public static function full_address(): string {
		$data = self::get();
		$region = trim( (string) $data['address_region'] . ' ' . (string) $data['postal_code'] );
		return implode(
			', ',
			array_filter(
				array( $data['street_address'], $data['address_locality'], $region, $data['address_country'] )
			)
		);
	}

	/**
	 * Return a stable Google Maps directions URL.
	 */
	public static function directions_url(): string {
		$address = self::full_address();
		if ( ! $address ) {
			return '';
		}

		return add_query_arg(
			array(
				'api'         => '1',
				'destination' => $address,
				'travelmode'  => 'driving',
			),
			'https://www.google.com/maps/dir/'
		);
	}

	/**
	 * Return the keyless Google Maps embed query URL.
	 */
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

	/**
	 * Return service areas as a clean list.
	 *
	 * @return array<int, string>
	 */
	public static function service_areas(): array {
		$data = self::get();
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $data['service_areas'] ) ) ) );
	}
}
