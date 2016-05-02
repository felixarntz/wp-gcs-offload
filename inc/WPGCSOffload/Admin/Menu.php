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

if ( ! class_exists( 'WPGCSOffload\Admin\Menu' ) ) {
	/**
	 * This class creates the plugin's admin menu item and content.
	 *
	 * @since 0.5.0
	 */
	class Menu {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private $manager = null;

		private $settings = null;

		private $cap = '';

		private function __construct() {
			// empty
		}

		public function set_manager( $manager ) {
			$this->manager = $manager;
		}

		public function set_settings( $settings ) {
			$this->settings = $settings;
		}

		public function add_components( $wpod ) {
			$wpod->add_components( array(
				Admin::MENU_SLUG			=> array(
					'screens'					=> array(
						Admin::SCREEN_SLUG			=> array(
							'title'						=> __( 'Google Cloud Storage', 'wp-gcs-offload' ),
							'label'						=> __( 'Google Cloud Storage', 'wp-gcs-offload' ),
							'capability'				=> Admin::CAP_MANAGE,
							'tabs'						=> array(
								Admin::MANAGE_TAB_SLUG		=> array(
									'title'						=> __( 'Manage', 'wp-gcs-offload' ),
									'description'				=> __( 'Manage and transfer media between your server and your Google Cloud Storage bucket.', 'wp-gcs-offload' ),
									'capability'				=> Admin::CAP_MANAGE,
									'callback'					=> array( $this->manager, 'render' ),
								),
								Admin::SETTINGS_TAB_SLUG	=> array(
									'title'						=> __( 'Settings', 'wp-gcs-offload' ),
									'description'				=> __( 'Set up your Google Cloud Storage bucket.', 'wp-gcs-offload' ),
									'capability'				=> Admin::CAP_SETUP,
									'mode'						=> 'draggable',
									'sections'					=> $this->settings->get_sections(),
								),
							),
						),
					),
				),
			), 'wp-gcs-offload' );
		}
	}
}
