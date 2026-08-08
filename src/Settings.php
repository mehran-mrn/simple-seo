<?php
/**
 * Generic SEO settings screen.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Settings {
	/** @var array<string, array<string, string>> */
	private array $fields = array(
		'name'                        => array(
			'section' => 'business',
			'label'   => 'Business / organization name',
		),
		'legal_name'                  => array(
			'section' => 'business',
			'label'   => 'Legal name',
		),
		'manager'                     => array(
			'section' => 'business',
			'label'   => 'Manager / owner',
		),
		'manager_job_title'           => array(
			'section' => 'business',
			'label'   => 'Manager job title',
		),
		'manager_schema_property'     => array(
			'section'     => 'schema',
			'label'       => 'Manager schema property',
			'description' => 'For example: founder or employee.',
		),
		'phone'                       => array(
			'section' => 'business',
			'label'   => 'Displayed phone',
		),
		'phone_e164'                  => array(
			'section' => 'business',
			'label'   => 'Phone (E.164)',
		),
		'email'                       => array(
			'section' => 'business',
			'label'   => 'Email',
			'type'    => 'email',
		),
		'street_address'              => array(
			'section' => 'business',
			'label'   => 'Street address',
		),
		'address_locality'            => array(
			'section' => 'business',
			'label'   => 'City',
		),
		'address_region'              => array(
			'section' => 'business',
			'label'   => 'State / province',
		),
		'postal_code'                 => array(
			'section' => 'business',
			'label'   => 'Postal code',
		),
		'address_country'             => array(
			'section' => 'business',
			'label'   => 'Country code',
		),
		'latitude'                    => array(
			'section' => 'business',
			'label'   => 'Latitude',
		),
		'longitude'                   => array(
			'section' => 'business',
			'label'   => 'Longitude',
		),
		'service_areas'               => array(
			'section'     => 'business',
			'label'       => 'Service areas',
			'description' => 'Comma-separated place names.',
		),
		'google_business_url'         => array(
			'section' => 'business',
			'label'   => 'Business profile URL',
			'type'    => 'url',
		),
		'price_range'                 => array(
			'section' => 'business',
			'label'   => 'Price range',
		),
		'schema_type'                 => array(
			'section'     => 'schema',
			'label'       => 'Business Schema.org type',
			'description' => 'One or more comma-separated types, for example: LocalBusiness, EducationalOrganization.',
		),
		'area_schema_type'            => array(
			'section'     => 'schema',
			'label'       => 'Service-area Schema.org type',
			'description' => 'For example: Place, City, Country.',
		),
		'language'                    => array(
			'section'     => 'schema',
			'label'       => 'Content language',
			'description' => 'BCP 47 code, for example en-CA or fa-IR.',
		),
		'og_locale'                   => array(
			'section'     => 'schema',
			'label'       => 'Open Graph locale',
			'description' => 'For example en_CA or fa_IR.',
		),
		'home_label'                  => array(
			'section' => 'schema',
			'label'   => 'Breadcrumb home label',
		),
		'logo_url'                    => array(
			'section' => 'schema',
			'label'   => 'Logo URL',
			'type'    => 'url',
		),
		'social_image_url'            => array(
			'section' => 'schema',
			'label'   => 'Default social image URL',
			'type'    => 'url',
		),
		'social_profiles'             => array(
			'section'     => 'schema',
			'label'       => 'Social profile URLs',
			'description' => 'Comma-separated URLs used in sameAs schema.',
		),
		'home_title'                  => array(
			'section' => 'metadata',
			'label'   => 'Homepage SEO title',
		),
		'home_description'            => array(
			'section' => 'metadata',
			'label'   => 'Homepage description',
			'type'    => 'textarea',
		),
		'default_description'         => array(
			'section' => 'metadata',
			'label'   => 'Fallback description',
			'type'    => 'textarea',
		),
		'page_metadata'               => array(
			'section'     => 'metadata',
			'label'       => 'Page-specific metadata',
			'type'        => 'textarea',
			'description' => 'One per line: slug :: SEO title :: meta description',
		),
		'faq_page_slug'               => array(
			'section' => 'metadata',
			'label'   => 'FAQ page slug',
		),
		'faq_items'                   => array(
			'section'     => 'metadata',
			'label'       => 'FAQ schema items',
			'type'        => 'textarea',
			'description' => 'One per line: question | answer',
		),
		'redirects'                   => array(
			'section'     => 'redirects',
			'label'       => 'Redirect rules',
			'type'        => 'textarea',
			'description' => 'One per line: old-path | destination | 301. Leave empty for no redirects.',
		),
		'staging_host_patterns'       => array(
			'section'     => 'indexing',
			'label'       => 'Staging hostname patterns',
			'description' => 'Comma-separated fragments that force noindex.',
		),
		'noindex_search'              => array(
			'section' => 'indexing',
			'label'   => 'Noindex search results',
			'type'    => 'checkbox',
		),
		'noindex_404'                 => array(
			'section' => 'indexing',
			'label'   => 'Noindex 404 pages',
			'type'    => 'checkbox',
		),
		'noindex_date_archives'       => array(
			'section' => 'indexing',
			'label'   => 'Noindex date archives',
			'type'    => 'checkbox',
		),
		'noindex_author_archives'     => array(
			'section' => 'indexing',
			'label'   => 'Noindex author archives',
			'type'    => 'checkbox',
		),
		'noindex_category_archives'   => array(
			'section' => 'indexing',
			'label'   => 'Noindex category archives',
			'type'    => 'checkbox',
		),
		'noindex_tag_archives'        => array(
			'section' => 'indexing',
			'label'   => 'Noindex tag archives',
			'type'    => 'checkbox',
		),
		'sitemap_excluded_post_types' => array(
			'section'     => 'sitemap',
			'label'       => 'Excluded post types',
			'description' => 'Comma-separated post-type slugs.',
		),
		'sitemap_excluded_taxonomies' => array(
			'section'     => 'sitemap',
			'label'       => 'Excluded taxonomies',
			'description' => 'Comma-separated taxonomy slugs.',
		),
		'sitemap_include_authors'     => array(
			'section' => 'sitemap',
			'label'   => 'Include author sitemap',
			'type'    => 'checkbox',
		),
		'sitemap_max_urls'            => array(
			'section'     => 'sitemap',
			'label'       => 'Maximum URLs per sitemap',
			'description' => 'Between 1 and 50,000.',
		),
	);

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_post_mrn_seo_export', array( $this, 'export' ) );
		add_action( 'admin_post_mrn_seo_import', array( $this, 'import' ) );
		add_action( 'admin_notices', array( $this, 'conflict_notice' ) );
		add_action( 'admin_notices', array( $this, 'transfer_notice' ) );
	}

	public function menu(): void {
		add_options_page( 'MRN SEO', 'MRN SEO', 'manage_options', 'mrn-seo', array( $this, 'page' ) );
	}

	public function register(): void {
		register_setting(
			'mrn_seo',
			Business::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Business::defaults(),
			)
		);

		$sections = array(
			'business'  => array( 'Business information', 'Used by local business schema and map links.' ),
			'schema'    => array( 'Schema and social identity', 'Control structured data without relying on a theme or hostname.' ),
			'metadata'  => array( 'Titles and descriptions', 'Configure defaults and optional page-specific metadata.' ),
			'redirects' => array( 'Redirects', 'Redirects run only when explicitly entered here.' ),
			'indexing'  => array( 'Indexing controls', 'Choose which archive surfaces search engines may index.' ),
			'sitemap'   => array( 'XML sitemap', 'Choose which registered WordPress providers appear in the sitemap.' ),
		);
		foreach ( $sections as $key => $section ) {
			add_settings_section(
				'mrn_seo_' . $key,
				$section[0],
				static function () use ( $section ): void {
					echo '<p>' . esc_html( $section[1] ) . '</p>';
				},
				'mrn-seo'
			);
		}
		foreach ( $this->fields as $key => $field ) {
			add_settings_field( $key, $field['label'], array( $this, 'field' ), 'mrn-seo', 'mrn_seo_' . $field['section'], array( 'key' => $key ) );
		}
	}

	/** @return array<string, mixed> */
	public function sanitize( mixed $input ): array {
		$defaults = Business::defaults();
		$input    = is_array( $input ) ? $input : array();
		$output   = array();
		foreach ( $defaults as $key => $default ) {
			$type  = $this->fields[ $key ]['type'] ?? 'text';
			$value = $input[ $key ] ?? ( 'checkbox' === $type ? '0' : $default );
			if ( 'checkbox' === $type ) {
				$output[ $key ] = empty( $value ) ? '0' : '1';
			} elseif ( 'email' === $type ) {
				$output[ $key ] = sanitize_email( (string) $value );
			} elseif ( 'url' === $type ) {
				$output[ $key ] = esc_url_raw( (string) $value, array( 'http', 'https' ) );
			} elseif ( 'textarea' === $type ) {
				$output[ $key ] = sanitize_textarea_field( (string) $value );
			} elseif ( in_array( $key, array( 'latitude', 'longitude' ), true ) ) {
				$output[ $key ] = (string) (float) $value;
			} elseif ( 'sitemap_max_urls' === $key ) {
				$output[ $key ] = (string) min( 50000, max( 1, absint( $value ) ) );
			} else {
				$output[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		Sitemap::clear_cache();
		return $output;
	}

	public function field( array $args ): void {
		$key         = $args['key'];
		$definition  = $this->fields[ $key ];
		$settings    = Business::get();
		$type        = $definition['type'] ?? 'text';
		$description = $definition['description'] ?? '';
		if ( 'textarea' === $type ) {
			printf( '<textarea class="large-text code" rows="5" id="mrn-seo-%1$s" name="%2$s[%1$s]">%3$s</textarea>', esc_attr( $key ), esc_attr( Business::OPTION ), esc_textarea( (string) $settings[ $key ] ) );
		} elseif ( 'checkbox' === $type ) {
			printf( '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> Enabled</label>', esc_attr( Business::OPTION ), esc_attr( $key ), checked( '1', (string) $settings[ $key ], false ) );
		} else {
			printf( '<input class="regular-text" type="%1$s" id="mrn-seo-%2$s" name="%3$s[%2$s]" value="%4$s">', esc_attr( $type ), esc_attr( $key ), esc_attr( Business::OPTION ), esc_attr( (string) $settings[ $key ] ) );
		}
		if ( $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
	}

	public function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap"><h1>MRN SEO</h1><p>Configure metadata, local business schema, redirects, indexing, and XML sitemaps for this site.</p><form action="options.php" method="post">
		<?php
		settings_fields( 'mrn_seo' );
		do_settings_sections( 'mrn-seo' );
		submit_button();
		?>
		</form>
		<hr>
		<h2>Import and export</h2>
		<p>Export a complete JSON template or replace this site's SEO settings from a compatible JSON file.</p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline-block;margin-right:1rem">
			<input type="hidden" name="action" value="mrn_seo_export">
			<?php wp_nonce_field( 'mrn_seo_export' ); ?>
			<?php submit_button( 'Export settings', 'secondary', 'submit', false ); ?>
		</form>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" style="display:inline-block">
			<input type="hidden" name="action" value="mrn_seo_import">
			<?php wp_nonce_field( 'mrn_seo_import' ); ?>
			<label for="mrn-seo-import"><span class="screen-reader-text">Settings JSON file</span></label>
			<input id="mrn-seo-import" type="file" name="mrn_seo_file" accept="application/json,.json" required>
			<?php submit_button( 'Import and replace settings', 'secondary', 'submit', false, array( 'onclick' => "return confirm('Replace all current MRN SEO settings with this file?');" ) ); ?>
		</form>
		</div>
		<?php
	}

	/** Download a complete JSON document, including empty keys. */
	public function export(): void {
		$this->guard_transfer();
		check_admin_referer( 'mrn_seo_export' );
		$payload  = SettingsTransfer::export_payload();
		$filename = 'mrn-seo-settings-' . gmdate( 'Y-m-d' ) . '.json';

		nocache_headers();
		header( 'Content-Type: application/json; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/** Validate and replace settings from an uploaded JSON document. */
	public function import(): void {
		$this->guard_transfer();
		check_admin_referer( 'mrn_seo_import' );
		$file = isset( $_FILES['mrn_seo_file'] ) && is_array( $_FILES['mrn_seo_file'] ) ? $_FILES['mrn_seo_file'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) || empty( $file['size'] ) || (int) $file['size'] > MB_IN_BYTES ) {
			$this->redirect_with_notice( 'invalid_file' );
		}

		$json = file_get_contents( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		try {
			$settings = SettingsTransfer::decode( is_string( $json ) ? $json : '' );
			update_option( Business::OPTION, $this->sanitize( $settings ) );
			Sitemap::clear_cache();
			$this->redirect_with_notice( 'imported' );
		} catch ( \InvalidArgumentException $exception ) {
			$this->redirect_with_notice( 'invalid_json' );
		}
	}

	private function guard_transfer(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage MRN SEO settings.', 'mrn-seo' ) );
		}
	}

	private function redirect_with_notice( string $notice ): never {
		$url = add_query_arg(
			array(
				'page'           => 'mrn-seo',
				'mrn_seo_notice' => $notice,
			),
			admin_url( 'options-general.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public function transfer_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || empty( $_GET['mrn_seo_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$notice   = sanitize_key( wp_unslash( $_GET['mrn_seo_notice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$messages = array(
			'imported'     => array( 'success', 'MRN SEO settings imported successfully.' ),
			'invalid_file' => array( 'error', 'Select a JSON file smaller than 1 MB.' ),
			'invalid_json' => array( 'error', 'The selected file is not a compatible MRN SEO settings export.' ),
		);
		if ( isset( $messages[ $notice ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );
		}
	}

	public function conflict_notice(): void {
		if ( Plugin::competing_plugin_active() && current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error"><p><strong>MRN SEO:</strong> Another full SEO plugin is active. Metadata, schema, and sitemap output are paused to prevent duplicates.</p></div>';
		}
	}
}
