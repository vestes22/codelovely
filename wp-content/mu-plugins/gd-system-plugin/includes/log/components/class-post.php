<?php

namespace WPaaS\Log\Components;

use WPaaS\Log\Event;
use WPaaS\Log\Timer;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

class Post extends Component {

	use Post_Helpers;

	/**
	 * {Post Type} > {Action}
	 *
	 * @action transition_post_status
	 *
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 */
	public function callback_transition_post_status( $new_status, $old_status, $post ) {

		if ( ! is_a( $post, 'WP_Post' ) ) {

			return;

		}

		if (
			$this->is_excluded_post_type( $post->post_type )
			||
			$this->is_excluded_post_status( $new_status )
		) {

			return;

		}

		Timer::stop();

		// Defaults
		$action  = 'update';
		$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
			'"%1$s" %2$s updated',
			'gd-system-plugin'
		);

		switch ( true ) {

			case ( $new_status === $old_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s updated',
					'gd-system-plugin'
				);

				break;

			case ( 'auto-draft' === $old_status ):
				$action  = 'create';
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s created',
					'gd-system-plugin'
				);

				break;

			case ( 'trash' === $old_status ):
				$action  = 'restore';
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s restored from trash',
					'gd-system-plugin'
				);

				break;

			case ( 'draft' === $new_status && 'publish' === $old_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s unpublished',
					'gd-system-plugin'
				);

				break;

			case ( 'draft' === $new_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s drafted',
					'gd-system-plugin'
				);

				break;

			case ( 'pending' === $new_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s pending review',
					'gd-system-plugin'
				);

				break;

			case ( 'future' === $new_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name, 3: Scheduled post date */ __(
					'"%1$s" %2$s scheduled for %3$s',
					'gd-system-plugin'
				);

				break;

			case ( 'publish' === $new_status && 'future' === $old_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" scheduled %2$s published',
					'gd-system-plugin'
				);

				break;

			case ( 'publish' === $new_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s published',
					'gd-system-plugin'
				);

				break;

			case ( 'private' === $new_status ):
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s privately published',
					'gd-system-plugin'
				);

				break;

			case ( 'trash' === $new_status ):
				$action  = 'trash';
				$summary = /* translators: 1: Post title, 2: Post type singular name */ __(
					'"%1$s" %2$s trashed',
					'gd-system-plugin'
				);

				break;

		}

		if (
			in_array( $new_status, [ 'publish', 'future' ], true )
			||
			( in_array( $old_status, [ 'publish', 'future' ], true ) && 'trash' === $new_status )
		) {

			$this->log_metric( 'publish' );

		}

		$this->log( $action, $summary, $this->get_log_meta( $post, $old_status ) );

	}

}
