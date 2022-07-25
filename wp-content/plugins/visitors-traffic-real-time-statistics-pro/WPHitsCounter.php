<?php

class WPHitsCounterPro {

    var $pageId;
    var $pageTitle;
    var $postType;
    var $ipAddress;
    var $ipIsUnknown;
    var $userAgent;
    var $referer;
    var $refererSite;
    var $browser;
    var $searchEngine;
    var $countryInternetCode;
    var $countryId;
    var $keyWords;
    var $requestUri;
    
    /**

     * Constructor

     *

     * @param integer $page_id

     * @param string $page_title Optional

     * @param string $post_type Optional

     */
    public function __construct($page_id, $page_title = NULL, $post_type = NULL) {

	global $_SERVER;

	$this->ipAddress = ahcpro_get_client_ip_address();


	if ($this->ipAddress == 'UNKNOWN') {

	    $this->ipIsUnknown = true;

	    $this->ipAddress = 'UNKNOWN' . uniqid();
	} else {

	    $this->ipIsUnknown = false;
	}



	$this->userAgent = $_SERVER['HTTP_USER_AGENT'];

	$this->pageId = (isset($page_id)) ? $page_id : $_GET['page_id'];

	$this->pageTitle = $page_title;

	$this->postType = $post_type;
	
	$this->requestUri = trim($_SERVER['REQUEST_URI'], '/');
	
	//$post_permalink = get_the_permalink($this->pageId);
	//$protocol_arr = array('http://','https://','www.');	
	//$link = str_replace($protocol_arr,'',$post_permalink);
	//$this->requestUri = trim(str_replace($_SERVER['HTTP_HOST'],'',$link),'/');
		
	if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {

	    $hostName = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);

	    if ($hostName != $_SERVER['SERVER_NAME']) {

		$this->referer = $_SERVER['HTTP_REFERER'];

		$this->refererSite = $hostName;
	    }
	}

	$this->searchEngine = NULL;

	$this->keyWords = NULL;

	$this->countryId = NULL;
    }

//--------------------------------------------
//--------------------------------------------

    /**

     * Trace visitor hit

     *

     * @return void

     */
    public function traceVisitorHit() {
		//print_r($this->updateRecentVisitors($this->ipAddress, $this->referer, $this->searchEngine, $this->browser, $this->countryId));
		//echo 'ddddddddddddddddddddddd';
		//print_r($this->isHitRecorded());exit();
	//$this->cleanUnwantedRecords();
		//print_r($this->browser);
	$this->cleanHitsTable();

	if (!$this->isHitRecorded()) {

	    $visitorRecorded = $this->isVisitorRecorded();

	    $this->getBrowser();

	    $this->getCountryId();
		//print_r($this->browser);
	    usleep(10000);

	    if (!empty($this->refererSite)) {

		$this->getSearchEngine();
	    }


		/*
	    if (!$this->isTodayPreparedInDb()) {

		$this->PrepareForTodayInDb();
	    }*/



	    if (!$visitorRecorded) {

		$this->updateVisitsTime(1, 1);

		$this->updateVisitors(1, 1);
	    } else {

		$this->updateVisitsTime(0, 1);

		$this->updateVisitors(0, 1);
	    }


	    if (!empty($this->pageId) && !empty($this->pageTitle) && ($this->postType == 'post' or $this->postType == 'product' or $this->postType == 'page')) {



		$this->updateTitleTraffic($this->pageId, $this->pageTitle);
	    }



	    if (!empty($this->keyWords) && !empty($this->searchEngine)) {

		$this->updateKeywords($this->ipAddress, $this->keyWords, $this->referer, $this->searchEngine, $this->browser, $this->countryId);
	    }



	    if (!empty($this->refererSite)) {

		$this->updateReferingSites($this->refererSite);
	    }



	    if (!empty($this->searchEngine)) {

		$this->updateSearchingVisits($this->searchEngine);
	    }



	    if (!empty($this->countryId)) {

		if ($visitorRecorded) {

		    $this->updateCountries($this->countryId, 0, 1);
		} else {

		    $this->updateCountries($this->countryId, 1, 1);
		}
	    }

	    $this->updateBrowsers($this->browser);



	    if (!$visitorRecorded) {

		$this->updateRecentVisitors($this->ipAddress, $this->referer, $this->searchEngine, $this->browser, $this->countryId);
	    }



	    $this->recordThisHits();
	
			
		}
    }

