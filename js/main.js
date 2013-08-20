jQuery(document).ready(function($) {
	var button_id = '#imgedit-open-btn-' + $('#post_ID').val();
	var current_history = '';

	if( $(button_id).length > 0 )
		var insta_wp_nonce = $(button_id).attr('onclick').match(/"(.*?)"/)[1];

	var ajax_data = {
		'action': 'imgedit-preview',
		'_ajax_nonce': insta_wp_nonce,
		'postid': $('#post_ID').val(),
		'rand': parseInt( Math.random() * 1000000 )
	};

	$(button_id).removeAttr('onclick');

	$('.size_edit').click(function(evt) {
		evt.preventDefault();

		insta_wp_modal();
	});

	$(button_id).click(function(evt) {
		evt.preventDefault();
		evt.stopPropagation();

		insta_wp_modal();
	});

	$(document).on("click", ".instawp-modal .media-modal-close", function(evt){
		insta_wp_modal_close();
		evt.preventDefault();
	});




	function insta_wp_modal() {
		var filters = new Array( 'default', 'grayscale', 'sepia', 'contrast', 'edge', 'emboss', 'gaussian_blur', 'selective_blur', 'negative' );

		var html = '<div class="instawp-modal media-modal wp-core-ui">';
		html += '<a class="media-modal-close" href="#" title="Sluiten"><span class="media-modal-icon"></span></a>';
		html += '<div class="media-modal-content"><div class="media-frame wp-core-ui">';

		html += '<div class="media-frame-title"><h1>Edit image</h1></div>';

		html += '<div class="media-frame-content">';

		html += '<div class="instawp-modal-content">';


		html += '<div class="imgedit-menu">';
		html += '<div class="imgedit-crop disabled" title="Crop"></div>';
		html += '<div class="imgedit-rleft" title="Rotate counter-clockwise"></div>';
		html += '<div class="imgedit-rright" title="Rotate clockwise"></div>';

		html += '<div class="imgedit-flipv" title="Flip vertically"></div>';
		html += '<div class="imgedit-fliph" title="Flip horizontally"></div>';

		html += '<div id="image-undo-40" class="imgedit-undo disabled" title="Undo"></div>';
		html += '<div id="image-redo-40" class="imgedit-redo disabled" title="Redo"></div>';
		html += '<br class="clear">';
		html += '</div>';


		html += '<div><img src="' + ajaxurl + '?' + $.param( ajax_data ) + '" id="instawp-modal-image" alt="Editable image" /></div>';
		html += '<a href="#" class="button media-button button-primary button-large media-button-insert">Save</a>';
		html += '</div>';

		html += '<div class="media-sidebar">';

		for (var filter in filters) {
			html += '<div class="instawp-filter">';
			html += '<img src="' + insta_wp_image_url( filters[ filter ] ) + '" alt="Image ' + filters[ filter ] + '" filter="' + filters[ filter ] + '" />';
			html += '<div>' + filters[ filter ] + '</div>';
			html += '</div>';
		}

		html += '</div>';

		html += '</div>';

		html += '</div></div></div>';

		$('body').append(html);
		$('body').append('<div class="media-modal-backdrop"></div>');
	}

	function insta_wp_modal_close() {
		$('.instawp-modal').remove();
		$('.media-modal-backdrop').remove();
	}

	function insta_wp_image_url( filter ) {
		var data_img = ajax_data;
		data_img['rand'] = parseInt( Math.random() * 1000000 );

		var history = {};
		history['type'] = 'filter';
		history['filter'] = filter;

		data_img['history'] = JSON.stringify( new Array( history ) );

		return ajaxurl + '?' + $.param(data_img);
	}

	$(document).on("click", ".instawp-modal .media-sidebar img", function(evt){
		evt.preventDefault();

		var history = {};
		history['type'] = 'filter';
		history['filter'] = $(this).attr('filter');

		current_history = JSON.stringify( new Array( history ) );

		$('#instawp-modal-image').attr( 'src', $(this).attr('src') );
	});

	$(document).on("click", ".instawp-modal .media-button-insert", function(evt){
		if( ! current_history ) {
			insta_wp_modal_close();
			return;
		}

		var postid = $('#post_ID').val();
		var target = 'all';

		var data = {
			'action': 'image-editor',
			'_ajax_nonce': insta_wp_nonce,
			'postid': postid,
			'history': current_history,
			'target': target,
			'context': $('#image-edit-context').length ? $('#image-edit-context').val() : null,
			'do': 'save'
		};

		$.post(ajaxurl, data, function(r) {
			var ret = JSON.parse(r);

			if ( ret.error ) {
				$('#imgedit-response-' + postid).html('<div class="error"><p>' + ret.error + '</p><div>');
				insta_wp_modal_close();
				return;
			}

			if ( ret.fw && ret.fh )
				$('#media-dims-' + postid).html( ret.fw + ' &times; ' + ret.fh );

			if ( ret.thumbnail )
				$('.thumbnail', '#thumbnail-head-' + postid).attr('src', ''+ret.thumbnail);

			if ( ret.msg )
				$('#imgedit-response-' + postid).html('<div class="updated"><p>' + ret.msg + '</p></div>');

			insta_wp_modal_close();
		});
	});
});