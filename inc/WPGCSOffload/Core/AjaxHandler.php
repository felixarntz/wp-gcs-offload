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

if ( ! class_exists( 'WPGCSOffload\Core\AjaxHandler' ) ) {
	/**
	 * This class handles AJAX calls.
	 *
	 * @since 0.5.0
	 */
	class AjaxHandler {
		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private $actions = array();
		private $nonces = array();

		private function __construct() {
			// empty
		}

		public function register_action( $name, $callback, $nopriv = false ) {
			if ( isset( $this->actions[ $name ] ) ) {
				return false;
			}

			$this->actions[ $name ] = array(
				'callback'		=> $callback,
				'nopriv'		=> $nopriv,
			);

			return true;
		}

		public function add_actions() {
			if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
				return;
			}

			foreach ( $this->actions as $action => $data ) {
				add_action( 'wp_ajax_wpgcso_' . $action, array( $this, 'request' ) );
				if ( $data['nopriv'] ) {
					add_action( 'wp_ajax_nopriv_wpgcso_' . $action, array( $this, 'request' ) );
				}
			}
		}

		public function request() {
			$name = str_replace( 'wpgcso_', '', $_REQUEST['action'] );

			if ( ! isset( $this->actions[ $name ] ) ) {
				wp_send_json_error( __( 'Invalid action.', 'wp-gcs-offload' ) );
			}

			if ( ! is_callable( $this->actions[ $name ]['callback'] ) ) {
				wp_send_json_error( __( 'Invalid action callback.', 'wp-gcs-offload' ) );
			}

			if ( ! isset( $_REQUEST['nonce'] ) ) {
				wp_send_json_error( __( 'Missing nonce.', 'wp-gcs-offload' ) );
			}

			if ( ! check_ajax_referer( $this->get_nonce_action( $name ), 'nonce', false ) ) {
				wp_send_json_error( __( 'Invalid nonce.', 'wp-gcs-offload' ) );
			}

			$response = call_user_func( $this->actions[ $name ]['callback'], $_REQUEST );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( $response->get_error_message() );
			}

			wp_send_json_success( $response );
		}

		public function get_action_nonce( $name ) {
			if ( ! isset( $this->nonces[ $name ] ) ) {
				$this->nonces[ $name ] = wp_create_nonce( $this->get_nonce_action( $name ) );
			}
			return $this->nonces[ $name ];
		}

		private function get_nonce_action( $name ) {
			return 'wpgcso_ajax_' . $name;
		}
	}
}
