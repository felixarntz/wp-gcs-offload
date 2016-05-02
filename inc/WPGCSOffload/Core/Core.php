<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Core;

use WPGCSOffload\Admin\Settings as Settings;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Core\Core' ) ) {
	/**
	 * This class handles the plugin's main functionality.
	 *
	 * @since 0.5.0
	 */
	class Core {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			// empty
		}

		public function run() {
			$this->setup_client();
			$this->setup_url_fixer();
		}

		public function setup_client() {
			$settings = Settings::instance();

			$client = Client::instance();
			$client->set_authentication_key( $settings->get_setting( 'authentication_key' ) );
			$client->set_bucket_name( $settings->get_setting( 'bucket_name' ) );
		}

		public function setup_url_fixer() {
			$gcs_mode = Settings::instance()->get_setting( 'gcs_mode' );

			if ( ! $gcs_mode || 'prefer_local' === $gcs_mode ) {
				return;
			}

			$url_fixer = URLFixer::instance();

			add_filter( 'image_downsize', array( $url_fixer, 'image_downsize' ), 10, 3 );
			add_filter( 'wp_get_attachment_url', array( $url_fixer, 'wp_get_attachment_url' ), 10, 2 );
			add_filter( 'attachment_url_to_postid', array( $url_fixer, 'attachment_url_to_postid' ), 10, 2 );
		}
	}
}
