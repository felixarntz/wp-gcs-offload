<?php
/**
 * WPGCSOffload\App class
 *
 * @package WPGCSOffload
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
 */

namespace WPGCSOffload;

use WPGCSOffload\Core\Core;
use WPGCSOffload\Admin\Admin;
use WPGCSOffload\Core\BackgroundSync;
use LaL_WP_Plugin as Plugin;
use WP_Background_Process_Logging;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\App' ) ) {
	/**
	 * This class initializes the plugin.
	 *
	 * It also triggers the action and filter to hook into and contains all API functions of the plugin.
	 *
	 * @since 0.5.0
	 */
	class App extends Plugin {

		/**
		 * @since 0.5.0
		 * @var array Holds the plugin data.
		 */
		protected static $_args = array();

		protected static $background_sync = null;

		/**
		 * Class constructor.
		 *
		 * This is protected on purpose since it is called by the parent class' singleton.
		 *
		 * @internal
		 * @since 0.5.0
		 */
		protected function __construct( $args ) {
			parent::__construct( $args );
		}

		/**
		 * The run() method.
		 *
		 * This will initialize the plugin.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $args array of class arguments (passed by the plugin utility class)
		 */
		protected function run() {
			WP_Background_Process_Logging::init();

			self::setup_background_sync();

			Core::instance()->run();
			Admin::instance()->run();
		}

		public static function setup_background_sync() {
			self::$background_sync = new BackgroundSync();
		}

		public static function get_background_sync() {
			return self::$background_sync;
		}

		/**
		 * Adds a link to the settings page to the plugins table.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_plugin_links( $links = array() ) {
			if ( ! current_user_can( Admin::CAP_SETUP ) ) {
				return $links;
			}

			$custom_links = array(
				'<a href="' . Admin::get_settings_url() . '">' . __( 'Settings', 'wp-gcs-offload' ) . '</a>',
			);

			return array_merge( $custom_links, $links );
		}

		/**
		 * Adds a link to the settings page to the network plugins table.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param array $links the original links
		 * @return array the modified links
		 */
		public static function filter_network_plugin_links( $links = array() ) {
			return self::filter_plugin_links( $links );
		}

		/**
		 * Renders a plugin information message.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_status_message( $status, $context = 'site' ) {
			?>
			<p>
				<?php if ( 'activated' === $status ) : ?>
					<?php printf( __( 'You have just activated %s.', 'wp-gcs-offload' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php elseif ( 'network' === $context ) : ?>
					<?php printf( __( 'You are running the plugin %s on your network.', 'wp-gcs-offload' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php else : ?>
					<?php printf( __( 'You are running the plugin %s on your site.', 'wp-gcs-offload' ), '<strong>' . self::get_info( 'name' ) . '</strong>' ); ?>
				<?php endif; ?>
			</p>
			<p>
				<?php _e( 'This plugin can be used to offload your media library to Google Cloud Storage.', 'wp-gcs-offload' ); ?>
				<?php if ( current_user_can( Admin::CAP_SETUP ) ) :
					printf( __( 'Go to the <a href="%s">Settings Page</a> to get started!', 'wp-gcs-offload' ), Admin::get_settings_url() );
				endif; ?>
			</p>
			<?php
		}

		/**
		 * Renders a network plugin information message.
		 *
		 * @internal
		 * @since 0.5.0
		 * @param string $status either 'activated' or 'active'
		 * @param string $context either 'site' or 'network'
		 */
		public static function render_network_status_message( $status, $context = 'network' ) {
			self::render_status_message( $status, $context );
		}
	}
}
