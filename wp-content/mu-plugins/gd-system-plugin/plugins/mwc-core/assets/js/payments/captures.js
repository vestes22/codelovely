(function($) {

	window.MWCPaymentsCaptureHandler = class MWCPaymentsCaptureHandler
	{
		constructor( args )
		{
			this.action = args.action;
			this.ajaxUrl = args.ajaxUrl;
			this.nonce = args.nonce;
			this.orderId = args.orderId;

			this.i18n = {};
			this.i18n.ays = args.i18n.ays;
			this.i18n.errorMessage = args.i18n.errorMessage;

			$( 'button.mwc-payments-capture' ).on( 'click', ( event ) => {

				if ( ! confirm(this.i18n.ays ) ) {
					event.preventDefault();
				}

				this.submit();

			} );
		}

		submit()
		{
			$( '#woocommerce-order-items' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );

			$.post({
				url: this.ajaxUrl,
				data: {
					action: this.action,
					nonce: this.nonce,
					orderId: this.orderId
				}
			}).done((response) => {

				if (! response.success) {
					alert(response.data.message);
					return;
				}

				location.reload();

			}).fail(() => {

				alert(this.i18n.errorMessage);

			}).always(() => {

				$( '#woocommerce-order-items' ).unblock();

			});
		}
	}

})(jQuery);
