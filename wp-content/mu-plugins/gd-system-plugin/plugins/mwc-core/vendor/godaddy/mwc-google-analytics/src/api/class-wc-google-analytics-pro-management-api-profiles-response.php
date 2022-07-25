<?php
/**
 * Google Analytics
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Google Analytics to newer
 * versions in the future. If you wish to customize Google Analytics for your
 * needs please refer to https://help.godaddy.com/help/40882
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2021, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GoogleAnalytics\API\Management_API;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Handles responses from the Google Analytics Management API Views (Profiles) routes.
 *
 * @link https://developers.google.com/analytics/devguides/config/mgmt/v3/mgmtReference/management/profiles
 *
 * @since 1.7.0
 */
class Profiles_Response extends Response {


	/**
	 * Returns a list of views (profiles) to which the current user has access to.
	 *
	 * @link https://developers.google.com/analytics/devguides/config/mgmt/v3/mgmtReference/management/profiles/list
	 *
	 * @since 1.7.0
	 *
	 * @return \stdClass[] array of profile objects
	 */
	public function list_views() {

		$profiles = [];

		if ( isset( $this->response_data->items ) ) {
			$profiles = (array) $this->response_data->items;
		}

		return $profiles;
	}


}
