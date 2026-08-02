<?php
/** Minimal standalone behavior test for the universal sitemap service. */

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'HOUR_IN_SECONDS', 3600 );
	define( 'MRN_WDS_SEO_VERSION', 'test' );

	$GLOBALS['test_actions']       = array();
	$GLOBALS['test_filters']       = array();
	$GLOBALS['test_rewrites']      = array();
	$GLOBALS['test_transients']    = array();
	$GLOBALS['test_options']       = array( 'blog_public' => 1 );
	$GLOBALS['test_stylesheet']    = 'generic-theme';
	$GLOBALS['test_home']          = 'https://example.com';

	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['test_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
	}
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['test_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
	}
	function apply_filters( $hook, $value, ...$args ) {
		foreach ( $GLOBALS['test_filters'][ $hook ] ?? array() as $filter ) {
			$value = call_user_func_array( $filter['callback'], array_slice( array_merge( array( $value ), $args ), 0, $filter['accepted_args'] ) );
		}
		return $value;
	}
	function add_rewrite_rule( $regex, $query, $position ) {
		$GLOBALS['test_rewrites'][ $regex ] = compact( 'query', 'position' );
	}
	function get_stylesheet() {
		return $GLOBALS['test_stylesheet'];
	}
	function home_url( $path = '/' ) {
		return rtrim( $GLOBALS['test_home'], '/' ) . '/' . ltrim( $path, '/' );
	}
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
	function get_bloginfo( $key ) {
		return 'language' === $key ? 'en-US' : 'Example';
	}
	function get_locale() {
		return 'en_US';
	}
	function get_option( $key, $default = false ) {
		return $GLOBALS['test_options'][ $key ] ?? $default;
	}
	function get_transient( $key ) {
		return $GLOBALS['test_transients'][ $key ] ?? false;
	}
	function set_transient( $key, $value, $ttl ) {
		$GLOBALS['test_transients'][ $key ] = $value;
		return true;
	}
	function delete_transient( $key ) {
		unset( $GLOBALS['test_transients'][ $key ] );
		return true;
	}
	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
	}
	function wp_sitemaps_get_server() {
		return $GLOBALS['test_sitemap_server'];
	}

	abstract class WP_Sitemaps_Provider {
		abstract public function get_url_list( $page_num, $object_subtype = '' );
		abstract public function get_max_num_pages( $object_subtype = '' );
		public function get_object_subtypes() {
			return array();
		}
	}

	final class Test_Provider extends WP_Sitemaps_Provider {
		private array $subtypes;
		private array $pages;
		public function __construct( array $subtypes, array $pages ) {
			$this->subtypes = $subtypes;
			$this->pages    = $pages;
		}
		public function get_object_subtypes() {
			return $this->subtypes;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return $this->pages[ $object_subtype ] ?? 0;
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			$type = $object_subtype ?: 'author';
			return array(
				array(
					'loc'     => home_url( "/{$type}/{$page_num}/" ),
					'lastmod' => sprintf( '2026-08-%02dT00:00:00+00:00', $page_num ),
				),
			);
		}
	}

	final class Test_Registry {
		public function get_providers(): array {
			return array(
				'posts'      => new Test_Provider( array( 'post' => new \stdClass(), 'product' => new \stdClass() ), array( 'post' => 2, 'product' => 1 ) ),
				'taxonomies' => new Test_Provider( array( 'category' => new \stdClass() ), array( 'category' => 1 ) ),
				'users'      => new Test_Provider( array(), array( '' => 1 ) ),
			);
		}
	}

	$GLOBALS['test_sitemap_server'] = (object) array( 'registry' => new Test_Registry() );
}

namespace MRN\WDS\SEO {
	require_once dirname( __DIR__ ) . '/src/Site.php';
	require_once dirname( __DIR__ ) . '/src/Business.php';
	require_once dirname( __DIR__ ) . '/src/Plugin.php';
	require_once dirname( __DIR__ ) . '/src/Sitemap.php';

	function assert_true( bool $condition, string $message ): void {
		if ( ! $condition ) {
			throw new \RuntimeException( $message );
		}
	}

	$sitemap = new Sitemap();
	assert_true( 'Example' === Business::defaults()['name'], 'Unknown sites must receive generic business defaults.' );
	$sitemap->hooks();
	Sitemap::add_rewrite_rules();
	assert_true( 4 === count( $GLOBALS['test_rewrites'] ), 'Expected four sitemap rewrite rules.' );
	assert_true( 1000 === $sitemap->max_urls( 2000, 'post' ), 'Expected Yoast-compatible page size.' );
	assert_true( str_contains( $sitemap->robots_txt( '', true ), '/sitemap_index.xml' ), 'robots.txt must advertise the sitemap index.' );

	$method = new \ReflectionMethod( Sitemap::class, 'maps' );
	$maps   = $method->invoke( $sitemap );
	$types  = array_column( $maps, 'type' );
	assert_true( array( 'post', 'product', 'category', 'author' ) === $types, 'Generic profile did not expose every provider.' );

	$index_method = new \ReflectionMethod( Sitemap::class, 'index_entries' );
	$entries      = $index_method->invoke( $sitemap );
	assert_true( 5 === count( $entries ), 'Expected paginated index entries.' );
	assert_true( 'https://example.com/post-sitemap2.xml' === $entries[1]['loc'], 'Expected Yoast-style numbered sitemap URL.' );

	$GLOBALS['test_stylesheet'] = 'mrn-child-wds';
	Sitemap::clear_cache();
	assert_true( 'WDS Driving School' === Business::defaults()['name'], 'The legacy WDS profile must remain available.' );
	$maps  = $method->invoke( $sitemap );
	$types = array_column( $maps, 'type' );
	assert_true( array( 'post', 'product' ) === $types, 'WDS noindex archives must be absent from the sitemap.' );

	echo "Sitemap behavior tests passed.\n";
}
