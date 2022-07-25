<?php

namespace WPaaS\Admin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Site_Health {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		add_filter( 'site_status_tests', [ $this, 'remove_site_status_tests' ] );

	}

	/**
	 * Exclude certain site status tests from running
	 *
	 * @param array $tests Site status tests.
	 *
	 * @return array Filtered list of site status tests to run.
	 */
	public function remove_site_status_tests( $tests ) {

		$excluded_tests = [
			'direct' => [
				'wordpress_version',
			],
			'async'  => [
				'background_updates',
			],
		];

		foreach ( $excluded_tests as $type => $test_labels ) {

			if ( ! array_key_exists( $type, $tests ) || empty( $tests[ $type ] ) ) {

				continue;

			}

			foreach ( $test_labels as $label ) {

				if ( ! array_key_exists( $label, $tests[ $type ] ) ) {

					continue;

				}

				unset( $tests[ $type ][ $label ] );

			}

		}

		return $tests;

	}

}
