<?php

class WPGCSO_UnitTestCase extends WP_UnitTestCase {
	protected function core() {
		return WPGCSOffload\App::instance();
	}

	protected function create_attachment( $img_name, $mime_type ) {
		return $this->factory->attachment->create_object( $img_name, 0, array(
			'post_mime_type' => $mime_type,
			'post_type' => 'attachment'
		) );
	}

	protected function create_and_get_attachment( $img_name, $mime_type ) {
		return WPGCSOffload\Core\Attachment::get( $this->create_attachment( $img_name, $mime_type ) );
	}

	protected function upload_attachment_to_gcs( $attachment_id, $bucket_name, $dir_name ) {
		update_post_meta( $attachment_id, '_wpgcso_bucket_name', $bucket_name );
		update_post_meta( $attachment_id, '_wpgcso_dir_name', $dir_name );

		$metadata = wp_get_attachment_metadata( $attachment_id );

		$did_full = false;
		if ( $metadata['sizes'] ) {
			foreach ( $metadata['sizes'] as $size => $data ) {
				if ( 'full' === $size ) {
					$did_full = true;
				}
				add_post_meta( $attachment_id, '_wpgcso_image_sizes', $size );
			}
		}

		if ( ! $did_full ) {
			add_post_meta( $attachment_id, '_wpgcso_image_sizes', 'full' );
		}
	}

	protected function delete_attachment_local_file( $attachment_id ) {
		update_post_meta( $attachment_id, '_wpgcso_remote_only', true );
	}

	protected function get_yearmonth_dir( $timestamp = '' ) {
		if ( ! $timestamp ) {
			$timestamp = current_time( 'timestamp' );
		}
		return date( 'Y/m', $timestamp );
	}
}
