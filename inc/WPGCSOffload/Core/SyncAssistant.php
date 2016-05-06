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

		public function sync_addition( $data, $id ) {
			$attachment = Attachment::get( $id );
			if ( ! $attachment ) {
				return $data;
			}

			$status = $attachment->upload_to_cloud_storage( $data );

			return $data;
		}

		public function sync_deletion( $id ) {
			$attachment = Attachment::get( $id );
			if ( ! $attachment ) {
				return;
			}

			$status = $attachment->delete_from_cloud_storage( false, true );
		}

		public function delete_local_on_upload( $id, $attachment, $metadata ) {
			$status = $attachment->delete_local_file( $metadata, true );
		}

		public function store_local_on_remote_only_delete( $id, $attachment, $metadata ) {
			if ( $attachment->is_local_file() ) {
				return;
			}

			$status = $attachment->download_from_cloud_storage( $metadata );
		}

		public function store_remote_on_local_only_delete( $id, $attachment, $metadata ) {
			if ( $attachment->is_cloud_storage_file() ) {
				return;
			}

			$status = $attachment->upload_to_cloud_storage( $metadata );
		}
	}
}
