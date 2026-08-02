<?php
/**
 * Legacy production URL redirects.
 *
 * @package MRN\WDS\SEO
 */

namespace MRN\WDS\SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Preserves the value of legacy URLs after the redesign cutover.
 */
final class Redirects {
	/** Register the redirect handler before normal templates. */
	public function hooks(): void {
		add_action( 'template_redirect', array( $this, 'redirect' ), -30 );
	}

	/** Redirect a known legacy path to its closest current equivalent. */
	public function redirect(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$map         = array(
			'home'                                     => '/',
			'courses'                                  => '/packages/',
			'admission'                                => '/register/',
			'newsletter'                               => '/contact-us/',
			'order-received'                           => '/',
			'thank-you-for-choosing-we-driving-school' => '/',
			'cart'                                     => '/register/',
			'checkout'                                 => '/register/',
			'account'                                  => '/register/',
			'register-old'                             => '/register/',
			'check-certification'                      => '/contact-us/',
			'package-1'                                => '/packages/',
			'package-2'                                => '/packages/',
			'g1-exit-to-get-g2'                        => '/packages/',
			'g2-exit-to-get-g'                         => '/packages/',
			'blog'                                     => '/',
		);
		if ( isset( $map[ $path ] ) ) {
			wp_safe_redirect( home_url( $map[ $path ] ), 301, 'MRN WDS SEO' );
			exit;
		}
	}
}
