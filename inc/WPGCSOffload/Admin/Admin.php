<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Admin\Admin' ) ) {
	/**
	 * This class handles the plugin's admin functionality.
	 *
	 * @since 0.5.0
	 */
	class Admin {
		const MENU_SLUG = 'tools.php';

		const SCREEN_SLUG = 'wp-gcs-offload';
		const MANAGE_TAB_SLUG = 'wp-gcs-offload-manage';
		const SETTINGS_TAB_SLUG = 'wp-gcs-offload-settings';

		const CAP_MANAGE = 'manage_google_cloud_storage';
		const CAP_SETUP = 'setup_google_cloud_storage';

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
			$menu = Menu::instance();

			$manager = Manager::instance();
			$settings = Settings::instance();
			$attachment_edit = AttachmentEdit::instance();

			$menu->set_manager( $manager );
			$menu->set_settings( $settings );

			add_action( 'wpod', array( $menu, 'add_components' ) );
			add_action( 'admin_enqueue_scripts', array( $attachment_edit, 'enqueue_scripts' ), 10, 1 );
			add_action( 'attachment_submitbox_misc_actions', array( $attachment_edit, 'attachment_submitbox_misc_actions' ), 100, 0 );

			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		}

		public function map_meta_cap( $caps, $cap, $user_id, $args ) {
			switch ( $cap ) {
				case self::CAP_MANAGE:
				case self::CAP_SETUP:
					if ( ! self::is_network_active() ) {
						return array( 'manage_options' );
					}
					break;
			}

			return $caps;
		}

		public static function is_network_active() {
			if ( ! is_multisite() ) {
				return false;
			}

			$network_active_plugins = get_site_option( 'lalwpplugin_activated_plugins', array() );

			return isset( $network_active_plugins['wp-gcs-offload'] );
		}

		public static function get_manage_url() {
			return add_query_arg( array(
				'page'	=> self::SCREEN_SLUG,
				'tab'	=> self::SETTINGS_TAB_SLUG
			), admin_url( self::MENU_SLUG ) );
		}

		public static function get_settings_url() {
			return add_query_arg( array(
				'page'	=> self::SCREEN_SLUG,
				'tab'	=> self::SETTINGS_TAB_SLUG
			), admin_url( self::MENU_SLUG ) );
		}
	}
}
