<?php
/*
Plugin Name: Improved image editor
Plugin URI: https://github.com/markoheijnen/Improved-image-editor
Description: WordPress needs a better image editor UI so let this be it
Author: Marko Heijnen 
Version: 0.1
Author URI: http://markoheijnen.com
*/

include 'inc/overwrite.php';

class Improved_Image_Editor {

	public function __construct() {
		add_action( 'init', array( $this, 'register_scripts_styles' ) );
		add_action( 'current_screen', array( $this, 'current_screen' ) );
		add_action( 'wp_enqueue_media', array( $this, 'load_template' ) );

		add_filter( 'admin_footer_text', array( $this, 'show_editor' ) );
		add_filter( 'wp_image_editors', array( $this, 'wp_image_editors' ) );

		add_filter( 'wp_image_editor_before_change', array( $this, 'wp_image_editor_before_change' ), 10, 2 );
	}

	public function register_scripts_styles() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'improved_image_editor', plugins_url( '/js/main.js', __FILE__ ), array( 'jquery', 'backbone' ) );

		wp_register_style( 'improved_image_editor', plugins_url( 'css/metabox.css', __FILE__ ), array( 'media-views' ) );
	}

	public function current_screen( $screen ) {
		if( $screen->base != 'post' || $screen->post_type != 'attachment' )
			return;

		$this->load_template();
	}

	public function load_template() {
		include 'inc/templates.php';

		new Improved_Image_Editor_Templates();
	}


	public function show_editor( $text ) {
		$text .= ' - ' . _wp_image_editor_choose();

		return $text;
	}

	public function wp_image_editors( $editors ) {
		include_once 'editors/gd.php';
		include_once 'editors/imagick.php';
		include_once 'editors/gmagick.php';

		$editors2 = array(
			//'Improved_Image_Editor_Gmagick',
			//'Improved_Image_Editor_Imagick',
			'Improved_Image_Editor_GD'
		);

		$editors = array_merge( $editors2, $editors );

		return $editors;
	}


	public function wp_image_editor_before_change( $image, $changes ) {
		foreach ( $changes as $operation ) {
			if( $operation->type == 'filter' && isset( $operation->filter ) ) {
				$method = 'filter_' . esc_attr( $operation->filter );

				if( method_exists( $image, $method ) ) {
					call_user_func( array( $image, $method ) );
				}
			}
		}

		return $image;
	}
}

new Improved_Image_Editor;