(function() {
	"use strict";

	MWCExtensionsHideDetails.names.forEach(basename => {
		let element = document.querySelector(`tr.plugin-update-tr[data-plugin="${basename}"] .plugin-update .notice p`);

		if (element) {
			let detailsLink = element.querySelector('.open-plugin-details-modal');
			let updateLink = element.querySelector('.update-link');

			if (! updateLink) {
				element.remove();

				return;
			}

			if (detailsLink) {
				detailsLink.remove();
			}

			updateLink.style.textTransform = "capitalize";
		}
	});
}());
