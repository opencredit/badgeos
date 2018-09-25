window.cmb2DateRange = window.cmb2DateRange || {};

(function(window, document, $, app, undefined){
	'use strict';

	app.init = function( evt, cmb ) {
		var $body = $( 'body' );

		$( '[data-daterange]' ).each( function() {

			var $this = $( this );
			var data = $this.data( 'daterange' );

			var fieldOpts = $this.data( 'daterangepicker' ) || {};
			var options   = $.extend( {
				initialText       : data.buttontext,
				datepickerOptions : {
					minDate: null,
					maxDate: null
				},
			}, fieldOpts );


			$this.addClass( 'button-secondary' ).next( '.spinner' ).removeClass( 'is-active' );
			$body.trigger( 'cmb2_daterange_init', { '$el' : $this, 'options' : options } );

			$this.daterangepicker( cmb.datePickerSetupOpts( fieldOpts, options, 'datepicker' ) );
		});

		$( '.cmb-type-date-range .comiseo-daterangepicker-triggerbutton' ).addClass( 'button-secondary' ).removeClass( 'comiseo-daterangepicker-top comiseo-daterangepicker-vfit' );
		$( '.comiseo-daterangepicker' ).addClass( 'cmb2-element' );

	};

	$( document ).on( 'cmb_init', app.init );

})(window, document, jQuery, window.cmb2DateRange);
