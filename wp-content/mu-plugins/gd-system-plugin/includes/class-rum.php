<?php

namespace WPaaS;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class RUM {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( ! self::is_enabled() ) {

			return;

		}

		add_action( 'wp_footer',    [ $this, 'print_inline_script' ], PHP_INT_MAX );
		add_action( 'admin_footer', [ $this, 'print_inline_script' ], PHP_INT_MAX );

	}

	/**
	 * Add the RUM code to the footer of all pages.
	 *
	 * @action wp_footer
	 * @action admin_footer
	 */
	public function print_inline_script() {

		global $wp_version, $post;

		$env  = Plugin::get_env();
		$host = in_array( $env, [ 'dev', 'test' ], true ) ? "{$env}-secureserver.net" : 'secureserver.net';

		?>
		<script>'undefined'=== typeof _trfq || (window._trfq = []);'undefined'=== typeof _trfd && (window._trfd=[]),_trfd.push({'tccl.baseHost':'<?php echo esc_js( $host ); ?>'}),_trfd.push({'ap':'wpaas'},{'server':'<?php echo esc_js( gethostname() ); ?>'},{'pod':'<?php echo esc_js( getenv('WPAAS_POD') ?: 'null' ); ?>'},{'storage':'<?php echo esc_js( getenv('WPAAS_STORAGE') ?: 'null' ); ?>'},{'xid':'<?php echo absint( Plugin::xid() ); ?>'},{'wp':'<?php echo esc_js( $wp_version ); ?>'},{'php':'<?php echo esc_js( PHP_VERSION ); ?>'},{'loggedin':'<?php echo is_user_logged_in() ? 1 : 0; ?>'},{'cdn':'<?php echo CDN::is_enabled() ? 1 : 0; ?>'},{'builder':'<?php echo esc_js( Plugin::get_page_builder( $post ) ); ?>'},{'theme':'<?php echo esc_js( sanitize_title( get_template() ) ); ?>'},{'nextgen':'<?php echo is_admin() && Plugin::use_nextgen() ? 1 : 0; ?>'},{'wds':'<?php echo defined( 'GD_cORe_VERSION' ) ? 1 : 0; ?>'},{'wp_alloptions_count':'<?php echo count( wp_load_alloptions() ); ?>'},{'wp_alloptions_bytes':'<?php echo strlen( serialize( wp_load_alloptions() ) ); ?>'})</script>
		<script>window.addEventListener('click', function (elem) { var _elem$target, _elem$target$dataset, _window, _window$_trfq; return (elem === null || elem === void 0 ? void 0 : (_elem$target = elem.target) === null || _elem$target === void 0 ? void 0 : (_elem$target$dataset = _elem$target.dataset) === null || _elem$target$dataset === void 0 ? void 0 : _elem$target$dataset.eid) && ((_window = window) === null || _window === void 0 ? void 0 : (_window$_trfq = _window._trfq) === null || _window$_trfq === void 0 ? void 0 : _window$_trfq.push(["cmdLogEvent", "click", elem.target.dataset.eid]));});</script>
		<script src='https://img1.wsimg.com/tcc/tcc_l.combined.1.0.6.min.js'></script>
		<script src='https://img1.wsimg.com/traffic-assets/js/tccl-tti.min.js' onload="window.tti.calculateTTI()"></script>
		<?php

	}

	/**
	 * Return whether RUM should be enabled on the current page load.
	 *
	 * @return bool
	 */
	public static function is_enabled() {

		$rum_enabled = (bool) apply_filters( 'wpaas_rum_enabled', defined( 'GD_RUM_ENABLED' ) ? GD_RUM_ENABLED : false );
		$temp_domain = defined( 'GD_TEMP_DOMAIN' ) ? GD_TEMP_DOMAIN : null;
		$is_nocache  = (bool) filter_input( INPUT_GET, 'nocache' );
		$is_gddebug  = (bool) filter_input( INPUT_GET, 'gddebug' );
		$is_amp      = ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() );

		return ( $rum_enabled && $temp_domain && ! $is_nocache && ! $is_gddebug && ! $is_amp && ! WP_DEBUG );

	}

}
