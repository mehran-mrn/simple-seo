<?php
/**
 * Titles, canonical URLs, social metadata, and indexing directives.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Manages metadata and crawler directives for specialized and generic sites.
 */
final class Meta {
	/**
	 * WDS page-specific search snippets.
	 *
	 * @var array<string, array{title:string,description:string}>
	 */
	private array $pages = array(
		'packages'                     => array(
			'title'       => 'Driving Lessons & BDE Course Packages | WDS',
			'description' => 'Compare MTO-approved BDE courses, individual driving lessons, and G2 or G road-test packages from WDS Driving School.',
		),
		'faqs'                         => array(
			'title'       => 'Driving School FAQs | WDS Driving School',
			'description' => 'Answers about Ontario G1, G2 and G training, BDE certification, road tests, insurance discounts, scheduling, and lesson vehicles.',
		),
		'wds-driving-school-locations' => array(
			'title'       => 'Driving School in Richmond Hill | WDS',
			'description' => 'Visit WDS Driving School at 60 Granton Dr in Richmond Hill. Serving Richmond Hill, Newmarket, and Scarborough.',
		),
		'contact-us'                   => array(
			'title'       => 'Contact WDS Driving School | Richmond Hill',
			'description' => 'Call, text, email, or send a message to WDS Driving School for course, lesson, road-test, and scheduling support.',
		),
		'register'                     => array(
			'title'       => 'Register for Driving Lessons | WDS Driving School',
			'description' => 'Register for an online or in-class BDE course, driving lesson package, or Ontario road-test preparation with WDS.',
		),
		'privacy-policy'               => array(
			'title'       => 'Privacy Policy | WDS Driving School',
			'description' => 'Learn how WDS Driving School collects, uses, stores, and protects information submitted through this website.',
		),
	);

	/**
	 * Register metadata hooks when no competing plugin is active.
	 */
	public function hooks(): void {
		if ( Plugin::competing_plugin_active() ) {
			return;
		}
		remove_action( 'wp_head', 'rel_canonical' );
		add_filter( 'pre_get_document_title', array( $this, 'title' ), 30 );
		add_filter( 'wp_robots', array( $this, 'robots' ), 30 );
		add_action( 'wp_head', array( $this, 'head' ), 2 );
	}

	/**
	 * Return the optimized document title.
	 *
	 * @param string $title Existing document title.
	 */
	public function title( string $title ): string {
		if ( is_admin() || is_feed() ) {
			return $title;
		}
		if ( Site::is_zarsam() ) {
			if ( is_front_page() ) {
				return 'فروشگاه طلا و جواهر زرسام | قیمت زنده طلا و سکه';
			}
			if ( function_exists( 'is_shop' ) && is_shop() ) {
				return 'فروشگاه طلا و جواهر زرسام | خرید آنلاین طلا';
			}
			if ( is_home() ) {
				return 'مجله زرسام | راهنمای خرید و نگهداری طلا';
			}
			return $title;
		}
		if ( ! Site::is_wds() ) {
			return $title;
		}
		if ( is_front_page() ) {
			return 'MTO-Approved Driving School in Richmond Hill | WDS';
		}
		$slug = $this->slug();
		return $this->pages[ $slug ]['title'] ?? $title;
	}

	/**
	 * Return crawler directives, including hostname-based staging protection.
	 *
	 * @param array<string, bool|string> $robots WordPress robots directives.
	 * @return array<string, bool|string>
	 */
	public function robots( array $robots ): array {
		$wds_thin_archive = Site::is_wds() && ( is_date() || is_author() || is_tag() || is_category() );
		if ( Site::is_staging() || is_search() || is_404() || $wds_thin_archive ) {
			return array(
				'noindex'  => true,
				'nofollow' => Site::is_staging(),
			);
		}
		$robots['index']             = true;
		$robots['follow']            = true;
		$robots['max-image-preview'] = 'large';
		$robots['max-snippet']       = '-1';
		$robots['max-video-preview'] = '-1';
		unset( $robots['noindex'], $robots['nofollow'] );
		return $robots;
	}

