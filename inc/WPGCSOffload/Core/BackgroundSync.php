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

		protected function before_start() {
			$ids = $this->get_attachment_ids();
			$this->data( $ids );
			$this->increase_total( count( $ids ) );
			$this->save();
			return;

			$this->push_to_queue( 'find_attachments' );
			$this->save();
		}

		protected function task( $item ) {
			/*if ( is_string( $item ) && 0 === strpos( $item, 'find_attachments' ) ) {
				$number = 2;
				$offset = 0;
				if ( false !== strpos( $item, ':' ) ) {
					$offset = absint( substr( $item, strlen( 'find_attachments:' ) ) );
				}

				$ids = $this->get_attachment_ids( 'all', $number, $offset );

				$this->log( sprintf( 'Found attachment ids %s.', implode( ', ', $ids ) ) );

				$this->data( $ids );
				$this->increase_total();
				$this->save();

				if ( count( $ids ) === $number ) {
					return 'find_attachments:' . ( $offset + $number );
				}

				return false;
			}*/

			$id = absint( $item );
			for ( $i = 0; $i < 50; $i++ ) {
				wp_mail( 'hahahaa@example.com', 'some ID', $id );
			}

			$this->log( sprintf( 'Sent mail with %d twenty times.', $id ) );
			$this->increase_progress();

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
