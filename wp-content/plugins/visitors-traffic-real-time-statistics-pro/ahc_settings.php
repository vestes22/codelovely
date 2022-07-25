<script src="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.25.0/slimselect.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/slim-select/1.25.0/slimselect.min.css" rel="stylesheet"></link>

<?php


if (!current_user_can('manage_options')) {
	die('Sorry, you are not allowed to access this page.');
	return;
}


?>
<script language="javascript" type="text/javascript">
	function imgFlagError(image) {
		image.onerror = "";
		image.src = "<?php echo plugins_url('/images/flags/noFlag.png', AHCPRO_PLUGIN_MAIN_FILE) ?>";
		return true;
	}
</script>
<style type="text/css">
	/* Desktops and laptops ----------- */
	@media only screen and (min-width : 1224px) {
		.ahcpro_app {
			position: absolute;
		}
	}


	i {
		color: #999
	}

	.panel {
		width: 90% !important;
		margin-bottom: 10px;
		border: 1px solid transparent;
		padding: 20px;


	}

	a,
	div {
		outline: 0;
	}

	body {
		background-color: #F1F1F1 !important;
	}


	input[type=checkbox] {
		border: 1px solid #7e8993;
		border-radius: 4px;
		background: #fff;
		color: #555;
		clear: none;
		cursor: pointer;
		display: inline-block;
		line-height: 0;
		height: auto !important;
		margin: -.25rem .25rem 0 0;
		outline: 0;
		padding: 0 !important;
		text-align: center;
		vertical-align: middle;
		width: auto !important;
		min-width: auto !important;
		-webkit-appearance: auto !important;
		box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1);
		transition: .05s border-color ease-in-out;
	}
</style>
<?php
ahcpro_include_scripts();
$msg = '';
if (!empty($_POST['save'])) {
	if (ahcpro_savesettings()) {
		$msg = ('<br /><b style="color:green; margin-left:30px; float:left">settings saved successfully</b><br /><b style=" margin-left:30px; float:left"><a href="admin.php?page=ahc_hits_counter_settings">back to settings</a> | <a href="admin.php?page=ahc_hits_counter_menu_pro">back to dashboard</a></b>');
	}
}
$ahcpro_get_save_settings = ahcpro_get_save_settings();
$hits_days = isset($ahcpro_get_save_settings[0]->set_hits_days)?$ahcpro_get_save_settings[0]->set_hits_days: 14;
$ajax_check = isset($ahcpro_get_save_settings[0]->set_ajax_check)?($ahcpro_get_save_settings[0]->set_ajax_check * 1000):10*1000;
$set_ips = isset($ahcpro_get_save_settings[0]->set_ips)?$ahcpro_get_save_settings[0]->set_ips:"";
$set_google_map = isset($ahcpro_get_save_settings[0]->set_google_map)?$ahcpro_get_save_settings[0]->set_google_map:'';
$delete_plugin_data = get_option('ahcpro_delete_plugin_data_on_uninstall');
$ahcpro_save_ips = get_option('ahcpro_save_ips_opn');
$ahcproUserRoles = get_option('ahcproUserRoles');
$ahcproExcludeRoles = get_option('ahcproExcludeRoles');
$ahcproRobots = get_option('ahcproRobots');
$ahcpro_haships = get_option('ahcpro_haships');
$ahcpro_hide_top_bar_icon = get_option('ahcpro_hide_top_bar_icon');

?>


