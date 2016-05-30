<?php
/**
 * WPGCSOffload\Admin\AttachmentEdit class
 *
 * @package WPGCSOffload
 * @subpackage Admin
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

namespace WPGCSOffload\Admin;

use WPGCSOffload\App;
use WPGCSOffload\Core\Attachment;
use WPGCSOffload\Core\Client;
use WPGCSOffload\Core\AjaxHandler;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Admin\AttachmentEdit' ) ) {
	/**
	 * This class handles the single editing page of an attachment.
	 *
	 * @since 0.5.0
	 */
	class AttachmentEdit {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {
			AjaxHandler::instance()->register_action( 'sync_attachment_upstream', array( $this, 'ajax_sync_attachment_upstream' ) );
			AjaxHandler::instance()->register_action( 'sync_attachment_downstream', array( $this, 'ajax_sync_attachment_downstream' ) );
			AjaxHandler::instance()->register_action( 'delete_attachment_upstream', array( $this, 'ajax_delete_attachment_upstream' ) );
			AjaxHandler::instance()->register_action( 'delete_attachment_downstream', array( $this, 'ajax_delete_attachment_downstream' ) );
		}

		public function enqueue_scripts( $hook ) {
			if ( 'post.php' !== $hook ) {
				return;
			}

			if ( 'attachment' !== get_post_type() ) {
				return;
			}

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'wp-gcs-offload-attachment-edit', App::get_url( 'assets/dist/js/attachment-edit' . $min . '.js' ), array( 'jquery', 'wp-util'), App::get_info( 'version' ), true );
			wp_enqueue_style( 'wp-gcs-offload-attachment-edit', App::get_url( 'assets/dist/css/attachment-edit' . $min . '.css' ), array(), App::get_info( 'version' ) );

			wp_localize_script( 'wp-gcs-offload-attachment-edit', '_wpGCSOffloadAttachmentEdit', array(
				'sync_attachment_upstream_nonce'	=> AjaxHandler::instance()->get_action_nonce( 'sync_attachment_upstream' ),
				'sync_attachment_downstream_nonce'	=> AjaxHandler::instance()->get_action_nonce( 'sync_attachment_downstream' ),
				'delete_attachment_upstream_nonce'	=> AjaxHandler::instance()->get_action_nonce( 'delete_attachment_upstream' ),
				'delete_attachment_downstream_nonce'=> AjaxHandler::instance()->get_action_nonce( 'delete_attachment_downstream' ),
				'error_prefix'						=> __( 'Error:', 'wp-gcs-offload' ),
			) );
		}

		public function attachment_submitbox_misc_actions( $id = null ) {
			$attachment = Attachment::get( $id );
			if ( ! $attachment ) {
				return;
			}

			?>
			<div id="wp-gcs-offload-data">
				<div class="misc-pub-section misc-pub-gcs-data">
					<hr />
					<strong><?php _e( 'Google Cloud Storage Data', 'wp-gcs-offload' ); ?></strong>
				</div>

				<?php
				if ( ! $attachment->is_cloud_storage_file() ) {
					?>
					<div class="misc-pub-section misc-pub-gcs-sync-upstream">
						<?php _e( 'This attachment is not synced with Google Cloud Storage.', 'wp-gcs-offload' ); ?>
						<?php if ( Client::instance()->is_configured() ) : ?>
							<a href="#" id="wp-gcs-offload-sync-attachment-upstream"><?php _e( 'Sync now', 'wp-gcs-offload' ); ?></a>
						<?php endif; ?>
					</div>
					<?php
					return;
				}

				if ( ! $attachment->is_local_file() ) {
					?>
					<div class="misc-pub-section misc-pub-gcs-sync-downstream">
						<?php _e( 'This attachment is on Google Cloud Storage, but not available locally.', 'wp-gcs-offload' ); ?>
						<?php if ( Client::instance()->is_configured() ) : ?>
							<a href="#" id="wp-gcs-offload-sync-attachment-downstream"><?php _e( 'Sync now', 'wp-gcs-offload' ); ?></a>
						<?php endif; ?>
					</div>
					<?php
				} else {
					?>
					<div class="misc-pub-section misc-pub-gcs-delete-downstream">
						<?php _e( 'This attachment is available on Google Cloud Storage and locally.', 'wp-gcs-offload' ); ?>
						<?php if ( Client::instance()->is_configured() ) : ?>
							<a href="#" id="wp-gcs-offload-delete-attachment-downstream"><?php _e( 'Delete locally', 'wp-gcs-offload' ); ?></a>
						<?php endif; ?>
					</div>
					<?php
				}

				?>

				<div class="misc-pub-section misc-pub-gcs-bucket-name">
					<?php _e( 'Bucket Name:', 'wp-gcs-offload' ); ?> <strong><?php echo $attachment->get_cloud_storage_bucket_name(); ?></strong>
				</div>
				<div class="misc-pub-section misc-pub-gcs-dir-name">
					<?php _e( 'Directory Name:', 'wp-gcs-offload' ); ?> <strong><?php echo $attachment->get_cloud_storage_dir_name(); ?></strong>
				</div>
				<div class="misc-pub-section misc-pub-gcs-delete-upstream">
					<?php _e( 'This attachment is on Google Cloud Storage.', 'wp-gcs-offload' ); ?>
					<?php if ( Client::instance()->is_configured() ) : ?>
						<a href="#" id="wp-gcs-offload-delete-attachment-upstream"><?php _e( 'Delete from Google Cloud Storage', 'wp-gcs-offload' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		public function ajax_sync_attachment_upstream( $data ) {
			$attachment = $this->ajax_get_attachment( $data );
			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}

			$gcs_data = $attachment->upload_to_cloud_storage();
			if ( is_wp_error( $gcs_data ) ) {
				return $gcs_data;
			}

			ob_start();
			$this->attachment_submitbox_misc_actions( $attachment_id );
			$html = ob_get_clean();

			return array(
				'gcs_data'	=> $gcs_data,
				'html'		=> $html,
				'message'	=> __( 'Attachment successfully uploaded to Google Cloud Storage.', 'wp-gcs-offload' ),
			);
		}

		public function ajax_sync_attachment_downstream( $data ) {
			$attachment = $this->ajax_get_attachment( $data );
			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}

			$gcs_data = $attachment->download_from_cloud_storage();
			if ( is_wp_error( $gcs_data ) ) {
				return $gcs_data;
			}

			ob_start();
			$this->attachment_submitbox_misc_actions( $attachment_id );
			$html = ob_get_clean();

			return array(
				'gcs_data'	=> $gcs_data,
				'html'		=> $html,
				'message'	=> __( 'Attachment successfully downloaded from Google Cloud Storage.', 'wp-gcs-offload' ),
			);
		}

		public function ajax_delete_attachment_upstream( $data ) {
			$attachment = $this->ajax_get_attachment( $data );
			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}

			$gcs_data = $attachment->delete_from_cloud_storage();
			if ( is_wp_error( $gcs_data ) ) {
				return $gcs_data;
			}

			ob_start();
			$this->attachment_submitbox_misc_actions( $attachment_id );
			$html = ob_get_clean();

			return array(
				'gcs_data'	=> $gcs_data,
				'html'		=> $html,
				'message'	=> __( 'Attachment successfully deleted from Google Cloud Storage.', 'wp-gcs-offload' ),
			);
		}

		public function ajax_delete_attachment_downstream( $data ) {
			$attachment = $this->ajax_get_attachment( $data );
			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}

			$gcs_data = $attachment->delete_local_file();
			if ( is_wp_error( $gcs_data ) ) {
				return $gcs_data;
			}

			ob_start();
			$this->attachment_submitbox_misc_actions( $attachment_id );
			$html = ob_get_clean();

			return array(
				'gcs_data'	=> $gcs_data,
				'html'		=> $html,
				'message'	=> __( 'Attachment successfully deleted locally.', 'wp-gcs-offload' ),
			);
		}

		private function ajax_get_attachment( $data ) {
			if ( ! isset( $data['attachment_id'] ) ) {
				return new WP_Error( 'attachment_id_missing', __( 'Missing attachment ID.', 'wp-gcs-offload' ) );
			}

			$attachment_id = absint( $data['attachment_id'] );

			$attachment = Attachment::get( $attachment_id );

			if ( ! $attachment ) {
				return new WP_Error( 'attachment_id_invalid', __( 'Invalid attachment ID.', 'wp-gcs-offload' ) );
			}

			return $attachment;
		}
	}
}