//--------------------------------------------

    /**

     * Is visit is already recorded

     *

     * @return boolean

     */
    protected function isHitRecorded() {

	global $wpdb;
        $custom_timezone_offset = ahcpro_get_current_timezone_offset();

		$wpdb->insert(
			'ahc_online_users',
			array(
				'date'			=> ahcpro_localtime('Y-m-d H:i:s'),
				'hit_ip_address' => $this->ipAddress,
				'hit_page_id'	=>	$this->pageId,
				'site_id'	=>	get_current_blog_id()
			)
	);

	//$sql = "SELECT COUNT(`hit_id`) AS ct  FROM `ahc_hits` WHERE DATE(`hit_date`) = '". ahcpro_localtime("Y-m-d") ."' AND `hit_ip_address` = %s AND `hit_page_id` = %s";

        $sql = "SELECT COUNT(`hit_id`) AS ct FROM `ahc_hits` WHERE DATE(CONVERT_TZ(CONCAT_WS(' ',hit_date,hit_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "')) = '".ahcpro_localtime("Y-m-d")."' AND `hit_ip_address` = %s AND `hit_page_id` = %s  AND `site_id` = %d ";

	$result = $wpdb->get_results($wpdb->prepare($sql, $this->ipAddress, $this->pageId,get_current_blog_id()), OBJECT);

	if ($result !== false) {

	    return ((int) $result[0]->ct > 0);
	}
    }

//--------------------------------------------

    /**

     * Is visitor is already recorded

     *

     * @return boolean

     */
    protected function isVisitorRecorded() {

	global $wpdb;

        $custom_timezone_offset = ahcpro_get_current_timezone_offset();

	/*$sql = "SELECT COUNT(`hit_id`) AS ct  FROM `ahc_hits` WHERE DATE(`hit_date`) = '". gmdate("Y-m-d") ."' AND `hit_ip_address` = %s";*/
	
        $sql = "SELECT COUNT(`hit_id`) AS ct  FROM `ahc_hits` WHERE site_id = ".get_current_blog_id()." and DATE(CONVERT_TZ(CONCAT_WS(' ',hit_date,hit_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "')) = '".ahcpro_localtime("Y-m-d")."' AND `hit_ip_address` = %s";

	$result = $wpdb->get_results($wpdb->prepare($sql, $this->ipAddress), OBJECT);
	if ($result !== false) {

	    return ((int) $result[0]->ct > 0);
	}
    }

//--------------------------------------------

    /**

     * Detect client browser

     *

     * @return void

     */
    public function get_browser_id($browser){
		global $wpdb;
    	$sql = "SELECT `bsr_id` FROM `ahc_browsers` WHERE `bsr_name` = %s and site_id = %d ";

	    $results = $wpdb->get_results($wpdb->prepare($sql, $browser,get_current_blog_id()), OBJECT);

	    if ($results !== false && !empty($results)) {
			return $results[0]->bsr_id;
		}
		return 0;
	}
    protected function getBrowser() {

	if (strpos($this->userAgent, 'MSIE') !== false) {

	    $this->browser = $this->get_browser_id('IE');
	} elseif (strpos($this->userAgent, 'Trident') !== false) {

	    $this->browser = $this->get_browser_id('IE');
	} elseif (strpos($this->userAgent, 'Gecko') !== false) {

	    if (strpos($this->userAgent, 'Firefox') !== false) {

		$this->browser = $this->get_browser_id('Firefox');
	    } elseif (strpos($this->userAgent, 'Netscape') !== false) {

		$this->browser = $this->get_browser_id('Netscape');
	    } elseif (strpos($this->userAgent, 'Chrome') !== false) {

		$this->browser = $this->get_browser_id('Chrome');
	    } else {

		$this->browser = $this->get_browser_id('Gecko/Mozilla');
	    }
	} elseif (strpos($this->userAgent, 'Opera Mini') !== false) {

	    $this->browser = $this->get_browser_id('Opera Mini');
	} elseif (strpos($this->userAgent, 'Opera') !== false) {

	    $this->browser = $this->get_browser_id('Opera');
	} elseif (strpos($this->userAgent, 'Safari') !== false) {

	    $this->browser = $this->get_browser_id('Safari');
	} elseif (strpos($this->userAgent, 'iPad') !== false) {

	    $this->browser = $this->get_browser_id('iPad');
	} elseif (strpos($this->userAgent, 'Android') !== false) {

	    $this->browser = $this->get_browser_id('Android');
	} elseif (strpos($this->userAgent, 'AIR') !== false) {

	    $this->browser = $this->get_browser_id('AIR');
	} elseif (strpos($this->userAgent, 'Fluid') !== false) {

	    $this->browser = $this->get_browser_id('Fluid');
	} elseif (strpos($this->userAgent, 'Maxthon') !== false) {

	    $this->browser = $this->get_browser_id('Maxthon');
	} else {

	    $this->browser = $this->get_browser_id('unknown');
	}
    }

