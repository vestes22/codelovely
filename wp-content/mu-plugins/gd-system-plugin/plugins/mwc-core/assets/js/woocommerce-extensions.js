(function($) {
	/**
	 * WooCommerce handlers
	 * @TODO: Should refactor to use vanilla JS here {JO 2021-02-21}
	 *
	 * @type {Object}
	 */
	var MWC = {
		hideManagedSubscriptions: function () {
			$(MWCExtensions.plugins).each(function(i, plugin) {
				if (plugin.homepageUrl) {
					$('a[href="' + plugin.homepageUrl + '"]').parents('tbody').hide();
				}
			});
		}
	};

	if (MWCExtensions.isSubscriptionsPage) {
		MWC.hideManagedSubscriptions();
	}
})(jQuery);
