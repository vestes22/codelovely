<?php

use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;


/**
 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron
 */
class MWC_Gift_Certificates_Cron_Test extends MWC_Gift_Certificates_Test_Case {


	/**
	 * Tests that can expire vouchers whose expiration date is in the past.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::expire_vouchers()
	 */
	public function test_can_expire_vouchers() {

		$handler = $this->get_handler_instance();

		$active_voucher_post_data = [
			'post_type'   => 'wc_voucher',
			'post_status' => 'wcpdf-active',
		];

		$should_not_expire = wp_insert_post( $active_voucher_post_data );

		update_post_meta( $should_not_expire, '_expiration_date', strtotime( '+1 year' ) );

		$should_expire = wp_insert_post( $active_voucher_post_data );

		update_post_meta( $should_expire, '_expiration_date', strtotime( '-1 year' ) );

		$handler->expire_vouchers();

		$this->assertEquals( 'wcpdf-active', get_post( $should_not_expire )->post_status );
		$this->assertEquals( 'wcpdf-expired', get_post( $should_expire )->post_status );
	}


	/**
	 * Tests that can delete completed and failed jobs if they are past their maximum age.
	 *
	 * @covers GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Cron::collect_garbage()
	 * @dataProvider provider_can_collect_garbage
	 *
	 * @param string $job_age datetime string
	 */
	public function test_can_collect_garbage( string $job_age ) {

		$handler = $this->get_handler_instance();

		$in_progress_job = $this->add_background_job( [ 'started_processing_at' => $job_age ] );
		$bulk_action_job = $this->add_background_job( [ 'source' => 'bulk_action', 'status' => 'completed', 'completed_at' => $job_age ] );
		$completed_job   = $this->add_background_job( [ 'status' => 'completed', 'completed_at' => $job_age ] );
		$failed_job      = $this->add_background_job( [ 'status' => 'failed', 'failed_at' => $job_age ] );

		$handler->collect_garbage();

		$to_be_deleted = strtotime( $job_age ) <= ( current_time( 'timestamp' ) - 14 * DAY_IN_SECONDS );

		$this->assertIsObject( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $in_progress_job->id ) );
		$this->assertIsObject( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $bulk_action_job->id ) );

		if ( $to_be_deleted ) {
			$this->assertNull( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $completed_job->id ) );
			$this->assertNull( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $failed_job->id ) );
		} else {
			$this->assertIsObject( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $completed_job->id ) );
			$this->assertIsObject( wc_pdf_product_vouchers()->get_background_generator_instance()->get_job( $failed_job->id ) );
		}
	}


	/** @see test_can_collect_garbage */
	public function provider_can_collect_garbage() {

		return [
			'Job age: 1 year'  => [ date( 'Y-m-d H:i:s', strtotime( '-1 year' ) ) ],
			'Job age: 1 month' => [ date( 'Y-m-d H:i:s', strtotime( '-1 month' ) ) ],
			'Job age: 1 week'  => [ date( 'Y-m-d H:i:s', strtotime( '-1 week' ) ) ],
			'Job age: 1 day'   => [ date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ],
		];
	}


	/**
	 * Adds a mock background job in database.
	 *
	 * @param array $args
	 * @return stdClass background job
	 */
	private function add_background_job( array $args = [] ) : stdClass {

		$job = wc_pdf_product_vouchers()->get_background_generator_instance()->create_job( $args );

		foreach ( $args as $property => $value ) {
			if ( isset( $job->$property ) ) {
				$job->$property = $value;
			}
		}

		update_option( 'wc_pdf_product_vouchers_background_generate_job_' . $job->id, json_encode( $job ) );

		return $job;
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
