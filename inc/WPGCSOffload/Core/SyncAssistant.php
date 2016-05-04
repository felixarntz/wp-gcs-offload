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

if ( ! class_exists( 'WPGCSOffload\Core\SyncAssistant' ) ) {
	/**
	 * This class is responsible for syncing attachments with Google Cloud Storage.
	 *
	 * @since 0.5.0
	 */
	class SyncAssistant {
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

		public function wp_update_attachment_metadata( $data, $id ) {
			if ( ! Client::instance()->is_configured() ) {
				return $data;
			}

			if ( ! Settings::instance()->get_setting( 'sync_addition' ) ) {
				return $data;
			}

			$attachment = Attachment::get( $id );
			if ( ! $attachment ) {
				return $data;
			}

			$status = $attachment->upload_to_google_cloud_storage( $data );
			if ( is_wp_error( $status ) ) {
				return $data;
			}

			if ( Settings::instance()->get_setting( 'remote_only' ) ) {
				$attachment->delete_local_file( $data );
			}

			return $data;
		}
	}
}
