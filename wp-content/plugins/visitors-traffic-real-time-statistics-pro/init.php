<?php
define('AHC_DS', DIRECTORY_SEPARATOR);
define('AHC_PLUGIN_SUPDIRE_FILE', dirname(__FILE__).'visitors-traffic-real-time-statistics-pro.php');

define('AHCPRO_RECENT_VISITORS_LIMIT', 20);
define('AHCPRO_RECENT_KEYWORDS_LIMIT', 20);
define('AHCPRO_VISITORS_VISITS_SUMMARY_LIMIT', 20); // used in ahc_get_ser_visits_by_date & search engines last days
define('AHCPRO_TOP_REFERING_SITES_LIMIT', 20); // used in ahcpro_get_top_refering_sites
define('AHCPRO_TOP_COUNTRIES_LIMIT', 20); // used in ahcpro_get_top_countries


define('AHCPRO_TRAFFIC_BY_TITLE_LIMIT', 20);


require_once("WPHitsCounter.php");

require_once("geoip".AHC_DS."src".AHC_DS."geoip.inc");

register_activation_hook(AHCPRO_PLUGIN_MAIN_FILE, 'ahcpro_set_default_options');
register_deactivation_hook(AHCPRO_PLUGIN_MAIN_FILE, 'ahcpro_unset_default_options');


class GlobalsPro{

	static $plugin_options = array();
	static $lang = NULL;
	static $post_type = NULL; // post | page | category
	static $page_id = NULL;
	static $page_title = NULL;
}

GlobalsPro::$plugin_options = get_option('ahcpro_wp_hits_counter_options');
GlobalsPro::$lang = 'en';


$ahcpro_get_save_settings = ahcpro_get_save_settings();

if($ahcpro_get_save_settings == false or empty($ahcpro_get_save_settings))
{
	ahcpro_add_settings();
}

if(isset($ahcpro_get_save_settings[0]))
{
$hits_days = ($ahcpro_get_save_settings[0]->set_hits_days >0)?$ahcpro_get_save_settings[0]->set_hits_days:14;
$ajax_check = ((($ahcpro_get_save_settings[0]->set_ajax_check>0)?$ahcpro_get_save_settings[0]->set_ajax_check:15) * 1000);
$set_ips = $ahcpro_get_save_settings[0]->set_ips;
$set_google_map = $ahcpro_get_save_settings[0]->set_google_map;
}else{

$hits_days = 30;
$ajax_check = 15000;
$set_ips = '';
$set_google_map = 'today_visitors';
}

$ajax_check = ($ajax_check>1) ? $ajax_check : 15000;

define('AHCPRO_VISITORS_VISITS_LIMIT', $hits_days );
define('AHCPRO_AJAX_CHECK', $ajax_check);
define('AHC_PRO_EXCLUDE_IPS', $set_ips);
define('AHC_PRO_SET_GOOGLE_MAP', $set_google_map);



$admincore = '';
	if (isset($_GET['page'])) $admincore = $_GET['page'];
	if( is_admin() && $admincore == 'ahc_hits_counter_menu_pro') 
	{
	add_action('admin_enqueue_scripts', 'ahcpro_include_scripts',99);
	}
	

add_action('admin_menu', 'ahcpro_create_admin_menu_link');
add_shortcode('ahcpro_show_google_map', 'ahcpro_google_map' );
//[ahcpro_show_google_map map_status="online"]

add_action('wp_ajax_ahcpro_get_hits_by_custom_duration','ahcpro_get_hits_by_custom_duration_callback');

define('AHCPRO_SERVER_CURRENT_TIMEZONE','+00:00');
?>
