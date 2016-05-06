<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
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
			AjaxHandler::instance()->register_action( 'sync_attachment', array( $this, 'ajax_sync_attachment' ) );
		}

		public function enqueue_scripts( $hook ) {
			if ( 'post.php' !== $hook ) {
				return;
			}

			if ( 'attachment' !== get_post_type() ) {
				return;
			}

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'wp-gcs-offload-attachment-edit', App::get_url( 'assets/attachment-edit' . $min . '.js' ), array( 'jquery', 'wp-util'), App::get_info( 'version' ), true );
			wp_enqueue_style( 'wp-gcs-offload-attachment-edit', App::get_url( 'assets/attachment-edit' . $min . '.css' ), array(), App::get_info( 'version' ) );

			wp_localize_script( 'wp-gcs-offload-attachment-edit', '_wpGCSOffloadAttachmentEdit', array(
				'sync_attachment_nonce'	=> AjaxHandler::instance()->get_action_nonce( 'sync_attachment' ),
				'error_prefix'			=> __( 'Error:', 'wp-gcs-offload' ),
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
					<div class="misc-pub-section misc-pub-gcs-not-synced">
						<?php _e( 'This attachment is not synced with Google Cloud Storage.', 'wp-gcs-offload' ); ?>
						<?php if ( Client::instance()->is_configured() ) : ?>
							<a href="#" id="wp-gcs-offload-sync-attachment"><?php _e( 'Sync now!', 'wp-gcs-offload' ); ?></a>
						<?php endif; ?>
					</div>
					<?php
					return;
				}
				?>

				<div class="misc-pub-section misc-pub-gcs-bucket-name">
					<?php _e( 'Bucket Name:', 'wp-gcs-offload' ); ?> <strong><?php echo $attachment->get_cloud_storage_bucket_name(); ?></strong>
				</div>
				<div class="misc-pub-section misc-pub-gcs-dir-name">
					<?php _e( 'Directory Name:', 'wp-gcs-offload' ); ?> <strong><?php echo $attachment->get_cloud_storage_dir_name(); ?></strong>
				</div>
			</div>
			<?php
		}

		public function ajax_sync_attachment( $data ) {
			return new WP_Error( 'TODO', __( 'This method is not yet implemented.', 'wp-gcs-offload' ) );

			if ( ! isset( $data['attachment_id'] ) ) {
				return new WP_Error( 'attachment_id_missing', __( 'Missing attachment ID.', 'wp-gcs-offload' ) );
			}

			$attachment_id = absint( $data['attachment_id'] );

			$attachment = Attachment::get( $attachment_id );

			if ( ! $attachment ) {
				return new WP_Error( 'attachment_id_invalid', __( 'Invalid attachment ID.', 'wp-gcs-offload' ) );
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
	}
}
