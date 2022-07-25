<?php

if ( !class_exists( 'FooBox_Free_Settings' ) ) {

	class FooBox_Free_Settings {

		function __construct() {
			add_filter('foobox-free-admin_settings', array($this, 'create_settings'));

			add_action( 'foobox-free-settings-sidebar', array( $this, 'settings_sidebar' ) );
		}

		function create_settings() {
			//region General Tab
			$tabs['general'] = __('General', 'foobox-image-lightbox');

			$sections['attach'] = array(
				'tab' => 'general',
				'name' => __('What do you want to attach FooBox to?', 'foobox-image-lightbox')
			);

			$settings[] = array(
				'id'      => 'enable_galleries',
				'title'   => __( 'WordPress Galleries', 'foobox-image-lightbox' ),
				'desc'    => __( 'Enable FooBox for all WordPress image galleries.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'section' => 'attach',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'enable_captions',
				'title'   => __( 'WordPress Images With Captions', 'foobox-image-lightbox' ),
				'desc'    => __( 'Enable FooBox for all WordPress images that have captions.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'section' => 'attach',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'enable_attachments',
				'title'   => __( 'Attachment Images', 'foobox-image-lightbox' ),
				'desc'    => __( 'Enable FooBox for all media images included in posts or pages.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'section' => 'attach',
				'tab'     => 'general'
			);

			$sections['settings'] = array(
				'tab' => 'settings',
				'name' => __('Display Settings', 'foobox-image-lightbox')
			);

			$settings[] = array(
				'id'      => 'fit_to_screen',
				'title'   => __( 'Fit To Screen', 'foobox-image-lightbox' ),
				'desc'    => __( 'Force smaller images to fit the screen dimensions.', 'foobox-image-lightbox' ),
				'default' => 'off',
				'type'    => 'checkbox',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'hide_scrollbars',
				'title'   => __( 'Hide Page Scrollbars', 'foobox-image-lightbox' ),
				'desc'    => __( 'Hide the page\'s scrollbars when FooBox is visible.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'show_count',
				'title'   => __( 'Show Counter', 'foobox-image-lightbox' ),
				'desc'    => __( 'Shows a counter under the FooBox modal when viewing a gallery of images.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'count_message',
				'title'   => __( 'Count Message', 'foobox' ),
				'desc'    => __( 'the message to use as the item counter. The fields <code>%index</code> and <code>%total</code> can be used to substitute the correct values. <br/ >Example : <code>item %index / %total</code> would result in <code>item 1 / 7</code>', 'foobox-image-lightbox' ),
				'default' => 'item %index of %total',
				'type'    => 'text',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'hide_caption',
				'title'   => __( 'Hide Captions', 'foobox' ),
				'desc'    => __( 'Whether or not to hide captions for images.', 'foobox' ),
				'type'    => 'checkbox',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'captions_show_on_hover',
				'title'   => __( 'Show Captions On Hover', 'foobox-image-lightbox' ),
				'desc'    => __( 'Only show the caption when hovering over the image.', 'foobox-image-lightbox' ),
				'type'    => 'checkbox',
				'section' => 'settings',
				'tab'     => 'general'
			);

			$settings[] = array(
				'id'      => 'error_message',
				'title'   => __( 'Error Message', 'foobox-image-lightbox' ),
				'desc'    => __( 'The error message to display when an image has trouble loading.', 'foobox-image-lightbox' ),
				'default' => __( 'Could not load the item', 'foobox-image-lightbox' ),
				'type'    => 'text',
				'section' => 'settings',
				'tab'     => 'general'
			);

			//endregion

			//region Advanced Tab

			$tabs['advanced'] = __('Advanced', 'foobox-image-lightbox');

			$settings[] = array(
				'id'      => 'close_overlay_click',
				'title'   => __( 'Close On Overlay Click', 'foobox-image-lightbox' ),
				'desc'    => __( 'Should the FooBox lightbox close when the overlay is clicked.', 'foobox-image-lightbox' ),
				'default' => 'on',
				'type'    => 'checkbox',
				'tab'     => 'advanced'
			);

			$settings[] = array(
				'id'      => 'disable_others',
				'title'   => __( 'Disable Other Lightboxes', 'foobox-image-lightbox' ),
				'desc'    => __( 'Certain themes and plugins use a hard-coded lightbox, which make it very difficult to override.<br>By enabling this setting, we inject a small amount of javascript onto the page which attempts to get around this issue.<br>But please note this is not guaranteed, as we cannot account for every lightbox solution out there :)', 'foobox-image-lightbox' ),
				'type'    => 'checkbox',
				'tab'     => 'advanced'
			);

			$settings[] = array(
				'id'      => 'enable_debug',
				'title'   => __( 'Enable Debug Mode', 'foobox-image-lightbox' ),
				'desc'    => __( 'Show an extra debug information tab to help debug any issues.', 'foobox-image-lightbox' ),
				'type'    => 'checkbox',
				'tab'     => 'advanced'
			);

			$settings[] = array(
				'id'      => 'force_hide_trial',
				'title'   => __( 'Force Hide Trial Notice', 'foobox-image-lightbox' ),
				'desc'    => __( 'Force the FooBox trial notice admin banner to never show', 'foobox-image-lightbox' ),
				'type'    => 'checkbox',
				'tab'     => 'advanced'
			);

			$settings[] = array(
				'id'      => 'excludebydefault',
				'title'   => __( 'Exclude FooBox Assets', 'foobox-image-lightbox' ),
				'desc'    => __( 'By default, FooBox includes javascript and stylesheet assets into all your pages. We do this, because we do not know if the page content contains media or not.<br>If you want more control over when FooBox assets are included, you can now exclude FooBox assets by default, by enabling this setting. Then on each page, you can choose to include the assets if required.<br>Or you can leave the setting disabled, and then choose to exclude FooBox assets from particular pages. A new FooBox metabox is now available when editing your pages or posts.', 'foobox-image-lightbox' ),
				'type'    => 'checkbox',
				'section' => __( 'JS &amp; CSS', 'foobox-image-lightbox' ),
				'tab'     => 'advanced'
			);

			//endregion

			//region Debug Tab
			$foobox_free = Foobox_Free::get_instance();

			if ( $foobox_free->options()->is_checked( 'enable_debug', false ) ) {

				$tabs['debug'] = __('Debug', 'foobox-image-lightbox');

				$settings[] = array(
					'id'      => 'debug_output',
					'title'   => __( 'Debug Information', 'foobox-image-lightbox' ),
					'type'    => 'debug_output',
					'tab'     => 'debug'
				);
			}
			//endregion

			//region Upgrade tab
			$tabs['upgrade'] = __('Upgrade to PRO!', 'foobox-image-lightbox');

			$link_text = __('Upgrade in WP Admin!', 'foobox-image-lightbox');

			if ( foobox_hide_pricing_menu() ) {
				$link_text = '';
			}

			$link = sprintf( '<p><a href="%s">%s</a></p><br />',  esc_url ( foobox_pricing_url() ), $link_text );

			$settings[] = array(
				'id'    => 'upgrade',
				'title' => $link . __('There are tons of reasons...', 'foobox-image-lightbox'),
				'type'  => 'upgrade',
				'tab'   => 'upgrade'
			);
			//endregion

			return array(
				'tabs' => $tabs,
				'sections' => $sections,
				'settings' => $settings
			);
		}

		function build_install_url( $slug ) {
			$action      = 'install-plugin';
			return wp_nonce_url(
				add_query_arg(
					array(
						'action' => $action,
						'plugin' => $slug
					),
					admin_url( 'update.php' )
				),
				$action . '_' . $slug
			);
        }

		function settings_sidebar() {
			$install_foogallery = $this->build_install_url( 'foogallery' );
			$install_foobar = $this->build_install_url( 'foobar-notifications-lite' );

            $show_foobar_ad = true;
            $show_foogallery_ad = true;

            if ( class_exists( 'FooGallery_Plugin') ) {
	            $show_foogallery_ad = false;
            }

			if ( class_exists( 'FooPlugins\FooBar\Init' ) ) {
				$show_foobar_ad = false;
			}

		    ?>
<style>
    .settings-sidebar-promo {
        width: 280px;
        border: 1px solid #ccd0d4;
        border-left-width: 4px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        background: #fff;
        border-left-color: #007cba;
        padding: 10px;
        margin-bottom: 10px;
        text-align: center;
    }

    .settings-sidebar-promo.please-rate {
        border-left-color: #ff69b4;
    }

    .settings-sidebar-promo h2 {
        margin-top: 0;
    }

    .settings-sidebar-promo h2 .dashicons {
        color: #ff69b4;
        margin-right: 5px;
    }

    .settings-sidebar-promo img {
        padding: 0;
        margin: 0;
    }
</style>

<div class="settings-sidebar-promo please-rate">
    <h2><i class="dashicons dashicons-heart"></i>Thanks for using FooBox!</h2>
	<p>
        If you love FooBox, please consider giving it a 5 star rating on WordPress.org. Your positive ratings help spread the word and help us grow.
    </p>

    <a class="button button-primary button-large" target="_blank" href="https://wordpress.org/support/plugin/foobox-image-lightbox/reviews/#new-post">Rate FooBox on WordPress.org</a>
</div>
<?php if ( $show_foobar_ad ) { ?>
<div class="settings-sidebar-promo">
    <h2>Grow Your Business</h2>
    <img src="https://foocdn.s3.amazonaws.com/logos/foobar-128x128.png" />
    <p>
        FooBar allows you to create unlimited eye-catching notification bars, announcements and cookie notices that catch your visitor's attention.
    </p>
    <a class="button button-primary button-large" target="_blank" href="<?php echo $install_foobar; ?>">Install FooBar</a>
    <a class="button button-large" target="_blank" href="https://wordpress.org/plugins/foobar-notifications-lite/">View Details</a>
</div>
<?php } ?>
<?php if ( $show_foogallery_ad ) { ?>
<div class="settings-sidebar-promo">
    <h2>Create Beautiful Galleries</h2>
    <img src="https://foocdn.s3.amazonaws.com/logos/foogallery-128x128.png" />
    <p>
        Make gallery management in WordPress great again! With FooGallery you can easily add a stunning photo gallery to your website in minutes.
    </p>
    <a class="button button-primary button-large" target="_blank" href="<?php echo $install_foogallery; ?>">Install FooGallery</a>
    <a class="button button-large" target="_blank" href="https://wordpress.org/plugins/foogallery">View Details</a>
</div>
<?php } ?>
<?php
		}
	}
}