<?php


$custom_timezone_offset = ahcpro_get_current_timezone_offset();
$custom_timezone_string = ahcpro_get_timezone_string();


$ahcpro_save_ips = get_option('ahcpro_save_ips_opn');
if ($custom_timezone_string) {
    $custom_timezone = new DateTimeZone(ahcpro_get_timezone_string());
}

$myend_date = new DateTime();
$myend_date->setTimezone(new DateTimeZone('UTC'));
//$myend_date->setTimezone($custom_timezone);
$myend_date_full = ahcpro_localtime('Y-m-d H:i:s');
$myend_date = ahcpro_localtime('Y-m-d');

$mystart_date = new DateTime($myend_date);
$mystart_date->modify(' - ' . (AHCPRO_VISITORS_VISITS_LIMIT - 1) . ' days');
$mystart_date->setTimezone(new DateTimeZone('UTC'));
//$mystart_date->setTimezone($custom_timezone);
$mystart_date_full = $mystart_date->format('Y-m-d H:i:s');
$mystart_date = $mystart_date->format('Y-m-d');

//echo date('Y-m-d H:i:s',time());
$is_settings_exists = ahcpro_check_settings();
if(empty($is_settings_exists) or  $is_settings_exists <=0)
{
	echo "dd";
	wp_redirect( admin_url( '/admin.php?page=ahc_hits_counter_settings' ) );
}

?>

<script language="javascript" type="text/javascript">
    function imgFlagError(image) {
        image.onerror = "";
        image.src = "<?php echo plugins_url('/images/flags/noFlag.png', AHCPRO_PLUGIN_MAIN_FILE) ?>";
        return true;
    }

    setInterval(function() {

        var now = new Date();
        var year = now.getFullYear();
        var month = now.getMonth() + 1;
        var day = now.getDate();
        var hour = now.getHours();
        var minute = now.getMinutes();
        var second = now.getSeconds();
        if (month.toString().length == 1) {
            month = '0' + month;
        }
        if (day.toString().length == 1) {
            day = '0' + day;
        }
        if (hour.toString().length == 1) {
            hour = '0' + hour;
        }
        if (minute.toString().length == 1) {
            minute = '0' + minute;
        }
        if (second.toString().length == 1) {
            second = '0' + second;
        }



        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
            "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
        ];

        const d = new Date();


        var dateTime = day + ' ' + monthNames[d.getMonth()] + ' ' + year + ', ' + hour + ':' + minute + ':' + second;
        document.getElementById('ahcpro_currenttime').innerHTML = dateTime;
    }, 500);
</script>
<style>
    body {
        background: #F1F1F1 !important
    }
</style>

