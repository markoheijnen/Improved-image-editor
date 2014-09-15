<?php
/*
Plugin Name: Improved image editor
Plugin URI: https://github.com/markoheijnen/Improved-image-editor
Description: WordPress needs a better image editor UI so let this be it
Author: Marko Heijnen 
Version: 0.2
Author URI: http://markoheijnen.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
}

class Improved_Image_Editor {
	const version = '0.2';

	private static $size_info = array();
	private static $current_image_size = false;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load' ) );
	}


	public function load() {
		if ( ! class_exists('WP_Image') ) {
			include 'inc/class.wp-image.php';
		}

		include 'inc/generate.php';

		new Improved_Image_Editor_Generate();

		add_action( 'init', array( $this, 'register_scripts_styles' ) );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
		add_action( 'wp_enqueue_media', array( $this, 'load_template' ) );

		add_filter( 'wp_image_editors', array( $this, 'wp_image_editors' ) );

		add_filter( 'editor_max_image_size', array( $this, 'editor_max_image_size' ), 10, 3 );
		add_filter( 'wp_image_editor_before_change', array( $this, 'wp_image_editor_before_change' ), 10, 2 );
	}


	public static function register_image_size_info( $image_size, $info ) {
		if ( ! is_array( $info ) ) {
			return false;
		}

		if ( isset( self::$size_info[ $image_size ] ) ) {
			self::$size_info[ $image_size ] = array_merge( self::$size_info[ $image_size ], $info );
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
		if ( $screen->base != 'post' || $screen->post_type != 'attachment' ) {
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


	public function editor_max_image_size( $max_size, $image_size, $context ) {
		$info = self::get_image_size_info( $image_size );

		if ( isset( $info['grid'] ) ) {
			$max_size[0] = $max_size[0] * 3;
			$max_size[1] = $max_size[1] * 3;
		}

		return $max_size;
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



	public static function _editor_update_size_data( $size_data, $image, $image_size ) {
		self::$current_image_size = $image_size;

		$info = self::get_image_size_info( $image_size );
		$info['immediate'] = isset( $info['immediate'] ) ? $info['immediate'] : true;
		$info['immediate'] = apply_filters( 'improved_image_editor_immediate_generate', $info['immediate'], $image_size );

		if ( ! $info['immediate'] && $image_size != 'thumbnail' ) { // What about medium and large.
			return false;
		}

		if ( isset( $info['zoom'] ) ) {
			// Higher priority since we should override the default filters.
			add_filter( 'image_resize_dimensions', array( __CLASS__, '_update_image_dimensions' ), 20, 6 );
		}

		if ( isset( $info['grid'] ) ) {
			$size_data = self::_grid_size_data( $size_data, $info['grid'], $image->get_size() );
		}

		return $size_data;
	}

	public static function _editor_update_image( $image, $image_size ) {
		self::$current_image_size = $image_size;

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

	public static function _update_image_dimensions( $dims, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
		remove_filter( 'image_resize_dimensions', array( __CLASS__, '_update_image_dimensions' ), 20, 6 );

		$info = self::get_image_size_info( self::$current_image_size );
		$info['zoom'] = $info['zoom'];

		if ( $crop ) {
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w = min($dest_w, $orig_w);
			$new_h = min($dest_h, $orig_h);

			if ( ! $new_w ) {
				$new_w = intval($new_h * $aspect_ratio);
			}

			if ( ! $new_h ) {
				$new_h = intval($new_w / $aspect_ratio);
			}

			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);

			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);

			if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {
				$crop = array( 'center', 'center' );
			}

			list( $x, $y ) = $crop;

			if ( 'left' === $x ) {
				$s_x = 0;
			} elseif ( 'right' === $x ) {
				$s_x = $orig_w - $crop_w;
			} else {
				$s_x = floor( ( $orig_w - $crop_w ) / 2 );
			}

			if ( 'top' === $y ) {
				$s_y = 0;
			} elseif ( 'bottom' === $y ) {
				$s_y = $orig_h - $crop_h;
			} else {
				$s_y = floor( ( $orig_h - $crop_h ) / 2 );
			}
		} else {
			// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
			$crop_w = $orig_w;
			$crop_h = $orig_h;

			$s_x = 0;
			$s_y = 0;

			list( $new_w, $new_h ) = wp_constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}


		if ( $info['zoom'] > 1 ) {
			$s_x = $s_x + ( $crop_w - $crop_w / $info['zoom'] ) / 2;
			$s_y = $s_y + ( $crop_w - $crop_w / $info['zoom'] ) / 2;

			$crop_w = $crop_w / $info['zoom'];
			$crop_h = $crop_h / $info['zoom'];
		}


		// if the resulting image would be the same size or larger we don't want to resize it
		if ( $new_w >= $orig_w && $new_h >= $orig_h ) {
			return false;
		}

		// the return array matches the parameters to imagecopyresampled()
		// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
		return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
	}


	private static function _grid_size_data( $size_data, $grid_options, $image_size ) {
		$calc = array();

		if ( $size_data['crop'] ) {
			$calc['div'] = ( $image_size['height'] / $size_data['height'] ) / ( $image_size['width'] / $size_data['width'] );
		}
		else {
			$calc['div'] = $image_size['height'] / $image_size['width'];
		}

		if ( $calc['div'] > 1 ) {
			$calc['type']  = 'h';
			$calc['ratio'] = round( $calc['div'] );
		}
		else {
			$calc['type']  = 'w';
			$calc['ratio'] = round( 1 / $calc['div'] );
		}

		if ( isset( $grid_options[ $calc['type'] . '-' . $calc['ratio'] ] ) ) {
			$size_data = array_merge( $size_data, $grid_options[ $calc['type'] . '-' . $calc['ratio'] ] );
		}

		return $size_data;
	}

}

$improved_image_editor = new Improved_Image_Editor;