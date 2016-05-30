<?php
/**
 * WPGCSOffload\Core\Attachment class
 *
 * @package WPGCSOffload
 * @subpackage Core
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 0.5.0
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

			$uploads = wp_get_upload_dir();
			$main_file = get_post_meta( $this->id, '_wp_attached_file', true );
			$mime_type = get_post_mime_type( $this->id );
			$args = array(
				'attachment_id'	=> $this->id,
			);

			if ( ! $this->is_cloud_storage_file() ) {
				$image_sizes = array();
				$dir_name = '';
				$file = $main_file;
				$path = path_join( $uploads['basedir'], $file );
				if ( wp_attachment_is_image( $this->id ) ) {
					if ( isset( $metadata['width'] ) ) {
						$args['width'] = $metadata['width'];
					}
					if ( isset( $metadata['height'] ) ) {
						$args['height'] = $metadata['height'];
					}
					$args['size'] = 'full';
				}

				$status = Client::instance()->upload( $file, $path, $dir_name, $mime_type, $args );
				if ( is_wp_error( $status ) ) {
					return $status;
				}

				if ( wp_attachment_is_image( $this->id ) ) {
					$image_sizes[] = 'full';
				}

				//TODO
				//$bucket_name = $status['bucket_name']; // ???
				//$dir_name = $status['dir_name']; // ???
			} else {
				$image_sizes = $this->get_cloud_storage_image_sizes();
				$dir_name = $this->get_cloud_storage_dir_name();
			}

			$errors = false;
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$errors = $this->upload_sizes_to_cloud_storage( $metadata['sizes'], $image_sizes, $main_file, $dir_name, $mime_type, $args );
			}

			if ( isset( $bucket_name ) ) {
				update_post_meta( $this->id, '_wpgcso_bucket_name', $bucket_name );
				update_post_meta( $this->id, '_wpgcso_dir_name', $dir_name );
			} else {
				delete_post_meta( $this->id, '_wpgcso_image_sizes' );
			}

			foreach ( $image_sizes as $image_size ) {
				add_post_meta( $this->id, '_wpgcso_image_sizes', $image_size );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_uploaded_to_cloud_storage', $this->id, $this, $metadata );
			}

			return $this->parse_return_value( $status, $errors );
		}

		public function download_from_cloud_storage( $metadata = false, $suppress_hooks = false ) {
			if ( ! Client::instance()->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( ! $this->is_cloud_storage_file() ) {
				return new WP_Error( 'not_in_cloud_storage', sprintf( __( 'The files for attachment %d cannot be downloaded since they are not available on Google Cloud Storage.', 'wp-gcs-offload' ), $this->id ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_download_from_cloud_storage', $this->id, $this, $metadata );
			}

			$main_file = get_post_meta( $this->id, '_wp_attached_file', true );
			$image_sizes = $this->get_cloud_storage_image_sizes();

			$file = $main_file;
			$path = path_join( $uploads['basedir'], $file );
			$dir_name = $this->get_cloud_storage_dir_name();

			$status = Client::instance()->download( $file, $path, $dir_name );
			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$errors = new WP_Error();
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$errors = $this->download_sizes_from_google_cloud_storage( $metadata['sizes'], $image_sizes, $main_file, $dir_name );
			}

			delete_post_meta( $this->id, '_wpgcso_remote_only' );

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_downloaded_from_cloud_storage', $this->id, $this, $metadata );
			}

			return $this->parse_return_value( $status, $errors );
		}

		public function delete_from_cloud_storage( $metadata = false, $suppress_hooks = false ) {
			if ( ! Client::instance()->is_configured() ) {
				return new WP_Error( 'client_not_configured', __( 'The Google Cloud Storage client is not configured properly.', 'wp-gcs-offload' ) );
			}

			if ( ! $this->is_cloud_storage_file() ) {
				return new WP_Error( 'not_in_cloud_storage', sprintf( __( 'The files for attachment %d cannot be deleted since they are not available on Google Cloud Storage.', 'wp-gcs-offload' ), $this->id ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_delete_from_cloud_storage', $this->id, $this, $metadata );
			}

			$main_file = get_post_meta( $this->id, '_wp_attached_file', true );
			$image_sizes = $this->get_cloud_storage_image_sizes();

			$file = $main_file;
			$dir_name = $this->get_cloud_storage_dir_name();

			$status = Client::instance()->delete( $file, $dir_name );
			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$errors = false;
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$errors = $this->delete_sizes_from_google_cloud_storage( $metadata['sizes'], $image_sizes, $main_file, $dir_name );
			}

			delete_post_meta( $this->id, '_wpgcso_bucket_name' );
			delete_post_meta( $this->id, '_wpgcso_dir_name' );
			delete_post_meta( $this->id, '_wpgcso_image_sizes' );

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_deleted_from_cloud_storage', $this->id, $this, $metadata );
			}

			return $this->parse_return_value( $status, $errors );
		}

		public function delete_local_file( $metadata = false, $suppress_hooks = false ) {
			if ( ! $this->is_cloud_storage_file() ) {
				return new WP_Error( 'not_in_cloud_storage', sprintf( __( 'The files for attachment %d cannot be deleted since they are not available on Google Cloud Storage.', 'wp-gcs-offload' ), $this->id ) );
			}

			if ( ! $metadata ) {
				$metadata = wp_get_attachment_metadata( $this->id );
			}

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_delete_local_file', $this->id, $this, $metadata );
			}

			$main_file = get_post_meta( $this->id, '_wp_attached_file', true );
			$image_sizes = $this->get_cloud_storage_image_sizes();

			if ( $this->is_local_file() ) {
				$file = $main_file;
				$path = path_join( $uploads['basedir'], $file );

				if ( ! @unlink( $path ) ) {
					return new WP_Error( 'cannot_delete_file', sprintf( __( 'The main file for attachment %d could not be deleted.', 'wp-gcs-offload' ), $this->id ) );
				}
			}

			$errors = false;
			if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				$errors = $this->delete_local_sizes( $metadata['sizes'], $image_sizes, $main_file, $dir_name );
			}

			update_post_meta( $this->id, '_wpgcso_remote_only', true );

			if ( ! $suppress_hooks ) {
				do_action( 'wpgcso_deleted_local_file', $this->id, $this, $metadata );
			}

			return $this->parse_return_value( array(), $errors );
		}

		private function parse_return_value( $data, $errors ) {
			$ret = array(
				'data'		=> $data,
				'errors'	=> array(),
			);

			// The errors in this object are not critical, so they do not make the overall process fail.
			if ( is_wp_error( $errors ) ) {
				//TODO
				$ret['errors'] = $this->parse_errors( $errors );
			}

			return $ret;
		}

		private function upload_sizes_to_cloud_storage( $meta_sizes, &$image_sizes, $main_file, $dir_name, $mime_type, $args ) {
			$uploads = wp_get_upload_dir();

			$errors = new WP_Error();

			foreach ( $meta_sizes as $size => $data ) {
				if ( in_array( $size, $image_sizes, true ) ) {
					continue;
				}

				$file = str_replace( basename( $main_file ), $data['file'], $main_file );
				$path = path_join( $uploads['basedir'], $file );
				$args['width'] = $data['width'];
				$args['height'] = $data['height'];
				$args['size'] = $size;

				$status = Client::instance()->upload( $file, $path, $dir_name, $mime_type, $args );
				if ( is_wp_error( $status ) ) {
					$errors->add( $status->get_error_code(), $status->get_error_message(), $status->get_error_data() );
					continue;
				}

				$image_sizes[] = $size;
			}

			if ( empty( $errors->errors ) ) {
				return true;
			}

			return $errors;
		}

		private function download_sizes_from_google_cloud_storage( $meta_sizes, $image_sizes, $main_file, $dir_name ) {
			$uploads = wp_get_upload_dir();

			$errors = new WP_Error();

			foreach ( $meta_sizes as $size => $data ) {
				if ( ! in_array( $size, $image_sizes, true ) ) {
					continue;
				}

				$file = str_replace( basename( $main_file ), $data['file'], $main_file );
				$path = path_join( $uploads['basedir'], $file );

				$status = Client::instance()->download( $file, $path, $dir_name );
				if ( is_wp_error( $status ) ) {
					$errors->add( $status->get_error_code(), $status->get_error_message(), $status->get_error_data() );
					continue;
				}
			}

			if ( empty( $errors->errors ) ) {
				return true;
			}

			return $errors;
		}

		private function delete_sizes_from_google_cloud_storage( $meta_sizes, $image_sizes, $main_file, $dir_name ) {
			$errors = new WP_Error();

			foreach ( $meta_sizes as $size => $data ) {
				if ( ! in_array( $size, $image_sizes, true ) ) {
					continue;
				}

				$file = str_replace( basename( $main_file ), $data['file'], $main_file );

				$status = Client::instance()->delete( $file, $dir_name );
				if ( is_wp_error( $status ) ) {
					$errors->add( $status->get_error_code(), $status->get_error_message(), $status->get_error_data() );
					continue;
				}
			}

			if ( empty( $errors->errors ) ) {
				return true;
			}

			return $errors;
		}

		private function delete_local_sizes( $meta_sizes, $image_sizes, $main_file, $dir_name ) {
			$uploads = wp_get_upload_dir();

			$errors = new WP_Error();

			foreach ( $meta_sizes as $size => $data ) {
				if ( ! in_array( $size, $image_sizes, true ) ) {
					continue;
				}

				$file = str_replace( basename( $main_file ), $data['file'], $main_file );
				$path = path_join( $uploads['basedir'], $file );

				if ( ! @unlink( $path ) ) {
					$errors->add( 'cannot_delete_file', sprintf( __( 'The file of size %s for attachment %d could not be deleted.', 'wp-gcs-offload' ), $size, $this->id ) );
					continue;
				}
			}

			if ( empty( $errors->errors ) ) {
				return true;
			}

			return $errors;
		}
	}
}
