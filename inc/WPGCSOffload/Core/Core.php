<?php
/**
 * WPGCSOffload\Core\Core class
 *
 * @package WPGCSOffload
 * @subpackage Core
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
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
			$this->setup_sync_assistant();
			$this->setup_ajax_handler();
		}

		public function setup_client() {
			$settings = Settings::instance();

			$client = Client::instance();
			$client->set_authentication_key( $settings->get_setting( 'authentication_key' ) );
			$client->set_bucket_name( $settings->get_setting( 'bucket_name' ) );
		}

		public function setup_url_fixer() {
			$url_fixer = URLFixer::instance();

			add_filter( 'image_downsize', array( $url_fixer, 'image_downsize' ), 10, 3 );
			add_filter( 'wp_get_attachment_url', array( $url_fixer, 'wp_get_attachment_url' ), 10, 2 );
			add_filter( 'attachment_url_to_postid', array( $url_fixer, 'attachment_url_to_postid' ), 10, 2 );
		}

		public function setup_sync_assistant() {
			if ( ! Client::instance()->is_configured() ) {
				return;
			}

			$sync_assistant = SyncAssistant::instance();

			if ( Settings::instance()->get_setting( 'sync_addition' ) ) {
				add_filter( 'wp_update_attachment_metadata', array( $sync_assistant, 'sync_addition' ), 10, 2 );
			}
			if ( Settings::instance()->get_setting( 'sync_deletion' ) ) {
				add_action( 'delete_attachment', array( $sync_assistant, 'sync_deletion' ), 10, 1 );
			}
			if ( Settings::instance()->get_setting( 'remote_only' ) ) {
				add_action( 'wpgcso_uploaded_to_cloud_storage', array( $sync_assistant, 'delete_local_on_upload' ), 10, 3 );
			}

			// the following hooks prevent accidental removing of files
			add_action( 'wpgcso_delete_from_cloud_storage', array( $sync_assistant, 'store_local_on_remote_only_delete' ), 10, 3 );
			add_action( 'wpgcso_delete_local_file', array( $sync_assistant, 'store_remote_on_local_only_delete' ), 10, 3 );
		}

		public function setup_ajax_handler() {
			$ajax_handler = AjaxHandler::instance();

			add_action( 'admin_init', array( $ajax_handler, 'add_actions' ) );
		}
	}
}
