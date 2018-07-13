<?php

class Improved_Image_Editor_GD extends WP_Image_Editor_GD {

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
			if ( in_array( $this->mime_type, array( 'image/jpeg', 'image/tiff' ) ) ) {
				$exif = exif_read_data($this->file);
				$orientation = 0;

				if ( isset($exif["Orientation"]) ) {
					$orientation = $exif["Orientation"];
				}
				elseif ( isset($exif["IFD0"]) && isset($exif["IFD0"]["Orientation"]) ) {
					$orientation = $exif["IFD0"]["Orientation"];
				}
				elseif ( isset($exif["COMPUTED"]) && isset($exif["COMPUTED"]["Orientation"]) ) {
					$orientation = $exif["COMPUTED"]["Orientation"];
				}

				switch($orientation) {
					case 3:
						$this->rotate(180);
						break;
					case 6:
						$this->rotate(-90);
						break;
					case 8:
						$this->rotate(90);
					break;
				}
			}
		}

		return $loaded;
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
	 *         @type bool ['crop']   Optional. Whether to crop the image. Default false.
	 *     }
	 * }
	 * @return array An array of resized images' metadata by size.
	 */
	public function multi_resize( $sizes ) {
		$metadata = array();
		$orig_size = $this->size;
		$orig_image = $this->image;
		$orig_quality = $this->get_quality();

		foreach ( $sizes as $size => $size_data ) {
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

			$this->image = $this->_resize( $size_data['width'], $size_data['height'], $size_data['crop'] );

			if( ! is_wp_error( $this->image ) ) {
				Improved_Image_Editor::_editor_update_image( $this, $size );

				$resized = $this->_save( $this->image );

				imagedestroy( $this->image );

				if ( ! is_wp_error( $resized ) && $resized ) {
					unset( $resized['path'] );
					$metadata[$size] = $resized;
				}
			}

			$this->size = $orig_size;
			$this->set_quality( $orig_quality );
			$this->image = $orig_image;
		}

		return $metadata;
	}


	public function filter_grayscale() {
		imagefilter( $this->image, IMG_FILTER_GRAYSCALE );
	}

	public function filter_sepia() {
		imagefilter( $this->image, IMG_FILTER_GRAYSCALE );
		imagefilter( $this->image, IMG_FILTER_COLORIZE, 100, 50, 0 );
	}

	public function filter_contrast() {
		imagefilter( $this->image, IMG_FILTER_CONTRAST, -40 );
	}

	public function filter_edge() {
		imagefilter( $this->image, IMG_FILTER_EDGEDETECT );
	}

	public function filter_emboss() {
		imagefilter( $this->image, IMG_FILTER_EMBOSS );
	}

	public function filter_gaussian_blur() {
		imagefilter( $this->image, IMG_FILTER_GAUSSIAN_BLUR );
	}

	public function filter_selective_blur() {
		imagefilter( $this->image, IMG_FILTER_SELECTIVE_BLUR );
	}

	public function filter_negative() {
		imagefilter( $this->image, IMG_FILTER_NEGATE );
	}

}