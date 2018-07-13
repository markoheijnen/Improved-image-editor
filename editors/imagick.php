<?php

class Improved_Image_Editor_Imagick extends WP_Image_Editor_Imagick {

 	/**
	 * Loads image from $this->file into new GD Resource.
	 *
	 * @since 3.5.0
	 *
	 * @return bool|WP_Error True if loaded successfully; WP_Error on failure.
	 */
	public function load() {
		$loaded = parent::load();

		if ($loaded) {
			if ( is_callable( array( $this->image, 'setImageOrientation' ) ) ) {
				$orientation = $this->image->getImageOrientation();

				switch($orientation) {
					case imagick::ORIENTATION_BOTTOMRIGHT:
						$this->rotate(180);
						break;
					case imagick::ORIENTATION_RIGHTTOP:
						$this->rotate(90);
						break;
					case imagick::ORIENTATION_LEFTBOTTOM:
						$this->rotate(-90);
						break;
				}

				if ($orientation) {
					$this->image->setImageOrientation(imagick::ORIENTATION_UNDEFINED);
				}
			}
		}

		return $loaded;
	}


	/**
	 * Resizes current image.
	 *
	 * At minimum, either a height or width must be provided.
	 * If one of the two is set to null, the resize will
	 * maintain aspect ratio according to the provided dimension.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param  int|null $max_w Image width.
	 * @param  int|null $max_h Image height.
	 * @param  boolean  $crop
	 * @return boolean|WP_Error
	 */
	public function resize( $max_w, $max_h, $crop = false ) {
		if ( ( $this->size['width'] == $max_w ) && ( $this->size['height'] == $max_h ) )
			return true;

		$dims = image_resize_dimensions( $this->size['width'], $this->size['height'], $max_w, $max_h, $crop );
		if ( ! $dims )
			return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );
		list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

		if ( $crop ) {
			return $this->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h );
		}

		try {
			/**
			 * @TODO: Thumbnail is more efficient, given a newer version of Imagemagick.
			 * $this->image->thumbnailImage( $dst_w, $dst_h );
			 */
			$this->image->scaleImage( $dst_w, $dst_h );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'image_resize_error', $e->getMessage() );
		}

		return $this->update_size( $dst_w, $dst_h );
	}

	/**
	 * Resize multiple images from a single source.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param array $sizes {
	 *     An array of image size arrays. Default sizes are 'small', 'medium', 'large'.
	 *
	 *     Either a height or width must be provided.
	 *     If one of the two is set to null, the resize will
	 *     maintain aspect ratio according to the provided dimension.
	 *
	 *     @type array $size {
	 *         @type int  ['width']  Optional. Image width.
	 *         @type int  ['height'] Optional. Image height.
	 *         @type bool $crop   Optional. Whether to crop the image. Default false.
	 *     }
	 * }
	 * @return array An array of resized images' metadata by size.
	 */
	public function multi_resize( $sizes ) {
		$metadata = array();
		$orig_size = $this->size;
		$orig_image = $this->image->getImage();
		$orig_quality = $this->get_quality();

		foreach ( $sizes as $size => $size_data ) {
			if ( ! $this->image ) {
				$this->image = $orig_image->getImage();
				$this->set_quality( $orig_quality );
			}

			if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
				continue;
			}

			if ( ! isset( $size_data['width'] ) ) {
				$size_data['width'] = null;
			}
			if ( ! isset( $size_data['height'] ) ) {
				$size_data['height'] = null;
			}

			if ( ! isset( $size_data['crop'] ) ) {
				$size_data['crop'] = false;
			}

			$size_data = Improved_Image_Editor::_editor_update_size_data( $size_data, $this, $size );

			if ( ! $size_data ) {
				continue;
			}

			$resize_result = $this->resize( $size_data['width'], $size_data['height'], $size_data['crop'] );

			if( ! is_wp_error( $resize_result ) ) {
				Improved_Image_Editor::_editor_update_image( $this, $size );

				$resized = $this->_save( $this->image );

				$this->image->clear();
				$this->image->destroy();
				$this->image = null;

				if ( ! is_wp_error( $resized ) && $resized ) {
					unset( $resized['path'] );
					$metadata[$size] = $resized;
				}
			}

			$this->size = $orig_size;
		}

		$this->image = $orig_image;

		return $metadata;
	}


	public function filter_grayscale() {
		$this->image->setImageColorSpace( Imagick::COLORSPACE_GRAY );
	}

	public function filter_sepia() {
		$this->image->sepiaToneImage( 80 );
	}

	public function filter_contrast() {
		$this->image->contrastImage( 1 );
	}

	public function filter_edge() {
		$this->image->edgeImage( 0 );
	}

	public function filter_emboss() {
		$this->image->embossImage( 0, 1 );
	}

	public function filter_gaussian_blur() {
		$this->image->gaussianBlurImage( 2, 3 );
	}

	public function filter_selective_blur() {
		$this->image->blurImage( 5, 3 );
	}

	public function filter_negative() {
		$this->image->negateImage( false );
	}

}