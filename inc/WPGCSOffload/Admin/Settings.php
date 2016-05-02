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

if ( ! class_exists( 'WPGCSOffload\Admin\Settings' ) ) {
	/**
	 * This class creates the plugin settings page.
	 *
	 * @since 0.5.0
	 */
	class Settings {
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

		public function get_setting( $name ) {
			if ( Admin::is_network_active() ) {
				$constant_name = $this->get_setting_constant_name( $name );
				if ( defined( $constant_name ) ) {
					return constant( $constant_name );
				}

				return '';
			}

			$settings = get_option( 'wp-gcs-offload-settings', array() );
			if ( isset( $settings[ $name ] ) ) {
				return $settings[ $name ];
			}

			return '';
		}

		public function get_sections() {
			return array(
				'bucket_configuration'		=> array(
					'title'						=> __( 'Bucket Configuration', 'wp-gcs-offload' ),
					'description'				=> __( 'Configurate your Google Cloud Storage bucket.', 'wp-gcs-offload' ),
					'fields'					=> $this->get_bucket_configuration_fields(),
				),
				'site_configuration'		=> array(
					'title'						=> __( 'Site Configuration', 'wp-gcs-offload' ),
					'description'				=> __( 'Define how your site interacts with Google Cloud Storage.', 'wp-gcs-offload' ),
					'fields'					=> $this->get_site_configuration_fields(),
				),
			);
		}

		public function get_bucket_configuration_fields() {
			$description_suffix = '';
			$readonly = false;
			if ( Admin::is_network_active() ) {
				$description_suffix .= ' ' . __( 'This value can only be changed through the constant %s.', 'wp-gcs-offload' );
				$readonly = true;
			}

			return array(
				'authentication_key'		=> array(
					'title'						=> __( 'Authentication Key', 'wp-gcs-offload' ),
					'description'				=> __( 'The JSON authentication key for your Google Cloud Storage account.', 'wp-gcs-offload' ) . sprintf( $description_suffix, '<code>' . $this->get_setting_constant_name( 'authentication_key' ) . '</code>' ),
					'type'						=> 'textarea',
					'readonly'					=> $readonly,
					'default'					=> $this->get_setting( 'authentication_key' ),
				),
				'bucket_name'				=> array(
					'title'						=> __( 'Bucket Name', 'wp-gcs-offload' ),
					'description'				=> __( 'The name of your Google Cloud Storage bucket.', 'wp-gcs-offload' ) . sprintf( $description_suffix, '<code>' . $this->get_setting_constant_name( 'bucket_name' ) . '</code>' ),
					'type'						=> 'text',
					'readonly'					=> $readonly,
					'default'					=> $this->get_setting( 'bucket_name' ),
				),
			);
		}

		public function get_site_configuration_fields() {
			return array(
				'gcs_mode'					=> array(
					'title'						=> __( 'Plugin Mode', 'wp-gcs-offload' ),
					'description'				=> __( 'Specify whether your site should prefer local or remote attachment files and whether files should remain local on the server. Note that this will not instantly change anything - it will only specify how the plugin is going to work in the future.', 'wp-gcs-offload' ),
					'type'						=> 'select',
					'options'					=> array(
						'prefer_local'				=> __( 'Prefer local files', 'wp-gcs-offload' ),
						'prefer_remote'				=> __( 'Prefer Google Cloud Storage files', 'wp-gcs-offload' ),
						'delete_local'				=> __( 'Delete local files and only load from Google Cloud Storage', 'wp-gcs-offload' ),
					),
				),
			);
		}

		private function get_setting_constant_name( $name ) {
			return 'WPGCS_OFFLOAD_' . strtoupper( $name );
		}
	}
}
