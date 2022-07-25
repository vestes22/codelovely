<?php
/*
Plugin Name: Visitor Traffic Real Time Statistics pro
Description: Hits counter that shows analytical numbers of your WordPress site visitors and hits.
Author: wp-buy
Author URI: https://www.wp-buy.com/
Version: 9.5

*/


require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://www.wp-buy.com/wp-update-server-php7/?action=get_metadata&slug=visitors-traffic-real-time-statistics-pro',
	__FILE__, //Full path to the main plugin file or functions.php.
	'visitors-traffic-real-time-statistics-pro'
);



define('AHCPRO_PLUGIN_MAIN_FILE', __FILE__);
define('AHCPRO_PLUGIN_ROOT_DIR', dirname(__FILE__));

require_once(AHCPRO_PLUGIN_ROOT_DIR. "/functions.php");
require_once(AHCPRO_PLUGIN_ROOT_DIR."/init.php");

if( !function_exists('get_plugin_data') or !function_exists('wp_get_current_user')){
	include_once(ABSPATH . 'wp-includes/pluggable.php');
}

//if ( function_exists('get_plugin_data') ) {
//	$woodhl_detail = get_plugin_data( AHCPRO_PLUGIN_ROOT_DIR );
//	$installed_version = get_option( 'visitors-traffic-real-time-statistics-pro-version' );
//	if( $installed_version != $woodhl_detail['Version'] ){
//
//		update_option( 'visitors-traffic-real-time-statistics-pro-version', $woodhl_detail['Version'] );
//	}
//}
add_action( 'plugins_loaded', 'ahcpro_init' );
add_action( 'plugins_loaded', 'ahcpro_multisite_init',99 );
if(ahcpro_GetWPTimezoneString() !='')
{
	date_default_timezone_set('UTC');
	//date_default_timezone_set(ahcpro_GetWPTimezoneString());
}





function ahcpro_action_links( $links ) {
	$links = array_merge( array(
		'<a href="' . esc_url( admin_url( '/admin.php?page=ahc_hits_counter_menu_pro' ) ) . '">' . __( 'Dashboard', 'textdomain' ) . '</a>',
		'<a href="' . esc_url( admin_url( '/admin.php?page=ahc_hits_counter_settings' ) ) . '">' . __( 'Settings', 'textdomain' ) . '</a>'
	), $links );
	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ahcpro_action_links' );



if(get_option('ahcpro_hide_top_bar_icon') != '1')
{
add_action('admin_bar_menu', 'vtrts_pro_add_items',  40);
add_action('wp_enqueue_scripts', 'vtrts_pro_top_bar_enqueue_style');
add_action('admin_enqueue_scripts', 'vtrts_pro_top_bar_enqueue_style');
}

require_once( AHCPRO_PLUGIN_ROOT_DIR . '/app.php' );

require_once(AHCPRO_PLUGIN_ROOT_DIR."/api/api.php");
//--------------------------------------------

