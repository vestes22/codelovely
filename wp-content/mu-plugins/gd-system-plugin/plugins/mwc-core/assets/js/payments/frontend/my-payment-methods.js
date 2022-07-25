if (typeof jQuery !== 'undefined') {
	jQuery(function($) {
		window.MWCPaymentsMyPaymentsMethodsHandler = class MWCPaymentsMyPaymentsMethodsHandler {
			constructor(args) {
				this.ajaxUrl = args.ajaxUrl;
				this.savePaymentMethodAction = args.savePaymentMethodAction;
				this.savePaymentMethodNonce = args.savePaymentMethodNonce;
				this.i18n = args.i18n;

				this.replaceMethodColumn();
				this.addEventListeners();
			}

			replaceMethodColumn() {
				let $rows = $('.woocommerce-MyAccount-paymentMethods tr');

				// remove the title header
				$rows.find('th.woocommerce-PaymentMethod--title').remove();

				// replace the content of the method column with the content of the title column
				// then remove the title column
				$rows.each((index, element) => {
					let $titleColumn = $(element).find('td.woocommerce-PaymentMethod--title');
					let isManagedWooCommerceToken = this.isManagedWooCommerceToken($(element));

					if (isManagedWooCommerceToken) {
						$(element).find('td.woocommerce-PaymentMethod--method').html($titleColumn.html());
					}

					// remove the column if this is a mwc-core token or the row doesn't look like a framework token
					if (isManagedWooCommerceToken || $(element).find('[name="token-id"]').length === 0) {
						$titleColumn.remove();
					}
				});
			}

			addEventListeners() {
				$( ".woocommerce-MyAccount-paymentMethods" )
					// handle the edit action
					.on('click', ".woocommerce-PaymentMethod--actions .button.edit", (event) => this.onEditButtonClicked(event))
					// handle the save action
					.on('click', ".woocommerce-PaymentMethod--actions .button.save", (event) => this.onSaveButtonClicked(event))
					// handle the cancel action
					.on('click', ".woocommerce-PaymentMethod--actions .cancel-edit", (event) => this.onCancelButtonClicked(event))
					// handle the delete action
					.on('click', ".woocommerce-PaymentMethod--actions .button.delete", (event) => this.onDeleteButtonClicked(event))

				// don't follow the Add Payment Method button URL if it's disabled
				$( '.button[href*="add-payment-method"]' ).click(function(event) {
					if ($(this).hasClass('disabled')) {
						event.preventDefault();
					}
				});

			}

			onEditButtonClicked(event) {
				event.preventDefault();

				let $button = $(event.currentTarget);
				let $row = $button.closest('tr');

				if (! this.isManagedWooCommerceToken($row)) {
					return;
				}

				$row.find('div.view').hide();
				$row.find('div.edit').show();
				$row.addClass('editing');

				$button.text(this.i18n.cancelButtonLabel).removeClass('edit').addClass('cancel-edit').removeClass('button');

				this.enableEditMode();
			}

			enableEditMode() {
				// set the methods table as 'editing'
				$('.woocommerce-MyAccount-paymentMethods').addClass('editing');

				// disable the Add Payment Method button
				$('.button[href*="add-payment-method"]').addClass('disabled');
			}

			disableEditMode() {
				// removes the methods table's "editing" status
				$('.woocommerce-MyAccount-paymentMethods').removeClass('editing');

				// re-enable the Add Payment Method button
				$('.button[href*="add-payment-method"]').removeClass('disabled');
			}

			onSaveButtonClicked(event) {
				event.preventDefault()

				let $button = $( event.currentTarget )
				let $row = $button.parents( 'tr' )

				if (! this.isManagedWooCommerceToken($row)) {
					return;
				}

				this.blockUi()

				// remove any previous errors
				$row.next( '.error' ).remove()

				$.post(this.ajaxUrl, this.getSavePaymentMethodData($row))
					.done((response) => {
						if (! response.success) {
							this.renderErrors($row, response.data);
							return;
						}

						if (response.data && response.data.title) {
							$row.find('.woocommerce-PaymentMethod--method').html(response.data.title);
						}

						if (response.data && response.data.nonce) {
							this.savePaymentMethodNonce = response.data.nonce;
						}

						// change the "Cancel" button back to "Edit"
						$button.siblings('.cancel-edit').removeClass('cancel-edit').addClass('edit').text(this.i18n.editButtonLabel).addClass( 'button' );

						this.disableEditMode();
					})
					.fail((jqXHR, textStatus, error) => {
						this.renderErrors($row, error);
					})
					.always(() => this.unblockUi());;
			}

			getSavePaymentMethodData($row) {
				return {
					action: this.savePaymentMethodAction,
					nonce: this.savePaymentMethodNonce,
					tokenId: $row.find('input[name=token-id]').val(),
					data: $row.find('input[name]').serialize(),
				};
			}

			onCancelButtonClicked(event) {
				event.preventDefault()

				let $button = $(event.currentTarget);
				let $row = $button.parents('tr');

				if (! this.isManagedWooCommerceToken($row)) {
					return;
				}

				$row.find('div.view').show();
				$row.find('div.edit').hide();
				$row.removeClass('editing');

				// change the "Cancel" button back to "Edit"
				$button.removeClass('cancel-edit').addClass('edit').text(this.i18n.editButtonLabel).addClass( 'button' );

				this.disableEditMode();
			}

			onDeleteButtonClicked(event) {
				let $button = $(event.currentTarget);
				let $row = $button.closest('tr');

				if (! this.isManagedWooCommerceToken($row)) {
					return;
				}

				if ($button.hasClass('disabled') || ! confirm(this.i18n.deleteAys)) {
					event.preventDefault();
				}
			}

			isManagedWooCommerceToken($row) {
				return !!$row.find('[data-mwc-core-token="yes"]').length;
			}

			blockUi() {
				$( ".woocommerce-MyAccount-paymentMethods" ).parent( 'div' ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6,
					}
				});
			}

			unblockUi() {
				$( ".woocommerce-MyAccount-paymentMethods" ).parent( 'div' ).unblock();
			}

			renderErrors($row, error) {
				console.error(error || {});

				let columns = $( ".woocommerce-MyAccount-paymentMethods thead tr th" ).length;
				let html = '<tr class="error"><td colspan="' + columns + '">' + this.i18n.savePaymentMethodError + '</td></tr>';

				$(html).insertAfter($row).find('td').delay(8000).slideUp(200);
			}
		};

		// dispatch loaded event
		$( document.body ).trigger( 'mwc_payments_my_payment_methods_handler_loaded' );
	});
}
