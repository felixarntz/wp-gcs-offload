<?php
/*
Plugin Name: WP GCS Offload
Plugin URI: https://wordpress.org/plugins/wp-gcs-offload/
Description: This plugin allows offloading your media library to Google Cloud Storage, including easy management and migration tools.
Version: 0.5.0
Author: Felix Arntz
Author URI: http://leaves-and-love.net
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wp-gcs-offload
Tags: wordpress, plugin, google cloud storage, offload, media library, cdn
*/
/**
 * @package WPGCSOffload
 * @version 0.5.0
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( version_compare( phpversion(), '5.3.0' ) >= 0 && ! class_exists( 'WPGCSOffload\App' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/wp-gcs-offload/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/wp-gcs-offload/vendor/autoload.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	}
} elseif ( ! class_exists( 'LaL_WP_Plugin_Loader' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/wp-gcs-offload/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/wp-gcs-offload/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/felixarntz/leavesandlove-wp-plugin-util/leavesandlove-wp-plugin-loader.php';
	}
}

if ( file_exists( dirname( __FILE__ ) . '/wp-gcs-offload/vendor/felixarntz/options-definitely/options-definitely.php' ) ) {
	require_once dirname( __FILE__ ) . '/wp-gcs-offload/vendor/felixarntz/options-definitely/options-definitely.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/vendor/felixarntz/options-definitely/options-definitely.php' ) ) {
	require_once dirname( __FILE__ ) . '/vendor/felixarntz/options-definitely/options-definitely.php';
}

LaL_WP_Plugin_Loader::load_plugin( array(
	'slug'					=> 'wp-gcs-offload',
	'name'					=> 'WP GCS Offload',
	'version'				=> '0.5.0',
	'main_file'				=> __FILE__,
	'namespace'				=> 'WPGCSOffload',
	'textdomain'			=> 'wp-gcs-offload',
	'use_language_packs'	=> true,
	'is_library'			=> true,
), array(
	'phpversion'			=> '5.4.0',
	'wpversion'				=> '4.4',
) );
