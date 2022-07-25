jQuery(function($) {
	var params = MWCNotices;

	$(document.body).on('click', '.notice.is-dismissible .notice-dismiss', function() {
		var messageId = $(this).closest('.notice').data('message-id');

		if (!messageId) {
			return;
		}

		$.ajax({
			url: ajaxurl,
			data: {
				action: params.dismissNoticeAction,
				messageId: messageId
			}
		});
	});

	// Open GoDaddy Payments modal on click of Get Started button
	$(document.body).on('click', '.mwc-godaddy-payments-recommendation .get-started', function(e) {
		e.preventDefault();

		if ($.WCBackboneModal) {
			new $.WCBackboneModal.View({
				target: 'mwc-payments-godaddy-onboarding-start'
			});
		} else {
			window.location.href = `${window.location.href.split('?')[0]}?page=wc-settings&tab=checkout`;
		}

	});
});