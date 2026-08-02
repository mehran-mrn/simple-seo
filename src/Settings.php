<?php
/**
 * Business and local SEO settings.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the business-data settings screen.
 */
final class Settings {
	/**
	 * Editable business fields.
	 *
	 * @var array<string, string>
	 */
	private array $fields = array(
		'name'                => 'Business name',
		'legal_name'          => 'Legal name',
		'manager'             => 'Manager / owner',
		'phone'               => 'Displayed phone',
		'phone_e164'          => 'Phone (E.164)',
		'email'               => 'Email',
		'street_address'      => 'Street address',
		'address_locality'    => 'City',
		'address_region'      => 'Province',
		'postal_code'         => 'Postal code',
		'address_country'     => 'Country code',
		'latitude'            => 'Latitude',
		'longitude'           => 'Longitude',
		'service_areas'       => 'Service areas (comma-separated)',
		'google_business_url' => 'Google Business Profile URL',
		'google_place_id'     => 'Google knowledge graph ID',
		'price_range'         => 'Price range',
	);

	/** Register admin hooks. */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_notices', array( $this, 'conflict_notice' ) );
	}

	/** Register the settings page. */
	public function menu(): void {
		$title = Site::is_zarsam() ? 'سئو و اطلاعات زرسام' : ( Site::is_wds() ? 'WDS Search & Local SEO' : 'MRN Search & Local SEO' );
		add_options_page( $title, $title, 'manage_options', 'mrn-wds-seo', array( $this, 'page' ) );
	}

	/** Register settings, section, and fields. */
	public function register(): void {
		register_setting(
			'mrn_wds_seo',
			Business::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Business::defaults(),
			)
		);

		add_settings_section(
			'mrn_wds_business',
			'Canonical business information',
			static function (): void {
				echo '<p>These values power the site address, Google Map links, and local schema. Keep them consistent with Google Business Profile.</p>';
			},
			'mrn-wds-seo'
		);

		foreach ( $this->fields as $key => $label ) {
			add_settings_field( $key, $label, array( $this, 'field' ), 'mrn-wds-seo', 'mrn_wds_business', array( 'key' => $key ) );
		}
	}

	/**
	 * Sanitize all submitted business fields.
	 *
	 * @param mixed $input Raw settings.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $input ): array {
		$defaults = Business::defaults();
		$input    = is_array( $input ) ? $input : array();
		$output   = array();

		foreach ( $defaults as $key => $default ) {
			$value = $input[ $key ] ?? $default;
			if ( 'email' === $key ) {
				$output[ $key ] = sanitize_email( $value );
			} elseif ( 'google_business_url' === $key ) {
				$output[ $key ] = esc_url_raw( $value, array( 'https' ) );
			} elseif ( in_array( $key, array( 'latitude', 'longitude' ), true ) ) {
				$output[ $key ] = (string) (float) $value;
			} else {
				$output[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $output;
	}

	/**
	 * Render one text-like field.
	 *
	 * @param array{key:string} $args Field arguments.
	 */
	public function field( array $args ): void {
		$key      = $args['key'];
		$settings = Business::get();
		$type     = 'email' === $key ? 'email' : ( 'google_business_url' === $key ? 'url' : 'text' );
		printf(
			'<input class="regular-text" type="%1$s" id="mrn-wds-%2$s" name="%3$s[%2$s]" value="%4$s">',
			esc_attr( $type ),
			esc_attr( $key ),
			esc_attr( Business::OPTION ),
			esc_attr( (string) $settings[ $key ] )
		);
	}

	/** Render the settings page. */
	public function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( Site::is_zarsam() ? 'سئو و اطلاعات زرسام' : ( Site::is_wds() ? 'WDS Search & Local SEO' : 'MRN Search & Local SEO' ) ); ?></h1>
			<p><?php echo esc_html( Site::is_zarsam() ? 'مدیریت عنوان‌ها، توضیحات، آدرس‌های canonical، شبکه‌های اجتماعی، اسکیما و نقشه سایت وردپرس.' : 'Manage metadata, local business schema, crawler directives, and the automatic XML sitemap index.' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( 'mrn_wds_seo' ); ?>
				<?php do_settings_sections( 'mrn-wds-seo' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/** Warn administrators about duplicate full SEO plugins. */
	public function conflict_notice(): void {
		if ( ! Plugin::competing_plugin_active() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>MRN SEO Profiles:</strong> Another full SEO plugin is active. MRN metadata, schema, and sitemap output are paused to prevent duplicates.</p></div>';
	}
}
