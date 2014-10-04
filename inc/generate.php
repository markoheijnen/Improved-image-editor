<?php

class Improved_Image_Editor_Generate {

	public function __construct() {
		add_filter( 'image_downsize', array( $this, 'generate_image_size' ), 10, 3 );
		add_action( 'delete_attachment', array( $this, '_delete_attachment' ) );
	}

	public function generate_image_size( $image_data, $id, $size ) {
		if ( ! is_array( $size ) ) {
			$intermediate = image_get_intermediate_size( $id, $size );
			
			if ( ! $intermediate ) {
				$image = new WP_Image( $id );
				$image->regenerate_image_size( $size );
			}
		}

		return $image_data;
	}

	public function _delete_attachment( $post_id ) {
		$file = get_attached_file( $post_id );
		$meta = wp_get_attachment_metadata( $post_id );

		$intermediate_sizes = array();
		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( $intermediate = image_get_intermediate_size( $post_id, $size ) ) {
				$intermediate_sizes[] = $intermediate['path'];
			}
		}

		if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size => $sizeinfo ) {
				$intermediate_file = str_replace( basename( $file ), $sizeinfo['file'], $file );

				if ( ! in_array( $intermediate_file, $intermediate_sizes ) ) {		
					$intermediate_file = apply_filters( 'wp_delete_file', $intermediate_file );
					@ unlink( path_join( $uploadpath['basedir'], $intermediate_file ) );
				}
			}
		}
	} 

}