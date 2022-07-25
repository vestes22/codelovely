/* global wpaas_stock_photos */

var backBtn = wp.media.View.extend({

	tagName:   'h2',
	className: 'backBtn',

	events: {
		'click': 'goBack'
	},

	initialize: function() {

		this.addEventListener();

	},

	render: function() {

		this.$el.text( wpaas_stock_photos.back_btn );

		return this;

	},

	goBack: function() {

		this.unbind();
		this.remove();

		this.trigger( 'close' );

		if ( this.collection.StockPhotosProps.get( 'previewing' ) ) {

			this.collection.StockPhotosProps.set( 'previewing', false );

		}

	},

	addEventListener: function() {

		var waitForMediaMenu = function( callback ) {
			if ( jQuery( '.media-menu-item').length ) {
				callback();
			} else {
				setTimeout( function() {
					waitForMediaMenu( callback );
				}, 100 );
			}
		};

		var tempThis = this;

		waitForMediaMenu( function() {
			jQuery( '.media-menu-item' ).one( 'click', function( e ) {
				tempThis.goBack();
				jQuery( '#menu-item-wpaas_stock_photos' ).click();
				jQuery( e.target ).click();
			} );
		} );

	}

});

module.exports = backBtn;
