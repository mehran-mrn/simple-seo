<?php
/**
 * Yoast-style XML sitemap index backed by WordPress sitemap providers.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes a complete, paginated sitemap for every public WordPress site.
 */
final class Sitemap {
	private const INDEX_PATH = '/sitemap_index.xml';
	private const XSL_PATH   = '/mrn-sitemap.xsl';
	private const CACHE_KEY  = 'mrn_seo_sitemap_index';

	/** Register sitemap hooks when no full SEO plugin owns sitemap routing. */
	public function hooks(): void {
		if ( Plugin::competing_plugin_active() ) {
			return;
		}

		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'wp_sitemaps_enabled', array( $this, 'core_sitemaps_enabled' ), 20 );
		add_filter( 'wp_sitemaps_max_urls', array( $this, 'max_urls' ), 20, 2 );
		add_action( 'template_redirect', array( $this, 'maybe_render' ), -20 );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 20, 2 );
		add_action( 'save_post', array( self::class, 'clear_cache' ) );
		add_action( 'deleted_post', array( self::class, 'clear_cache' ) );
		add_action( 'created_term', array( self::class, 'clear_cache' ) );
		add_action( 'edited_term', array( self::class, 'clear_cache' ) );
		add_action( 'delete_term', array( self::class, 'clear_cache' ) );
		add_action( 'user_register', array( self::class, 'clear_cache' ) );
		add_action( 'profile_update', array( self::class, 'clear_cache' ) );
	}

	/** Register Yoast-compatible sitemap routes. */
	public static function add_rewrite_rules(): void {
		if ( Plugin::competing_plugin_active() ) {
			return;
		}

		add_rewrite_rule( '^sitemap_index\.xml$', 'index.php?mrn_seo_sitemap=index', 'top' );
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?mrn_seo_sitemap=redirect', 'top' );
		add_rewrite_rule( '^mrn-sitemap\.xsl$', 'index.php?mrn_seo_sitemap=xsl', 'top' );
		add_rewrite_rule(
			'^([a-zA-Z0-9_-]+)-sitemap([0-9]+)?\.xml$',
			'index.php?mrn_seo_sitemap=map&mrn_seo_sitemap_type=$matches[1]&mrn_seo_sitemap_page=$matches[2]',
			'top'
		);
	}

	/**
	 * Add public query variables.
	 *
	 * @param array<int, string> $vars Query variables.
	 * @return array<int, string>
	 */
	public function query_vars( array $vars ): array {
		$vars[] = 'mrn_seo_sitemap';
		$vars[] = 'mrn_seo_sitemap_type';
		$vars[] = 'mrn_seo_sitemap_page';
		return array_values( array_unique( $vars ) );
	}

	/**
	 * Use Yoast's default page size while respecting the sitemap protocol limit.
	 *
	 * @param int    $max_urls    Existing maximum.
	 * @param string $object_type Provider object type.
	 */
	public function max_urls( int $max_urls, string $object_type ): int {
		/**
		 * Filter the maximum URLs in each generated sitemap.
		 *
		 * @param int    $max_urls    Default 1000, matching Yoast SEO.
		 * @param string $object_type WordPress sitemap object type.
		 */
		$data     = Business::get();
		$max_urls = (int) apply_filters( 'mrn_seo_sitemap_entries_per_page', (int) $data['sitemap_max_urls'], $object_type );
		return min( 50000, max( 1, $max_urls ) );
	}

	/** Keep WordPress providers enabled on public production sites only. */
	public function core_sitemaps_enabled( bool $enabled ): bool {
		return $enabled && ! Site::is_staging();
	}

	/** Render or redirect a sitemap request. */
	public function maybe_render(): void {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = untrailingslashit( (string) wp_parse_url( $request_uri, PHP_URL_PATH ) );
		$request     = sanitize_key( (string) get_query_var( 'mrn_seo_sitemap' ) );

		if ( in_array( $path, array( '/sitemap.xml', '/wp-sitemap.xml' ), true ) || 'redirect' === $request ) {
			wp_safe_redirect( home_url( self::INDEX_PATH ), 301, 'MRN SEO Profiles' );
			exit;
		}

		if ( self::INDEX_PATH === $path || 'index' === $request ) {
			$this->render_index();
		}

		if ( self::XSL_PATH === $path || 'xsl' === $request ) {
			$this->render_xsl();
		}

		if ( 'map' !== $request && ! preg_match( '#^/([a-zA-Z0-9_-]+)-sitemap([0-9]+)?\.xml$#', $path, $matches ) ) {
			return;
		}

		$type = sanitize_key( (string) get_query_var( 'mrn_seo_sitemap_type' ) );
		$page = absint( get_query_var( 'mrn_seo_sitemap_page' ) );
		if ( ! $type && isset( $matches[1] ) ) {
			$type = sanitize_key( $matches[1] );
		}
		if ( ! $page && isset( $matches[2] ) && '' !== $matches[2] ) {
			$page = absint( $matches[2] );
		}

		if ( $page < 2 && preg_match( '/-sitemap[01]\.xml$/', $path ) ) {
			wp_safe_redirect( $this->map_url( $type, 1 ), 301, 'MRN SEO Profiles' );
			exit;
		}

		$this->render_map( $type, max( 1, $page ) );
	}

	/** Render the XML sitemap index. */
	private function render_index(): void {
		$this->xml_headers();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_xml( home_url( self::XSL_PATH ) ) . '"?>' . "\n";
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		if ( $this->is_public() ) {
			foreach ( $this->index_entries() as $entry ) {
				echo "\t<sitemap>\n";
				echo "\t\t<loc>" . esc_xml( $entry['loc'] ) . "</loc>\n";
				if ( $entry['lastmod'] ) {
					echo "\t\t<lastmod>" . esc_xml( $entry['lastmod'] ) . "</lastmod>\n";
				}
				echo "\t</sitemap>\n";
			}
		}

		echo '</sitemapindex>';
		exit;
	}

	/** Render one post type, taxonomy, or author sitemap. */
	private function render_map( string $type, int $page ): void {
		$map = $this->resolve_map( $type );
		if ( ! $map || ! $this->is_public() || $page > $map['pages'] ) {
			$this->not_found();
			return;
		}

		$urls = $map['provider']->get_url_list( $page, $map['subtype'] );
		if ( ! is_array( $urls ) || ! $urls ) {
			$this->not_found();
			return;
		}

		$this->xml_headers();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_xml( home_url( self::XSL_PATH ) ) . '"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( $urls as $entry ) {
			if ( empty( $entry['loc'] ) ) {
				continue;
			}

			/**
			 * Filter a generated sitemap URL entry.
			 *
			 * @param array<string, string> $entry Sitemap URL data.
			 * @param string                $type  Public sitemap type.
			 * @param int                   $page  Sitemap page.
			 */
			$entry = apply_filters( 'mrn_seo_sitemap_url_entry', $entry, $type, $page );
			if ( ! is_array( $entry ) || empty( $entry['loc'] ) ) {
				continue;
			}

			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_xml( (string) $entry['loc'] ) . "</loc>\n";
			if ( ! empty( $entry['lastmod'] ) ) {
				echo "\t\t<lastmod>" . esc_xml( (string) $entry['lastmod'] ) . "</lastmod>\n";
			}
			echo "\t</url>\n";
		}
		echo '</urlset>';
		exit;
	}

	/**
	 * Build index entries from WordPress' registered sitemap providers.
	 *
	 * @return array<int, array{loc:string,lastmod:string}>
	 */
	private function index_entries(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$entries = array();
		foreach ( $this->maps() as $map ) {
			for ( $page = 1; $page <= $map['pages']; $page++ ) {
				$urls      = $map['provider']->get_url_list( $page, $map['subtype'] );
				$entries[] = array(
					'loc'     => $this->map_url( $map['type'], $page ),
					'lastmod' => $this->latest_modified( is_array( $urls ) ? $urls : array() ),
				);
			}
		}

		/**
		 * Filter sitemap index entries, including adding external sitemaps.
		 *
		 * @param array<int, array{loc:string,lastmod:string}> $entries Index entries.
		 */
		$entries = (array) apply_filters( 'mrn_seo_sitemap_index_entries', $entries );
		$ttl     = (int) apply_filters( 'mrn_seo_sitemap_index_cache_ttl', HOUR_IN_SECONDS );
		if ( $ttl > 0 ) {
			set_transient( self::CACHE_KEY, $entries, $ttl );
		}
		return $entries;
	}

	/** Clear the cached sitemap index after public content changes. */
	public static function clear_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Return every non-empty map exposed by WordPress providers.
	 *
	 * @return array<int, array{type:string,subtype:string,pages:int,provider:\WP_Sitemaps_Provider}>
	 */
	private function maps(): array {
		$server    = wp_sitemaps_get_server();
		$providers = $server->registry->get_providers();
		$maps      = array();

		$data                = Business::get();
		$excluded_post_types = Business::list_setting( 'sitemap_excluded_post_types' );
		$excluded_taxonomies = Business::list_setting( 'sitemap_excluded_taxonomies' );

		foreach ( $providers as $name => $provider ) {
			if ( ! $provider instanceof \WP_Sitemaps_Provider ) {
				continue;
			}

			if ( 'users' === $name ) {
				if ( '1' !== $data['sitemap_include_authors'] ) {
					continue;
				}
				$pages = (int) $provider->get_max_num_pages();
				if ( $pages > 0 ) {
					$maps[] = array(
						'type'     => 'author',
						'subtype'  => '',
						'pages'    => $pages,
						'provider' => $provider,
					);
				}
				continue;
			}

			$subtypes = (array) $provider->get_object_subtypes();
			if ( ! $subtypes ) {
				$pages = (int) $provider->get_max_num_pages();
				if ( $pages > 0 ) {
					$maps[] = array(
						'type'     => sanitize_key( (string) $name ),
						'subtype'  => '',
						'pages'    => $pages,
						'provider' => $provider,
					);
				}
				continue;
			}

			foreach ( array_keys( $subtypes ) as $subtype_name ) {
				if ( ! is_string( $subtype_name ) ) {
					continue;
				}
				$subtype = $subtype_name;
				if ( ( 'taxonomies' === $name && in_array( $subtype, $excluded_taxonomies, true ) ) || ( 'posts' === $name && in_array( $subtype, $excluded_post_types, true ) ) ) {
					continue;
				}
				$pages = (int) $provider->get_max_num_pages( $subtype );
				if ( $pages < 1 ) {
					continue;
				}
				$maps[] = array(
					'type'     => sanitize_key( $subtype ),
					'subtype'  => $subtype,
					'pages'    => $pages,
					'provider' => $provider,
				);
			}
		}

		/**
		 * Filter the generated sitemap definitions.
		 *
		 * @param array<int, array<string, mixed>> $maps Sitemap definitions.
		 */
		return (array) apply_filters( 'mrn_seo_sitemap_maps', $maps );
	}

	/**
	 * Resolve one public URL type to its provider.
	 *
	 * @return array{type:string,subtype:string,pages:int,provider:\WP_Sitemaps_Provider}|null
	 */
	private function resolve_map( string $type ): ?array {
		foreach ( $this->maps() as $map ) {
			if ( $type === $map['type'] ) {
				return $map;
			}
		}
		return null;
	}

	/** Build the canonical URL for one sitemap page. */
	private function map_url( string $type, int $page ): string {
		$suffix = $page > 1 ? (string) $page : '';
		return home_url( '/' . $type . '-sitemap' . $suffix . '.xml' );
	}

	/**
	 * Find the newest modification timestamp in a provider URL list.
	 *
	 * @param array<int, array<string, string>> $urls Provider URLs.
	 */
	private function latest_modified( array $urls ): string {
		$latest = '';
		foreach ( $urls as $url ) {
			if ( ! empty( $url['lastmod'] ) && $url['lastmod'] > $latest ) {
				$latest = (string) $url['lastmod'];
			}
		}
		return $latest;
	}

	/** Advertise only the canonical sitemap index in virtual robots.txt. */
	public function robots_txt( string $output, bool $is_public ): string {
		if ( ! $is_public || Site::is_staging() ) {
			return "User-agent: *\nDisallow: /\n";
		}

		$output = (string) preg_replace( '/^Sitemap:\s*.*$/mi', '', trim( $output ) );
		$output = trim( (string) preg_replace( "/\n{3,}/", "\n\n", $output ) );
		if ( ! str_contains( $output, 'User-agent:' ) ) {
			$output .= ( $output ? "\n" : '' ) . "User-agent: *\nDisallow:";
		}
		return $output . "\nSitemap: " . home_url( self::INDEX_PATH ) . "\n";
	}

	/** Return whether search visibility and the hostname allow indexing. */
	private function is_public(): bool {
		return (bool) get_option( 'blog_public' ) && ! Site::is_staging();
	}

	/** Send XML response headers. */
	private function xml_headers(): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow', true );
	}

	/** Return a normal WordPress 404 for an invalid or empty sitemap. */
	private function not_found(): void {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/** Render a small browser-friendly XSL table. */
	private function render_xsl(): never {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: text/xsl; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">
	<xsl:output method="html" encoding="UTF-8"/>
	<xsl:template match="/">
		<html><head><title>XML Sitemap</title><style>body{font:16px/1.5 system-ui,sans-serif;margin:2rem;color:#202124}table{border-collapse:collapse;width:100%;max-width:1100px}th,td{padding:.65rem;border-bottom:1px solid #ddd;text-align:left}a{color:#1769aa}</style></head>
		<body><h1>XML Sitemap</h1><p>Generated automatically by MRN SEO Profiles.</p><table><tr><th>URL</th><th>Last modified</th></tr>
		<xsl:for-each select="s:sitemapindex/s:sitemap | s:urlset/s:url"><tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td><xsl:value-of select="s:lastmod"/></td></tr></xsl:for-each>
		</table></body></html>
	</xsl:template>
</xsl:stylesheet>
		<?php
		exit;
	}
}
