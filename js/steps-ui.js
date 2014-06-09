jQuery(document).ready(function($) {

	// Hide our Triggers metabox if unnecessary
	$("#_badgeos_earned_by").change( function() {
		if ( 'triggers' == $(this).val() )
			$('#badgeos_steps_ui').show();
		else
			$('#badgeos_steps_ui').hide();
	}).change();

	// Make our Triggers list sortable
	$("#steps_list").sortable({

		// When the list order is updated
		update : function () {

			// Loop through each element
			$('#steps_list li').each(function( index, value ) {

				// Write it's current position to our hidden input value
				$(this).children('input[name="order"]').val( index );

			});

		}
	});

	// Listen for our change to our trigger type selectors
	$('#steps_list').on( 'change', '.select-trigger-type', function() {

		// Grab our selected trigger type and achievement selector
		var trigger_type = $(this).val();
		var achievement_selector = $(this).siblings('.select-achievement-type');

		// If we're working with achievements, show the achievement selecter (otherwise, hide it)
		if ( 'any-achievement' == trigger_type || 'all-achievements' == trigger_type || 'specific-achievement' == trigger_type ) {
			achievement_selector.show();
		} else {
			achievement_selector.hide();
		}

		// Trigger a change for our achievement type post selector to determine if it should show
		achievement_selector.change();

	});
	// Trigger a change for our trigger type post selector to determine if it should show
	$( '.select-trigger-type' ).change();

	// Listen for a change to our achivement type selectors
	$('#steps_list').on( 'change', '.select-achievement-type', function() {

		// Setup our necessary variables
		var achievement_selector = $(this);
		var achievement_type     = achievement_selector.val();
		var step_id              = achievement_selector.parent('li').attr('data-step-id');
		var excluded_posts       = [achievement_selector.siblings('input[name="post_id"]').val()];
		var trigger_type         = achievement_selector.siblings('.select-trigger-type').val();

		// If we've selected a *specific* achievement type, show our post selector
		// and populate it w/ the corresponding achievement posts
		if ( '' !== achievement_type && 'specific-achievement' == trigger_type ) {
			$.post(
				ajaxurl,
				{
					action: 'post_select_ajax',
					achievement_type: achievement_type,
					step_id: step_id,
					excluded_posts: excluded_posts
				},
				function( response ) {
					achievement_selector.siblings('select.select-achievement-post').html( response );
					achievement_selector.siblings('select.select-achievement-post').show();
				}
			);

		// Otherwise, keep our post selector hidden
		} else {
			achievement_selector.siblings('.select-achievement-post').hide();
			if ( 'badgeos_specific_new_comment' == trigger_type ) {
				achievement_selector.siblings('input.select-achievement-post').show();
			}
		}
	});
	// Trigger a change for our achievement type post selector to determine if it should show
	$( '.select-achievement-type' ).change();
});

// Add a step
function badgeos_add_new_step( achievement_id ) {
	jQuery.post(
		ajaxurl,
		{
			action: 'add_step',
			achievement_id: achievement_id
		 },
		function( response ) {
			jQuery( response ).appendTo( '#steps_list' );

			// Dynamically add the menu order for the new step to be one higher
			// than the last in line
			new_step_menu_order = Number( jQuery( '#steps_list li.step-row' ).eq( -2 ).children( 'input[name="order"]' ).val() ) + 1;
			jQuery( '#steps_list li.step-row:last' ).children( 'input[name="order"]' ).val( new_step_menu_order );

			// Trigger a change for the new trigger type <select> element
			jQuery( '#steps_list li.step-row:last' ).children( '.select-trigger-type' ).change();
		}
	);
}

// Delete a step
function badgeos_delete_step( step_id ) {
	jQuery.post(
		ajaxurl,
		{
			action: 'delete_step',
			step_id: step_id
		},
		function( response ) {
			jQuery( '.step-' + step_id ).remove();
		}
	);
}

// Update all steps
function badgeos_update_steps(e) {

	jQuery( '.save-steps-spinner' ).show();
	step_data = {
		action: 'update_steps',
		steps: []
	};

	// Loop through each step and collect its data
	jQuery( '.step-row' ).each( function() {

		// Cache our step object
		var step = jQuery(this);
		var trigger_type = step.find( '.select-trigger-type' ).val();

		// Setup our step object
		var step_details = {
			"step_id"          : step.attr( 'data-step-id' ),
			"order"            : step.find( 'input[name="order"]' ).val(),
			"required_count"   : step.find( '.required-count' ).val(),
			"trigger_type"     : trigger_type,
			"achievement_type" : step.find( '.select-achievement-type' ).val(),
			"achievement_post" : 'badgeos_specific_new_comment' === trigger_type ? step.find( 'input.select-achievement-post' ).val() : step.find( 'select.select-achievement-post' ).val(),
			"title"            : step.find( '.step-title .title' ).val()
		};

		// Allow external functions to add their own data to the array
		step.trigger( 'update_step_data', [ step_details, step ] );

		// Add our relevant data to the array
		step_data.steps.push( step_details );

	});

	jQuery.post(
		ajaxurl,
		step_data,
		function( response ) {

			// Parse our response and update our step titles
			var titles = jQuery.parseJSON( response );
			jQuery.each( titles, function( index, value ) {
				jQuery('#step-'+index+'-title').val(value);
			});

			// Hide our save spinner
			jQuery( '.save-steps-spinner' ).hide();
		}
	);
}
