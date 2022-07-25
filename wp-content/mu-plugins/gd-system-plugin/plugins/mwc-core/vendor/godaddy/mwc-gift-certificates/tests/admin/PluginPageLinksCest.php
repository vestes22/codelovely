<?php

namespace SkyVerge\WooCommerce\PDF_Product_Vouchers\Tests\Admin;

use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates;
use SkyVerge\Lumiere\Tests;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;

/**
 * Tests for the plugin action links.
 */
class PluginPageLinksCest extends Tests\Admin\PluginPageLinksCest {


	/**
	 * Gets the plugin instance.
	 *
	 * @return MWC_Gift_Certificates
	 */
	protected function get_plugin() {

		return wc_pdf_product_vouchers();
	}


}