//--------------------------------------------

    /**

     * Detect country internet code

     *

     * @return void

     */
   /* protected function getCountryInternetCode() {

	if (!$this->ipIsUnknown) {

	    $gi = geoip_open(AHCPRO_PLUGIN_ROOT_DIR . AHC_DS . "geoip" . AHC_DS . "data" . AHC_DS . "GeoIP.dat", GEOIP_STANDARD);

	    $this->countryInternetCode = geoip_country_code_by_addr($gi, $this->ipAddress);

	    geoip_close($gi);
	}

	if (empty($this->countryInternetCode)) {

	    $this->countryInternetCode = 'XX';
	}
    }*/
	
	protected function getCountryInternetCode() {
	//https://nominatim.openstreetmap.org/reverse?format=json&lat=31.354675&lon=34.308826&zoom=18&addressdetails=1
	if (!$this->ipIsUnknown) {
		
	  $ip_data = advanced_get_link("https://geoip-db.com/json/".$this->ipAddress);
	 


	  if(trim($ip_data->country_code) != '' && strlen($ip_data->country_code) == 2)
	  {
		$this->countryInternetCode  =  $ip_data->country_code;
		
	  }else{
	  

	    $gi = geoip_open(AHCPRO_PLUGIN_ROOT_DIR . AHC_DS . "geoip" . AHC_DS . "data" . AHC_DS . "GeoIP.dat", GEOIP_STANDARD);

	    $this->countryInternetCode = geoip_country_code_by_addr($gi, $this->ipAddress);

	    geoip_close($gi);
	  }
	}

	if (empty($this->countryInternetCode)) {

	    $this->countryInternetCode = 'XX';
	}
    }

//--------------------------------------------

    /**

     * Detect country ID

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     *

     * @return void

     */
    protected function getCountryId() {

	global $wpdb;

	$this->getCountryInternetCode();

	if (!empty($this->countryInternetCode)) {

	    $sql = "SELECT `ctr_id` FROM `ahc_countries` WHERE `ctr_internet_code` = %s and site_id = %d ";

	    $results = $wpdb->get_results($wpdb->prepare($sql, $this->countryInternetCode,get_current_blog_id()), OBJECT);

	    if ($results !== false && !empty($results)) {

		$this->countryId = $results[0]->ctr_id;

		return;
	    }
	}
    }

//--------------------------------------------

    /**

     * Detect search engine

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     *

     * @return void

     */
    protected function getSearchEngine() {

	global $wpdb;

	$sql = "SELECT `srh_id`, `srh_query_parameter`, `srh_identifier` FROM `ahc_search_engines`";

	$results = $wpdb->get_results($sql, OBJECT);

	if ($results !== false) {

	    foreach ($results as $s) {

		if (strpos($this->referer, $s->srh_identifier . '.') !== false) {

		    $this->searchEngine = $s->srh_id;

		    $this->getKeyWords($s->srh_query_parameter);
		}
	    }
	}
    }

//--------------------------------------------

    /**

     * Detect search engine

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     *

     * @return void

     */
    protected function getKeyWords($query_param) {

	$query = parse_url($this->referer, PHP_URL_QUERY);

	$query = rawurldecode($query);

	$arr = array();

	parse_str($query, $arr);

	if (isset($arr[$query_param])) {

	    $this->keyWords = $arr[$query_param];
	}
    }

