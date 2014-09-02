<?php
/*
Plugin Name: Improved image editor
Plugin URI: https://github.com/markoheijnen/Improved-image-editor
Description: WordPress needs a better image editor UI so let this be it
Author: Marko Heijnen 
Version: 0.1
Author URI: http://markoheijnen.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
}

class Improved_Image_Editor {
	const version = '0.1';

	private static $size_info = array();

	public function __construct() {
		add_action( 'init', array( $this, 'register_scripts_styles' ) );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
		add_action( 'wp_enqueue_media', array( $this, 'load_template' ) );

		add_filter( 'wp_image_editors', array( $this, 'wp_image_editors' ) );

		add_filter( 'wp_image_editor_before_change', array( $this, 'wp_image_editor_before_change' ), 10, 2 );
	}


	public static function register_image_size_info( $image_size, $info ) {
		if ( ! is_array( $info ) ) {
			return false;
		}

		if ( isset( self::$size_info[ $image_size ] ) ) {
			self::$size_info[ $image_size ] = array_merge( $info, self::$size_info[ $image_size ] );
		}
		else {
			self::$size_info[ $image_size ] = $info;
		}

		return true;
	}

	public static function get_image_size_info( $image_size ) {
		if ( isset( self::$size_info[ $image_size ] ) ) {
			return self::$size_info[ $image_size ];
		}

		return array();
	}


	public function register_scripts_styles() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'improved_image_editor', plugins_url( '/js/main.js', __FILE__ ), array( 'jquery', 'backbone' ), self::version );

		wp_register_style( 'improved_image_editor', plugins_url( '/css/main.css', __FILE__ ), array( 'media-views' ), self::version );
	}

	public function current_screen( $screen ) {
		if( $screen->base != 'post' || $screen->post_type != 'attachment' ) {
			return;
		}

		$this->load_template();
	}

	public function load_template() {
		wp_enqueue_script( 'improved_image_editor' );
		wp_enqueue_style( 'improved_image_editor');

		include 'inc/templates.php';
		new Improved_Image_Editor_Templates();
	}


	public function wp_image_editors( $editors ) {
		include_once 'editors/gd.php';
		include_once 'editors/imagick.php';
		include_once 'editors/gmagick.php';

		$new_editors = array(
			'Improved_Image_Editor_Gmagick',
			'Improved_Image_Editor_Imagick',
			'Improved_Image_Editor_GD'
		);

		$editors = array_merge( $new_editors, $editors );

		return $editors;
	}


	public function wp_image_editor_before_change( $image, $changes ) {
		foreach ( $changes as $operation ) {
			if ( $operation->type == 'filter' && isset( $operation->filter ) ) {
				$method = 'filter_' . esc_attr( $operation->filter );

				if ( method_exists( $image, $method ) ) {
					call_user_func( array( $image, $method ) );
				}
			}
		}

		return $image;
	}


	public static function _update_image( $image, $image_size ) {
		$info = self::get_image_size_info( $image_size );

		if ( isset( $info['quality'] ) ) {
			$image->set_quality( $info['quality'] );
		}

		if ( isset( $info['filters'] ) ) {
			foreach ( $info['filters'] as $filter ) {
				$method = 'filter_' . $filter;

				if ( method_exists( $image, $method ) ) {
					call_user_func( array( $image, $method ) );
				}
			}
		}
	}

}

$improved_image_editor = new Improved_Image_Editor;