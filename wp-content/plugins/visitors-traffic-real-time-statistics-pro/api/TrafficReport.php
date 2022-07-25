<?php

class AHCPRO_api_TrafficReport
{
    public $app_log_active = '';

    public function __construct()
    {

        add_action('rest_api_init', array($this, 'register_api'));
        $this->app_log_active = get_option('AHCPRO_app_log_key');
    }

    public function register_api()
    {
        register_rest_route('traffic/statistics/v1', 'traffic/report', array(
                'methods' => 'POST',
                'callback' => array($this, 'get_chart_end'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'traffic/map', array(
                'methods' => 'GET',
                'callback' => array($this, 'map'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'traffic/summary/statistics', array(
                'methods' => 'GET',
                'callback' => array($this, 'summary_statistics'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'search/engines/statistics', array(
                'methods' => 'GET',
                'callback' => array($this, 'search_engines_statistics'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'recent/visitor/by/ip', array(
                'methods' => 'POST',
                'callback' => array($this, 'recent_visitor_by_ip_callback'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'traffic/by/countries', array(
                'methods' => 'POST',
                'callback' => array($this, 'traffic_by_countries_callback'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'visits/time/graph', array(
                'methods' => 'POST',
                'callback' => array($this, 'visits_time_graph_callback'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'browsers/statistics', array(
                'methods' => 'GET',
                'callback' => array($this, 'browsers_statistics'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'search/engines', array(
                'methods' => 'GET',
                'callback' => array($this, 'search_engines'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'top/referring/countries', array(
                'methods' => 'GET',
                'callback' => array($this, 'Top_Referring_Countries'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'top/referring/sites', array(
                'methods' => 'GET',
                'callback' => array($this, 'Top_Referring_Sites'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'traffic/by/title', array(
                'methods' => 'POST',
                'callback' => array($this, 'Traffic_by_title'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'latest/search/words', array(
                'methods' => 'POST',
                'callback' => array($this, 'Latest_Search_Words'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'traffic/by/country', array(
                'methods' => 'POST',
                'callback' => array($this, 'Traffic_by_Country'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'top/statistics', array(
                'methods' => 'GET',
                'callback' => array($this, 'Top_statistics'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'get/settings', array(
                'methods' => 'GET',
                'callback' => array($this, 'getSettings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'set/setting', array(
                'methods' => 'POST',
                'callback' => array($this, 'setSetting'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route('traffic/statistics/v1', 'get/popup', array(
                'methods' => 'POST',
                'callback' => array($this, 'getModall'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route( 'traffic/statistics/v1', 'connection/information/add', array(
                'methods' => 'POST',
                'callback' => array($this,'store'),
                'permission_callback' => '__return_true',
            )
        );
    }
    public function get_headers( $server ) {
        $headers = array();
        $additional = array( 'CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true );
        foreach ( $server as $key => $value ) {
            if ( strpos( $key, 'HTTP_' ) === 0 ) {
                $headers[ substr( $key, 5 ) ] = $value;
            } elseif ( isset( $additional[ $key ] ) ) {
                $headers[ $key ] = $value;
            }
        }

        return $headers;
    }
    public function deviceId($device){
        $options = get_option('AHCPRO_device_information');

        $removeKey = array_search(sanitize_text_field($device), array_column($options, 'deviceId'));
        if(strlen($removeKey)){
            return true;
        }else{
            return false;
        }
    }
    public function store(WP_REST_Request $request)
    {
        $key = $this->get_headers(wp_unslash($_SERVER));
        if (!isset($key['KEY']) || $this->app_log_active != $key['KEY']) {
            return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'key error']);
        }
        global $wpdb;
        ## Read value

        $deviceId = $request->get_param('deviceId');
        $deviceName = $request->get_param('deviceName');
        $deviceModel = $request->get_param('deviceModel');
        $userId = $request->get_param('userId');

        if(empty($deviceId)){
            return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'Device Id can\'t be empty']);
        }
        if(empty($deviceName)){
            return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'Device Name can\'t be empty']);
        }
        if(empty($userId)){
            return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'user Id can\'t be empty']);
        }
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $app_log_key = '';
        $length = 13;
        for ($i = 0; $i < $length; $i++) {
            $app_log_key .= $characters[rand(0, $charactersLength - 1)];
        }
        $data = array();
        $data['id'] = $app_log_key;
        $data['deviceId'] = $deviceId;
        $data['deviceName'] = $deviceName;
        $data['deviceModel'] = $deviceModel;
        $data['userId'] = $userId;
        $data['date'] = date_i18n('Y-m-d H:i:s');
        $data['status'] = 1;

        $options = get_option('AHCPRO_device_information');
        if(empty($options)){
            $options = array();
        }
        $options[] = $data;
        if(update_option('AHCPRO_device_information',$options)){
            return rest_ensure_response(['success' => true, 'data' => $data,'massage'=>'data saved']);
        }else{
            return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'can\'t be empty']);
        }

    }
    public function getModall(WP_REST_Request $request){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }
        global $wpdb;

        $hit_ip_address = !empty($request->get_param( 'hit_ip_address'))?$request->get_param( 'hit_ip_address'):'';
        $ctr_name_ahc_city = !empty($request->get_param( 'ctr_name_ahc_city'))?$request->get_param( 'ctr_name_ahc_city'):'';
        $hit_date = !empty($request->get_param( 'hit_date'))?$request->get_param( 'hit_date'):'';
        if($hit_ip_address == ''){
            return rest_ensure_response(['success' => false, 'massage' =>__('ip address can not be empty'),'data'=>array()]);
        }
        if($ctr_name_ahc_city == ''){
            return rest_ensure_response(['success' => false, 'massage' =>__('city name can not be empty'),'data'=>array()]);
        }
        if($hit_date == ''){
            return rest_ensure_response(['success' => false, 'massage' =>__('date can not be empty'),'data'=>array()]);
        }

        $sql_query = "SELECT * FROM `ahc_hits` where `hit_ip_address` = '".$hit_ip_address."' and `site_id` = '".get_current_blog_id()."' and `hit_date` = '".$hit_date."' and `hit_page_title` !='' order by `hit_id` asc limit 30 ";

        $results = $wpdb->get_results($sql_query);
        $date = array();
        if ($results !== false) {

            if (is_array($results)) {
                $date['hit_ip_address']=$hit_ip_address;
                $date['ctr_name_ahc_city']=$ctr_name_ahc_city;
                $date['hit_date']=$hit_date;
                $date['data']=array();


                foreach ($results as $hit) {
                    $date['data'][]=array(
                        'request_uri'=>home_url($hit->hit_request_uri),
                        'hit_page_title'=>$hit->hit_page_title,
                        'hit_date'=>$hit->hit_date,
                        'hit_time'=>$hit->hit_time
                    );
                }
                return rest_ensure_response(['success' => true, 'massage' => __('successfully'),'data'=>$date]);
            }else{
                return rest_ensure_response(['success' => false, 'massage' =>__('Empty'),'data'=>array()]);
            }
        }
        return rest_ensure_response(['success' => false, 'massage' =>__('Empty'),'data'=>array()]);
    }

    public function setSetting(WP_REST_Request $request){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }
        global $wpdb;

        $ahcpro_hide_top_bar_icon = !empty($request->get_param( 'ahcpro_hide_top_bar_icon'))?$request->get_param( 'ahcpro_hide_top_bar_icon'):'0';
        $ahcpro_haships = !empty($request->get_param( 'ahcpro_haships'))?$request->get_param( 'ahcpro_haships'):'0';


        $set_hits_days = intval($request->get_param( 'set_hits_days'));
        $set_ajax_check = intval($request->get_param( 'set_ajax_check'));

        $set_ips = esc_html($request->get_param('set_ips'));
        $set_google_map =$request->get_param( 'set_google_map');

        $custom_timezone_offset = $request->get_param( 'set_custom_timezone');
        if ($custom_timezone_offset && $custom_timezone_offset != '') {
            update_option('ahcpro_custom_timezone', $custom_timezone_offset);
        }

        $delete_plugin_data = !empty($request->get_param( 'delete_plugin_data'))?$request->get_param( 'delete_plugin_data'):'0';
        $ahcpro_save_ips = !empty($request->get_param( 'ahcpro_save_ips'))?$request->get_param( 'ahcpro_save_ips'):'0';
        update_option('ahcpro_delete_plugin_data_on_uninstall', $delete_plugin_data);
        update_option('ahcpro_save_ips_opn', $ahcpro_save_ips);




       // $ahcproUserRoles = '';
        $ahcproUserRoles = $request->get_param('ahcproUserRoles');
        /*
         *
         if(!empty($role)){
            $role = json_decode($role);
            foreach ($role as $v)
            {
                $ahcproUserRoles .= $v.",";
            }
        }*/


        update_option('ahcpro_hide_top_bar_icon', $ahcpro_hide_top_bar_icon);
        update_option('ahcpro_haships', $ahcpro_haships);

        $ahcproUserRoles = substr($ahcproUserRoles,0,-1);

        update_option('ahcproUserRoles',$ahcproUserRoles);


        $sql = $wpdb->prepare("UPDATE `ahc_settings` set `set_ips` = %s, `set_google_map` = %s where `site_id` = %d ", $set_ips, $set_google_map,get_current_blog_id());

        if ($wpdb->query($sql) !== false) {

            return rest_ensure_response(['success' => true, 'massage' => __('Settings have been modified successfully'),'data'=>$this->getSettings()]);
        }

        return rest_ensure_response(['success' => false, 'massage' =>__('The data has not been modified, please try again later'),'data'=>$this->getSettings()]);
    }
    public function getSettings(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $ahcpro_get_save_settings = ahcpro_get_save_settings();



        $UserRoles = get_option('ahcproUserRoles');
        $UserRoles_arr = explode(',',$UserRoles);

        global $wp_roles;
        if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();
        $capabilites = array();
        $available_roles_names = $wp_roles->get_names();//we get all roles names
        $available_roles_capable = array();
        foreach ($available_roles_names as $role_key => $role_name) { //we iterate all the names
            $available_roles_capable[] = array('key'=>$role_key,'value'=>$role_name); //we populate the array of capable roles
        }


        $data['data']['Plugin_Accessibility'] = array(
            'title'=>'Plugin Accessibility',
            'disc'=>'',
            'value'=>$UserRoles_arr,
            'data'=>$available_roles_capable
        );


        $data['data']['Map_will_display'] = array(
            'title'=>'Map will display',
            'disc'=>'',
            'value'=>$ahcpro_get_save_settings[0]->set_google_map,
            'data'=>array(
                array('key'=>'all','value'=>'All time visitors'),
                array('key'=>'today_visitors','value'=>'Today visitors per country'),
                array('key'=>'top10','value'=>'Top 10 countries'),
                array('key'=>'online','value'=>'Online Visitors'),
                array('key'=>'this_month','value'=>'This Month Visitors'),
                array('key'=>'past_month','value'=>'Past Month Visitors')
            )
        );


        $wp_timezone_string = get_option('timezone_string');
        $custom_timezone_offset = (get_option('ahcpro_custom_timezone') !='') ?  get_option('ahcpro_custom_timezone') : $wp_timezone_string;

        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

        $data['data']['Select_Timezone'] = array(
            'title'=>'Select Timezone',
            'disc'=>'',
            'value'=>$custom_timezone_offset,
            'data'=>$timezones
        );

        $data['data']['IPs_to_exclude'] = array(
            'title'=>'IP\'s to exclude',
            'disc'=>'Excluded IPs will not be tracked by your counter, enter IP per line',
            'value'=>$ahcpro_get_save_settings[0]->set_ips,
        );


        return rest_ensure_response(['success' => true, 'data' => $data]);
    }
    public function Top_statistics(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }
        $data= array();
        $alltimeSER = ahcpro_get_hits_search_engines_referers('alltime');
        $tot_srch = 0;
        if (is_array($alltimeSER)) {
            foreach ($alltimeSER as $ser=>$v) {
                $tot_srch += $v;
            }
        }
        $data['data'][] = array('title'=>ahc_search_engines,'image'=>'','icon'=>plugins_url('/images/searchengin.png', AHCPRO_PLUGIN_MAIN_FILE),'total'=>ahc_pro_NumFormat($tot_srch));

        $ahc_sum_stat = ahcpro_get_summary_statistics();
        $data['data'][] = array('title'=>'Today Visitors','image'=>'','icon'=>plugins_url('/images/visitors.png', AHCPRO_PLUGIN_MAIN_FILE),'total'=>ahc_pro_NumFormat($ahc_sum_stat['today']['visitors']));
        $data['data'][] = array('title'=>'Today Visits','image'=>'','icon'=>plugins_url('/images/visits.png', AHCPRO_PLUGIN_MAIN_FILE),'total'=>ahc_pro_NumFormat($ahc_sum_stat['today']['visits']));

        global $wpdb;
        $sql = "SELECT DISTINCT hit_ip_address FROM `ahc_online_users` WHERE  `site_id` = '".get_current_blog_id()."' and `date` >=  DATE_ADD('". ahcpro_localtime("Y-m-d H:i:s" ) ."', INTERVAL -2 MINUTE) ";

        $result = $wpdb->get_results($sql, OBJECT);
        $online_users = "0";
        if ($result !== false) {
            $online_users = count($result);
        }
        $data['data'][] = array('title'=>'Online Users','image'=>plugins_url('/images/live.gif', AHCPRO_PLUGIN_MAIN_FILE),'icon'=>plugins_url('/images/online.png', AHCPRO_PLUGIN_MAIN_FILE),'total'=>$online_users);
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    function Traffic_by_Country(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }
        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $mystart_date = !empty($request->get_param( 'start_date'))?$request->get_param( 'start_date'):'';
        $myend_date = !empty($request->get_param( 'end_date'))?$request->get_param( 'end_date'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';

        if(isset($page) && $page=="all"){
            $res = $this->ahcpro_get_vsitors_by_country(1,false,"","",$mystart_date,$myend_date);
            return rest_ensure_response(['success' => true, 'data' => $res]);
        }else{
            $cnt = $this->ahcpro_get_vsitors_by_country("",true,"","",$mystart_date,$myend_date);
            $countries = $this->ahcpro_get_vsitors_by_country("",false,$start,$length,$mystart_date,$myend_date);
            $arr["title"]= 'Traffic by Country';
            $arr["draw"]= 0;
            $arr["recordsTotal"]= $cnt;
            $arr["recordsFiltered"]= $cnt;
            $arr['data'] = $countries;

            return rest_ensure_response(['success' => true, 'data' => $arr]);
        }
    }
    function ahcpro_get_vsitors_by_country($all,$cnt=true,$start='',$limit='',$fdt='',$tdt='') {
        global $wpdb;
        $custom_timezone_offset = ahcpro_get_current_timezone_offset();
        $vtr_date = "CONVERT_TZ(concat(vtr_date,' ',vtr_time), '" . AHCPRO_SERVER_CURRENT_TIMEZONE . "', '" . $custom_timezone_offset . "')";
        $cond = "";
        if (isset($fdt) && $fdt != '' && isset($tdt) && $tdt != '' && isset($_POST['section']) && $_POST['section'] == "traffic_index_country") {
            $fdt = $_POST['t_from_dt'];
            $tdt = $_POST['t_to_dt'];
        } else if (isset($fdt) && $fdt != '') {
            $fdt = $fdt;
            $fromdt = getFormattedDate($fdt, 'yymmdd');
            $cond = " and vtr_date ='$fromdt'";
        }

        if ($fdt != '' && $tdt != '') {
            $fromdt = getFormattedDate($fdt, 'yymmdd');
            $todt = getFormattedDate($tdt, 'yymmdd');
            $cond = "and (DATE($vtr_date) between '" . $fromdt . "' and '$todt')";
            //$cond =" and (vtr_date between '$fromdt' and '$todt')";
        } else if ($fdt != '') {
            $fromdt = getFormattedDate($fdt, 'yymmdd');
            $cond = "and DATE($vtr_date) = '" . $fromdt . "'";
            //$cond =" and vtr_date ='$fromdt'";
        } else {
            $cond = "and DATE(CONVERT_TZ(concat(vtr_date,' ',vtr_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "')) = '" . ahcpro_localtime('Y-m-d') . "'";
        }

        if ($cnt == true) {
            /*$sql = "select tot.ctr_name, tot.ctr_internet_code, tot.total from (SELECT c.ctr_name, c.ctr_internet_code, count(1) as total FROM ahc_recent_visitors v, ahc_countries c  where v.ctr_id = c.ctr_id  $cond group by ctr_name) as tot order by tot.total desc";
            $results = $wpdb->get_results($sql, OBJECT);*/
            $sql = "select count(*) as cnt from (SELECT c.ctr_name, c.ctr_internet_code, count(1) as total FROM ahc_recent_visitors v, ahc_countries c  where v.site_id = ".get_current_blog_id()." and v.ctr_id = c.ctr_id  $cond group by ctr_name ) as tot order by tot.total desc";

            return $wpdb->get_var($sql);
        }

        $limitCond = "";
        if ($start != '' && $limit != '') {
            $limitCond = " limit $start,$limit";
        }
        if ($all == 1) {
            $limitCond = "";
        }

        $sql = "select tot.ctr_name, tot.ctr_internet_code, tot.total from (SELECT c.ctr_name, c.ctr_internet_code, count(1) as total FROM ahc_recent_visitors v, ahc_countries c  where v.site_id = ".get_current_blog_id()." and v.ctr_id = c.ctr_id  $cond group by ctr_name ) as tot order by tot.total desc $limitCond";
        $results = $wpdb->get_results($sql, OBJECT);
        //echo $sql;
        if ($results !== false) {
            $arr = array();
            $new = array();
            $c = 0;
            if($start=="")
                $start = 0;
            $no =$start+1;
            $sum=0;
            foreach ($results as $ctr) {

                /*if ($ctr->total > 1) {*/
                $imgurl = plugins_url('/images/flags/' . strtolower($ctr->ctr_internet_code) . '.png', AHCPRO_PLUGIN_MAIN_FILE);
                $arr[$c]['no'] = $no;
                $arr[$c]['country'] =$imgurl;
                $arr[$c]['ctr_name'] = $ctr->ctr_name;
                $arr[$c]['ctr_internet_code'] = $ctr->ctr_internet_code;
                $arr[$c]['total'] = $ctr->total;

                if($all==1)
                {
                    $new[$c]['no'] =$no;
                    $new[$c]['ctr_name'] = $ctr->ctr_name;
                    $new[$c]['total'] = $ctr->total;
                }

                $c++;
                $no++;


                /*} else {

                    $sum += 1;
                }*/


            }

            if($sum>0)
            {
                $k = count($arr);
                $arr[$k]['no'] =$no;
                $imgurl = plugins_url('/images/flags/xx.png', AHCPRO_PLUGIN_MAIN_FILE);
                $arr[$k]['country'] = $imgurl;
                $arr[$k]['ctr_name'] = 'others';
                $arr[$k]['ctr_internet_code'] = 'XX';
                $arr[$k]['total'] = $sum;

                if($all==1)
                {
                    $new[$k]['no'] =$no;
                    $new[$k]['ctr_name'] = 'others';
                    $new[$k]['total'] = $sum;
                }

            }
            if($all==1)
            {
                return $new;
            }



            return $arr;
        } else {
            return false;
        }
    }


    function Latest_Search_Words(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $mystart_date = !empty($request->get_param( 'start_date'))?$request->get_param( 'start_date'):'';
        $myend_date = !empty($request->get_param( 'end_date'))?$request->get_param( 'end_date'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';

        if(isset($page) && $page=="all"){
            $res = $this->ahcpro_get_latest_search_key_words_used(1,false,"","",$mystart_date,$myend_date);
            return rest_ensure_response(['success' => true, 'data' => $res]);
        }else{
            $cnt = $this->ahcpro_get_latest_search_key_words_used("",true,"","",$mystart_date,$myend_date);
            $recentVisitors = $this->ahcpro_get_latest_search_key_words_used("",false,$start,$length,$mystart_date,$myend_date);

            $arr["draw"]= 0;
            $arr["recordsTotal"]= $cnt;
            $arr["recordsFiltered"]= $cnt;
            $arr['data'] = $recentVisitors;
            return rest_ensure_response(['success' => true, 'data' => $arr]);
        }
    }
    function ahcpro_get_latest_search_key_words_used($all,$cnt=true,$start='',$limit='',$fdt='',$tdt='') {
        global $wpdb;
        $custom_timezone_offset = ahcpro_get_current_timezone_offset();
        $cond="";
        $cond1="";

        if($fdt!='' && $tdt!='')
        {
            $fromdt = getFormattedDate($fdt,'yymmdd');
            $todt = getFormattedDate($tdt,'yymmdd');
            //$cond =" and (k.kwd_date between '$fromdt' and '$todt')";
            $cond =" having (dt between '$fromdt' and '$todt')";
            $cond1 =" and (kwd_date between '$fromdt' and '$todt')";
        }

        if($cnt==true)
        {
            $sql = "SELECT count(*) FROM `ahc_keywords` AS k LEFT JOIN `ahc_countries` AS c ON k.ctr_id = c.ctr_id JOIN `ahc_browsers` AS b ON k.bsr_id = b.bsr_id JOIN `ahc_search_engines` AS s on k.srh_id = s.srh_id WHERE k.site_id = ".get_current_blog_id()." and k.kwd_ip_address != 'UNKNOWN' and k.kwd_keywords !='amazon' and c.ctr_id IS NOT NULL $cond1 ORDER BY k.kwd_date DESC, k.kwd_time DESC ";
            $count = $wpdb->get_var($sql);
            return $count;
        }

        $limitCond="";
        if($start !='' && $limit!='')
        {
            $limitCond =" LIMIT $start, $limit";
        }

        if($all==1)
        {
            $limitCond="";
        }

        $sql = "SELECT  k.kwd_date as dt,k.kwd_ip_address, k.kwd_referer, k.kwd_keywords, CONVERT_TZ(CONCAT_WS(' ',k.kwd_date,k.kwd_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "') as kwd_date, CONVERT_TZ(k.kwd_time,'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "') as kwd_time, k.ctr_id, 
		c.ctr_name, c.ctr_internet_code, b.bsr_name, b.bsr_icon, s.srh_name, s.srh_icon 
		FROM `ahc_keywords` AS k 
		LEFT JOIN `ahc_countries` AS c ON k.ctr_id = c.ctr_id 
		JOIN `ahc_browsers` AS b ON k.bsr_id = b.bsr_id 
		JOIN `ahc_search_engines` AS s on k.srh_id = s.srh_id 
		WHERE k.site_id = ".get_current_blog_id()." and k.kwd_ip_address != 'UNKNOWN' and k.kwd_keywords !='amazon' and c.ctr_id IS NOT NULL $cond
		ORDER BY k.kwd_date DESC, k.kwd_time DESC $limitCond";
        $results = $wpdb->get_results($sql, OBJECT);

        if ($results !== false) {
            $arr = array();
            $new = array();
            $c = 0;
            $custom_timezone_offset = ahcpro_get_current_timezone_offset();
            $custom_timezone_string = ahcpro_get_timezone_string();
            $ahcpro_save_ips = get_option('ahcpro_save_ips_opn');
            if ($custom_timezone_string) {
                $custom_timezone = new DateTimeZone(ahcpro_get_timezone_string());
            }
            foreach ($results as $re) {

                $arr[$c]['hit_referer'] = rawurldecode($re->kwd_referer);
                $arr[$c]['hit_search_words'] = $re->kwd_keywords;
                $arr[$c]['hit_date'] = $re->kwd_date;
                $arr[$c]['hit_time'] = $re->kwd_time;
                $arr[$c]['hit_ip_address'] = $re->kwd_ip_address;
                $arr[$c]['ctr_name'] = $re->ctr_name;
                $arr[$c]['ctr_internet_code'] = $re->ctr_internet_code;
                $arr[$c]['bsr_name'] = $re->bsr_name;
                $arr[$c]['bsr_icon'] = $re->bsr_icon;
                $arr[$c]['srh_name'] = $re->srh_name;
                $arr[$c]['srh_icon'] = $re->srh_icon;

                $ctr_internet_code = '';
                if ($re->ctr_internet_code != '') {
                    $ctr_internet_code = plugins_url('/images/flags/' . strtolower( $re->ctr_internet_code) . '.png', AHCPRO_PLUGIN_MAIN_FILE);
                }
                $arr[$c]['ctr_internet_code']=$ctr_internet_code;
                $eurl=plugins_url('/images/search_engines/' . $re->srh_icon, AHCPRO_PLUGIN_MAIN_FILE);
                $arr[$c]['eurl']=$eurl;
                $burl=plugins_url('/images/browsers/' . $re->bsr_icon, AHCPRO_PLUGIN_MAIN_FILE);
                $arr[$c]['burl']=$burl;


                $visitDate = new DateTime($re->kwd_date);
                $visitDate->setTimezone($custom_timezone);
                //$arr[$c]['dt'] = '<span class="visitDateTime">'.$visitDate->format('d/m/Y').'</span>';
                $arr[$c]['dt'] = $visitDate->format('d/m/Y');

                if($all==1)
                {
                    $new[$c]['csb']=$re->ctr_name."/".$re->srh_name."/".$re->bsr_name;
                    $new[$c]['keyword'] = $re->kwd_keywords;
                    $new[$c]['dt'] = $visitDate->format('d/m/Y');
                }
                $c++;
            }
            if($all==1)
                return $new;
            return $arr;
        } else {
            return false;
        }
    }

    function Traffic_by_title(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $search = !empty($request->get_param( 'search'))?$request->get_param( 'search'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';

        if(isset($page) && $page=="all")
        {
            $res = $this->ahcpor_get_traffic_by_title(1,false,"","",$search);
            return rest_ensure_response(['success' => true, 'data' => $res]);
        }else{
            $cnt = $this->ahcpor_get_traffic_by_title("",true,"","",$search);
            $tTitles = $this->ahcpor_get_traffic_by_title("",false,$start,$length,$search);

            $arr["draw"]= 0;
            $arr["recordsTotal"]= $cnt;
            $arr["recordsFiltered"]= $cnt;
            $arr['data'] = $tTitles;

            return rest_ensure_response(['success' => true, 'title' => traffic_by_title, 'data' => $arr]);
        }
    }

    function ahcpor_get_traffic_by_title($all,$cnt=false, $start='', $limit='',$search='') {

        global $wpdb;
        $sql1 = "SELECT SUM(hits) AS sm FROM (
			SELECT SUM(til_hits) AS hits 
			FROM ahc_title_traffic 
			WHERE `site_id` = '".get_current_blog_id()."'  
			GROUP BY til_page_id
			) myTable";

        $cond ="";
        if($search!='')
        {
            $cond =" and til_page_title like '%".$search."%'";
        }

        if($cnt==true)
        {
            $sql2 = "SELECT til_page_id, til_page_title, til_hits 
			FROM ahc_title_traffic where `site_id` = '".get_current_blog_id()."'   $cond 
			GROUP BY til_page_id , til_page_title, til_hits
			ORDER BY til_hits DESC limit %d, %d";

            $count = $wpdb->get_results($wpdb->prepare($sql2, $start, $limit));
            return  isset($count->num_rows)?$count->num_rows:count($count);
        }

        $limitCond = "";
        if($start !='' && $limit!='')
        {

            $limitCond = " limit ".intval($start).",".intval($limit);
        }

        if($all=="1" && $cond!='')
        {
            $limitCond = "";
        }

        $sql2 = "SELECT til_page_id, til_page_title, til_hits 
			FROM ahc_title_traffic where `site_id` = '".get_current_blog_id()."'   $cond 
			GROUP BY til_page_id 
			ORDER BY til_hits DESC $limitCond";

        $result1 = $wpdb->get_results($sql1);
        if ($result1 !== false) {
            $total = $result1[0]->sm;
            $result2 = $wpdb->get_results($wpdb->prepare($sql2, AHCPRO_TRAFFIC_BY_TITLE_LIMIT));
            if ($result2 !== false) {
                $arr = array();
                if ($wpdb->num_rows > 0) {
                    $c = 0;
                    if($start=="")
                        $start = 0;
                    $no =$start;
                    foreach ($result2 as $r) {
                        $ans=0;
                        $arr[$c]['rank'] = $no + 1;
                        //$arr[$c]['til_page_id'] = $r->til_page_id;
                        if($all==1)
                            $arr[$c]['til_page_title'] = $r->til_page_title;
                        else
                            $arr[$c]['til_page_title'] = $r->til_page_title;
                        //$arr[$c]['til_page_title'] .= "<br /><a style=\"color:gray\" href='".get_permalink($r->til_page_id)."' target='_blank'><small>".get_permalink($r->til_page_id)."</small></a>";
                        $arr[$c]['til_hits'] = $r->til_hits;
                        $ans = ($total > 0) ? ahcpro_ceil_dec((($r->til_hits / $total) * 100), 2, ".")  : 0;
                        $arr[$c]['percent'] = ahc_pro_NumFormat($ans).'%';
                        $c++;
                        $no++;
                    }
                }
                return $arr;
            }
        }
        return false;
    }

    public function get_chart_end(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $mystart_date = !empty($request->get_param( 'start_date'))?$request->get_param( 'start_date'):date("Y-m-d",strtotime("-1 month"));
        $myend_date = !empty($request->get_param( 'end_date'))?$request->get_param( 'end_date'):date("Y-m-d");
        $visits_visitors_data = ahcpro_get_visits_by_custom_duration_callback($mystart_date,$myend_date,$stat='');
        return rest_ensure_response(['success' => true, 'data' => $visits_visitors_data]);
    }


    public function map(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $data = array();
        if(AHC_PRO_SET_GOOGLE_MAP == 'online'){
            $data['title'] =  'Online users';
        }else if(AHC_PRO_SET_GOOGLE_MAP == 'top10'){
            $data['title'] =  'Top 10 countries';
        }else if(AHC_PRO_SET_GOOGLE_MAP == 'all'){
            $data['title'] =  'All countries';
        }else if(AHC_PRO_SET_GOOGLE_MAP == 'this_month'){
            $data['title'] =  'This month visitors';
        }else if(AHC_PRO_SET_GOOGLE_MAP == 'past_month'){
            $data['title'] =  'Past month visitors';
        }else{
            $data['title'] = 'Today visitors per country';
        }
        global $wpdb;

        $ahcpro_get_save_settings = ahcpro_get_save_settings();
        $map_status = $ahcpro_get_save_settings[0]->set_google_map;

        if ($map_status == 'online') {
            $ctrArr = ahcpro_get_online_visitors_for_map();

        } else if ($map_status == 'all') { // top 10 coutries
            $ctrArr = ahcpro_get_all_visitors_for_map(' limit 300');

        }else if ($map_status == 'top10') { // top 10 coutries
            $ctrArr = ahcpro_get_all_visitors_for_map(' limit 10');

        } else if($map_status == 'this_month') // this month visitors
        {
            $ctrArr = ahcpro_get_today_visitors_for_map('this_month'); // today visitors
        }else if($map_status == 'past_month') // this month visitors
        {
            $ctrArr = ahcpro_get_today_visitors_for_map('past_month'); // today visitors
        }else{
            $ctrArr = ahcpro_get_today_visitors_for_map();  // default : today visitors
        }
        if($ctrArr['success'] && isset($ctrArr['data']) && count($ctrArr['data']) > 0){
            foreach ($ctrArr['data'] as $vc) {
                $map_data= array();
                $map_data['flags'] = plugins_url('/images/flags/' . strtolower($vc['ctr_internet_code']) . '.png', AHCPRO_PLUGIN_MAIN_FILE);
                $map_data['ctr_name'] = str_replace("'", "", $vc['ctr_name']);
                $map_data['visitors'] = str_replace("'", "", $vc['visitors']);
                $map_data['latitude'] = $vc['ctr_latitude'];
                $map_data['longitude'] = $vc['ctr_longitude'];
                $data['map'][]=$map_data;
            }
        }
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    public function summary_statistics(){
        
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $ahc_sum_stat = ahcpro_get_summary_statistics();
        $data= array();
        $data['title']= ahc_summary_statistics;

        $data['data']['title_table'] = array('title'=>'','visitors'=>ahc_visitors,'visits'=>ahc_visits);
        $data['data']['today'] = array('title'=>ahc_today,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['today']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['today']['visits']));
        $data['data']['yesterday'] = array('title'=>ahc_yesterday,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['yesterday']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['yesterday']['visits']));
        $data['data']['this_week'] = array('title'=>ahc_this_week,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['week']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['week']['visits']));
        $data['data']['this_month'] = array('title'=>ahc_this_month,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['month']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['month']['visits']));
        $data['data']['this_yesr'] = array('title'=>ahc_this_yesr,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['year']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['year']['visits']));
        $data['data']['total'] = array('title'=>ahc_total,'visitors'=>ahc_pro_NumFormat($ahc_sum_stat['total']['visitors']),'visits'=>ahc_pro_NumFormat($ahc_sum_stat['total']['visits']));
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    public function search_engines_statistics(){

        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $ahc_sum_stat = ahcpro_get_summary_statistics();
        $data= array();
        $data['title']= ahc_search_engines_statistics;

        $alltimeSER = ahcpro_get_hits_search_engines_referers('alltime');

        $tot_srch = 0;
        if (is_array($alltimeSER)) {
            foreach ($alltimeSER as $ser=>$v) {
                $tot_srch += $v;
                $ser = (!empty($ser)) ? $ser : 'Other';
                $data['data'][] = array('engine'=>$ser,'total'=>ahc_pro_NumFormat($v));
            }
        }
        $data['data'][] = array('engine'=>'Total','total'=>ahc_pro_NumFormat($tot_srch));
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    public function browsers_statistics(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $data= array();
        $data['title']= ahc_browsers;
        $data['data'] = ahcpro_get_browsers_hits_counts();
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }
    public function search_engines(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $data= array();
        $data['title']= 'Search Engines';
        $data['data'] = $this->ahcpro_get_serch_visits_by_date();
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }
    function ahcpro_get_serch_visits_by_date() {

        global $wpdb;
        $response = array();
        $sql = "SELECT ase.srh_name, asv.vtsh_date, asv.srh_id, SUM(asv.vtsh_visits) as vtsh_visits FROM `ahc_searching_visits` asv, `ahc_search_engines` ase where asv.site_id = '".get_current_blog_id()."' and asv.srh_id = ase.srh_id GROUP by asv.srh_id order by SUM(asv.vtsh_visits) DESC";
        $results = $wpdb->get_results($sql, OBJECT);
        if ($results !== false) {
            foreach ($results as $r) {
                $response[] = array('title'=>$r->srh_name,'value'=>$r->vtsh_visits);
            }
        } else {
            $response['success'] = false;
        }

        return $response;
    }
    public function Top_Referring_Countries(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $data= array();
        $data['title']= 'Search Engines';
        $data['data'] = $this->ahcpro_get_top_countries(10,"","","",false);
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }
    public function Top_Referring_Sites(){
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $data= array();
        $data['title']= ahc_refering_sites;
        $data['table_head']['1']= ahc_site_name;
        $data['table_head']['2']= ahc_total_times;
        $rets = '';
        $referingSites = ahcpro_get_top_refering_sites();
        $row = array();
        if (is_array($referingSites) && count($referingSites) > 0) {
            foreach ($referingSites as $site) {
                str_replace('https://', '', $site['site_name']);
                $row[] = array('site_name'=>$site['site_name'],'link'=>"https://" . str_replace('http://', '', $site['site_name']),'img'=>plugins_url('/images/openW.jpg', AHCPRO_PLUGIN_MAIN_FILE),'total_hits'=>$site['total_hits'],'image_title'=>ahc_view_referer) ;

            }

            echo $rets;
        }
        $data['data'] = $row;

        return rest_ensure_response(['success' => true, 'data' => $data]);
    }
    function visits_time_graph_callback(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $mystart_date = !empty($request->get_param( 'start_date'))?$request->get_param( 'start_date'):'';
        $myend_date = !empty($request->get_param( 'end_date'))?$request->get_param( 'end_date'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';

        if(isset($page) && $page=="all")
        {
            $times = $this->ahcpro_get_time_visits(1,"","",$mystart_date,$myend_date);
            return rest_ensure_response(['success' => true, 'data' => $times]);
        }else{
            $times = $this->ahcpro_get_time_visits("",$start,$length,$mystart_date,$myend_date);
            $cnt = 24;
            $arr["draw"]= 0;
            $arr["recordsTotal"]= $cnt;
            $arr["recordsFiltered"]= $cnt;
            $arr['data'] = $times;
            return rest_ensure_response(['success' => true, 'data' => $arr]);
        }
    }

    function ahcpro_get_time_visits($all,$start='',$limit='',$fdt='',$tdt='') {
        global $wpdb;
        $custom_timezone_offset = ahcpro_get_current_timezone_offset();
        $vst_date = "CONVERT_TZ(vst_date, '" . AHCPRO_SERVER_CURRENT_TIMEZONE . "', '" . $custom_timezone_offset . "')";

        $cond="";

        if(isset($fdt) && $fdt!='' && isset($tdt) && $tdt!='')
        {
            $fdt = $fdt;
            $tdt = $tdt;
        }
        else if(isset($fdt) && $fdt!='')
        {
            $fdt = $fdt;
        }
        if($fdt!='' && $tdt!='')
        {
            $fromdt = getFormattedDate($fdt,'yymmdd');
            $todt = getFormattedDate($tdt,'yymmdd');
            $cond = "(DATE($vst_date) between '".$fromdt."' and '$todt')";
            $groupby = " hour";
        }
        else if($fdt!='')
        {
            $fromdt = getFormattedDate($fdt,'yymmdd');
            $cond = "DATE($vst_date) = '".$fromdt."'";
            $groupby = " hour($vst_date)";
        }
        else
        {
            $cond = "DATE($vst_date) = '".ahcpro_localtime('Y-m-d')."'";
            $groupby = " hour($vst_date)";
        }

        $sql1 = "SELECT SUM(vtm_visitors) AS sm FROM ahc_visits_time WHERE `site_id` = '".get_current_blog_id()."' and  DATE($vst_date) = '".ahcpro_localtime('Y-m-d')."'";


        $sql2 = "SELECT date(vst_date) as dt,hour($vst_date) AS hour, SUM(vst_visitors) AS vst_visitors, SUM(vst_visits) AS vst_visits FROM `ahc_visitors` 
WHERE site_id = ".get_current_blog_id()." and $cond GROUP BY $groupby";

        $total = 0;
        $result2 = $wpdb->get_results($sql2);
        $utc_data = array();

        if ($result2 !== false) {
            $arr = array();
            $new = array();
            $hourDetails = array();
            foreach ($result2 as $r) {

                if(isset($hourDetails[ $r->hour ]))
                {
                    $hourDetails[ $r->hour ]['visitor']	+=$r->vst_visitors;
                    $hourDetails[ $r->hour ]['visits']	+=$r->vst_visits;
                    $hourDetails[ $r->hour ]['counter'] += 1;
                }
                else
                {
                    $hourDetails[ $r->hour ] = array(
                        'visitor' 	=> $r->vst_visitors,
                        'visits'	=> $r->vst_visits,
                        'counter' => 1
                    );
                }
                //$dtArr[]= $hourDetails;
                $total += $r->vst_visitors;
            }

            if($start=='')
                $start = 0;
            if($limit!='' && $start == 20)
                $end = 24;
            else if($limit=="")
                $end = 24;
            else
                $end = $limit+$start;

            if($all==1)
            {
                $start=0;
                $end=24;
            }
            $k=0;
            $avgtotal = 0;
            for( $i = $start; $i < $end; $i++ ){

                $vtm_visitors = 0;
                $vtm_visits = 0;
                $totalDt =  count($hourDetails);

                if( isset( $hourDetails[$i] ) ){
                    $vtm_visitors = $hourDetails[$i]['visitor']/$hourDetails[$i]['counter'];
                    $avgtotal +=$vtm_visitors;
                    $vtm_visits = $hourDetails[$i]['visits']/$hourDetails[$i]['counter'];
                }
                if( $i < 10 ){
                    $timeTo = $timeFrom = '0'.$i;
                }else{
                    $timeTo = $timeFrom = $i;
                }
                $arr[$k]['vtm_time_from'] = $timeFrom.':00';
                $arr[$k]['vtm_time_to'] = $timeTo.':59';
                // $arr[$k]['percent'] = ($total > 0) ? ahcpro_ceil_dec((($vtm_visitors / $total) * 100), 2, ".") : 0;

                $arr[$k]['time'] = $timeFrom.':00 - '.$timeTo.':59';

                $arr[$k]['vtm_visitors'] = ceil($vtm_visitors);
                $arr[$k]['vtm_visits'] = ceil($vtm_visits);

                if($all==1)
                {
                    $new[$k]['time'] = $timeFrom.':00 - '.$timeTo.':59';
                    $new[$k]['vtm_visitors'] = ceil($vtm_visitors);
                    $new[$k]['vtm_visits'] = ceil($vtm_visits);
                }
                $k++;
            }
            $avgtotal = $total;

            $j=0;
            for( $i = $start; $i < $end; $i++ )
            {
                if( isset( $hourDetails[$i] ) )
                {
                    $vtm_visitors = $hourDetails[$i]['visitor']/$hourDetails[$i]['counter'];
                }else{
                    $vtm_visitors = 0;
                }

                $arr[$j]['percent'] = ($avgtotal > 0) ? ahcpro_ceil_dec((($vtm_visitors / $total) * 100), 2, ".") : 0;
                $per = ($avgtotal > 0) ? ahcpro_ceil_dec((($vtm_visitors / $avgtotal) * 100), 2, ".") : 0;

                if($all==1)
                    $new[$j]['percent'] = $per;


                $j++;

            }
            if($all==1)
                return $new;
            return $arr;
        }
        //}
        return false;

    }



    public function recent_visitor_by_ip_callback(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $mystart_date = !empty($request->get_param( 'start_date'))?$request->get_param( 'start_date'):'';
        $myend_date = !empty($request->get_param( 'end_date'))?$request->get_param( 'end_date'):'';
        $ip = !empty($request->get_param( 'ip'))?$request->get_param( 'ip'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';

        if($page != '' && $page=="all"){
            $res = $this->ahcpro_get_recent_visitors(1,false,"","",$mystart_date,$myend_date,$ip);
            return rest_ensure_response(['success' => true, 'data' => $res]);
        }else{
            $cnt = $this->ahcpro_get_recent_visitors("",true,"","",$mystart_date,$myend_date,$ip);
            $recentVisitors = $this->ahcpro_get_recent_visitors("",false,$start,$length,$mystart_date,$myend_date,$ip);
            $arr["title"]= 'Recent Traffic by IP';
            $arr["recordsTotal"]= strval($cnt);
            $arr["recordsFiltered"]= strval($cnt);
            $arr['data'] = $recentVisitors;
            return rest_ensure_response(['success' => true, 'data' => $arr]);
        }
    }
    public function ahcpro_get_recent_visitors($all,$cnt=true,$start='',$limit='',$fdt='',$tdt='',$ip='') {
        global $wpdb, $_SERVER;
        $cond="";
        $having = '';
        $ahcpro_save_ips = get_option('ahcpro_save_ips_opn');

        if(isset($fdt) && $fdt!='' && isset($tdt) && $tdt!='')
        {
            $fdt = $fdt;
            $tdt = $tdt;
        }
        else if(isset($fdt) && $fdt!='')
        {
            $fdt = $fdt;

        }
        if(isset($ip) && $ip!='' )
        {
            $ip =$ip;
        }

        if($ip!='')
        {
            $cond .=" and vtr_ip_address='".$ip."'";
            //$cond1 .=" and vtr_ip_address='".$ip."'";
        }

        if($fdt!='' && $tdt!='')
        {
            $fromdt = getFormattedDate($fdt,'yymmdd');
            $todt = getFormattedDate($tdt,'yymmdd');
            $having .=" having (dt between '$fromdt' and '$todt')";
            //$cond1 .=" and (vtr_date between '$fromdt' and '$todt')";
        }
        else if($fdt!='' && $tdt='')
        {
            $fromdt = getFormattedDate($fdt,'yymmdd');
            $cond .=" and dt ='$fromdt'";
            //$cond1 .=" and dt ='$fromdt'";
        }

        $custom_timezone_offset = ahcpro_get_current_timezone_offset();
        if($cnt==true)
        {
            $sql_query = "SELECT * from (Select DATE_FORMAT(CONVERT_TZ(CONCAT_WS(' ',v.vtr_date,v.vtr_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'), '%Y-%m-%d') as dt FROM `ahc_recent_visitors` AS v LEFT JOIN `ahc_countries` AS c ON v.ctr_id = c.ctr_id LEFT JOIN `ahc_browsers` AS b ON v.bsr_id = b.bsr_id WHERE v.site_id = ".get_current_blog_id()." and v.vtr_ip_address NOT LIKE 'UNKNOWN%%' $cond group by vtr_ip_address, vtr_date ORDER BY v.vtr_id DESC) as res";
            $count = $wpdb->get_results($sql_query);
            return count($count);
        }

        $limitCond="";
        if($start !='' && $limit!='')
        {
            $limitCond =" LIMIT $start, $limit";
        }

        if($all==1)
        {
            $limitCond="";
        }




        $sql_query = "SELECT hit_date, hit_ip_address, count(hit_id) as day_hits, (
                 SELECT Count(1)
                 FROM   `ahc_hits`
                 WHERE  `hit_ip_address` = v.vtr_ip_address 
                 AND    date(`hit_date`) = date(v.vtr_date)
                 AND    `hit_page_title` !='' ) AS day_hits2, v.vtr_id, v.vtr_ip_address, v.vtr_referer, DATE_FORMAT(CONVERT_TZ(CONCAT_WS(' ',v.vtr_date,v.vtr_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'), '%Y-%m-%d') as dt ,DATE_FORMAT(CONVERT_TZ(CONCAT_WS(' ',v.vtr_date,v.vtr_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'), '%Y-%m-%d') as vtr_date, DATE_FORMAT(CONVERT_TZ(CONCAT_WS(' ',v.vtr_date,v.vtr_time),'" . AHCPRO_SERVER_CURRENT_TIMEZONE . "','" . $custom_timezone_offset . "'), '%H:%i:%s') as vtr_time, v.ahc_city, v.ahc_region,
		c.ctr_name, c.ctr_internet_code, b.bsr_name, b.bsr_icon 
		FROM `ahc_recent_visitors` AS v  left join ahc_hits as hit on v.vtr_ip_address = hit.hit_ip_address and date(v.vtr_date) = date(hit_date) and hit.hit_page_title !=''
		LEFT JOIN `ahc_countries` AS c ON v.ctr_id = c.ctr_id 
		LEFT JOIN `ahc_browsers` AS b ON v.bsr_id = b.bsr_id 
		WHERE v.site_id = ".get_current_blog_id()." and v.vtr_ip_address NOT LIKE 'UNKNOWN%%' $cond  
		group by vtr_ip_address, vtr_date $having ORDER BY v.vtr_id DESC $limitCond";

        $results = $wpdb->get_results($sql_query);

        if ($results !== false) {
            $arr = array();
            $c = 0;
            if (is_array($results)) {
                foreach ($results as $hit) {
                    if (strlen($hit->vtr_ip_address) < 17) {

                        $visitDate = new DateTime($hit->vtr_date. ' ' . $hit->vtr_time);

                        $arr[$c]['hit_ip_address'] = (get_option('ahcpro_haships') != '1') ? $hit->vtr_ip_address: ahcfpro_haship($hit->vtr_ip_address);
                        $img="";
                        if ( $hit->ctr_internet_code != '')
                        {
                            $img = plugins_url('/images/flags/' . strtolower($hit->ctr_internet_code) . '.png', AHCPRO_PLUGIN_MAIN_FILE);
                        }
                        $arr[$c]['city_image'] = $img;
                        $arr[$c]['ctr_name'] = $hit->ctr_name;
                        $arr[$c]['ahc_city'] = $hit->ahc_city;
                        $arr[$c]['ahc_region'] = $hit->ahc_region;
                        $arr[$c]['time'] = $visitDate->format('d M Y @ h:i a');
                        $arr[$c]['modall_day_hits'] = ( $hit->day_hits >0 ) ? $hit->day_hits:'/';
                        $arr[$c]['modall_hit_date'] = $hit->hit_date;
                        $arr[$c]['modall_hit_ip_address'] = $hit->hit_ip_address;
                        $arr[$c]['modall_ctr_name_ahc_city'] = $hit->ctr_name.'-'.$hit->ahc_city;
                        $arr[$c]['modall_hit_date'] = $hit->hit_date;
                        $c++;
                    }
                }
            }
            return $arr;
        } else {
            return false;
        }
    }

    function traffic_by_countries_callback(WP_REST_Request $request)
    {
        $key = $this->get_headers( wp_unslash( $_SERVER ) );
        if(!isset($key['KEY']) || $this->app_log_active != $key['KEY']){
            return rest_ensure_response( ['success'=>false,'data'=>array(),'massage'=>'key error'] );
        }
        if (!isset($key['DEVICEID']) || !$this->deviceId($key['DEVICEID'])) {
            //return rest_ensure_response(['success' => false, 'data' => array(),'massage'=>'DEVICEID error']);
        }

        $page = !empty($request->get_param( 'page'))?$request->get_param( 'page'):'';
        $start = !empty($request->get_param( 'start'))?$request->get_param( 'start'):'';
        $length = !empty($request->get_param( 'length'))?$request->get_param( 'length'):'';


        if(isset($page) && $page=="all")
        {
            $res =$this->ahcpro_get_top_countries(0,"","",1,false);
            return rest_ensure_response(['success' => true, 'data' => $res]);
        }
        else
        {
            $tTitles = $this->ahcpro_get_top_countries(0,$start,$length,"",false);
            $cnt = $this->ahcpro_get_top_countries(0,"","","",true);

            $arr["title"]= 'Traffic by countries';
            $arr["recordsTotal"]= $cnt;
            $arr["recordsFiltered"]= $cnt;
            $arr['data'] = $tTitles['data'];
            return rest_ensure_response(['success' => true, 'data' => $arr]);
        }
    }

    function ahcpro_get_top_countries( $limit = 0,$start='',$pagelimit='' ,$all='',$cnt=true) {
        global $wpdb;
        if( $limit == 0 ){
            $limit = AHCPRO_TOP_COUNTRIES_LIMIT;
        }

        if($cnt==true)
        {
            $sql = "SELECT count(*) FROM `ahc_countries` WHERE `site_id` = '".get_current_blog_id()."' and ctr_visits > 0 ORDER BY ctr_visitors DESC";
            $count = $wpdb->get_var($sql);
            return $count;

        }

        $limitCond = "";
        if($start !='' && $pagelimit!='')
        {

            $limitCond = " limit ".intval($start).",".intval($pagelimit);
        }


        if($limit > 0 && $pagelimit == "" )
        {
            $sql = "SELECT ctr_name, ctr_internet_code, ctr_visitors, ctr_visits 
		FROM `ahc_countries` WHERE `site_id` = '".get_current_blog_id()."' and  ctr_visits > 0 
		ORDER BY ctr_visitors DESC 
		LIMIT %d OFFSET 0";

            $results = $wpdb->get_results($wpdb->prepare($sql, $limit), OBJECT);
        }
        else
        {
            $sql = "SELECT ctr_name, ctr_internet_code, ctr_visitors, ctr_visits 
				FROM `ahc_countries` WHERE `site_id` = '".get_current_blog_id()."' and  ctr_visits > 0 
				ORDER BY ctr_visitors DESC $limitCond";
            $results = $wpdb->get_results($sql, OBJECT);
        }

        $response = array();
        if ($results !== false) {
            $new=array();
            $response['success'] = true;
            $response['data'] = array();
            $c = 0;
            if($start=="")
                $start = 0;
            $rank=$start+1;
            foreach ($results as $ctr) {
                $response['data'][$c]['rank'] = $rank;
                $furl = plugins_url('/images/flags/' . strtolower( $ctr->ctr_internet_code) . '.png', AHCPRO_PLUGIN_MAIN_FILE);
                $flag =$furl;
                $response['data'][$c]['flag'] = $flag;
                $response['data'][$c]['ctr_name'] = $ctr->ctr_name;
                //$response['data'][$c]['ctr_internet_code'] = $ctr->ctr_internet_code;
                $response['data'][$c]['visitors'] = $ctr->ctr_visitors;
                $response['data'][$c]['visits'] = $ctr->ctr_visits;

                if($all==1)
                {
                    $new[$c]['rank'] = $rank;
                    $new[$c]['ctr_name'] = $ctr->ctr_name;
                    $new[$c]['visitors'] = $ctr->ctr_visitors;
                    $new[$c]['visits'] = $ctr->ctr_visits;
                }
                $c++;
                $rank++;
            }
        } else {
            $response['success'] = false;
        }
        if($all==1)
        {
            return $new;
        }
        return $response;
    }


}

new AHCPRO_api_TrafficReport();