//--------------------------------------------

    /**

     * Is there a record prepared for today's visits

     *

     * @uses wpdb::get_results()

     *

     * @return boolean

     */
    protected function isTodayPreparedInDb() {

	global $wpdb;

	$del_sql = "DELETE v1 FROM ahc_visitors v1, ahc_visitors v2 WHERE v1.`vst_id` > v2.`vst_id` AND v1.`vst_date` = v2.`vst_date`";
	
        //$del_sql = "DELETE v1 FROM ahc_visitors v1, ahc_visitors v2 WHERE v1.`vst_id` > v2.`vst_id` AND CONVERT_TZ(v1.vst_date, '" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "') = CONVERT_TZ(v2.vst_date, '" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "') ";
        
	$del_result = $wpdb->get_results($del_sql, OBJECT);


	$sql = "SELECT COUNT(`vst_id`) AS ct  FROM `ahc_visitors` WHERE site_id = ".get_current_blog_id()." DATE(`vst_date`) = '".gmdate("Y-m-d")."'";
	
        //$sql = "SELECT COUNT(`vst_id`) AS ct  FROM `ahc_visitors` WHERE DATE(CONVERT_TZ(vst_date,'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "')) = DATE(CONVERT_TZ(NOW( ),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'))";

	$result = $wpdb->get_results($sql, OBJECT);

	if ($result !== false) {

	    return ((int) $result[0]->ct > 0);
	}
    }

//--------------------------------------------

    /**

     * Prepared a record for today's visits

     *

     * @uses wpdb::query()

     *

     * @return boolean

     */
    protected function PrepareForTodayInDb() {

	global $wpdb;

	$sql = "INSERT INTO `ahc_visitors` (`vst_date`, `vst_visitors`, `vst_visits`, `site_id`) VALUES ('".gmdate("Y-m-d H:i:s")."', 0, 0,'".get_current_blog_id()."')";

	if ($wpdb->query($sql) !== false) {

	    return true;
	}

	return false;
    }

//--------------------------------------------

    /**

     * Clean daily hits table

     *

     * @uses wpdb::query()

     *

     * @return boolean

     */
    protected function cleanHitsTable() {

	global $wpdb;

	$sql = "DELETE FROM ahc_online_users WHERE DATE(`date`) <> '".gmdate("Y-m-d")."'";
	$wpdb->query($sql);
	
	$sql = "DELETE FROM `ahc_hits` WHERE DATE(`hit_date`) <> '".gmdate("Y-m-d")."'";
		

	if ($wpdb->query($sql) !== false) {

	    return true;
	} else {

	    return false;
	}
    }

//--------------------------------------------

    /**

     * Update browser visits

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param integer $bsr_id

     * @return boolean

     */
    protected function updateBrowsers($bsr_id) {

	global $wpdb;

	$sql = "UPDATE `ahc_browsers` SET bsr_visits = bsr_visits + 1 WHERE bsr_id = %d and site_id=%d";
	//print_r($sql);
	if ($wpdb->query($wpdb->prepare($sql, $bsr_id,get_current_blog_id())) !== false) {

	    return true;
	}

	return false;
    }

//--------------------------------------------

    /**

     * Update country visits

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param integer $ctr_id

     * @param integer $visitors Optional

     * @param integer $visits Optional

     * @return boolean

     */
    protected function updateCountries($ctr_id, $visitors = 0, $visits = 0) {

	global $wpdb;

	$sql = "UPDATE `ahc_countries` SET ctr_visitors = ctr_visitors + %d, ctr_visits = ctr_visits + %d WHERE ctr_id = %d and site_id = %d";

	return ($wpdb->query($wpdb->prepare($sql, $visitors, $visits, $ctr_id,get_current_blog_id())) !== false);
    }

