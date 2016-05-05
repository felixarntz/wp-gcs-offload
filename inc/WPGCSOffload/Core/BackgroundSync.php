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
	}
}
