<?php

class Improved_Image_Editor_GD extends WP_Image_Editor_GD {

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