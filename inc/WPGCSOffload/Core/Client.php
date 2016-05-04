<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Core;

use Google_Client;
use Google_Service_Storage;
use Google_Service_Storage_StorageObject;
use Google_Service_Storage_ObjectAccessControl;
use WP_Error;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Core\Client' ) ) {
	/**
	 * This is a client class to interact with Google Cloud Storage.
	 *
	 * @since 0.5.0
	 */
	class Client {
		const BASE_URL = '//storage.googleapis.com/';

		private static $instance = null;

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private $authentication_key = '';
		private $bucket_name = '';

		private $client = null;
		private $storage = null;

		private function __construct() {
			// empty
		}

		public function set_authentication_key( $key ) {
			$this->authentication_key = $key;
		}

		public function set_bucket_name( $bucket_name ) {
			$this->bucket_name = $bucket_name;
		}

		public function init() {
			if ( ! $this->is_configured() ) {
				return;
			}

			$this->client = new Google_Client();
			$this->client->setAuthConfig( json_decode( $this->authentication_key, true ) );
			$this->client->setApplicationName( $this->get_dir_name() );
			$this->client->setScopes( array( 'https://www.googleapis.com/auth/devstorage.full_control' ) );

			$this->storage = new Google_Service_Storage( $this->client );
		}

		public function upload( $file, $path, $dir_name = '', $mime_type = 'image/jpeg', $metadata = array(), $args = array() ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			$metadata = wp_parse_args( $metadata, array(
				'attachment_id'	=> 0,
				'width'			=> null,
				'height'		=> null,
				'size'			=> 'full',
			) );

			if ( empty( $file ) ) {
				return new WP_Error( 'file_missing', __( 'Missing attached file name.', 'wp-gcs-offload' ) );
			}

			if ( empty( $path ) || ! file_exists( $path ) ) {
				return new WP_Error( 'file_not_found', __( 'The file cannot be found.', 'wp-gcs-offload' ) );
			}

			if ( empty( $dir_name ) ) {
				$dir_name = $this->get_dir_name();
			}

			$data = $this->get_info( $file, $dir_name );
			if ( ! is_wp_error( $data ) ) {
				return $data; // file is already on Google Cloud Storage, so we return it
			}

			$file = $dir_name . '/' . $file;

			$obj = new Google_Service_Storage_StorageObject();
			$obj->setName( $file );
			$obj->setMetadata( $metadata );
			foreach ( $args as $key => $value ) {
				$method = 'set' . ucfirst( $key );
				if ( method_exists( $obj, $method ) ) {
					$obj->$method( $value );
				}
			}

			try {
				$data = $this->storage->objects->insert( $this->bucket_name, $obj, array(
					'data'				=> file_get_contents( $path ),
					'uploadType'		=> 'media',
					'mimeType'			=> $mime_type,
					'predefinedAcl'		=> 'bucketOwnerFullControl',
				) );

				$acl = new Google_Service_Storage_ObjectAccessControl();
				$acl->setEntity( 'allUsers' );
				$acl->setRole( 'READER' );

				$this->storage->objectAccessControls->insert( $this->bucket_name, $file, $acl );
			} catch ( Exception $e ) {
				return new WP_Error( 'object_not_inserted', sprintf( __( 'The object cannot be uploaded. Original error message: %s', 'wp-gcs-offload' ), $e->getMessage() ) );
			}

			return $data;
		}

		public function download( $file, $path, $dir_name = '' ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( empty( $dir_name ) ) {
				$dir_name = $this->get_dir_name();
			}

			$data = $this->get_info( $file, $dir_name );
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			if ( ! file_exists( dirname( $path ) ) ) {
				wp_mkdir_p( dirname( $path ) );
			}

			$result = $this->client->getHttpClient()->get( $data->getMediaLink(), array(
				'save_to'	=> $path,
			) )->getStatusCode();

			if ( $status >= 500 ) {
				return new WP_Error( 'server_error', __( 'A server error occurred.', 'wp-gcs-offload' ) );
			}

			if ( $status >= 400 ) {
				return new WP_Error( 'client_error', __( 'A client error occurred.', 'wp-gcs-offload' ) );
			}

			return $data;
		}

		public function get_info( $file, $dir_name = '' ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( empty( $dir_name ) ) {
				$dir_name = $this->get_dir_name();
			}

			$file = $dir_name . '/' . $file;

			try {
				$data = $this->storage->objects->get( $this->bucket_name, $file );
			} catch ( Exception $e ) {
				return new WP_Error( 'object_not_found', __( 'The object cannot be found.', 'wp-gcs-offload' ) );
			}

			if ( empty( $data->id ) ) {
				return new WP_Error( 'object_missing_id', __( 'The object is missing an ID.', 'wp-gcs-offload' ) );
			}

			return $data;
		}

		public function delete( $file, $dir_name = '' ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( empty( $dir_name ) ) {
				$dir_name = $this->get_dir_name();
			}

			$file = $dir_name . '/' . $file;

			try {
				$this->storage->objects->delete( $this->bucket_name, $file );
			} catch ( Exception $e ) {
				return new WP_Error( 'object_not_deleted', sprintf( __( 'The object cannot be deleted. Original error message: %s', 'wp-gcs-offload' ), $e->getMessage() ) );
			}

			return true;
		}

		public function is_connected() {
			if ( ! $this->is_configured() ) {
				return false;
			}

			try {
				$bucket = $this->storage->buckets->get( $this->bucket_name );
			} catch ( Exception $e ) {
				return false;
			}

			return true;
		}

		public function is_configured() {
			return ! empty( $this->authentication_key ) && ! empty( $this->bucket_name );
		}

		private function get_dir_name() {
			if ( ! is_multisite() ) {
				$url = str_replace( array( 'https://', 'http://' ), '', untrailingslashit( home_url() ) );
			} else {
				global $current_blog;

				$url = untrailingslashit( $current_blog->domain . $current_blog->path );
			}

			return str_replace( '/', '-', $url );
		}
	}
}
