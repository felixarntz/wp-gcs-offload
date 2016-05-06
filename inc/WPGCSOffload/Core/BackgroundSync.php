<?php
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

namespace WPGCSOffload\Core;

use WP_Trackable_Background_Process;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WPGCSOffload\Core\BackgroundSync' ) ) {
	/**
	 * This class handles background sync processes.
	 *
	 * @since 0.5.0
	 */
	class BackgroundSync extends WP_Trackable_Background_Process {
		protected $prefix = 'wpgcso';

		protected $action = 'image_background_sync';

		protected function task( $item ) {
			$to = 'user' . $item . '@example.com';
			$subject = 'Subject ' . $item;
			$message = 'Hello, this is useless.';

			$status = wp_mail( $to, $subject, $message );

			if ( $status ) {
				$this->log( sprintf( 'Successfully sent email with subject %1$s to %2$s.', $subject, $to ), 'success' );
			} else {
				$this->log( sprintf( 'Could not send email with subject %1$s to %2$s.', $subject, $to ), 'error' );
			}

			return false;
		}

		public function get_attachment_ids( $mode = 'all', $number = -1, $offset = 0 ) {
			$args = array(
				'posts_per_page'			=> $number,
				'offset'					=> $offset,
				'no_found_rows'				=> true,
				'suppress_filters'			=> true,
				'update_post_term_cache'	=> false,
				'orderby'					=> false,
				'post_type'					=> 'attachment',
				'fields'					=> 'ids',
			);

			$meta_keys = array();

			switch ( $mode ) {
				case 'remote':
					$meta_keys['_wpgcso_bucket_name'] = 'EXISTS';
					$meta_keys['_wpgcso_dir_name'] = 'EXISTS';
					break;
				case 'local':
					$meta_keys['_wpgcso_remote_only'] = 'NOT EXISTS';
					break;
				case 'remote_only':
					$meta_keys['_wpgcso_remote_only'] = 'EXISTS';
					break;
				case 'local_only':
					$meta_keys['_wpgcso_bucket_name'] = 'NOT EXISTS';
					$meta_keys['_wpgcso_dir_name'] = 'NOT EXISTS';
					break;
			}

			if ( $meta_keys ) {
				$args['meta_query'] = array(
					'relation'	=> 'AND',
				);
				foreach ( $meta_keys as $key => $compare ) {
					$args['meta_query'][] = array(
						'key'		=> $key,
						'compare'	=> $compare,
					);
				}
			}

			return get_posts( $args );
		}
	}
}
