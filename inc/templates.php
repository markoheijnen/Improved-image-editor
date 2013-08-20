<?php

class Improved_Image_Editor_Templates {

	public function __construct() {
		$this->enqueue_script();
	}

	private function enqueue_script() {
		wp_enqueue_script( 'improved_image_editor' );

		wp_enqueue_style( 'improved_image_editor');
	}
}