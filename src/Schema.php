<?php
/**
 * Local business and page structured data.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Builds a small JSON-LD graph from canonical site data.
 */
final class Schema {
	/** Register schema output when no competing SEO plugin is active. */
	public function hooks(): void {
		if ( ! Plugin::competing_plugin_active() ) {
			add_action( 'wp_head', array( $this, 'render' ), 20 );
		}
	}

	/** Render business, website, page, breadcrumb, and optional FAQ schema. */
	public function render(): void {
		if ( is_admin() || is_feed() || is_404() || is_search() ) {
			return;
		}

		$data        = Business::get();
		$home        = home_url( '/' );
		$business_id = $home . '#business';
		$website_id  = $home . '#website';
		$page_url    = ( new Meta() )->canonical();
		$graph       = array();
		$schema_type = 'Organization';
		if ( Site::is_zarsam() ) {
			$schema_type = 'JewelryStore';
		} elseif ( Site::is_wds() ) {
			$schema_type = array( 'LocalBusiness', 'EducationalOrganization' );
		} elseif ( ! empty( $data['street_address'] ) || ! empty( $data['phone_e164'] ) ) {
			$schema_type = 'LocalBusiness';
		}

		/**
		 * Filter the public business schema type for custom site profiles.
		 *
		 * @param string|array<int, string> $schema_type Schema.org type or types.
		 * @param array<string, mixed>      $data        Business settings.
		 */
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
			$business['areaServed'] = array_map(
				static fn( string $area ): array => array(
					'@type' => Site::is_zarsam() ? 'Country' : 'City',
					'name'  => $area,
				),
				$service_areas
			);
		}
		if ( ! empty( $data['email'] ) ) {
			$business['email'] = $data['email'];
		}
		if ( ! empty( $data['street_address'] ) ) {
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
			$business['geo']    = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $data['latitude'],
				'longitude' => (float) $data['longitude'],
			);
			$directions_url = Business::directions_url();
			if ( $directions_url ) {
				$business['hasMap'] = $directions_url;
			}
		}
		if ( ! empty( $data['manager'] ) ) {
			$business[ Site::is_zarsam() ? 'employee' : 'founder' ] = array(
				'@type'    => 'Person',
				'name'     => $data['manager'],
				'jobTitle' => Site::is_zarsam() ? 'مدیریت' : 'Owner',
			);
		}
		if ( ! empty( $data['google_business_url'] ) ) {
			$business['sameAs'] = array( $data['google_business_url'] );
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
			'description' => ( new Meta() )->description(),
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
						'name'     => Site::is_zarsam() ? 'خانه' : __( 'Home', 'mrn-wds-seo' ),
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

		if ( Site::is_wds() && is_page( 'faqs' ) && function_exists( 'mrn_wds_faqs' ) ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $page_url . '#faq',
				'mainEntity' => array_map(
					static fn( array $faq ): array => array(
						'@type'          => 'Question',
						'name'           => $faq['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $faq['answer'],
						),
					),
					mrn_wds_faqs()
				),
			);
		}

		$payload = array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}
