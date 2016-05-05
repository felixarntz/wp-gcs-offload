<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
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
			App::get_background_sync()->enqueue_script( '#wpgcso-progress', '#wpgcso-dispatch' );
		}

		public function render() {
			?>
			<button id="wpgcso-dispatch" class="button button-primary"><?php _e( 'Start Sync', 'wp-gcs-offload' ); ?></button>
			<div id="wpgcso-progress"></div>
			<?php
		}
	}
}
