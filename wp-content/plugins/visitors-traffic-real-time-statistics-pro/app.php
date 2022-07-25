<?php
if (!class_exists('ahcpro_log_APP_PRO')) {
    class ahcpro_log_APP_PRO
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'ahcpro_options_page'));
            add_action('admin_enqueue_scripts', array($this, 'my_enqueue'));
        }

        function my_enqueue($hook)
        {
            if (strpos($hook, 'ahcpro_app') !== false) {
				 wp_register_style('ahc_custom_css', plugins_url('/css/custom.css', AHCPRO_PLUGIN_MAIN_FILE), '', time());
    wp_enqueue_style('ahc_custom_css');
                wp_enqueue_script('datatables_log', plugin_dir_url(__FILE__) . '/assets/js/datatables.min.js', array('jquery'));
                wp_enqueue_script('datatables_log_responsive', plugin_dir_url(__FILE__) . '/assets/js/dataTables.responsive.min.js', array('jquery'));
                wp_enqueue_style('datatables', plugin_dir_url(__FILE__) . '/assets/css/datatables.min.css');
                wp_enqueue_style('responsive_dataTables', plugin_dir_url(__FILE__) . '/assets/css/responsive.dataTables.min.css');
                wp_enqueue_style('failed_admin-pro-css', plugin_dir_url(__FILE__)  . '/assets/css/admin-css.css?re=1.1.1');
            }

            //wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . '/myscript.js');
        }

        public function ahcpro_options_page()
        {
            add_submenu_page('', __('APP', 'ahcpro'), __('APP', 'ahcpro'), 'manage_options', 'ahcpro_app', array($this, "ahcpro_log"));
        }
        function update_notice()
        {
?>
            <div class="updated notice">
                <p><?php _e('The operation completed successfully.', 'ahcpro'); ?></p>
            </div>
        <?php
        }
        function error_notice()
        {
        ?>
            <div class="error notice">
                <p><?php _e('There has been an error. !', 'ahcpro'); ?></p>
            </div>
            <?php
        }
        public function ahcpro_log()
        {
            require_once(AHCPRO_PLUGIN_ROOT_DIR . '/phpqrcode/qrlib.php');
            $app_log_key = get_option('AHCPRO_app_log_key');
            $AHCPRO_log_active = get_option('AHCPRO_log_active');
            if (isset($_GET['delete_id']) && $_GET['delete_id'] != '') {
                $options = get_option('AHCPRO_device_information');
                $options = array_values($options);
                $removeKey = array_search(sanitize_text_field($_GET['delete_id']), array_column($options, 'id'));
                if (!empty($removeKey)) {
                    unset($options[$removeKey]);
                    update_option('AHCPRO_device_information', $options);
                    $this->update_notice();
                } else {
                    $this->error_notice();
                }
            }
            if (isset($_GET['reset_api']) || $app_log_key == '') {
                $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $charactersLength = strlen($characters);
                $app_log_key = '';
                $length = 25;
                for ($i = 0; $i < $length; $i++) {
                    $app_log_key .= $characters[rand(0, $charactersLength - 1)];
                }
                update_option('AHCPRO_app_log_key', $app_log_key);
                update_option('AHCPRO_device_information', '');
            ?>
                <script>
                    window.location.href = "<?php echo esc_js(admin_url() . 'admin.php?page=ahcpro_app'); ?>";
                </script>
            <?php
            }
            if (isset($_POST) && !empty($_POST)) {
                if (isset($_POST['active_app'])) {
                    update_option('AHCPRO_log_active', 1);
                    $AHCPRO_log_active = 1;
                } else {
                    update_option('AHCPRO_log_active', 0);
                    $AHCPRO_log_active = 0;
                }
            }
            $data_send = array('key' => $app_log_key, 'url' => site_url(), 'apiUrl' => get_rest_url(), 'title' => get_bloginfo('name'), 'site_icon_url' => "", 'userID' => get_current_user_id());



            $text = json_encode($data_send);

            $path = AHCPRO_PLUGIN_ROOT_DIR . '/assets/images/';
            $imgPath = uniqid() . ".png";
            $file = $path . $imgPath;

            $ecc = 'L';
            $pixel_Size = 4;
            $frame_Size = 4;

            QRcode::png($text, $file, $ecc, $pixel_Size, $frame_Size);

            ?>
            <div class="ahc_main_container">
                <h1><img width="40px" src="<?php echo plugins_url('/images/logo.png', AHCPRO_PLUGIN_MAIN_FILE) ?>">&nbsp;Visitor Traffic (Mobile App) <a title="change settings" href="admin.php?page=ahc_hits_counter_settings"><img src="<?php echo plugins_url('/images/settings.jpg', AHCPRO_PLUGIN_MAIN_FILE) ?>" /></a></h1><br />
                <div class="panel" style="border-radius: 7px !important;
    border: 0 !important;  box-shadow: 0 4px 25px 0 rgb(168 180 208 / 10%) !important; background: #fff;
    padding: 50px;
    width: 87%;">
                    <div class="parent grid-Qr" style=" ">
                        <div>
                            <h2 class="box-heading">APP Settings</h2>

                            <p>Step 1: Activate the "Mobile App" using the below option</p>
                            <p>Step 1: Download the App (<a target="_blank" href="https://play.google.com/store/apps/details?id=com.codepress.trafic.trafic_static_app">click here</a>)</p>
                            <p>Step 2: Open the App and scan the QR cdoe</p>

                            <form action="#" method="post">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr class="ahcpro_row">
                                            <th scope="row"><label for="ahcpro_active_app"><?php echo __('Mobile App', 'ahcpro'); ?></label></th>
                                            <td>
                                                <span class="on_off "><?php echo __('OFF', 'ahcpro'); ?></span>
                                                <label class="switch">
                                                    <input type="checkbox" value="1" id="ahcpro_active_app" data-custom="1" name="active_app" <?php if ($AHCPRO_log_active == 1) {
                                                                                                                                                    echo 'checked="checked"';
                                                                                                                                                } ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                                <span class="on_off "><?php echo __('ON', 'ahcpro'); ?></span>
                                                <p class="description"> </p>

                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p class="submit">
                                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'ahcpro'); ?>">
                                    <a href="<?php esc_attr_e(admin_url() . 'admin.php?page=ahcpro_app&reset_api=1'); ?>" onclick="if (! confirm('<?php esc_attr_e('change key ?', 'ahcpro'); ?>')) { return false; }" id="submit" class="button button-default"><?php echo __('Change Key', 'ahcpro'); ?></a>
                                </p>
                            </form>

                        </div>
                        <div>
                            <?php
                            if ($AHCPRO_log_active == 1) {
                                $path = $file;
                                $type = pathinfo($path, PATHINFO_EXTENSION);
                                $data = file_get_contents($path);
                                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                                echo "<center><img src='" . $base64 . "'></center>";
                                if (file_exists($path)) {
                                    unlink($path);
                                }
                            }

                            $options = get_option('AHCPRO_device_information');

                            ?>
                        </div>
                    </div>
                    <div class="panelcontent">


                        <table id="table" class="display responsive nowrap failed_login_rep" style="width:100%">
                            <thead>
                                <tr role="row" style="background-color:#ffffff; text-align:left">
                                    <th><?php echo __('Username', 'ahcpro'); ?></th>
                                    <th><?php echo __('Device Id', 'ahcpro'); ?></th>
                                    <th><?php echo __('Device Name', 'ahcpro'); ?></th>
                                    <th><?php echo __('Device Model', 'ahcpro'); ?></th>
                                    <th><?php echo __('Connection Date', 'ahcpro'); ?></th>
                                    <th><?php echo __('Delete', 'ahcpro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($options)) { ?>
                                    <?php foreach ($options as $option) { ?>
                                        <tr id="row_3" role="row" class="odd">
                                            <?php $user = get_user_by('id', $option['userId']);; ?>
                                            <td class="dtr-control"><?php esc_html_e($user->user_login); ?></td>
                                            <td><?php esc_html_e($option['deviceId']); ?></td>
                                            <td><?php esc_html_e($option['deviceName']); ?></td>
                                            <td><?php esc_html_e($option['deviceModel']); ?></td>
                                            <td><?php esc_html_e($option['date']); ?></td>
                                            <td><a href="<?php esc_attr_e(admin_url() . 'admin.php?page=ahcpro_app&'); ?>delete_id=<?php esc_attr_e($option['id']); ?>" href="<?php esc_attr_e(admin_url() . 'admin.php?page=ahcpro_app&delete_connection_id=');
                                                                                                                                                                                esc_attr_e($option['id']); ?>" onclick="if (! confirm('<?php esc_attr_e('Delete Device?', 'ahcpro'); ?>')) { return false; }" class="delete_block_link"><?php echo __('Delete', 'ahcpro'); ?></a></td>
                                        </tr>
                                    <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>

                        <input type="button" name="cancel" value="back to dashboard" onclick="javascript:window.location.href = 'admin.php?page=ahc_hits_counter_menu_pro'" style=" background-color: #e7e7e7; color: black;
  border: none;
  cursor: pointer;
  padding: 15px 32px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 16px;
  border-radius:4px ;
  margin-top:10px" />

                        <script>
                            jQuery(document).ready(function($) {
                                var jobtable = jQuery('#table').DataTable();
                            });
                        </script>
                    </div>
                </div>
    <?php


        }
    }
    new ahcpro_log_APP_PRO();
}
