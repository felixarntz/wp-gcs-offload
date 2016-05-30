<?php
/**
 * WPGCSOffload\Admin\Manager class
 *
 * @package WPGCSOffload
 * @subpackage Admin
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

namespace WPGCSOffload\Admin;

use WPGCSOffload\App;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Admin\Manager' ) ) {
	/**
	 * This class creates the GCS media management page.
	 *
	 * @since 0.5.0
	 */
	class Manager {
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

		public function init() {
			$background_sync = App::get_background_sync();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_footer', array( $background_sync, 'print_script_template' ), 1 );
		}

		public function enqueue_scripts() {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style( 'wp-gcs-offload-manage-gcs', App::get_url( 'assets/dist/css/manage-gcs' . $min . '.css' ), array(), App::get_info( 'version' ) );

			App::get_background_sync()->enqueue_script( '#wpgcso-progress', '#wpgcso-start', '#wpgcso-empty-logs' );
		}

		public function render() {
			?>
			<div class="wpgcso-sync-wrap">
				<p class="highlight-text"><?php _e( 'Click on the sync button below to start transferring your existing attachments to Google Cloud Storage.', 'wp-gcs-offload' ); ?></p>
				<p><button id="wpgcso-start" class="button button-primary button-hero"><?php _e( 'Start Sync', 'wp-gcs-offload' ); ?></button></p>
				<p><a id="wpgcso-empty-logs" class="remove" href="#"><?php _e( 'Empty Logs', 'wp-gcs-offload' ); ?></a></p>
			</div>
			<div id="wpgcso-progress"></div>
			<?php
		}
	}
}
