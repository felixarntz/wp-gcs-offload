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

if ( ! class_exists( 'WPGCSOffload\Core\URLFixer' ) ) {
	/**
	 * This class adjusts URLs for Google Cloud Storage attachments.
	 *
	 * @since 0.5.0
	 */
	class URLFixer {
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

		public function image_downsize( $false, $id, $size ) {
			$attachment = Attachment::get( $id );
			if ( ! $this->url_fixes_needed( $attachment ) ) {
				return $false;
			}

			$image_downsize = $attachment->get_cloud_storage_image_downsize( $size );
			if ( ! $image_downsize ) {
				return $false;
			}

			return $image_downsize;
		}

		public function wp_get_attachment_url( $url, $id ) {
			$attachment = Attachment::get( $id );
			if ( ! $this->url_fixes_needed( $attachment ) ) {
				return $url;
			}

			$gcs_url = $attachment->get_cloud_storage_url();
			if ( ! $gcs_url ) {
				return $url;
			}

			return $gcs_url;
		}

		public function attachment_url_to_postid( $id, $url ) {
			if ( ! $id && preg_match( '#^https:' . str_replace( '.', '\.', Client::BASE_URL ) . '([A-Za-z0-9\.\-_]+)/([A-Za-z0-9\.\-_]+)/(\S+)$#', $url, $matches ) ) {
				$bucket_name = $matches[1];
				$dir_name = $matches[2];
				$file_name = preg_replace( '#\-(\d+)x(\d+)\.#', '.', $matches[3] );

				$posts = get_posts( array(
					'posts_per_page'	=> 1,
					'post_type'			=> 'attachment',
					'fields'			=> 'ids',
					'meta_query'		=> array(
						'relation'			=> 'AND',
						array(
							'key'				=> '_wpgcso_bucket_name',
							'value'				=> $bucket_name,
						),
						array(
							'key'				=> '_wpgcso_dir_name',
							'value'				=> $dir_name,
						),
						array(
							'key'				=> '_wp_attached_file',
							'value'				=> $file_name,
						),
					),
				) );
				if ( $posts ) {
					return absint( $posts[0] );
				}
			}

			return $id;
		}

		private function url_fixes_needed( $attachment ) {
			if ( ! $attachment->is_cloud_storage_file() ) {
				return false;
			}

			if ( 'prefer_local' === Settings::instance()->get_setting( 'gcs_mode' ) && $attachment->is_local_file() ) {
				return false;
			}

			return true;
		}
	}
}
