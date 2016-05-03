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
			// empty
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
	}
}
