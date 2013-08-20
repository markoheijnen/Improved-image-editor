window.wp = window.wp || {};

(function($){
	wp.image = wp.image || {};

	wp.image.editor = {
		open: function( id ) {

		},

		init: function() {
			$('.edit-attachment').unbind("click");
			$(document.body).on( 'click', '.edit-attachment', function( event ) {
				var $this = $(this);

				event.preventDefault();

				alert('first');
				//wp.image.editor.open( editor );
			});
		}
	};

	_.bindAll( wp.image.editor, 'open' );
	$( wp.image.editor.init );
}(jQuery));