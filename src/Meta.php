<?php
/**
 * Titles, canonical URLs, social metadata, and indexing directives.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Meta {
	public function hooks(): void {
		if ( Plugin::competing_plugin_active() ) {
			return;
		}
		remove_action( 'wp_head', 'rel_canonical' );
		add_filter( 'pre_get_document_title', array( $this, 'title' ), 30 );
		add_filter( 'wp_robots', array( $this, 'robots' ), 30 );
		add_action( 'wp_head', array( $this, 'head' ), 2 );
	}

	public function title( string $title ): string {
		if ( is_admin() || is_feed() ) {
			return $title;
		}
		$data = Business::get();
		if ( is_front_page() && $data['home_title'] ) {
			return (string) $data['home_title'];
		}
		$rule = $this->page_rule();
		return $rule && $rule['title'] ? $rule['title'] : $title;
	}

	/** @param array<string, bool|string> $robots */
	public function robots( array $robots ): array {
		$data    = Business::get();
		$noindex = Site::is_staging()
			|| ( is_search() && '1' === $data['noindex_search'] )
			|| ( is_404() && '1' === $data['noindex_404'] )
			|| ( is_date() && '1' === $data['noindex_date_archives'] )
			|| ( is_author() && '1' === $data['noindex_author_archives'] )
			|| ( is_category() && '1' === $data['noindex_category_archives'] )
			|| ( is_tag() && '1' === $data['noindex_tag_archives'] );
		if ( $noindex ) {
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

	public function head(): void {
		if ( is_feed() || is_404() || is_search() ) {
			return;
		}
		$description = $this->description();
		$canonical   = $this->canonical();
		$image       = Site::image_url();
		$type        = is_singular( array( 'post', 'product' ) ) ? 'article' : 'website';
		$title       = wp_get_document_title();
		echo "\n<!-- MRN SEO -->\n";
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

	public function description(): string {
		$data = Business::get();
		if ( is_front_page() && $data['home_description'] ) {
			return (string) $data['home_description'];
		}
		$rule = $this->page_rule();
		if ( $rule && $rule['description'] ) {
			return $rule['description'];
		}
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
		if ( $data['default_description'] ) {
			return (string) $data['default_description'];
		}
		$tagline = wp_strip_all_tags( (string) get_bloginfo( 'description' ) );
		return $tagline ? $tagline : (string) get_bloginfo( 'name' );
	}

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

	/** @return array{slug:string,title:string,description:string}|null */
	private function page_rule(): ?array {
		if ( is_home() ) {
			$page_id = (int) get_option( 'page_for_posts' );
		} elseif ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'wc_get_page_id' ) ) {
			$page_id = (int) wc_get_page_id( 'shop' );
		} elseif ( is_singular() ) {
			$page_id = get_queried_object_id();
		} else {
			return null;
		}
		if ( $page_id < 1 ) {
			return null;
		}
		$current_slug = (string) get_post_field( 'post_name', $page_id );
		$data         = Business::get();
		$lines        = preg_split( '/\R/', (string) $data['page_metadata'] );
		foreach ( $lines ? $lines : array() as $line ) {
			$parts = array_map( 'trim', explode( '::', $line, 3 ) );
			if ( ( $parts[0] ?? '' ) === $current_slug ) {
				return array(
					'slug'        => $current_slug,
					'title'       => (string) ( $parts[1] ?? '' ),
					'description' => (string) ( $parts[2] ?? '' ),
				);
			}
		}
		return null;
	}
}
