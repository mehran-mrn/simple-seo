<?php
/** Standalone settings transfer tests. */

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'MRN_SEO_VERSION', 'test' );

	$GLOBALS['test_options'] = array( 'admin_email' => 'admin@example.com' );
	function get_locale() { return 'en_US'; }
	function get_bloginfo( $key ) { return 'language' === $key ? 'en-US' : 'Example'; }
	function get_option( $key, $default = false ) { return $GLOBALS['test_options'][ $key ] ?? $default; }
}

namespace MRN\SEO {
	require_once dirname( __DIR__ ) . '/src/Business.php';
	require_once dirname( __DIR__ ) . '/src/SettingsTransfer.php';

	function assert_transfer( bool $condition, string $message ): void {
		if ( ! $condition ) {
			throw new \RuntimeException( $message );
		}
	}

	$payload = SettingsTransfer::export_payload();
	assert_transfer( SettingsTransfer::FORMAT === $payload['format'], 'Expected a typed settings document.' );
	assert_transfer( array_keys( Business::defaults() ) === array_keys( $payload['settings'] ), 'Every supported setting must be exported.' );
	assert_transfer( '' === $payload['settings']['name'], 'Unstored settings must be exported as explicit empty values.' );

	$GLOBALS['test_options'][ Business::OPTION ] = array( 'name' => 'Configured site', 'unknown' => 'discard me' );
	$payload = SettingsTransfer::export_payload();
	assert_transfer( 'Configured site' === $payload['settings']['name'], 'Stored values must be exported.' );
	assert_transfer( ! isset( $payload['settings']['unknown'] ), 'Unknown stored keys must not be exported.' );

	$payload['settings']['unknown'] = 'discard me';
	$decoded = SettingsTransfer::decode( json_encode( $payload, JSON_THROW_ON_ERROR ) );
	assert_transfer( 'Configured site' === $decoded['name'], 'Valid values must be decoded.' );
	assert_transfer( ! isset( $decoded['unknown'] ), 'Unknown imported keys must be discarded.' );
	assert_transfer( count( Business::defaults() ) === count( $decoded ), 'Decoded settings must contain every supported key.' );
	$payload['settings']['name'] = array( 'nested values are invalid' );
	try {
		SettingsTransfer::decode( json_encode( $payload, JSON_THROW_ON_ERROR ) );
		throw new \RuntimeException( 'Nested setting values must be rejected.' );
	} catch ( \InvalidArgumentException $exception ) {
		assert_transfer( true, 'Nested setting value rejected.' );
	}

	try {
		SettingsTransfer::decode( '{invalid' );
		throw new \RuntimeException( 'Invalid JSON must be rejected.' );
	} catch ( \InvalidArgumentException $exception ) {
		assert_transfer( true, 'Invalid JSON rejected.' );
	}

	echo "MRN SEO settings transfer tests passed.\n";
}
