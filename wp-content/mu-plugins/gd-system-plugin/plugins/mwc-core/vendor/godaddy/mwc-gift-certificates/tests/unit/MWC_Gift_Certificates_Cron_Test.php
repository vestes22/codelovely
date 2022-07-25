<?php

use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron;

/**
 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron
 */
class MWC_Gift_Certificates_Cron_Test extends MWC_Gift_Certificates_Test_Case  {


	/**
	 * Sets up the tests.
	 */
	public function _setUp() {

		parent::_setUp();

		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 60 * 60 );
		}

		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
		}

		require_once dirname( __FILE__, 3 ) . '/src/class-wc-pdf-product-vouchers-cron.php';
	}


	/**
	 * Tests that can add hooks.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::__construct()
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::add_hooks()
	 *
	 * @throws ReflectionException
	 */
	public function test_can_add_hooks() {

		$handler = $this->get_handler_instance();

		$expire_vouchers_hook         = $this->get_inaccessible_property( $handler, 'expire_vouchers_hook' )->getValue( $handler );
		$cleanup_background_jobs_hook = $this->get_inaccessible_property( $handler, 'cleanup_background_jobs_hook' )->getValue( $handler );

		WP_Mock::expectActionAdded( 'init', [ $handler, 'schedule_voucher_expiry' ], 900 );
		WP_Mock::expectActionAdded( $expire_vouchers_hook, [ $handler, 'expire_vouchers' ], 10 );
		WP_Mock::expectActionAdded( 'init', [ $handler, 'schedule_garbage_collection' ], 900 );
		WP_Mock::expectActionAdded( $cleanup_background_jobs_hook, [ $handler, 'collect_garbage' ], 10 );

		$this->get_inaccessible_method( $handler, 'add_hooks' )->invoke( $handler );
	}


	/**
	 * Tests that can schedule voucher expiry.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::schedule_voucher_expiry()
	 * @dataProvider provider_can_schedule_voucher_expiry
	 *
	 * @param bool $scheduled
	 * @throws ReflectionException
	 */
	public function test_can_schedule_voucher_expiry( bool $scheduled ) {

		$handler = $this->get_handler_instance();

		$expire_vouchers_hook = $this->get_inaccessible_property( $handler, 'expire_vouchers_hook' )->getValue( $handler );

		WP_Mock::userFunction( 'wp_next_scheduled' )
			->once()
			->with( $expire_vouchers_hook )
			->andReturn( $scheduled );

		WP_Mock::userFunction( 'wp_schedule_event' )
			->times( (int) ! $scheduled );

		$handler->schedule_voucher_expiry();
	}


	/** @see test_can_schedule_voucher_expiry */
	public function provider_can_schedule_voucher_expiry() : array {

		return [
			'Scheduled'      => [ true ],
			'Not scheduled'  => [ false ],
		];
	}


	/**
	 * Tests that can schedule garbage collection.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::schedule_garbage_collection()
	 * @dataProvider provider_can_schedule_garbage_collection
	 *
	 * @param bool $scheduled
	 * @throws ReflectionException
	 */
	public function test_can_schedule_garbage_collection( bool $scheduled ) {

		$handler = $this->get_handler_instance();

		$cleanup_background_jobs_hook = $this->get_inaccessible_property( $handler, 'cleanup_background_jobs_hook' )->getValue( $handler );

		WP_Mock::userFunction( 'as_next_scheduled_action' )
			->once()
			->with( $cleanup_background_jobs_hook )
			->andReturn( $scheduled );

		$times    = (int) ! $scheduled;
		$schedule = 24 * HOUR_IN_SECONDS;

		if ( $times > 0 ) {
			WP_Mock::expectFilter( 'wc_pdf_product_vouchers_background_jobs_cleanup_interval', $schedule );
		}

		WP_Mock::userFunction( 'absint' )
			->times( $times )
			->andReturn( $schedule );

		WP_Mock::userFunction( 'as_schedule_recurring_action' )
			->times( $times );

		$handler->schedule_garbage_collection();
	}


	/** @see test_can_schedule_garbage_collection */
	public function provider_can_schedule_garbage_collection() : array {

		return [
			'Scheduled'      => [ true ],
			'Not scheduled'  => [ false ],
		];
	}


	/**
	 * Tests that can get the cleanup schedule interval.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::get_cleanup_schedule_interval()
	 *
	 * @throws ReflectionException
	 */
	public function test_can_get_cleanup_schedule_interval() {

		$handler  = $this->get_handler_instance();
		$method   = $this->get_inaccessible_method( $handler, 'get_cleanup_schedule_interval' );
		$schedule = 24 * HOUR_IN_SECONDS;

		WP_Mock::userFunction( 'absint' )
			->times( 1 )
			->andReturn( $schedule );

		WP_Mock::expectFilter( 'wc_pdf_product_vouchers_cleanup_schedule_interval', $schedule );

		$this->assertEquals( $schedule, $method->invoke( $handler ) );
	}


	/**
	 * Tests that can get the background jobs maximum age for cleanup.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::get_background_jobs_max_age_for_cleanup()
	 *
	 * @throws ReflectionException
	 */
	public function test_can_get_background_jobs_max_age_for_cleanup() {

		$handler  = $this->get_handler_instance();
		$method   = $this->get_inaccessible_method( $handler, 'get_background_jobs_max_age_for_cleanup' );
		$schedule = 14 * DAY_IN_SECONDS;

		WP_Mock::userFunction( 'absint' )
			->times( 1 )
			->andReturn( $schedule );

		WP_Mock::expectFilter( 'wc_pdf_product_vouchers_background_jobs_age_for_cleanup', $schedule );

		$this->assertEquals( $schedule, $method->invoke( $handler ) );
	}


	/**
	 * Gets an instance of the cron handler.
	 *
	 * @return MWC_Gift_Certificates_Cron
	 */
	private function get_handler_instance() : MWC_Gift_Certificates_Cron {

		return new class extends MWC_Gift_Certificates_Cron {


			public function __construct() {

			}


		};
	}


}
