/**
 * Poynt Collect payment form handler.
 *
 * @since 1.0.0
 */
jQuery( ( $ ) => {

	'use strict';

	/**
	 * Payment form handler.
	 *
	 * Interacts with the Poynt Collect API to process a checkout payment form.
	 *
	 * @link https://docs.poynt.com/app-integration/poynt-collect/#poynt-collect
	 *
	 * @since 1.0.0
	 */
	window.MWCPaymentsPoyntPaymentFormHandler = class MWCPaymentsPoyntPaymentFormHandler {

		/**
		 * Instantiates the payment form handler.
		 *
		 * Loads the payment handler and intercepts form submissions to inject the token returned by Poynt Collect API.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} args form handler arguments
		 */
		constructor( args ) {

			this.appId            = args.appId;
			this.businessId       = args.businessId;
			this.customerAddress  = args.customerAddress;
			this.isLoggingEnabled = args.isLoggingEnabled;
			this.formInitialized = false;

			if ( $( 'form.checkout' ).length ) {
				this.form = $( 'form.checkout' );
				this.handleCheckout();
			} else if ( $( 'form#order_review' ).length ) {
				this.form = $( 'form#order_review' );
				this.handlePayPage();
			} else if ( $( 'form#add_payment_method' ).length ) {
				this.form = $( 'form#add_payment_method' );
				this.handleMyAccount();
			} else {
				this.debugLog('No payment form available');
				return;
			}

			// clear the payment nonce on errors
			$( document.body ).on( 'checkout_error', () => {
				this.clearNonce();
			} );
		}

		/**
		 * Gets the nonce field.
		 *
		 * Returns a jQuery object with the hidden input that holds a nonce value.
		 *
		 * @since 1.0.0
		 *
		 * @returns {Object} jQuery object
		 */
		getNonceField() {
			return $( '#mwc-payments-poynt-payment-nonce' );
		}

		/**
		 * Clears the payment nonce.
		 *
		 * Resets the nonce value in the hidden input.
		 *
		 * @since 1.0.0
		 */
		clearNonce() {
			this.getNonceField().val( '' );
		}

		/**
		 * Creates a nonce using Poynt Collect.
		 *
		 * Saves the nonce to a hidden input and resubmits the form.
		 *
		 * @link https://docs.poynt.com/app-integration/poynt-collect/#creating-a-nonce
		 *
		 * @since 1.0.0
		 */
		createNonce() {

			let nonceData = {
				businessId: this.businessId
			};

			if ( this.customerAddress.firstName )  {
				nonceData.firstName = this.customerAddress.firstName;
			}

			if ( this.customerAddress.lastName )  {
				nonceData.lastName = this.customerAddress.lastName;
			}

			if ( this.customerAddress.line1 )  {
				nonceData.line1 = this.customerAddress.line1;
			}

			if ( this.customerAddress.postcode )  {
				nonceData.zip = this.customerAddress.postcode;
			}

			this.debugLog( nonceData );

			/**
			 * @link https://docs.poynt.com/app-integration/poynt-collect/#collect-getnonce
			 */
			this.collect.getNonce( nonceData );
		}

		handleCheckout() {
			$( document.body ).on( 'updated_checkout', () => this.setFields() );

			$( document.body ).on( 'updated_checkout', () => this.handleSavedPaymentMethods() );

			this.form.on( 'checkout_place_order_poynt', () => this.validatePaymentData() );
		}

		/**
		 * Determines whether a nonce exists.
		 *
		 * Checks the hidden input for a value.
		 *
		 * @since 1.0.0
		 *
		 * @returns {boolean} whether a nonce exists
		 */
		hasNonce() {
			return this.getNonceField().val().length > 0;
		}

		/**
		 * Determines whether a postcode field is present.
		 *
		 * @since 1.0.0
		 *
		 * @returns {boolean} whether the postcode field exists
		 */
		hasPostcodeField() {
			return $( '#billing_postcode' ).length > 0;
		}

		handleMyAccount() {

			this.setFields();

			this.form.submit( () => {

				if ( $( '#add_payment_method input[name=payment_method]:checked' ).val() === 'poynt' ) {
					return this.validatePaymentData();
				}
			} );
		}

		/**
		 * Handles the error event data.
		 *
		 * Logs errors to console and maybe renders them in a user-facing notice.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} event after a form error
		 */
		handleError( event ) {

			let errorMessage = '';

			// Poynt Collect API has some inconsistency about error message response data:
			if ( 'error' === event.type && event.data ) {
				if ( event.data.error && event.data.error.message && event.data.error.message.message ) {
					errorMessage = event.data.error.message.message;
				} else if ( event.data.message ) {
					errorMessage = event.data.message;
				} else if ( event.data.error && event.data.error.message && event.data.error.source && 'submit' === event.data.error.source ) {
					errorMessage = event.data.error.message;
				} else if ( event.data.error ) {
					errorMessage = event.data.error;
				} else {
					errorMessage = poyntPaymentFormI18n.errorMessages.genericError;
				}
			}

			if ( 'string' === typeof errorMessage && errorMessage.length > 0 ) {

				this.debugLog( errorMessage );

				if ( errorMessage.includes( 'Request failed' ) ) {
					this.renderErrors( [ poyntPaymentFormI18n.errorMessages.genericError ] );
				} else if ( errorMessage.includes( 'Missing details' ) || errorMessage.includes( 'Enter a' ) ) {
					this.renderErrors( [ poyntPaymentFormI18n.errorMessages.missingCardDetails ] );
				} else if ( errorMessage.includes( 'Missing field' ) ) {
					this.renderErrors([ poyntPaymentFormI18n.errorMessages.missingBillingDetails ] );
				} else {
					this.renderErrors( [ errorMessage ] );
				}

			} else {

				this.debugLog( event );
			}
		}

		handlePayPage() {

			this.setFields();

			this.handleSavedPaymentMethods();

			this.form.submit( () => {

				if ( $( '#order_review input[name=payment_method]:checked' ).val() === 'poynt' ) {
					return this.validatePaymentData();
				}
			} );
		}

		/**
		 * Handles a payment form ready event.
		 *
		 * Unblocks the payment form after initialization.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} event after the form is ready
		 */
		handlePaymentFormReady( event ) {

			if ( ! event.type || 'ready' !== event.type ) {
				this.debugLog( event );
			} else {
				this.debugLog( 'Payment form ready' );
			}

			this.form.unblock();
		}

		/**
		 * Handles a nonce ready event.
		 *
		 * Sets the nonce to hidden field and submits the form.
		 *
		 * @since 1.0.0
		 *
		 * @param {Object} payload containing the nonce
		 */
		handleNonceReady( payload ) {

			if ( payload.data && payload.data.nonce ) {
				this.getNonceField().val( payload.data.nonce );
				this.debugLog( 'Nonce set' );
			} else {
				this.clearNonce();
				this.debugLog( 'Nonce value is empty' );
			}

			this.form.submit();
		}

		handleSavedPaymentMethods() {

			let $newMethodForm = $('.mwc-payments-poynt-new-payment-method-form');

			$('input.mwc-payments-poynt-payment-method').change( () => {

				if ( $( "input.mwc-payments-poynt-payment-method:checked" ).val() ) {
					$newMethodForm.slideUp( 200 );
				} else {
					$newMethodForm.slideDown( 200 );
				}

			} ).change();

			$( 'input#createaccount' ).change(function () {

				let $parentRow = $('input.mwc-payments-tokenize-payment-method').closest( 'p.form-row' );

				if ( $( this ).is( ':checked' ) ) {
					$parentRow.slideDown();
					$parentRow.next().show();
				} else {
					$parentRow.hide();
					$parentRow.next().hide();
				}
			});

			if (! $( 'input#createaccount' ).is( ':checked' ) ) {
				$( 'input#createaccount' ).change();
			}
		}

		/**
		 * Initializes the form.
		 *
		 * Adds listeners for the ready and error events.
		 *
		 * @link https://docs.poynt.com/app-integration/poynt-collect/#collect-mount
		 *
		 * @since 1.0.0
		 */
		initForm() {

			// run only once
			if ( this.initializingForm ) {
				return;
			}

			this.initializingForm = true;

			this.collect = new TokenizeJs( this.businessId, this.appId );

			let showZip = ! this.customerAddress.postcode && ! this.hasPostcodeField();

			/**
			 * Initialize the Payment Form with Poynt Collect API.
			 *
			 * For configuration options, see:
			 * @link https://docs.poynt.com/app-integration/poynt-collect/#collect-mount
			 *
			 * For CSS options, see:
			 * @link https://docs.poynt.com/app-integration/poynt-collect/#passing-in-custom-css-optional
			 * @link https://github.com/medipass/react-payment-inputs#styles
			 */
			this.collect.mount( 'mwc-payments-poynt-hosted-form', document, {
				displayComponents: {
					firstName:    false,
					lastName:     false,
					emailAddress: false,
					zipCode:      showZip,
					labels:       true,
					submitButton: false,
				},
				iFrame: {
					border:       '0px',
					borderRadius: '0px',
					boxShadow:    'none',
					height:       showZip ? '280px' : '230px',
					width:        'auto',
				},
				style: {
					theme: 'checkout',
				},
			} );

			// triggers when a nonce is ready
			this.collect.on( 'nonce', payload => {
				this.handleNonceReady( payload );
			} );

			// triggers when the payment form is ready
			this.collect.on( 'ready', event => {

				this.initializingForm = false;
				this.formInitialized  = true;

				this.handlePaymentFormReady( event );
			} );

			// triggers when there is a payment form error
			this.collect.on( 'error', error => {
				this.handleError( error );
			} );
		}

		/**
		 * Sets up the payment fields.
		 *
		 * Calls parent method and initializes the payment form.
		 *
		 * @since 1.0.0
		 */
		setFields() {

			this.fields = $('.payment_method_poynt');

			if ( this.formInitialized ) {
				this.collect.unmount( 'mwc-payments-poynt-hosted-form', document );
				this.formInitialized = false;
			}

			if ( this.businessId && this.appId && ! this.initializingForm ) {
				this.initForm();
			}
		}

		validatePaymentData() {

			if ( this.form.is( '.processing' ) ) {
				return false;
			}

			if ( this.fields.find( 'input.mwc-payments-poynt-payment-method:checked' ).val() || this.hasNonce() ) {
				return true;
			}

			// override the loaded address data if available via form fields
			if ( $( '#billing_first_name' ).val() ) {
				this.customerAddress.firstName = $( '#billing_first_name' ).val();
			}

			if ( $( '#billing_last_name' ).val() ) {
				this.customerAddress.lastName = $( '#billing_last_name' ).val();
			}

			if ( $( '#billing_address_1' ).val() ) {
				this.customerAddress.line1 = $( '#billing_address_1' ).val();
			}

			if ( $( '#billing_postcode' ).val() ) {
				this.customerAddress.postcode = $( '#billing_postcode' ).val();
			}

			// block the UI
			this.form.block( { message: null, overlayCSS: { background: '#fff', opacity: 0.6 } } );

			// create the nonce
			this.createNonce();

			// always return false to resubmit the form
			return false;
		}

		/**
		 * Logs an item to console if logging is enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param {String|Object} logData
		 */
		debugLog( logData ) {
			if ( this.isLoggingEnabled ) {
				console.log( logData );
			}
		}

		renderErrors(errors) {
			$( '.woocommerce-error, .woocommerce-message' ).remove();

			this.form.prepend( '<ul class="woocommerce-error"><li>' + errors.join( '</li><li>' ) + '</li></ul>' );

			this.form.removeClass( 'processing' ).unblock();
			this.form.find( '.input-text, select' ).blur();

			$( 'html, body' ).animate( { scrollTop: this.form.offset().top - 100 }, 1000 );
		}

	}

	// dispatch loaded event
	$( document.body ).trigger( 'mwc_payments_poynt_payment_form_handler_loaded' );

} );
