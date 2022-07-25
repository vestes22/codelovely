<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Starter_Tips {

	public function __construct() {

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ] );

	}

	/**
	 * Enqueue starter tip scripts
	 */
	public function enqueue_script() {

		if ( ! is_user_logged_in() || wp_is_mobile() || 1 !== get_current_user_id() || ! Plugin::use_nextgen() ) {

			return;

		}

		$tips = $this->get_starter_tips();

		if ( empty( $tips ) ) {

			return;

		}

		$rtl    = ! is_rtl() ? '' : '-rtl';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wp-nux' );
		wp_enqueue_style( 'wpaas-starter-tips', Plugin::assets_url( "css/admin-tooltips{$rtl}{$suffix}.css" ), [ 'wp-nux' ], Plugin::version() );

		wp_enqueue_script( 'wp-nux' );
		wp_enqueue_script( 'wp-edit-post' );
		wp_enqueue_script( 'wpaas-starter-tips', Plugin::assets_url( 'js/wpaas-starter-tooltips.min.js' ), [ 'jquery' ], Plugin::version(), true );

		wp_localize_script(
			'wpaas-starter-tips',
			'wpaasStarterTips',
			[
				'tipData' => $tips,
			]
		);

	}

	/**
	 * Retreive the starter tips
	 *
	 * @return array Starter tip data
	 */
	public function get_starter_tips() {

		$starter_tips = [
			[
				'target' => '#wp-admin-bar-site-name',
				'text'   => __( 'Go to your Dashboard to install plugins, manage users and more.', 'gd-system-plugin' ),
			],
			[
				'target' => '#wp-admin-bar-new-content',
				'text'   => __( 'Add a new page or blog post to organize your content or engage visitors.', 'gd-system-plugin' ),
			],
			[
				'target' => '#wp-admin-bar-edit',
				'text'   => __( "Edit the page you're currently on.", 'gd-system-plugin' ),
			],
			[
				'target' => '#wp-admin-bar-customize',
				'text'   => __( "Change your website's design, color palette and more.", 'gd-system-plugin' ),
			],
			[
				'target' => '#wp-admin-bar-wpaas',
				'text'   => __( 'Find help, troubleshooting and how to get back to your Hosting Dashboard.', 'gd-system-plugin' ),
			],
		];

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			$key = array_search( '#wp-admin-bar-new-content', array_column( $starter_tips, 'target' ), true );

			if ( false !== $key ) {

				$starter_tips[ $key ]['text'] = __( 'Add pages, blog posts, and products to engage visitors and keep your shop organized.', 'gd-system-plugin' );

			}

		}

		/**
		 * Filter the available list of starter tips
		 *
		 * @var array
		 */
		return (array) apply_filters( 'wpaas_starter_tips', $starter_tips );

	}

}