//--------------------------------------------

    /**

     * Update visits sum order by search engine

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     * @uses wpdb::query()

     *

     * @param integer $srh_id

     * @return boolean

     */
    protected function updateSearchingVisits($srh_id) {

	global $wpdb;

    $custom_timezone_offset = ahcpro_get_current_timezone_offset();

	$sql = "SELECT vtsh_id FROM `ahc_searching_visits` WHERE site_id = %d AND srh_id = %d AND DATE(vtsh_date) = '".gmdate("Y-m-d")."'";
	//$sql = "SELECT vtsh_id FROM `ahc_searching_visits` WHERE srh_id = %d AND DATE(CONVERT_TZ(vtsh_date,'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "')) = DATE(CONVERT_TZ(NOW( ),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'))";

	$result = $wpdb->get_results($wpdb->prepare($sql,get_current_blog_id(), $srh_id), OBJECT);

	if ($result !== false) {

	    if ($wpdb->num_rows > 0) {

		$sql2 = "UPDATE `ahc_searching_visits` SET vtsh_visits = vtsh_visits + 1 WHERE vtsh_id = %d and site_id=%d";

		return ($wpdb->query($wpdb->prepare($sql2, $result[0]->vtsh_id,get_current_blog_id())) !== false);
	    } else {

		$sql2 = "INSERT INTO `ahc_searching_visits` (srh_id, vtsh_date, vtsh_visits,site_id) 

						VALUES (%d, %s, 1,%d)";

		return ($wpdb->query($wpdb->prepare($sql2, $srh_id, gmdate("Y-m-d H:i:s"),get_current_blog_id())) !== false);
	    }
	} else {

	    return false;
	}
    }

//--------------------------------------------

    /**

     * Update visitors count

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param integer $visitors Optional

     * @param integer $visits Optional

     * @return boolean

     */
    protected function updateVisitors($visitors = 0, $visits = 0) {

	global $wpdb;

	
	$sql = "INSERT INTO `ahc_daily_visitors_stats` (vst_date, vst_visitors, vst_visits,site_id) values(%s, %d, %d, %d )";

	$wpdb->query($wpdb->prepare($sql, gmdate("Y-m-d H:i:s"), $visitors, $visits,get_current_blog_id()));
	
	$sql = "INSERT INTO `ahc_visitors` (vst_date, vst_visitors, vst_visits,site_id) values(%s, %d, %d , %d )";
	//$sql = "UPDATE `ahc_visitors` SET vst_visitors = vst_visitors + %d, vst_visits = vst_visits + %d WHERE DATE(vst_date) = DATE(NOW())";

	return ($wpdb->query($wpdb->prepare($sql, gmdate("Y-m-d H:i:s"), $visitors, $visits,get_current_blog_id())) !== false);
    }

//--------------------------------------------

    /**

     * Update referring sites visits table

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     * @uses wpdb::get_results()

     *

     * @param string $rfr_site_name. referring site name

     * @return boolean

     */
    protected function updateReferingSites($rfr_site_name) {

	global $wpdb;

	$sql = "SELECT rfr_id FROM `ahc_refering_sites` where rfr_site_name = %s and site_id=%d";

	$result = $wpdb->get_results($wpdb->prepare($sql, $rfr_site_name,get_current_blog_id()), OBJECT);

	if ($result !== false) {

	    if (!empty($result)) {

		$sql2 = "UPDATE `ahc_refering_sites` SET rfr_visits = rfr_visits + 1 WHERE rfr_id = %d and site_id=%d";

		return ($wpdb->query($wpdb->prepare($sql2, $result[0]->rfr_id,get_current_blog_id())) !== false);
	    } else {

		$sql2 = "INSERT INTO `ahc_refering_sites` (rfr_site_name, rfr_visits,site_id) 

						VALUES(%s, 1,%d)";

		return ($wpdb->query($wpdb->prepare($sql2, $rfr_site_name,get_current_blog_id())) !== false);
	    }
	} else {

	    return false;
	}
    }