<div class="ahc_main_container">

    <div class="row">
        <div class="col-12">
            <div class="add">
                <div style="text-align:left; padding:10px">
                    <img src="<?php echo plugins_url('/images/App.png', AHCPRO_PLUGIN_MAIN_FILE) ?>" width="50px" alt="">

                </div>
                <p> <strong> See your site stats from anywhere</strong> <br> Download the mobile app

                </p>
                <a title="Download App" style="text-align:right;  padding-right:10px ; display:inline-block" href="https://play.google.com/store/apps/details?id=com.codepress.trafic.trafic_static_app"><img width="150px" class="googlePlay" src="<?php echo plugins_url('/images/DownloadMobileApp.png', AHCPRO_PLUGIN_MAIN_FILE) ?>" alt=""></a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <h1><img height="55px" src="<?php echo plugins_url('/images/logo.png', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp;Visitor Traffic Real Time Statistics pro</h1><br />
        </div>
        <div class="col-lg-6">
            <h2 id="ahcpro_currenttime"></h2>
        </div>
    </div>



    <div class="row">
        <div class="col-lg-3">
            <div class="box_widget greenBox">
                <span id="up-down"></span><span id="onlinecounter">0</span>
                <br /><span class="txt"><img src="<?php echo plugins_url('/images/live.gif', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp; Online Users</span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="box_widget redBox">
                <span id="today_visitors_box">0</span><br /><span class="txt">Today's Visitors</span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="box_widget blueBox">
                <span id="today_visits_box">0</span><br /><span class="txt">Today's Page Views</span>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="box_widget movBox">
                <span id="today_search_box">0</span><br /><span class="txt">All Time Search Engines</span>
            </div>
        </div>
    </div>
    <!--div class="row">
		<div class="col-lg-12">
			<div class="panel" >
				<div class="panelcontent text-center search-panel">
					<form method="get" id="search_frm">
						<label>Search in Time Frame: </label>
						<input type="hidden" name="page" value="ahc_hits_counter_menu_pro"/>
						<input type="text" readonly="readonly" name="from_dt" id="from_dt" autocomplete="off" value="<?php echo isset($_GET['from_dt']) ? $_GET['from_dt'] : ''; ?>"/>
						<input type="text" readonly="readonly" name="to_dt" id="to_dt" autocomplete="off" value="<?php echo isset($_GET['to_dt']) ? $_GET['to_dt'] : ''; ?>"/>
						<input type="submit" class="button button-primary"/>
						<input type="button" class="button button-primary clear_form" value="Clear"/>
					</form>
				</div>
			</div>
		</div>
    </div-->
    <div class="row">
        <div class="col-lg-12">

            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important; background: #fff; padding:15px;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important">
                    <?php echo "Traffic Report "; ?></h2>
                <div class="hits_duration_select">


                    <select id="hits-duration" class="hits-duration" style="width: 150px; height: 35px; font-size: 15px;">
                        <option value="">Last <?php echo AHCPRO_VISITORS_VISITS_LIMIT; ?> days</option>
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="current_month">This month</option>
                        <option value="last_month">Last month</option>
                        <option value="0">Last 12 months</option>
                        <option value="range">Custom Period</option>
                    </select>

                    <span id="duration_area">
                        <input type="text" readonly="readonly" placeholder="From Date" class="ahc_clear" name="summary_from_dt" id="summary_from_dt" autocomplete="off" value="<?php echo isset($_POST['summary_from_dt']) ? $_POST['summary_from_dt'] : ''; ?>" />
                        <input type="text" readonly="readonly" placeholder="To Date" class="ahc_clear" name="summary_to_dt" id="summary_to_dt" autocomplete="off" value="<?php echo isset($_POST['summary_to_dt']) ? $_POST['summary_to_dt'] : ''; ?>" />
                    </span>


                </div>
                <div class="panelcontent" id="visitors_graph_stats" style="width:100% !important ;  border-radius:0 0 7px 7px !important;">
                    <div id="visitscount" style="height:400px; width:100% !important"></div>
                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important"><?php
                                                                                                                                                    if (AHC_PRO_SET_GOOGLE_MAP == 'online') {
                                                                                                                                                        echo 'Online users';
                                                                                                                                                    } else if (AHC_PRO_SET_GOOGLE_MAP == 'top10') {
                                                                                                                                                        echo 'Top 10 countries';
                                                                                                                                                    } else if (AHC_PRO_SET_GOOGLE_MAP == 'all') {
                                                                                                                                                        echo 'All countries';
                                                                                                                                                    } else if (AHC_PRO_SET_GOOGLE_MAP == 'this_month') {
                                                                                                                                                        echo 'This month visitors';
                                                                                                                                                    } else if (AHC_PRO_SET_GOOGLE_MAP == 'past_month') {
                                                                                                                                                        echo 'Past month visitors';
                                                                                                                                                    } else {
                                                                                                                                                        echo 'Today visitors per country';
                                                                                                                                                    }
                                                                                                                                                    ?></h2>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">

                    <?php
                    if (function_exists('ahcpro_google_map')) {
                        echo  ahcpro_google_map(AHC_PRO_SET_GOOGLE_MAP);
                    }
                    ?>
                    <br /><a href="admin.php?page=ahc_hits_counter_settings">Change map settings</a>
                    <b>shortcode:</b> <small>[ahcpro_show_google_map map_status="<?php echo AHC_PRO_SET_GOOGLE_MAP; ?>"]</small>
                </div>
            </div>
        </div>
        <?php
        $ahc_sum_stat = ahcpro_get_summary_statistics();
        ?>
        <div class="col-lg-4">
            <div class="panel-group">
                <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;margin-bottom:30px !important">
                    <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important"><?php echo ahc_summary_statistics ?><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                    <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                        <table width="95%" border="0" cellspacing="0" id="summary_statistics">
                            <thead>
                                <tr>
                                    <th width="40%"></th>
                                    <th width="30%"><?php echo ahc_visitors ?></th>
                                    <th width="30%"><?php echo ahc_visits ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo ahc_today ?></td>
                                    <td class="values"><span id="today_visitors"><?php echo ahc_pro_NumFormat($ahc_sum_stat['today']['visitors']); ?></span></td>
                                    <td class="values"><span id="today_visits"><?php echo ahc_pro_NumFormat($ahc_sum_stat['today']['visits']); ?></span></td>
                                </tr>

                                <tr>
                                    <td><?php echo ahc_yesterday ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['yesterday']['visitors']); ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['yesterday']['visits']); ?></td>
                                </tr>

                                <tr>
                                    <td><?php echo ahc_this_week ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['week']['visitors']); ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['week']['visits']); ?></td>
                                </tr>

                                <tr>
                                    <td><?php echo ahc_this_month ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['month']['visitors']); ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['month']['visits']); ?></td>
                                </tr>

                                <tr>
                                    <td><?php echo ahc_this_yesr ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['year']['visitors']); ?></td>
                                    <td class="values"><?php echo ahc_pro_NumFormat($ahc_sum_stat['year']['visits']); ?></td>
                                </tr>

                                <tr>
                                    <td style="color:#090"><strong><?php echo ahc_total ?></strong></td>
                                    <td class="values" style="color:#090"><strong><?php echo ahc_pro_NumFormat($ahc_sum_stat['total']['visitors']); ?></strong></td>
                                    <td class="values" style="color:#090"><strong><?php echo ahc_pro_NumFormat($ahc_sum_stat['total']['visits']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- end visitors and visits section -->

                    </div>
                </div>

                <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                    <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important"><?php echo ahc_search_engines_statistics ?></h2>
                    <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                        <table width="95%" border="0" cellspacing="0" id="search_engine">
                            <thead>
                                <tr>
                                    <th width="40%">Engine</th>
                                    <th width="30%">Total</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                $alltimeSER = ahcpro_get_hits_search_engines_referers('alltime');

                                $tot_srch = 0;
                                if (is_array($alltimeSER)) {
                                    foreach ($alltimeSER as $ser => $v) {
                                        $tot_srch += $v;
                                        $ser = (!empty($ser)) ? $ser : 'Other';
                                ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <span><b><?php echo $ser; ?></b></span>
                                                </div>
                                            </td>
                                            <td class="values"><?php echo ahc_pro_NumFormat($v); ?></td>

                                        </tr>
                                <?php
                                    }
                                }
                                ?>
                                <tr>
                                    <td><strong>Total </strong></td>
                                    <td class="values"><strong id="today_search"><?php echo ahc_pro_NumFormat($tot_srch); ?></strong></td>

                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important">Recent Traffic by IP<span class="search_data"><a href="#" class="dashicons dashicons-search" title="Search"></a></span><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                <div class="search-panel <?php echo (isset($_POST['section']) && $_POST['section'] == "recent_visitor_by_ip") ? "open" : ''; ?>">
                    <form method="post" class="search_frm">
                        <label>Search: </label>
                        <input type="hidden" name="page" value="ahc_hits_counter_menu_pro" />
                        <input type="hidden" name="section" value="recent_visitor_by_ip" />
                        <input type="text" readonly="readonly" placeholder="From Date" class="ahc_clear" name="r_from_dt" id="r_from_dt" autocomplete="off" value="<?php echo isset($_POST['r_from_dt']) ? $_POST['r_from_dt'] : ''; ?>" />
                        <input type="text" readonly="readonly" placeholder="To Date" class="ahc_clear" name="r_to_dt" id="r_to_dt" autocomplete="off" value="<?php echo isset($_POST['r_to_dt']) ? $_POST['r_to_dt'] : ''; ?>" />
                        <input type="text" name="ip_addr" id="ip_addr" placeholder="IP address" class="ahc_clear" value="<?php echo isset($_POST['ip_addr']) ? $_POST['ip_addr'] : ''; ?>" />
                        <input type="submit" class="button button-primary" />
                        <input type="button" class="button button-primary clear_form" value="Clear" />
                    </form>
                </div>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">

                    <!-- Modal -->
                    <div class="modal fade" id="DayHitsModal" role="dialog">
                        <br>
                        <div class="modal-dialog">

                            <!-- Modal content-->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title">IP Traking</h4>

                                </div>
                                <div class="modal-body">


                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>


                    <table width="95%" border="0" cellspacing="0" class="recentv" id="recent_visit_by_ip">
                        <thead>
                            <tr>

                                <th>IP Address</th>
                                <th>Location</th>
                                <th>Time</th>
                                <th>Hits</th>
                            </tr>
                        </thead>


                        <tbody>

                        </tbody>


                    </table>


                </div>
            </div>
        </div>
        <?php
        /*$countries = ahcpro_get_vsitors_by_country();*/
        $countries  = array();
        ?>
        <div class="col-lg-4">
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important; ">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important">
                    <?php
                    if (isset($_POST['t_from_dt']) && $_POST['t_from_dt'] != '' && isset($_POST['section']) && $_POST['section'] == "traffic_index_country") {
                        echo "Traffic Index by Country";
                    } else {
                        echo "Today's visitors by country ";
                    }
                    ?>
                    <span class="search_data"><a href="#" class="dashicons dashicons-search" title="Search"></a></span><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span>
                </h2>
                <div class="search-panel <?php echo (isset($_POST['section']) && $_POST['section'] == "traffic_index_country") ? "open" : ''; ?>">
                    <form method="post" class="search_frm">
                        <label>Search: </label>
                        <input type="hidden" name="page" value="ahc_hits_counter_menu_pro" />
                        <input type="hidden" name="section" value="traffic_index_country" />
                        <input type="text" readonly="readonly" placeholder="From Date" class="ahc_clear" name="t_from_dt" id="t_from_dt" autocomplete="off" value="<?php echo isset($_POST['t_from_dt']) ? $_POST['t_from_dt'] : ''; ?>" />
                        <input type="text" readonly="readonly" placeholder="To Date" class="ahc_clear" name="t_to_dt" id="t_to_dt" autocomplete="off" value="<?php echo isset($_POST['t_to_dt']) ? $_POST['t_to_dt'] : ''; ?>" />
                        <input type="submit" class="button button-primary" />
                        <input type="button" class="button button-primary clear_form" value="Clear" />
                    </form>
                </div>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <table height="100%" id="today_traffic_index_by_country">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Country</th>
                                <th>Country Name</th>
                                <th>Visitors</th>
                            </tr>
                        </thead>

                        <?php
                        $norecord = "";
                        if (is_array($countries) && count($countries) > 0) {
                        ?>
                            <tbody>
                                <?php
                                $ordr = 1;
                                foreach ($countries as $country) {
                                ?>
                                    <tr>
                                        <td><?php echo $ordr; ?></td>
                                        <td><img src="<?php echo plugins_url('/images/flags/' . strtolower($country['ctr_internet_code']) . '.png', AHCPRO_PLUGIN_MAIN_FILE) ?>" border="0" alt="<?php echo $country['ctr_name'] ?>" width="30" height="20" onerror="imgFlagError(this)" /></td>
                                        <td><?php echo $country['ctr_name'] ?></td>
                                        <td><strong><?php echo ahc_pro_NumFormat($country['total']); ?></strong></td>
                                    </tr>
                                <?php
                                    $ordr++;
                                }

                                ?>
                            </tbody>
                        <?php
                        }
                        ?>


                    </table>
                    <?php
                    if ($norecord == "1") {
                    ?>
                        <div class="no-record">No data available.</div>
                    <?php
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <!-- browsers chart panel -->
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important"><?php echo ahc_browsers ?></h2>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <div class="row">
                        <div class="col-lg-7">
                            <canvas id="brsBiechartContainer" height="200px"></canvas>
                        </div>
                        <div class="col-lg-5">
                            <div class="legendsContainer" id="browsersLegContainer"></div>

                        </div>


                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <!-- search engines chart panel -->


            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;height: 351px;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important">Search Engines</h2>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <div class="row">
                        <div class="col-lg-7">
                            <canvas id="srhEngBieChartContainer" height="200px"></canvas>
                        </div>
                        <div class="col-lg-5">
                            <div class="legendsContainer" id="srchEngLegContainer">

                            </div>


                        </div>
                    </div>
                </div>




            </div>
        </div>
    </div>

    <div class="row">
        <?php
        /*$countries_data = ahcpro_get_top_countries("","","","",true);*/
        $countries_data = array();
        if (isset($countries_data['data'])) {
            $countries = $countries_data['data'];
        } else {
            $countries = 0;
        }
        ?>
        <div class="col-lg-6">
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important">Traffic by countries<span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <table width="95%" border="0" cellspacing="0" id="traffic_by_countries">
                        <thead>
                            <tr>
                                <th width="10%"><?php echo ahc_rank ?></th>
                                <th width="15%"><?php echo ahc_flag ?></th>
                                <th width="45%"><?php echo ahc_country ?></th>
                                <th width="15%"><?php echo ahc_visitors ?></th>
                                <th width="15%"><?php echo ahc_visits ?></th>
                            </tr>
                        </thead>

                        <?php
                        $rank = 1;
                        $norecord = "";
                        if (is_array($countries) && count($countries) > 0) {
                        ?>
                            <tbody>
                                <?php
                                foreach ($countries as $country) {
                                ?>
                                    <tr>
                                        <td class="values"><?php echo $rank ?></td>
                                        <td class="values">
                                            <img src="<?php echo plugins_url('/images/flags/' . strtolower($country['ctr_internet_code']) . '.png', AHCPRO_PLUGIN_MAIN_FILE) ?>" border="0" alt="<?php echo $country['ctr_name'] ?>" width="30" height="20" onerror="imgFlagError(this)" />
                                        </td>
                                        <td class="values fineFont"><?php echo $country['ctr_name'] ?></td>
                                        <td class="values"><?php echo ahc_pro_NumFormat($country['visitors']); ?></td>
                                        <td class="values"><?php echo ahc_pro_NumFormat($country['visits']); ?></td>
                                    </tr>
                                <?php
                                    $rank++;
                                }
                                ?>
                            </tbody>
                        <?php
                        }

                        ?>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

        <div class="col-lg-6">
            <!-- Countries chart panel -->
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;height: 514px;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important">Top Referring Countries</h2>

                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <div class="row">
                        <div class="col-lg-7">
                            <canvas id="countriesPiechartContainer" height="200px"></canvas>
                        </div>
                        <div class="col-lg-5">
                            <div class="legendsContainer" id="countriesLegContainer"></div>
                        </div>
                    </div>
                    <!--canvas id="countriesPiechartContainer" height="200"></canvas-->
                </div>

            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">

            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important"><?php echo ahc_refering_sites ?><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important;">
                    <table width="95%" border="0" cellspacing="0" id="top_refering_sites">
                        <thead>
                            <tr>
                                <th width="70%"><?php echo ahc_site_name ?></th>
                                <th width="30%"><?php echo ahc_total_times ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $googlehits = 0;
                            $rets = '';
                            $norecord = "";
                            $referingSites = ahcpro_get_top_refering_sites();
                            if (is_array($referingSites) && count($referingSites) > 0) {
                                foreach ($referingSites as $site) {
                                    /*if (strpos($site['site_name'], 'google')) {
										$googlehits += $site['total_hits'];
									} else {
*/

                                    str_replace('https://', '', $site['site_name']);
                                    $rets .= '<tr>
							<td  class="values">' . $site['site_name'] . '&nbsp;<a href="https://' . str_replace('http://', '', $site['site_name']) . '" target="_blank"><img src="' . plugins_url('/images/openW.jpg', AHCPRO_PLUGIN_MAIN_FILE) . '" title="' . ahc_view_referer . '"></a></td>
							<td  class="values">' . $site['total_hits'] . '</td>
							</tr>';
                                    //}
                                }
                                if ($googlehits > 0) {
                                    /*
									echo '<tr>
							<td  class="values">www.google.com&nbsp;<a href="http://www.google.com" target="_blank"><img src="' . plugins_url('/images/openW.jpg', AHCPRO_PLUGIN_MAIN_FILE) . '" title="' . ahc_view_referer . '"></a></td>
							<td  class="values">' . $googlehits . '</td>
						  </tr>';
						  */
                                }
                                echo $rets;
                            } else {
                                $norecord = 1;
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php
                    if ($norecord == "1") {
                    ?>
                        <div class="no-record">No data available.</div>
                    <?php
                    }
                    ?>
                </div>
            </div>

        </div>



        <div class="col-lg-6">
            <!-- time visits graph begin -->
            <?php
            //$times = ahcpro_get_time_visits();
            $times = array();
            ?>
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important"><?php echo ahc_visits_time_graph ?><span class="search_data"><a href="#" class="dashicons dashicons-search" title="Search"></a></span><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                <div class="search-panel <?php echo (isset($_POST['section']) && $_POST['section'] == "visit_time") ? "open" : ''; ?>">
                    <form method="post" class="search_frm">
                        <label>Search : </label>
                        <input type="hidden" name="page" value="ahc_hits_counter_menu_pro" />
                        <input type="hidden" name="section" value="visit_time" />
                        <input type="text" readonly="readonly" placeholder="From Date" class="ahc_clear" name="vfrom_dt" id="vfrom_dt" autocomplete="off" value="<?php echo isset($_POST['vfrom_dt']) ? $_POST['vfrom_dt'] : ''; ?>" />
                        <input type="text" readonly="readonly" placeholder="To Date" class="ahc_clear" name="vto_dt" id="vto_dt" autocomplete="off" value="<?php echo isset($_POST['vto_dt']) ? $_POST['vto_dt'] : ''; ?>" />
                        <input type="submit" class="button button-primary" />
                        <input type="button" class="button button-primary clear_form" value="Clear" />
                    </form>
                </div>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important; padding-right: 50px;">
                    <table width="100%" border="0" cellspacing="0" id="visit_time_graph_table">
                        <thead>
                            <tr>
                                <th width="25%"><?php echo ahc_time ?></th>
                                <th width="55%"><?php echo ahc_visitors_graph ?></th>
                                <th width="10%"><?php echo ahc_visitors ?></th>
                                <th width="10%">Visits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (is_array($times)) {
                                foreach ($times as $t) {
                            ?>
                                    <tr>
                                        <td class="values"><?php echo $t['vtm_time_from'] . ' - ' . $t['vtm_time_to'] ?></td>
                                        <td class="values">
                                            <div class="visitorsGraphContainer">
                                                <div class="<?php
                                                            if (ceil($t['percent']) > 25 && ceil($t['percent']) < 50) {
                                                                echo 'visitorsGraph2';
                                                            } else if (ceil($t['percent']) > 50) {
                                                                echo 'visitorsGraph3';
                                                            } else {
                                                                echo 'visitorsGraph';
                                                            }
                                                            ?>" <?php echo (!empty($t['percent']) ? 'style="width: ' . ceil($t['percent']) . '%;"' : '') ?>>&nbsp;</div>
                                                <div class="cleaner"></div>
                                            </div>
                                            <div class="visitorsPercent">(<?php echo ceil($t['percent']) ?>)%..</div>
                                        </td>
                                        <td class="values"><?php echo $t['vtm_visitors'] ?></td>
                                        <td class="values"><?php echo $t['vtm_visits'] ?></td>
                                    </tr>
                            <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- traffic by title -->
        <div class="col-lg-6">
            <?php
            /*$tTitles = ahcpor_get_traffic_by_title();*/
            $tTitles = array();
            ?>
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important ; border-bottom:0 !important"><?php echo traffic_by_title ?><span class="search_data"><a href="#" class="dashicons dashicons-search" title="Search"></a></span><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>

                <div class="panelcontent" style="border-radius:0 0 7px 7px !important; padding-right: 50px;">
                    <table width="100%" border="0" cellspacing="0" id="traffic_by_title">
                        <thead>
                            <tr>
                                <th width="5%"><?php echo ahc_rank ?></th>
                                <th width="65%"><?php echo ahc_title ?></th>
                                <th width="15%"><?php echo ahc_hits ?></th>
                                <th width="15%"><?php echo ahc_percent ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $norecord = "";
                            if (is_array($tTitles) && count($tTitles) > 0) {
                                foreach ($tTitles as $t) {
                            ?>
                                    <tr>
                                        <td class="values"><?php echo $t['rank'] ?></td>
                                        <td class="values"><a href="<?php echo get_permalink($t['til_page_id']); ?>" target="_blank"><?php echo $t['til_page_title'] ?></a></td>
                                        <td class="values"><?php echo ahc_pro_NumFormat($t['til_hits']); ?></td>
                                        <td class="values"><?php echo $t['percent'] ?></td>
                                    </tr>
                            <?php
                                }
                            }

                            ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <?php
            /*$lastSearchKeyWordsUsed = ahcpro_get_latest_search_key_words_used();*/
            $lastSearchKeyWordsUsed = array();
            /*if ($lastSearchKeyWordsUsed) 
            {*/
            ?>
            <!-- last search key words used -->
            <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
                <h2 class="box-heading" style="border-radius: 7px 7px 0 0 !important; padding:12px 15px !important; border-bottom:0 !important"><?php echo ahc_latest_search_words; ?><span class="search_data"><a href="#" class="dashicons dashicons-search" title="Search"></a></span><span class="export_data"><a href="#" class="dashicons dashicons-external" title="Export Data"></a></span></h2>
                <div class="search-panel <?php echo (isset($_POST['section']) && $_POST['section'] == "lastest_search") ? "open" : ''; ?>">
                    <form method="post" class="search_frm">
                        <label>Search in Time Frame: </label>
                        <input type="hidden" name="page" value="ahc_hits_counter_menu_pro" />
                        <input type="hidden" name="section" value="lastest_search" />
                        <input type="text" readonly="readonly" placeholder="From Date" class="ahc_clear" name="from_dt" id="from_dt" autocomplete="off" value="<?php echo isset($_POST['from_dt']) ? $_POST['from_dt'] : ''; ?>" />
                        <input type="text" readonly="readonly" placeholder="To Date" class="ahc_clear" name="to_dt" id="to_dt" autocomplete="off" value="<?php echo isset($_POST['to_dt']) ? $_POST['to_dt'] : ''; ?>" />
                        <input type="submit" class="button button-primary" />
                        <input type="button" class="button button-primary clear_form" value="Clear" />
                    </form>
                </div>
                <div class="panelcontent" style="border-radius:0 0 7px 7px !important; padding-right: 50px;">
                    <table width="100%" border="0" cellspacing="0" id="lasest_search_words">
                        <thead>
                            <tr>
                                <th width="25%">Country/SE/Browser</th>
                                <th width="65%">Country/SE/Browser</th>
                                <th width="65%">Keyword</th>
                                <th width="10%" class='text-center'>Date</th>
                            </tr>
                        </thead>


                        <?php
                        if (count($lastSearchKeyWordsUsed) > 0) {
                        ?>
                            <tbody>
                                <?php
                                foreach ($lastSearchKeyWordsUsed as $searchWord) {
                                    $visitDate = new DateTime($searchWord['hit_date']);
                                    $visitDate->setTimezone($custom_timezone);
                                ?>
                                    <tr>
                                        <td>
                                            <span><?php if ($searchWord['ctr_internet_code'] != '') { ?><img src="<?php echo plugins_url('/images/flags/' . strtolower($searchWord['ctr_internet_code']) . '.png', AHCPRO_PLUGIN_MAIN_FILE); ?>" border="0" width="22" height="18" title="<?php echo $searchWord['ctr_name'] ?>" onerror="imgFlagError(this)" /><?php } ?></span>
                                            <span><img src="<?php echo plugins_url('/images/search_engines/' . $searchWord['srh_icon'], AHCPRO_PLUGIN_MAIN_FILE); ?>" border="0" width="22" height="22" title="<?php echo $searchWord['srh_name'] ?>" /></span>
                                            <span><img src="<?php echo plugins_url('/images/browsers/' . $searchWord['bsr_icon'], AHCPRO_PLUGIN_MAIN_FILE); ?>" border="0" width="20" height="20" title="<?php echo $searchWord['bsr_name'] ?>" /></span>
                                        </td>
                                        <td class="hide"><?php echo $searchWord['csb']; ?></td>
                                        <td>
                                            <span class="searchKeyWords"><a href="<?php echo $searchWord['hit_referer'] ?>" target="_blank"><?php echo $searchWord['hit_search_words'] ?></a></span>
                                        </td>
                                        <td>
                                            <span class="visitDateTime">&nbsp;<?php echo $visitDate->format('d/m/Y') ?></span>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        <?php
                        }

                        ?>
                    </table>
                </div>
            </div>
            <?php /*}*/ ?>
        </div>
    </div>

    <?php
    $visits_visitors_data = ahcpro_get_visits_by_custom_duration_callback($mystart_date, $myend_date, $stat = '');
    ?>


    <script language="javascript" type="text/javascript">
        //////// Ajax //////

        /*function GetXmlHttpObject()
        {
            var xmlHttp = null;
            try
            {
                // Firefox, Opera 8.0+, Safari
                xmlHttp = new XMLHttpRequest();
            } catch (e)
            {
                // Internet Explorer
                try
                {
                    xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
                } catch (e)
                {
                    xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
                }
            }
            return xmlHttp;
        }*/

        function ahc_getOnlineUsers() {
            /*xmlHttp = GetXmlHttpObject();
            if (xmlHttp == null)
            {
                alert("Your browser does not support AJAX!");
                return;
            }
            var url = "<?php echo plugins_url('/ajaxpages/getUsersOnline.php', AHCPRO_PLUGIN_MAIN_FILE) ?>";

            xmlHttp.onreadystatechange = ahc_showOnlineRes;
            xmlHttp.open("GET", url, true);
            xmlHttp.send(null);*/



            jQuery.ajax({
                type: 'GET',
                url: ahc_ajax.ajax_url,
                data: {
                    'action': 'ahcpro_countOnlineusers',
                },
                success: function(data) {
                    data = jQuery.parseJSON(data);
                    var counter = parseInt(jQuery('#onlinecounter').html());

                    jQuery('#onlinecounter').html(data);
                    jQuery('#up-down').removeClass('increase_counter');
                    jQuery('#up-down').removeClass('decrease_counter');

                    if (data < counter) {
                        jQuery('#up-down').addClass('decrease_counter');
                    }
                    if (data > counter) {
                        jQuery('#up-down').addClass('increase_counter');
                    }

                },
                error: function(data) {
                    console.log(data);
                }
            });
        }
        /*function ahc_showOnlineRes()
        {
            if (xmlHttp.responseText != 'x')
            {
                if (xmlHttp.readyState == 4)
                {
                    var old_online = document.getElementById("onlinecounter").innerHTML;
                    if (parseFloat(old_online) < parseFloat(xmlHttp.responseText))
                    {
                        document.getElementById("up-down").innerHTML = '&nbsp&nbsp<img src="<?php echo plugins_url('/images/increase.png', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp&nbsp';
                        document.getElementById('onlinecounter').innerHTML = xmlHttp.responseText;
                    } else if (parseFloat(old_online) > parseFloat(xmlHttp.responseText))
                    {
                        document.getElementById("up-down").innerHTML = '&nbsp&nbsp<img src="<?php echo plugins_url('/images/decrease.png', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp&nbsp';
                        document.getElementById('onlinecounter').innerHTML = xmlHttp.responseText;
                    }
                }
            }
        }*/

        setInterval(ahc_getOnlineUsers, <?php echo AHCPRO_AJAX_CHECK ?>)

        /*function getVisitsByDate( start_date, end_date, interval ){
            var visitsData = '';
            jQuery.ajax({
                url : ahc_ajax.ajax_url,
                data: {
                    'action' : 'ahcpro_get_visits_by_custom_duration',
                    'start_date' : start_date,
                    'end_date' : end_date,
                    'interval' : interval
                },
                method : 'post',
                async: false,
                success : function(res){
                    if(res){
                        visitsData = jQuery.parseJSON(res);
                    }
                 }
            });
            
            return visitsData;
        }*/

        /*function getVisitorsByDate( start_date, end_date, interval ){
            var visitorsData = '';
            jQuery.ajax({
                url : ahc_ajax.ajax_url,
                data: {
                    'action' : 'ahcpro_get_visitors_by_custom_duration',
                    'start_date' : start_date,
                    'end_date' : end_date,
                    'interval' : interval
                },
                method : 'post',
                async: false,
                success : function(res){
                    if(res){
                        visitorsData = jQuery.parseJSON(res);
                    }
                 }
            });
            
            return visitorsData;
        }*/

        function drawVisitsLineChart(start_date, end_date, interval, visitors, visits, duration) {

            var visit_chart;

            var visit_data_line = visits;
            var visitor_data_line = visitors;

            jQuery(document).ready(function() {
                var high_visit = 0;
                for (var k = 0; k < visit_data_line.length; k++) {
                    if (high_visit < parseInt(visit_data_line[k][1])) {
                        high_visit = parseInt(visit_data_line[k][1]);
                    }
                }
                if (high_visit > 5) {
                    high_visit = high_visit + 5;
                }

                var interval_formatString;
                if (duration == '365') {
                    interval_formatString = '%b';
                } else if (duration == '0') {
                    interval_formatString = '%b/%Y';
                } else {
                    interval_formatString = '%d/%m';
                }

                var numberTicks_val = visit_data_line.length;

                jQuery('#visitscount').empty();
                //var visit_data_line = getVisitsByDate( full_start_date, full_end_date, interval );
                //var visitor_data_line = getVisitorsByDate( full_start_date, full_end_date, interval );

                visit_chart = jQuery.jqplot('visitscount', [visit_data_line, visitor_data_line], {
                    title: {
                        text: '',
                        fontSize: '10px',
                        fontFamily: 'Tahoma',
                        textColor: '#000000',
                    },
                    axes: {
                        xaxis: {
                            min: start_date,
                            max: end_date,
                            //tickInterval: interval,
                            numberTicks: numberTicks_val,
                            renderer: jQuery.jqplot.DateAxisRenderer,
                            tickRenderer: jQuery.jqplot.CanvasAxisTickRenderer,
                            tickOptions: {
                                angle: -40,
                                formatString: interval_formatString,
                                showGridline: true,
                            },
                        },
                        yaxis: {
                            min: 0,
                            max: high_visit,
                            padMin: 1.0,
                            label: '',
                            labelRenderer: jQuery.jqplot.CanvasAxisLabelRenderer,
                            labelOptions: {
                                angle: -100,
                                fontSize: '12px',
                                fontFamily: 'Tahoma',
                                fontWeight: 'bold',
                            },
                            tickOptions: {
                                formatString: "%d"
                            }
                        }
                    },
                    legend: {
                        show: true,
                        location: 's',
                        placement: 'outsideGrid',
                        labels: ['Visit', 'Visitor'],
                        renderer: jQuery.jqplot.EnhancedLegendRenderer,
                        rendererOptions: {
                            numberColumns: 2,
                            disableIEFading: false,
                            border: 'none',
                        },
                    },
                    highlighter: {
                        show: true,
                        bringSeriesToFront: true,
                        tooltipAxes: 'xy',
                        formatString: '%s:&nbsp;<b>%i</b>&nbsp;',
                        tooltipContentEditor: tooltipContentEditor,
                    },
                    grid: {
                        drawGridlines: true,
                        borderColor: 'transparent',
                        shadow: false,
                        drawBorder: false,
                        shadowColor: 'transparent'
                    },
                });

                function tooltipContentEditor(str, seriesIndex, pointIndex, plot) {
                    // display series_label, x-axis_tick, y-axis value
                    return "<b>" + plot.legend.labels[seriesIndex] + "</b><br>" + str;

                }

                jQuery(window).resize(function() {
                    JQPlotVisitChartLengendClickRedraw()
                });

                function JQPlotVisitChartLengendClickRedraw() {
                    /*visit_chart.replot({resetAxes: ['yaxis']});*/
                    visit_chart.replot();

                    jQuery('div[id="visitscount"] .jqplot-table-legend').click(function() {
                        JQPlotVisitChartLengendClickRedraw();
                    });
                }

                jQuery('div[id="visitscount"] .jqplot-table-legend').click(function() {
                    JQPlotVisitChartLengendClickRedraw()
                });
            });
        }


        var mystart_date = "<?php echo $mystart_date; ?>";
        var myend_date = "<?php echo $myend_date; ?>";
        var mystart_date_full = "<?php echo $mystart_date_full; ?>";
        var myend_date_full = "<?php echo $myend_date_full; ?>";
        var browsersData = <?php echo json_encode(ahcpro_get_browsers_hits_counts()); ?>;
        var srhEngVisitsData = <?php echo json_encode(ahcpro_get_serch_visits_by_date()); ?>;
        var countriesData = <?php echo json_encode(ahcpro_get_top_countries(10, "", "", "", false)); ?>;
        var visits_data = <?php echo json_encode($visits_visitors_data['visits']); ?>;
        var visitors_data = <?php echo json_encode($visits_visitors_data['visitors']); ?>;


        jQuery(document).ready(function() {



            jQuery('#duration_area').hide();

            //------------------------------------------
            //if(visitsData.success && typeof visitsData.data != 'undefined'){
            var duration = jQuery('#hits-duration').val();
            drawVisitsLineChart(mystart_date, myend_date, '1 day', visitors_data, visits_data, duration);
            //}
            //------------------------------------------
            if (browsersData.success && typeof browsersData.data != 'undefined' && typeof drawBrowsersBieChart === "function") {
                drawBrowsersBieChart(browsersData.data);
            }
            //------------------------------------------
            if (srhEngVisitsData.success && typeof srhEngVisitsData.data != 'undefined' && typeof drawSrhEngVstLineChart === "function") {
                drawSrhEngVstLineChart(srhEngVisitsData);
            }
            //------------------------------------------
            if (countriesData.success && typeof countriesData.data != 'undefined' && typeof drawCountriesPieChart === "function") {
                drawCountriesPieChart(countriesData.data);
            }
            //------------------------------------------

        });

        jQuery(document).on('change', '#hits-duration', function() {


            var self = jQuery(this);
            var duration = self.val();
            if (duration == 'range') {
                jQuery('#duration_area').show();

            } else {
                jQuery('#duration_area').hide();

                jQuery('#visitors_graph_stats').addClass('loader');
                jQuery.ajax({
                    url: ahc_ajax.ajax_url,
                    data: {
                        action: 'ahcpro_get_hits_by_custom_duration',
                        'hits_duration': duration
                    },
                    method: 'post',
                    success: function(res) {
                        if (res) {
                            var data = jQuery.parseJSON(res);

                            var start_date = data.mystart_date;
                            var end_date = data.myend_date;
                            var full_start_date = data.full_start_date;
                            var full_end_date = data.full_end_date;
                            var interval = data.interval;
                            var visitors = JSON.parse(data.visitors_data);
                            var visits = JSON.parse(data.visits_data);

                            drawVisitsLineChart(start_date, end_date, interval, visitors, visits, duration);
                            jQuery('#visitors_graph_stats').removeClass('loader');
                            return false;
                        }
                    }
                });
            }
        });

        jQuery(document).on('change', '#summary_from_dt, #summary_to_dt', function() {
            var self = jQuery(this);
            var duration = jQuery('#summary_from_dt').val() + '#' + self.val();

            if (jQuery('#summary_to_dt').val() != '') {
                jQuery('#visitors_graph_stats').addClass('loader');

                jQuery.ajax({
                    url: ahc_ajax.ajax_url,
                    data: {
                        action: 'ahcpro_get_hits_by_custom_duration',
                        'hits_duration_from': jQuery('#summary_from_dt').val(),
                        'hits_duration_to': jQuery('#summary_to_dt').val(),
                        'hits_duration': 'range'
                    },
                    method: 'post',
                    success: function(res) {
                        if (res) {
                            var data = jQuery.parseJSON(res);
                            //console.log(data);
                            var start_date = data.full_start_date;
                            var end_date = data.full_end_date;
                            var full_start_date = data.full_start_date;
                            var full_end_date = data.full_end_date;
                            var interval = data.interval;
                            var visitors = JSON.parse(data.visitors_data);
                            var visits = JSON.parse(data.visits_data);
                            // console.log(visitors);
                            // console.log(visits);
                            drawVisitsLineChart(start_date, end_date, interval, visitors, visits, 'range');
                            jQuery('#visitors_graph_stats').removeClass('loader');
                            return false;
                        }
                    }
                });
            }
        });

        document.getElementById('today_visitors_box').innerHTML = (document.getElementById('today_visitors').innerHTML);
        //document.getElementById('today_visitors_detail_cnt').innerHTML = (document.getElementById('today_visitors').innerHTML);
        document.getElementById('today_visits_box').innerHTML = (document.getElementById('today_visits').innerHTML);
        document.getElementById('today_search_box').innerHTML = (document.getElementById('today_search').innerHTML);
    </script>