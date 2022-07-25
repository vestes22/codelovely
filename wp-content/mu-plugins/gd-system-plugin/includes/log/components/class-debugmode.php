<?php

namespace WPaaS\Log\Components;

use WPaaS\Log\Timer;
use WPaaS\Debug_Mode;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class DebugMode extends Component {

	/**
	 * Make sure callbacks are added only if Debug Mode is active.
	 */
	protected function do_callbacks_on_hooks() {

		if ( call_user_func( 'WPaaS\Debug_Mode::is_start' ) ) {

			parent::do_callbacks_on_hooks();

		}

	}

	/**
	 * Fires when debug mode is in session
	 *
	 * @action wpaas_debug_mode_session
	 */
	public function callback_wpaas_debug_mode_session() {

		$debug_mode_cookie = isset( $_COOKIE[ Debug_Mode::COOKIE ] ) ? json_decode( $_COOKIE[ Debug_Mode::COOKIE ], true ) : null;

		if ( ! $debug_mode_cookie ) {

			return;

		}

		Timer::stop();

		$this->log(
			'session',
			'Debug Mode',
			[
				'cookie_data' => $debug_mode_cookie,
			]
		);

	}

}
