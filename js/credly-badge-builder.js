jQuery(document).ready(function($) {

	// Listen for Credly Badge Builder events
	window.addEventListener( 'message', function(e) {
		// Only continue if data is from credly.com
		if ( "https://credly.com" === e.origin && "object" === typeof( data = e.data ) ) {
			var win = window.dialogArguments || opener || parent || top;

			// Remove the badge builder thickbox
			tb_remove();

			win.WPSetThumbnailHTML('<p>Updating featured image, please wait...</p>');

			// Send the badge data along for uploading
			$.ajax({
				url: ajaxurl,
				data: {
					'action':     'badge-builder-save-badge',
					'post_id':    $('#post_ID').val(),
					'image':      e.data.image,
					'icon_meta':  e.data.iconMetadata,
					'badge_meta': e.data.packagedData,
					'all_data':   e.data
				},
				dataType: 'json',
				success: function( response ) {
					// Update the featured image metabox
					win.WPSetThumbnailHTML(response.data.metabox_html);
				}
			});
		}
	});

	// Resize ThickBox when a badge builder link is clicked
	$('body').on( 'click', '.badge-builder-link', function(e) {
		e.preventDefault();
		badge_builder_setup_thickbox( $(this) );
	});

	// Resize badge builder thickbox on window resize
	$(window).resize(function() {
		badge_builder_resize_tb( $('.badge-builder-link') );
	});

	// Add a custom class to our badge builder thickbox, then resize
	function badge_builder_setup_thickbox( link ) {
		setTimeout( function() {
			$('#TB_window').addClass('badge-builder-thickbox');
			badge_builder_resize_tb( link );
		}, 0 );
	}

	// Force badge builder thickboxes to our specified width/height
	function badge_builder_resize_tb( link ) {
		setTimeout( function() {

			var width  = link.attr('data-width');
			var height = link.attr('data-height');

			$('.badge-builder-thickbox').css({ 'marginLeft': -(width / 2) });
			$('.badge-builder-thickbox, .badge-builder-thickbox #TB_iframeContent').width(width).height(height);
			$('.badge-builder-thickbox, .badge-builder-thickbox #TB_ajaxContent').width(width).height(height).css({'padding':'0px'});

		}, 0 );
	}

});
