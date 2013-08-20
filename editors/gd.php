<?php

class Improved_Image_Editor_GD extends WP_Image_Editor_GD {
	function filter_grayscale() {
		imagefilter( $this->image, IMG_FILTER_GRAYSCALE );
	}

	function filter_sepia() {
		imagefilter( $this->image, IMG_FILTER_GRAYSCALE );
		imagefilter( $this->image, IMG_FILTER_COLORIZE, 100, 50, 0 );
	}

	function filter_contrast() {
		imagefilter( $this->image, IMG_FILTER_CONTRAST, -40 );
	}

	function filter_edge() {
		imagefilter( $this->image, IMG_FILTER_EDGEDETECT );
	}

	function filter_emboss() {
		imagefilter( $this->image, IMG_FILTER_EMBOSS );
	}

	function filter_gaussian_blur() {
		imagefilter( $this->image, IMG_FILTER_GAUSSIAN_BLUR );
	}

	function filter_selective_blur() {
		imagefilter( $this->image, IMG_FILTER_SELECTIVE_BLUR );
	}

	function filter_negative() {
		imagefilter( $this->image, IMG_FILTER_NEGATE );
	}
}