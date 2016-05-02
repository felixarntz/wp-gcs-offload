<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Core;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Core\Client' ) ) {
	/**
	 * This is a client class to interact with Google Cloud Storage.
	 *
	 * @since 0.5.0
	 */
	class Client {
		const BASE_URL = '//storage.googleapis.com/';

		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private $authentication_key = '';
		private $bucket_name = '';

		private function __construct() {
			// empty
		}

		public function set_authentication_key( $key ) {
			$this->authentication_key = $key;
		}

		public function set_bucket_name( $bucket_name ) {
			$this->bucket_name = $bucket_name;
		}

		public function is_configured() {
			return ! empty( $this->authentication_key ) && ! empty( $this->bucket_name );
		}

		private function get_dir_name() {
			if ( ! is_multisite() ) {
				$url = str_replace( array( 'https://', 'http://' ), '', untrailingslashit( home_url() ) );
			} else {
				global $current_blog;

				$url = untrailingslashit( $current_blog->domain . $current_blog->path );
			}

			return str_replace( '/', '-', $url );
		}
	}
}
