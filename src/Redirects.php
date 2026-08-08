<?php
/**
 * User-configurable redirects.
 *
 * @package MRN\SEO
 */

namespace MRN\SEO;

defined( 'ABSPATH' ) || exit;

final class Redirects {
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ), -30 );
	}

	public function redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		foreach ( $this->rules() as $rule ) {
			if ( $path !== $rule['source'] ) {
				continue;
			}
			$destination = str_starts_with( $rule['destination'], 'http://' ) || str_starts_with( $rule['destination'], 'https://' )
				? $rule['destination']
				: home_url( '/' . ltrim( $rule['destination'], '/' ) );
			if ( untrailingslashit( $destination ) === untrailingslashit( home_url( '/' . $path ) ) ) {
				return;
			}
			wp_safe_redirect( $destination, $rule['status'], 'MRN SEO' );
			exit;
		}
	}

	/** @return array<int, array{source:string,destination:string,status:int}> */
	public function rules(): array {
		$data  = Business::get();
		$rules = array();
		$lines = preg_split( '/\R/', (string) $data['redirects'] );
		foreach ( $lines ? $lines : array() as $line ) {
			$parts       = array_map( 'trim', explode( '|', $line, 3 ) );
			$source      = trim( (string) ( $parts[0] ?? '' ), '/' );
			$destination = (string) ( $parts[1] ?? '' );
			$status      = (int) ( $parts[2] ?? 301 );
			if ( '' === $source || '' === $destination ) {
				continue;
			}
			$rules[] = array(
				'source'      => $source,
				'destination' => $destination,
				'status'      => in_array( $status, array( 301, 302, 307, 308 ), true ) ? $status : 301,
			);
		}
		return $rules;
	}
}
