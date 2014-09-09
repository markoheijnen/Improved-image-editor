<?php

class Improved_Image_Editor_Regenerate {

	public function __construct() {
		add_filter( 'image_downsize', array( $this, 'generate_image_size' ), 10, 3 );	
	}

	public function generate_image_size( $image_data, $id, $size ) {
		if ( ! is_array( $size ) ) {
			$intermediate = image_get_intermediate_size( $id, $size );
			
			if ( ! $intermediate ) {
				echo 'marko';
				$image = new WP_Image( $id );
				$image->regenerate_image_size( $size );
			}
		}

		return $image_data;
	}

}