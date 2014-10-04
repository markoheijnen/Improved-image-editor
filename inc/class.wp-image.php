<?php
/**
 * WordPress Image Class for using image data and creating new image sizes
 *
 * @package WordPress
 * @uses 
 */
class WP_Image {
	private $filepath;
	private $attachment_id;

	private $editor;

	private $metadata;

	/**
	 * Each instance handles a single attachment.
	 */
	public function __construct( $attachment_id ) {
		if ( wp_attachment_is_image( $attachment_id ) ) {
			$filepath = get_attached_file( $attachment_id );
			$size     = 'full';

			if ( $filepath && file_exists( $filepath ) ) {
				if ( 'full' != $size && ( $data = image_get_intermediate_size( $attachment_id, $size ) ) ) {
					$filepath = apply_filters( 'load_image_to_edit_filesystempath', path_join( dirname( $filepath ), $data['file'] ), $attachment_id, $size );
				}

				$this->filepath      = apply_filters( 'load_image_to_edit_path', $filepath, $attachment_id, 'full' );
				$this->attachment_id = $attachment_id;
			}
		}
	}

	/**
	 * Regenerate a certain image size.
	 *
	 * @access public
	 *
	 * @param string $name The name of the image size
	 * @return boolean|WP_Error
	 */
	public function regenerate_image_size( $name ) {
		global $_wp_additional_image_sizes;

		if ( isset( $_wp_additional_image_sizes[ $name ] ) ) {
			$size_data = $_wp_additional_image_sizes[ $name ];
			$editor    = $this->get_editor();

			if ( is_wp_error( $editor ) ) {
				return $editor;
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

			$size_data = Improved_Image_Editor::_editor_update_size_data( $size_data, $editor, $name );

			$this->get_metadata();

			$resize_result = $editor->resize( $size_data['width'], $size_data['height'], $size_data['crop'] );

			if( ! is_wp_error( $resize_result ) ) {
				Improved_Image_Editor::_editor_update_image( $editor, $name );
				$resized = $editor->save();

				return $this->store_image( $name, $resized );
			}

			return $resize_result;
		}
	}

	/**
	 * Creates a new image size for an attachment.
	 *
	 * @access public
	 *
	 * @param string $name The name of the image size
	 * @param int $max_w
	 * @param int $max_h
	 * @param boolean $crop
	 * @return boolean|WP_Error
	 */
	public function add_image_size( $name, $max_w, $max_h, $crop = false, $extra = array(), $force = false ) {
		if ( has_image_size( $name ) ) {
			return new WP_Error( 'image_size_exists', __( 'This image size has been registered' ) );
		}

		$editor = $this->get_editor();

		if ( $force == false && isset( $this->metadata['sizes'][ $name ] ) ) {
			return new WP_Error( 'image_exists', __( 'This image size already exists' ) );
		}

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$editor->resize( $max_w, $max_h, $crop );
		$resized = $editor->save();

		return $this->store_image( $name, $resized );
	}

	/**
	 * Saves the new data of an image size to the metadata.
	 *
	 * @access public
	 *
	 * @param array $resized The array you get back from WP_Image_Editor:save()
	 * @return boolean
	 */
	public function store_image( $name, $resized ) {
		if ( ! is_wp_error( $resized ) && $resized ) {
			unset( $resized['path'] );
			$this->metadata['sizes'][ $name ] = $resized;

			return $this->update_metadata();
		}

		return false;
	}

	/**
	 * Gets an WP_Image_Editor for current attachment
	 *
	 * @access public
	 *
	 * @return WP_Image_Editor
	 */
	public function get_editor() {
		if ( ! isset( $this->editor ) ) {
			$this->editor = wp_get_image_editor( $this->filepath );
		}

		return $this->editor;
	}

	/**
	 * Gets the attachment meta data
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_metadata() {
		if ( ! isset( $this->metadata ) ) {
			$this->metadata = wp_get_attachment_metadata( $this->attachment_id );
		}

		return $this->metadata; 
	}

	/**
	 * Updates attachment metadata if it's set
	 *
	 * @access public
	 *
	 * @return boolean
	 */
	public function update_metadata() {
		if ( $this->metadata ) {
			return wp_update_attachment_metadata( $this->attachment_id, $this->metadata );
		}

		return false;
	}

}
