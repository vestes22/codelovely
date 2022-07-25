<?php

namespace WPaaS\Log\Components;

use WPaaS\Log\Timer;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class BeaverBuilder extends Component {

	use Post_Helpers;

	/**
	 * Get post from Beaver Builder POST data.
	 *
	 * @return WP_Post|false
	 */
	public static function get_post() {

		// @codingStandardsIgnoreStart
		if ( empty( $_POST['fl_builder_data']['post_id'] ) ) {

			return false;

		}

		$post = get_post( $_POST['fl_builder_data']['post_id'] );
		// @codingStandardsIgnoreEnd

		if ( empty( $post ) ) {

			return false;

		}

		// We have a post so stop the timer right away
		Timer::stop();

		return $post;

	}

	/**
	 * Make sure callbacks are added only if Beaver Builder is active.
	 */
	protected function do_callbacks_on_hooks() {

		if ( class_exists( 'FLBuilder' ) ) {

			parent::do_callbacks_on_hooks();

		}

	}

	/**
	 * Fires when Beaver Builder Save & Exit button is clicked.
	 *
	 * @action fl_ajax_before_save_draft
	 */
	public function callback_fl_ajax_before_save_draft() {

		$post = self::get_post();

		if ( ! $post ) {

			return;

		}

		$summary = /* translators: 2. singular post type name (e.g. page), 1. post title */ __(
			'Beaver Builder %2$s "%1$s" drafted',
			'gd-system-plugin'
		);

		$this->log( 'draft', $summary, $this->get_log_meta( $post ) );

	}

	/**
	 * Fires when Beaver Builder Discard & Exit button is clicked.
	 *
	 * @action fl_ajax_before_clear_draft_layout
	 */
	public function callback_fl_ajax_before_clear_draft_layout() {

		$post = self::get_post();

		if ( ! $post ) {

			return;

		}

		$summary = /* translators: 2. singular post type name (e.g. page), 1. post title */ __(
			'Beaver Builder %2$s "%1$s" discarded',
			'gd-system-plugin'
		);

		$this->log( 'discard', $summary, $this->get_log_meta( $post ) );

	}

}
