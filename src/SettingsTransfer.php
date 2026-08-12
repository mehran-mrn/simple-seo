<?php
/**
 * Portable JSON settings import and export.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

/** Builds and validates site-agnostic settings documents. */
final class SettingsTransfer {
	public const FORMAT         = 'mrn-seo-settings';
	public const SCHEMA_VERSION = 1;

	/** @return array<string, mixed> */
	public static function export_payload(): array {
		$template = array_fill_keys( array_keys( Business::defaults() ), '' );
		$stored   = get_option( Business::OPTION, array() );
		$stored   = is_array( $stored ) ? array_intersect_key( $stored, $template ) : array();

		return array(
			'format'         => self::FORMAT,
			'schema_version' => self::SCHEMA_VERSION,
			'plugin_version' => MRN_SEO_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'settings'       => array_replace( $template, $stored ),
		);
	}

	/**
	 * Decode a JSON document and return a complete settings array.
	 *
	 * @return array<string, mixed>
	 * @throws \InvalidArgumentException When the document is invalid or incompatible.
	 */
	public static function decode( string $json ): array {
		try {
			$payload = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException ) {
			throw new \InvalidArgumentException( 'The selected file is not valid JSON.' );
		}

		if ( ! is_array( $payload ) || self::FORMAT !== ( $payload['format'] ?? '' ) ) {
			throw new \InvalidArgumentException( 'The selected file is not an MRN SEO settings export.' );
		}
		if ( self::SCHEMA_VERSION !== (int) ( $payload['schema_version'] ?? 0 ) ) {
			throw new \InvalidArgumentException( 'The settings file schema version is not supported.' );
		}
		if ( ! isset( $payload['settings'] ) || ! is_array( $payload['settings'] ) ) {
			throw new \InvalidArgumentException( 'The settings object is missing from the selected file.' );
		}

		$template = array_fill_keys( array_keys( Business::defaults() ), '' );
		$settings = array_intersect_key( $payload['settings'], $template );
		foreach ( $settings as $value ) {
			if ( ! is_scalar( $value ) && null !== $value ) {
				throw new \InvalidArgumentException( 'Setting values must be scalar or null.' );
			}
		}
		return array_replace( $template, $settings );
	}
}