	/**
	 * Render canonical, description, Open Graph, and Twitter metadata.
	 */
	public function head(): void {
		if ( is_feed() || is_404() || is_search() ) {
			return;
		}
		$description = $this->description();
		$canonical   = $this->canonical();
		$image       = Site::image_url();
		$type        = is_singular( array( 'post', 'product' ) ) ? 'article' : 'website';
		$title       = wp_get_document_title();

		echo "\n<!-- MRN SEO Profiles -->\n";
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
		}
		printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( Site::og_locale() ) );
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
		if ( $image ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		}
		printf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
		if ( $image ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
		}
	}

	/**
	 * Return the optimized meta description for the current request.
	 */
	public function description(): string {
		if ( Site::is_zarsam() ) {
			if ( is_front_page() ) {
				return 'فروشگاه طلا و جواهر زرسام به مدیریت سهیل قربانعلی پور؛ خرید طلای ۱۸ عیار و سکه با نمایش قیمت زنده و مشاوره مستقیم: ۰۹۱۲۰۳۹۱۱۳۶.';
			}
			if ( function_exists( 'is_shop' ) && is_shop() ) {
				return 'خرید آنلاین طلا و جواهر منتخب زرسام با قیمت‌گذاری شفاف، تضمین اصالت و پشتیبانی مستقیم.';
			}
			if ( is_home() ) {
				return 'راهنمای انتخاب، خرید و نگهداری طلا و جواهر در مجله زرسام.';
			}
			if ( is_singular() ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( get_queried_object_id() ) );
				if ( $excerpt ) {
					return wp_html_excerpt( $excerpt, 155, '…' );
				}
			}
			return 'فروشگاه طلا و جواهر زرسام؛ خرید مطمئن طلا و سکه با قیمت‌گذاری شفاف و پشتیبانی مستقیم.';
		}
		if ( ! Site::is_wds() ) {
			return $this->generic_description();
		}
		if ( is_front_page() ) {
			return 'MTO-approved driving school offering online, in-class, and in-car BDE training in Richmond Hill, Newmarket, and Scarborough.';
		}
		$slug = $this->slug();
		if ( isset( $this->pages[ $slug ] ) ) {
			return $this->pages[ $slug ]['description'];
		}
		return 'Professional MTO-approved driver education and in-car training from WDS Driving School.';
	}

	/** Build a useful description from standard WordPress content. */
	private function generic_description(): string {
		if ( is_singular() ) {
			$post_id     = get_queried_object_id();
			$description = wp_strip_all_tags( (string) get_the_excerpt( $post_id ) );
			if ( ! $description ) {
				$description = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
			}
			if ( $description ) {
				return wp_html_excerpt( preg_replace( '/\s+/', ' ', $description ), 160, '…' );
			}
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$description = wp_strip_all_tags( (string) term_description() );
			if ( $description ) {
				return wp_html_excerpt( preg_replace( '/\s+/', ' ', $description ), 160, '…' );
			}
		}

		$tagline = wp_strip_all_tags( (string) get_bloginfo( 'description' ) );
		return $tagline ?: (string) get_bloginfo( 'name' );
	}

	/**
	 * Return the canonical URL for the current request.
	 */
	public function canonical(): string {
		if ( is_paged() ) {
			return (string) get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
		}
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_singular() ) {
			return (string) get_permalink();
		}
		if ( is_home() && get_option( 'page_for_posts' ) ) {
			return (string) get_permalink( (int) get_option( 'page_for_posts' ) );
		}
		if ( is_home() ) {
			return home_url( '/' );
		}
		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
			return (string) get_post_type_archive_link( (string) $post_type );
		}
		if ( is_category() || is_tag() || is_tax() ) {
			$link = get_term_link( get_queried_object() );
			return is_wp_error( $link ) ? '' : (string) $link;
		}
		if ( is_author() ) {
			return (string) get_author_posts_url( (int) get_queried_object_id() );
		}
		if ( is_day() ) {
			return (string) get_day_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ), (int) get_query_var( 'day' ) );
		}
		if ( is_month() ) {
			return (string) get_month_link( (int) get_query_var( 'year' ), (int) get_query_var( 'monthnum' ) );
		}
		if ( is_year() ) {
			return (string) get_year_link( (int) get_query_var( 'year' ) );
		}
		return home_url( '/' );
	}

	/**
	 * Return the current singular slug.
	 */
	private function slug(): string {
		return is_singular() ? (string) get_post_field( 'post_name', get_queried_object_id() ) : '';
	}

}
