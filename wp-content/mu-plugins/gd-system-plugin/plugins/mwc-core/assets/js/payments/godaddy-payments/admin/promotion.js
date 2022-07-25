jQuery(function($) {
	"use strict";

	let sectionParams = document.location.search.match(/\Wsection=([\w-]+)\W?/);
	let promotionBlock = $('#godaddy-payments-promotion-block');

	if (promotionBlock.length === 0) {
		return;
	}

	if (sectionParams === null || sectionParams[1] === '_featured') {
		let defaultPromotionBlock = $('#godaddy-payments-promotion-block-placeholder').parents('.addons-banner-block');

		if (defaultPromotionBlock.length) {
			promotionBlock.insertBefore(defaultPromotionBlock);

			promotionBlock.show();
			defaultPromotionBlock.hide();
		}
	} else if (sectionParams[1] === 'payment-gateways') {
		let productGrid = $('ul.products');
		let wcPaymentsBlock = $('.addons-shipping-methods');

		if (wcPaymentsBlock.length && productGrid.length) {
			promotionBlock.insertBefore(productGrid);

			promotionBlock.show();
			wcPaymentsBlock.hide();
		}
	}
});
