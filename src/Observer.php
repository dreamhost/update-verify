<?php
/**
 * Observes the upgrade process
 *
 * @package Update-Verify
 */

namespace UpdateVerify;

/**
 * Verifies core updates
 */
class Observer {

	/**
	 * Fires near the beginning of the upgrade process
	 *
	 * @param false       $retval   Returns false to continue the process.
	 * @param string      $package  The package file name.
	 * @param WP_Upgrader $upgrader The WP_Upgrader instance.
	 */
	public static function filter_upgrader_pre_download( $retval, $package, $upgrader ) {
		self::log_message( 'Fetching pre-update site response...' );
		$site_response = self::check_site_response( home_url( '/' ) );
		/**
		 * Permit modification of $retval based on the site response.
		 *
		 * @param mixed       $retval        Return value to WP_Upgrader.
		 * @param array       $site_response Values for the site heuristics check.
		 * @param string      $package       The package file name.
		 * @param WP_Upgrader $upgrader      The WP_Upgrader instance.
		 */
		$retval = apply_filters( 'upgrade_verify_upgrader_pre_download', $retval, $site_response, $package, $upgrader );
		$stage  = 'pre';

		if ( 200 !== (int) $site_response['status_code'] ) {
			$is_errored = sprintf( 'Failed %s-update status code check (HTTP code %d).', $stage, $site_response['status_code'] );
		} elseif ( ! empty( $site_response['php_fatal'] ) ) {
			$is_errored = sprintf( 'Failed %s-update PHP fatal error check.', $stage );
		} elseif ( empty( $site_response['closing_body'] ) ) {
			$is_errored = sprintf( 'Failed %s-update closing </body> tag check.', $stage );
		}

		if ( $is_errored ) {
			if ( method_exists( 'WP_Upgrader', 'release_lock' ) ) {
				\WP_Upgrader::release_lock( 'core_updater' );
			}
			return new \WP_Error( 'upgrade_verify_fail', $is_errored );
		}

		return $retval;
	}

	/**
	 * Fires at the end of the upgrade process
	 *
	 * @param object $upgrader Upgrader instance.
	 * @param array  $result   Result of the upgrade process.
	 */
	public static function action_upgrader_process_complete( $upgrader, $result ) {
		self::log_message( 'Fetching post-update site response...' );
		$site_response = self::check_site_response( home_url( '/' ) );

		$stage  = 'post';

		if ( 200 !== (int) $site_response['status_code'] ) {
			$is_errored = sprintf( 'Failed %s-update status code check (HTTP code %d).', $stage, $site_response['status_code'] );
		} elseif ( ! empty( $site_response['php_fatal'] ) ) {
			$is_errored = sprintf( 'Failed %s-update PHP fatal error check.', $stage );
		} elseif ( empty( $site_response['closing_body'] ) ) {
			$is_errored = sprintf( 'Failed %s-update closing </body> tag check.', $stage );
		}

		if ( $is_errored ) {
			if ( method_exists( 'WP_Upgrader', 'release_lock' ) ) {
				\WP_Upgrader::release_lock( 'core_updater' );
			}
			return new \WP_Error( 'upgrade_verify_fail', $is_errored );
		}

		/**
		 * Permit action based on the post-update site response check.
		 *
		 * @param array       $site_response Values for the site heuristics check.
		 * @param WP_Upgrader $upgrader      The WP_Upgrader instance.
		 */
		do_action( 'upgrade_verify_upgrader_process_complete', $site_response, $upgrader );
	}

	/**
	 * Log a message to STDOUT
	 *
	 * @param string $message Message to render.
	 */
	private static function log_message( $message ) {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::log( $message );
		} else {
			echo htmlentities( $message ) . PHP_EOL;
		}
	}

	/**
	 * Check a site response for basic operating details and log output.
	 *
	 * @param string $url URL to check.
	 * @return array Response data.
	 */
	public static function check_site_response( $url ) {
		$curl_response = self::url_test( $url );

		if ( false === $curl_response ) {
			$response = array(
				'status_code' => '418',
				'body'        => 'I\'m a little teapot.',
			);
		} else {
			$response = self::get_site_response( $url );
		}

		self::log_message( ' -> HTTP status code: ' . $response['status_code'] );

		$site_response = array(
			'status_code'  => (int) $response['status_code'],
			'closing_body' => true,
			'php_fatal'    => false,
		);

		if ( 418 !== (int) $response['status_code'] ) {
			if ( false === stripos( $response['body'], '</body>' ) ) {
				self::log_message( ' -> No closing </body> tag detected.' );
				$site_response['closing_body'] = false;
			} else {
				self::log_message( ' -> Correctly detected closing </body> tag.' );
				$site_response['closing_body'] = true;
			}
			$stripped_body = strip_tags( $response['body'] );
			if ( false !== stripos( $stripped_body, 'Fatal error:' ) ) {
				self::log_message( ' -> Detected uncaught fatal error.' );
				$site_response['php_fatal'] = true;
			} else {
				self::log_message( ' -> No uncaught fatal error detected.' );
				$site_response['php_fatal'] = false;
			}
		} else {
			self::log_message( ' -> ' . $response['body'] );
			$site_response['php_fatal'] = true;
		}

		return $site_response;
	}

	/**
	 * Capture basic operating details
	 *
	 * We do this via CURL for access to CURLOPT_RESOLVE, which is needed in
	 * order to skip around DNS issues.
	 *
	 * @param  string $check_url URL to check.
	 * @return array  status_code (int), body (html)
	 */
	private static function get_site_response( $check_url ) {
		$timeout = 10;
		$ip      = getenv( 'RESOLVE_DOMAIN' );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $check_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
		if ( false !== $ip ) {
			curl_setopt(
				$ch,
				CURLOPT_RESOLVE,
				array(
					'www.' . $check_url . ':443:' . $ip,
					$check_url . ':443:' . $ip,
					'www.' . $check_url . ':80:' . $ip,
					$check_url . ':80:' . $ip,
				),
			);
		}
		// Get the data we need.
		$raw_body    = curl_exec( $ch );
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$http_code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$body        = substr( $raw_body, $header_size );

		curl_close( $ch );

		// Build array.
		$response = array(
			'status_code' => (int) $http_code,
			'body'        => $body,
		);

		return $response;
	}

	/**
	 * A basic CURL check first
	 *
	 * @param string $url URL to check.
	 */
	private function url_test( $url ) {

		// Get IP from environment variable.
		$ip = getenv( 'RESOLVE_DOMAIN' );

		// parse URL.
		$parsed_url = wp_parse_url( $url );

		$timeout = 10;
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		if ( false !== $ip ) {
			curl_setopt( $ch, CURLOPT_RESOLVE, array( $ip ) );
			curl_setopt(
				$ch,
				CURLOPT_RESOLVE,
				array(
					'www.' . $parsed_url['host'] . ':443:' . $ip,
					$parsed_url['host'] . ':443:' . $ip,
					'www.' . $parsed_url['host'] . ':80:' . $ip,
					$parsed_url['host'] . ':80:' . $ip,
				),
			);
		}
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		$http_respond = curl_exec( $ch );
		$http_respond = trim( strip_tags( $http_respond ) );
		$http_code    = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( in_array( $http_code, array( '200', '302' ) ) ) {
			return true;
		} else {
			return false;
		}
		curl_close( $ch );
	}

}