<div class="ahc_main_container" style=" margin:20px; width:auto; padding:20px; border-radius:20px">
	<h1><img width="40px" src="<?php echo plugins_url('/images/logo.png', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp;Visitor Traffic Real Time Statistics pro <a title="change settings" href="admin.php?page=ahc_hits_counter_settings"><img src="<?php echo plugins_url('/images/settings.jpg', AHCPRO_PLUGIN_MAIN_FILE) ?>" /></a></h1><br />
	<div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important;">
		<h2 class="box-heading">Settings</h2>
		<div class="panelcontent">
			<form method="post" enctype="multipart/form-data" name="myform">
			
			<div class="row">
			
				<!-- left side -->
				<div class="form-group col-md-6">
				<label for="exampleFormControlSelect1">Select Timezone</label>
						<select class="form-control" id="set_custom_timezone" name="set_custom_timezone">

							<?php

							$wp_timezone_string = get_option('timezone_string');
							$custom_timezone_offset = (get_option('ahcpro_custom_timezone') != '') ?  get_option('ahcpro_custom_timezone') : $wp_timezone_string;

							$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
							foreach ($timezones as $key => $value) {
							?>
								<option value="<?php echo $value; ?>" <?php echo ($value == $custom_timezone_offset) ? 'selected' : ''; ?>><?php echo $value; ?></option>
							<?php
							}
							?>
						</select>
						<br><span style="color:red; font-size:13px; ">Please select the same timezone in your</span> <a style="font-size:13px; " href="options-general.php" target="_blank">general settings page</a>
						
						
						<br />
						<br />
						
						
						<label for="exampleInput">check for online users every</label>
						<input type="text" value="<?php echo ($ajax_check / 1000) ?>" class="form-control" id="set_ajax_check" name="set_ajax_check" placeholder="Enter number of days">
						<small id="Help" class="form-text text-muted">Enter total seconds. default: 10 seconds</small>


						<br />
						<br />
						
						
						<label for="exampleInput">show hits in last</label>
						<input type="text" value="<?php echo $hits_days ?>" class="form-control" id="set_hits_days" name="set_hits_days" placeholder="Enter number of days">
						<small id="Help" class="form-text text-muted">this will affect the chart in the statistics page. default: 14 day</small>
				

						<br />
						<br />
						
						
						
						
						<label for="exampleInput">Map will display</label>
						<select class="form-control" name="set_google_map" id="set_google_map">
							<option value="all" <?php
												if ($set_google_map == 'all') {
													echo 'selected=selected';
												}
												?>>All time visitors</option>

							<option value="today_visitors" <?php
															if ($set_google_map == 'today_visitors') {
																echo 'selected=selected';
															}
															?>>Today visitors per country</option>

							<option value="top10" <?php
													if ($set_google_map == 'top10') {
														echo 'selected=selected';
													}
													?>>Top 10 countries</option>
							<option value="online" <?php
													if ($set_google_map == 'online') {
														echo 'selected=selected';
													}
													?>>Online Visitors</option>


							<option value="this_month" <?php
														if ($set_google_map == 'this_month') {
															echo 'selected=selected';
														}
														?>>This Month Visitors</option>


							<option value="past_month" <?php
														if ($set_google_map == 'past_month') {
															echo 'selected=selected';
														}
														?>>Past Month Visitors</option>
						</select>
						
						
						<br />
						<br />
						
						<label for='exampleInput'>Plugin Accessibility</label><br>
						<?php
						$html = '';

						global $wp_roles;
						if (!isset($wp_roles)) $wp_roles = new WP_Roles();
						$capabilites = array();
						$available_roles_names = $wp_roles->get_names(); //we get all roles names
						$available_roles_capable = array();
						foreach ($available_roles_names as $role_key => $role_name) { //we iterate all the names
							$role_object = get_role($role_key); //we get the Role Object
							$array_of_capabilities = $role_object->capabilities; //we get the array of capabilities for this role

							$available_roles_capable[$role_key] = $role_name; //we populate the array of capable roles

						}

						$html .= '';
						$UserRoles = get_option('ahcproUserRoles');

						$UserRoles_arr = explode(',', $UserRoles);
						$html .= "<select id='ahcproUserRoles' name='ahcproUserRoles[]' multiple='true' style='width:100%;'>";
						foreach ($available_roles_capable as $role) {
							$translated_role_name = $role;
							if (in_array($translated_role_name, $UserRoles_arr) or $translated_role_name == 'Administrator' or $translated_role_name == 'Super Admin') {
								$selected_value = 'selected=selected';
							} else {
								$selected_value = '';
							}
							$html .= "<option " . $selected_value . " value='" . $translated_role_name . "'>" . $translated_role_name . "</option>";
						}

						$html .= '</select>';


						echo $html;
						?>

						<script language="javascript" type="text/javascript">
							new SlimSelect({
								select: '#ahcproUserRoles'
							})
						</script>
						
						
						
						
						
						
						<br />
						<br />
						
						<label for="exampleInput">Stats Data</label>
						<p> <label style="color:red"><input type="checkbox" value="1" name="delete_plugin_data" <?php echo ($delete_plugin_data == 1) ? 'checked=checked' : ''; ?>> If checked, all the stats will be deleted on deleting plugin. </label></p>
						
						
						
						<br />
						<br />
						
						<label for="exampleInput">Hash IPs</label>
						<p> <label><input type="checkbox" value="1" name="ahcpro_haships" <?php echo ($ahcpro_haships == 1) ? 'checked=checked' : ''; ?>> If checked, We will hide the last 3 digits in all IP's. </label></p>
						
						
						
						<br />
						<br />
						<label for="exampleInput">Hide Top Bar Icon</label>
						<p> <label><input type="checkbox" value="1" name="ahcpro_hide_top_bar_icon" <?php echo ($ahcpro_hide_top_bar_icon == 1) ? 'checked=checked' : ''; ?>> If checked, We will hide the top bar icon. </label></p>
						
						

				</div>
				<!-- end left side -->
				
				
				
				<!-- right side -->
				<div class="form-group col-md-6">
				
				
				
						
						<label for='exampleInput'>User Role Exclusion From Statistics</label><br>
						<?php
						$html = '';

						global $wp_roles;
						if (!isset($wp_roles)) $wp_roles = new WP_Roles();
						$capabilites = array();
						$available_roles_names = $wp_roles->get_names(); //we get all roles names
						$available_roles_capable = array();
						foreach ($available_roles_names as $role_key => $role_name) { //we iterate all the names
							$role_object = get_role($role_key); //we get the Role Object
							$array_of_capabilities = $role_object->capabilities; //we get the array of capabilities for this role

							$available_roles_capable[$role_key] = $role_name; //we populate the array of capable roles

						}

						$html .= '';
						$UserRoles = get_option('ahcproExcludeRoles');

						$UserRoles_arr = explode(',', $UserRoles);
						$html .= "<select id='ahcproExcludeRoles' name='ahcproExcludeRoles[]' multiple='true' style='width:100%;'>";
						foreach ($available_roles_capable as $role) {
							$translated_role_name = $role;
							if (in_array($translated_role_name, $UserRoles_arr) ) {
								$selected_value = 'selected=selected';
							} else {
								$selected_value = '';
							}
							$html .= "<option " . $selected_value . " value='" . $translated_role_name . "'>" . $translated_role_name . "</option>";
						}

						$html .= '</select>';


						echo $html;
						?>

						<script language="javascript" type="text/javascript">
							new SlimSelect({
								select: '#ahcproExcludeRoles'
							})
						</script>
						
				
						<br />
						<br />
						
						
				<label for="exampleInput">IP Addresses Exclusion</label>
						<textarea placeholder="192.168.0.1&#10;192.168.0.2" name="set_ips" id="set_ips" rows="3" class="form-control"><?php echo $set_ips ?></textarea>
						<small id="Help" class="form-text text-muted">One IP per line</small>
					
					
						<br />
						<br />
				
				
						<label>Mobile App</label>
						<p>Step 1: Download the App (<a target="_blank" href="https://play.google.com/store/apps/details?id=com.codepress.trafic.trafic_static_app">click here</a>)</p>
						<p>Step 2: Activate the Mobile App to generate QR Code (<a href="<?php echo esc_url(admin_url('/admin.php?page=ahcpro_app')) ?>">click here</a>)</p>
						<p>Step 3: Open the App and scan the QR Cdoe </p>

						

					
				</div>
				<!-- end right side -->
			</div>
			

<input type="submit" name="save" value="save settings" style=" background-color: #4CAF50; /* Green */
  border: none;
  color: white;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  border-radius:5px" />
		<input type="button" name="cancel" value="back to dashboard" onclick="javascript:window.location.href = 'admin.php?page=ahc_hits_counter_menu_pro'" style=" background-color: #e7e7e7; color: black;
  border: none;

  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  border-radius:5px" />


</form>
		</div>

<?php
echo $msg;
?>
		


	</div>

</div>