//--------------------------------------------

    /**

     * Update recent visitors table

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param string $vtr_ip_address. IP address

     * @param string $vtr_referer Optional. Referring site name

     * @param integer $srh_id Optional. Search engine ID

     * @param integer $bsr_id Optional. Browser ID

     * @param integer $ctr_id Optional. Country ID

     * @return boolean

     */
       protected function updateRecentVisitors($vtr_ip_address, $vtr_referer = '', $srh_id = NULL, $bsr_id = NULL, $ctr_id = NULL) {

	global $wpdb;


	  $ahc_city = '';
	  $ahc_region = '';

	 
		   
	  $ip_data = (advanced_get_link("http://ip-api.com/json/".$vtr_ip_address));
	     

	  $ahc_city =  isset($ip_data->city) ? $ip_data->city : '';
	  $ahc_region =  isset($ip_data->regionName) ? $ip_data->regionName : '';
		   
	 if($ahc_city == '' || $ahc_city == 'null' || $ahc_city == null || empty($ahc_city))
		   {
			   $ip_data = advanced_get_link("https://geoip-db.com/json/".$vtr_ip_address);
			   
			   $ahc_city =  isset($ip_data->city) ? $ip_data->city : '';
			   $ahc_region =  isset($ip_data->state) ? $ip_data->state : '';
	 		  
			  if($ahc_city == '' || $ahc_city == 'null' || $ahc_city == null || empty($ahc_city))
			  {	

					$ip_data = (advanced_get_link("http://www.geoplugin.net/json.gp?ip=".$vtr_ip_address));

					$ahc_city =  isset($ip_data->geoplugin_city) ? $ip_data->geoplugin_city : '';
					$ahc_region =  isset($ip_data->geoplugin_region) ? $ip_data->geoplugin_region : '';


			   }
			 
		   }

	$sql = "INSERT INTO `ahc_recent_visitors` (vtr_ip_address, vtr_referer, srh_id, bsr_id, ctr_id, ahc_city, ahc_region, vtr_date, vtr_time,site_id) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %d)";



	return ($wpdb->query($wpdb->prepare($sql, $vtr_ip_address, $vtr_referer, $srh_id, $bsr_id, $ctr_id, $ahc_city, $ahc_region, gmdate("Y-m-d"), gmdate("H:i:s"),get_current_blog_id() )) !== false);
  }

//--------------------------------------------

    /**

     * Update key words table

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param string $vtr_ip_address. IP address

     * @param string $kwd_keywords. Key word

     * @param string $kwd_referer. Referring site name.

     * @param integer $srh_id. Search engine ID

     * @param integer $bsr_id. Browser ID

     * @param integer $ctr_id Optional. Country ID

     * @return boolean

     */
    protected function updateKeywords($kwd_ip_address, $kwd_keywords, $kwd_referer, $srh_id, $bsr_id, $ctr_id = NULL) {

	global $wpdb;

	$sql = "INSERT INTO `ahc_keywords` (kwd_ip_address, kwd_keywords, kwd_referer, srh_id, ctr_id, bsr_id, kwd_date, kwd_time,site_id) 

				VALUES (%s, %s, %s, %d, %d, %d, %s, %s, %d)";

	return ($wpdb->query($wpdb->prepare($sql, $kwd_ip_address, $kwd_keywords, $kwd_referer, $srh_id, $ctr_id, $bsr_id, gmdate("Y-m-d"), gmdate("H:i:s"),get_current_blog_id())) !== false);
    }

//--------------------------------------------

    /**

     * Clean unwanted records. Only keeping a limit of fresh records. Limit is set by AHCPRO_RECENT_VISITORS_LIMIT

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     * @uses wpdb::query()

     *

     * @return boolean

     */
    protected function cleanUnwantedRecords() {

	global $wpdb;

	$sql11 = "SELECT vtr_id FROM `ahc_recent_visitors` where site_id = %d ORDER BY vtr_id LIMIT %d";

	$result = $wpdb->get_results($wpdb->prepare($sql11, get_current_blog_id(),AHCPRO_RECENT_VISITORS_LIMIT), OBJECT);

	if ($result !== false) {

	    $ids1 = array();

	    $length = count($result);

	    foreach ($result as $r) {

		$ids1[] = $r->vtr_id;
	    }

	    $ids1 = implode(',', $ids1);

	    $sql12 = "DELETE FROM `ahc_recent_visitors`" . ((!empty($ids1)) ? " WHERE site_id = ".get_current_blog_id()." and vtr_id NOT IN (" . $ids1 . ")" : "");



	    $sql21 = "SELECT kwd_id FROM `ahc_keywords` where site_id=%d ORDER BY kwd_id LIMIT %d";

	    $result2 = $wpdb->get_results($wpdb->prepare($sql21, get_current_blog_id(),AHCPRO_RECENT_KEYWORDS_LIMIT), OBJECT);

	    if ($result2 !== false) {

		$ids2 = array();

		foreach ($result2 as $r) {

		    $ids2[] = $r->kwd_id;
		}

		$ids2 = implode(',', $ids2);

		$sql22 = "DELETE FROM `ahc_keywords`" . ((!empty($ids2)) ? " WHERE kwd_id NOT IN (" . $ids2 . ")" : "");



		if ($wpdb->query($sql12) !== false) {

		    return ($wpdb->query($sql22) !== false);
		}
	    }
	}

	return false;
    }

