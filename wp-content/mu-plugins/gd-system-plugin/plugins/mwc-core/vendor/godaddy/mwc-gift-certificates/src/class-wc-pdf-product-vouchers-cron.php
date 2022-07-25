<?php
/**
 * MWC Gift Certificates
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
 * Do not edit or add to this file if you wish to upgrade MWC Gift Certificates to newer
 * versions in the future. If you wish to customize MWC Gift Certificates for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GiftCertificates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Vouchers Cron Class.
 *
 * Adds custom update schedule and schedules voucher expiry update events.
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Cron {


	/** @var string WP Cron event to expiry vouchers */
	protected $expire_vouchers_hook = 'wc_pdf_product_vouchers_expire_vouchers';

	/** @var string Action Scheduler garbage collection hook name */
	protected $cleanup_background_jobs_hook = 'wc_pdf_product_vouchers_cleanup_background_jobs';


	/**
	 * Initializes the cron handler.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Adds hooks.
	 *
	 * @since 3.9.4
	 */
	protected function add_hooks() {

		// schedule expiry events - run in both frontend and backend so events are still scheduled when an admin reactivates the plugin
		add_action( 'init', [ $this, 'schedule_voucher_expiry' ], 900 );
		// expire vouchers that are past their expiration date
		add_action( $this->expire_vouchers_hook, [ $this, 'expire_vouchers' ] );

		// schedule garbage collection for completed background jobs
		add_action( 'init', [ $this, 'schedule_garbage_collection' ], 900 );
		// deletes background jobs that have completed but persist in database
		add_action( $this->cleanup_background_jobs_hook, [ $this, 'collect_garbage' ] );
	}


	/**
	 * Adds the expiry event if not already scheduled.
	 *
	 * This performs a `do_action( 'wc_pdf_product_vouchers_expire_vouchers' )` on our custom schedule.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function schedule_voucher_expiry() {

		if ( wp_next_scheduled( $this->expire_vouchers_hook ) ) {
			return;
		}

		wp_schedule_event( time(), 'hourly', $this->expire_vouchers_hook );
	}


	/**
	 * Sets vouchers whose expiration date is the past, as expired.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function expire_vouchers() {

		$voucher_posts = get_posts( [
			'nopaging'     => true,
			'post_type'    => 'wc_voucher',
			'post_status'  => 'wcpdf-active',
			'meta_query'   => [
				'relation' => 'AND',
				[
					'key'     => '_expiration_date',
					'compare' => '>',
					'value'   => '0',
				],
				[
					'key'     => '_expiration_date',
					'compare' => '<=',
					'value'   => time(),
				],
			],
		] );

		if ( ! empty( $voucher_posts ) ) {
			foreach ( $voucher_posts as $post ) {
				if ( $voucher = wc_pdf_product_vouchers_get_voucher( $post ) ) {
					$voucher->update_status( 'expired' );
				}
			}
		}
	}


	/**
	 * Schedules an action to cleanup past background jobs that have completed.
	 *
	 * Runs every 24 hours by default, but this can be adjusted via filter hook.
	 *
	 * @internal
	 *
	 * @since 3.9.4
	 */
	public function schedule_garbage_collection() {

		if ( as_next_scheduled_action( $this->cleanup_background_jobs_hook ) ) {
			return;
		}

		as_schedule_recurring_action( strtotime( '+5 minutes' ), $this->get_cleanup_schedule_interval(), $this->cleanup_background_jobs_hook );
	}


	/**
	 * Collects and deletes completed jobs past 14 days.
	 *
	 * The interval can be adjusted via filter.
	 *
	 * @internal
	 *
	 * @since 3.9.4
	 */
	public function collect_garbage() {

		$handler = wc_pdf_product_vouchers()->get_background_generator_instance();

		if ( ! $handler ) {
			return;
		}

		$jobs = $handler->get_jobs( [
			'status' => [ 'completed', 'failed', 'queued' ],
		] );

		if ( empty( $jobs ) ) {
			return;
		}

		foreach ( $jobs as $job ) {

			// skip jobs created via bulk action
			if ( ! empty( $job->source ) && 'bulk_action' === $job->source ) {
				continue;
			}

			if ( 'completed' === $job->status && isset( $job->completed_at ) ) {
				$datetime = $job->completed_at;
			} else {
				$datetime = $job->failed_at ?? false;
			}

			$timestamp = $datetime ? strtotime( $datetime ) : false;

			if ( $timestamp && ( $timestamp <= current_time( 'timestamp' ) - $this->get_background_jobs_max_age_for_cleanup() ) ) {
				$handler->delete_job( $job );
			}
		}
	}


	/**
	 * Gets the cleanup schedule interval in seconds.
	 *
	 * @see MWC_Gift_Certificates_Cron::schedule_garbage_collection()
	 *
	 * @since 3.9.4
	 *
	 * @return int defaults to 24 hours
	 */
	protected function get_cleanup_schedule_interval() : int {

		/**
		 * Filters how often a cleanup of old background jobs should be done.
		 *
		 * @since 3.9.4
		 *
		 * @param int $interval number of seconds between cleanups (defaults to 24 hours)
		 */
		return absint( apply_filters( 'wc_pdf_product_vouchers_cleanup_schedule_interval', 24 * HOUR_IN_SECONDS ) );
	}


	/**
	 * Gets the background jobs maximum age for cleaning up, in seconds.
	 *
	 * @see MWC_Gift_Certificates_Cron::collect_garbage()
	 *
	 * @since 3.9.4
	 *
	 * @return int defaults to two weeks
	 */
	protected function get_background_jobs_max_age_for_cleanup() : int {

		/**
		 * Filters the maximum age of background jobs scheduled for cleanup.
		 *
		 * @since 3.9.4
		 *
		 * @param int $interval age of a background job in seconds
		 */
		return absint( apply_filters( 'wc_pdf_product_vouchers_background_jobs_age_for_cleanup', 14 * DAY_IN_SECONDS ) );
	}


}
