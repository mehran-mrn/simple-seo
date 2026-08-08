<?php
/**
 * Configurable business and page structured data.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Schema {
	public function hooks(): void {
		if ( ! Plugin::competing_plugin_active() ) {
			add_action( 'wp_head', array( $this, 'render' ), 20 );
		}
	}

	public function render(): void {
		if ( is_admin() || is_feed() || is_404() || is_search() ) {
			return;
		}
		$data        = Business::get();
		$home        = home_url( '/' );
		$business_id = $home . '#business';
		$website_id  = $home . '#website';
		$meta        = new Meta();
		$page_url    = $meta->canonical();
		$graph       = array();
		$schema_type = sanitize_text_field( (string) $data['schema_type'] );
		$schema_type = $schema_type ? $schema_type : 'Organization';
		$schema_type = apply_filters( 'mrn_seo_business_schema_type', $schema_type, $data );

		$business = array(
			'@type' => $schema_type,
			'@id'   => $business_id,
			'name'  => $data['name'],
			'url'   => $home,
		);
		$optional = array(
			'legalName'  => $data['legal_name'],
			'logo'       => Site::logo_url(),
			'image'      => Site::image_url(),
			'telephone'  => $data['phone_e164'],
			'priceRange' => $data['price_range'],
		);
		foreach ( $optional as $property => $value ) {
			if ( ! empty( $value ) ) {
				$business[ $property ] = $value;
			}
		}
		$service_areas = Business::service_areas();
		if ( $service_areas ) {
			$area_type              = sanitize_text_field( (string) $data['area_schema_type'] );
			$business['areaServed'] = array_map(
				static fn( string $area ): array => array(
					'@type' => $area_type ? $area_type : 'Place',
					'name'  => $area,
				),
				$service_areas
			);
		}
		if ( $data['email'] ) {
			$business['email'] = $data['email'];
		}
		if ( $data['street_address'] ) {
			$business['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $data['street_address'],
				'addressLocality' => $data['address_locality'],
				'addressRegion'   => $data['address_region'],
				'postalCode'      => $data['postal_code'],
				'addressCountry'  => $data['address_country'],
			);
		}
		if ( (float) $data['latitude'] && (float) $data['longitude'] ) {
			$business['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $data['latitude'],
				'longitude' => (float) $data['longitude'],
			);
			if ( Business::directions_url() ) {
				$business['hasMap'] = Business::directions_url();
			}
		}
		if ( $data['manager'] ) {
			$manager_property              = sanitize_key( (string) $data['manager_schema_property'] );
			$manager_property              = $manager_property ? $manager_property : 'founder';
			$business[ $manager_property ] = array(
				'@type'    => 'Person',
				'name'     => $data['manager'],
				'jobTitle' => $data['manager_job_title'],
			);
		}
		$profiles = Business::list_setting( 'social_profiles' );
		if ( $data['google_business_url'] ) {
			$profiles[] = $data['google_business_url'];
		}
		if ( $profiles ) {
			$business['sameAs'] = array_values( array_unique( $profiles ) );
		}
		$graph[] = $business;
		$graph[] = array(
			'@type'      => 'WebSite',
			'@id'        => $website_id,
			'url'        => $home,
			'name'       => $data['name'],
			'inLanguage' => Site::language(),
			'publisher'  => array( '@id' => $business_id ),
		);
		$graph[] = array(
			'@type'       => 'WebPage',
			'@id'         => $page_url . '#webpage',
			'url'         => $page_url,
			'name'        => wp_get_document_title(),
			'description' => $meta->description(),
			'isPartOf'    => array( '@id' => $website_id ),
			'about'       => array( '@id' => $business_id ),
			'inLanguage'  => Site::language(),
		);

		if ( ! is_front_page() ) {
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $page_url . '#breadcrumb',
				'itemListElement' => array(
					array(
						'@type'    => 'ListItem',
						'position' => 1,
						'name'     => (string) $data['home_label'],
						'item'     => $home,
					),
					array(
						'@type'    => 'ListItem',
						'position' => 2,
						'name'     => get_the_title(),
						'item'     => $page_url,
					),
				),
			);
		}

		$faq = $this->faq_items( (string) $data['faq_items'] );
		if ( $faq && is_page( sanitize_title( (string) $data['faq_page_slug'] ) ) ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $page_url . '#faq',
				'mainEntity' => $faq,
			);
		}
		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/** @return array<int, array<string, mixed>> */
	private function faq_items( string $raw ): array {
		$items = array();
		$lines = preg_split( '/\R/', $raw );
		foreach ( $lines ? $lines : array() as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			if ( empty( $parts[0] ) || empty( $parts[1] ) ) {
				continue;
			}
			$items[] = array(
				'@type'          => 'Question',
				'name'           => $parts[0],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $parts[1],
				),
			);
		}
		return $items;
	}
}
