<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Core;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Core\Attachment' ) ) {
	/**
	 * This class represents a single attachment.
	 *
	 * @since 0.5.0
	 */
	class Attachment {
		private static $instances = array();

		public static function get( $id = null ) {
			if ( ! $id ) {
				$post = get_post();
				if ( ! $post ) {
					return null;
				}
				$id = $post->ID;
			}

			$id = absint( $id );

			if ( ! isset( self::$instances[ $id ] ) ) {
				if ( 'attachment' !== get_post_type( $id ) ) {
					return null;
				}

				self::$instances[ $id ] = new self( $id );
			}

			return self::$instances[ $id ];
		}

		private $id = null;

		private function __construct( $id ) {
			$this->id = $id;
		}

		public function get_id() {
			return $this->id;
		}

		public function is_local_file() {
			return ! get_post_meta( $this->id, '_wpgcso_remote_only', true );
		}

		public function is_cloud_storage_file() {
			return (bool) get_post_meta( $this->id, '_wpgcso_bucket_name', true ) && (bool) get_post_meta( $this->id, '_wpgcso_dir_name', true );
		}

		public function get_cloud_storage_bucket_name() {
			return get_post_meta( $this->id, '_wpgcso_bucket_name', true );
		}

		public function get_cloud_storage_dir_name() {
			return get_post_meta( $this->id, '_wpgcso_dir_name', true );
		}

		public function get_cloud_storage_image_sizes() {
			$gcs_sizes = get_post_meta( $this->id, '_wpgcso_image_sizes' );
			if ( ! $gcs_sizes ) {
				return array();
			}
			return $gcs_sizes;
		}

		public function get_cloud_storage_url() {
			$bucket_name = get_post_meta( $this->id, '_wpgcso_bucket_name', true );
			if ( ! $bucket_name ) {
				return false;
			}

			$dir_name = get_post_meta( $this->id, '_wpgcso_dir_name', true );
			if ( ! $dir_name ) {
				return false;
			}

			$file = get_post_meta( $this->id, '_wp_attached_file', true );
			if ( ! $file ) {
				return false;
			}

			return 'https:' . Client::BASE_URL . $bucket_name . '/' . $dir_name . '/' . $file;
		}

		public function get_cloud_storage_image_downsize( $size = 'thumbnail' ) {
			$gcs_url = $this->get_cloud_storage_url();
			if ( ! $gcs_url ) {
				return false;
			}

			$gcs_sizes = $this->get_cloud_storage_image_sizes();
			if ( ! in_array( $size, $gcs_sizes, true ) ) {
				return false;
			}

			$meta = wp_get_attachment_metadata( $this->id );
			$width = $height = 0;
			$is_intermediate = false;
			$gcs_url_basename = wp_basename( $gcs_url );

			if ( $intermediate = image_get_intermediate_size( $this->id, $size ) ) {
				$gcs_url = str_replace( $gcs_url_basename, $intermediate['file'], $gcs_url );
				$width = $intermediate['width'];
				$height = $intermediate['height'];
				$is_intermediate = true;
			} elseif ( 'thumbnail' === $size ) {
				//TODO: can we handle this?
			}

			if ( ! $width && ! $height && $meta && isset( $meta['width'] ) && isset( $meta['height'] ) ) {
				$width = $meta['width'];
				$height = $meta['height'];
			}

			list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $size );

			return array( $gcs_url, $width, $height, $is_intermediate );
		}

		public function upload_to_cloud_storage( $metadata = false, $suppress_hooks = false ) {
			if ( ! Client::instance()->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_upload_to_cloud_storage', $this->id, $this, $metadata );
			}

			//update_post_meta( $this->id, '_wpgcso_bucket_name', $bucket_name );
			//update_post_meta( $this->id, '_wpgcso_dir_name', $dir_name );
			/*foreach ( $image_sizes as $image_size ) {
				add_post_meta( $this->id, '_wpgcso_image_sizes', $image_size );
			}*/

			// if success
			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_uploaded_to_cloud_storage', $this->id, $this, $metadata );
			}
		}

		public function delete_from_cloud_storage( $metadata = false, $suppress_hooks = false ) {
			if ( ! Client::instance()->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_delete_from_cloud_storage', $this->id, $this, $metadata );
			}

			//delete_post_meta( $this->id, '_wpgcso_bucket_name' );
			//delete_post_meta( $this->id, '_wpgcso_dir_name' );
			//delete_post_meta( $this->id, '_wpgcso_image_sizes' );

			// if success
			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_deleted_from_cloud_storage', $this->id, $this, $metadata );
			}
		}

		public function download_from_cloud_storage( $metadata = false, $suppress_hooks = false ) {
			if ( ! Client::instance()->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_download_from_cloud_storage', $this->id, $this, $metadata );
			}

			//delete_post_meta( $this->id, '_wpgcso_remote_only' );

			// if success
			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_downloaded_from_cloud_storage', $this->id, $this, $metadata );
			}
		}

		public function delete_local_file( $metadata = false, $suppress_hooks = false ) {
			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_delete_local_file', $this->id, $this, $metadata );
			}

			//update_post_meta( $this->id, '_wpgcso_remote_only', true );

			// if success
			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_deleted_local_file', $this->id, $this, $metadata );
			}
		}
	}
}