//--------------------------------------------

    /**

     * Update traffic by title table

     *

     * @uses wpdb::prepare()

     * @uses wpdb::get_results()

     * @uses wpdb::query()

     *

     * @param integer $til_page_id

     * @param string $til_page_title

     * @return boolean

     */
    protected function updateTitleTraffic($til_page_id, $til_page_title) {

	global $wpdb;

	$sql = "SELECT til_id FROM `ahc_title_traffic` where til_page_id = %s and site_id=%d";

	$result = $wpdb->get_results($wpdb->prepare($sql, $til_page_id,get_current_blog_id()), OBJECT);
	if ($result !== false) {

	    if (!empty($result)) {

		$sql2 = "UPDATE `ahc_title_traffic` 

						SET til_hits = til_hits + 1, til_page_title = %s 

						WHERE til_id = %d and site_id = %d";

		return ($wpdb->query($wpdb->prepare($sql2, $til_page_title, $result[0]->til_id,get_current_blog_id())) !== false);
	    } else {

		$sql2 = "INSERT INTO `ahc_title_traffic` (til_page_id, til_page_title, til_hits,site_id)  

						VALUES(%s, %s, 1, %d)";

		return ($wpdb->query($wpdb->prepare($sql2, $til_page_id, $til_page_title,get_current_blog_id())) !== false);
	    }
	} else {

	    return false;
	}
    }

//--------------------------------------------

    /**

     * Update visitor's & visits' times table

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @param integer $visitors Optional

     * @param integer $visits Optional

     * @return boolean

     */
    protected function updateVisitsTime($visitors = 0, $visits = 0) {

	global $wpdb;
	$time = gmdate('H:i:s');
	$sql = "UPDATE `ahc_visits_time` SET vtm_visitors = vtm_visitors + %d, vtm_visits = vtm_visits + %d 

				WHERE  TIME(vtm_time_from) <= '$time' AND TIME(vtm_time_to) >= '$time' and site_id=".get_current_blog_id();
	$query = $wpdb->prepare($sql, $visitors, $visits);
	$result = ($wpdb->query($query) !== false);
		//print_r($query);
	$sql = "UPDATE `ahc_visits_time` SET vtm_visitors = 1

				WHERE vtm_visitors = 0 AND TIME(vtm_time_from) <= '$time' AND TIME(vtm_time_to) >= '$time' and site_id=".get_current_blog_id();
	$query = $wpdb->query($sql);
	
	$sql = "UPDATE `ahc_visits_time` SET vtm_visits = 1

				WHERE vtm_visits = 0 AND TIME(vtm_time_from) <= '$time' AND TIME(vtm_time_to) >= '$time' and site_id = ".get_current_blog_id();
	$query = $wpdb->query($sql);
	
	return $result;
    }

//--------------------------------------------

    /**

     * Record (insert) the visit

     *

     * @uses wpdb::prepare()

     * @uses wpdb::query()

     *

     * @return boolean

     */
    protected function recordThisHits() {

	global $wpdb;

	$sql = "INSERT INTO `ahc_hits` 

				(`hit_ip_address`, `hit_user_agent`, `hit_request_uri`, `hit_page_id`, `hit_page_title`, `ctr_id`, `hit_referer`, `hit_referer_site`, 

				`srh_id`, `hit_search_words`, `bsr_id`, `hit_date`, `hit_time`, `site_id`) 

				VALUES (%s, %s, %s, %s, %s, %d, %s, %s, %d, %s, %d, %s, %s, %d)";

	$result = $wpdb->query($wpdb->prepare($sql, $this->ipAddress, $this->userAgent, $this->requestUri, $this->pageId, $this->pageTitle, $this->countryId, $this->referer, $this->refererSite, $this->searchEngine, $this->keyWords, $this->browser, gmdate("Y-m-d"), gmdate("H:i:s") ,get_current_blog_id()));

	return ($result !== false);
    }

//--------------------------------------------
}

?>
