jQuery(function($) {
	"use strict";

	let paymentMethods = MWCPaymentsPaymentMethods;

	for ( let method of paymentMethods ) {
		if (! method.allowManage) {
			$('tr[data-gateway_id="'+method.gatewayId+'"] td.name a').replaceWith(function() {
				return $("<span>" + $(this).html() + "</span>");
			});
		}

		if (! method.allowButton) {
			$('tr[data-gateway_id="'+method.gatewayId+'"] .onboarding-action a').css('pointer-events','none').css('opacity', '0.2');
		}

		if (! method.allowEnable) {
			$('tr[data-gateway_id="'+method.gatewayId+'"] .wc-payment-gateway-method-toggle-enabled').css('pointer-events','none').css('opacity', '0.2');
		}

		let $setUpButton = $('tr[data-gateway_id="'+method.gatewayId+'"] .onboarding-action a.start, tr[data-gateway_id="'+method.gatewayId+'"] .onboarding-action a.disconnected').on('click', function(event){

			event.preventDefault();

			let data = {
				action: method.setupIntentAction,
				setupIntentNonce: method.setupIntentNonce,
			}

			const sourceMatch = document.location.search.match(/\Wsource=([\w-]+)\W?/);
			if (sourceMatch && sourceMatch[1]) {
				data.source = sourceMatch[1];
			}

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
			});

			new $.WCBackboneModal.View({
				target: 'mwc-payments-godaddy-onboarding-start'
			});
		});

		// open the Set up modal if the gdpsetup parameter is included in the URL and the button would have normally opened the Set up modal
		if (document.location.search.match(/\bgdpsetup=true\b/)) {
			$setUpButton.click();
		}

		$('tr[data-gateway_id="'+method.gatewayId+'"] .onboarding-action a.remove').on('click', function(event){

			event.preventDefault();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: method.removePaymentMethodAction,
					nonce: method.removePaymentMethodNonce,
				}
			});

			$('tr[data-gateway_id="'+method.gatewayId+'"]').remove();

			if ('poynt' === method.gatewayId) {
				// when poynt is removed, godaddy-payments-payinperson must be removed as well
				$('tr[data-gateway_id="godaddy-payments-payinperson"]').remove();
			}
		});

		$('#woocommerce_'+method.gatewayId+'_transaction_type').on('change', function(){

			if ($(this).val() === 'authorization') {
				$('#woocommerce_'+method.gatewayId+'_charge_virtual_orders, #woocommerce_'+method.gatewayId+'_capture_paid_orders').closest('tr').show();
			} else {
				$('#woocommerce_'+method.gatewayId+'_charge_virtual_orders, #woocommerce_'+method.gatewayId+'_capture_paid_orders').closest('tr').hide();
			}

		}).change();
	}
});
