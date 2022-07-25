<?php
/**
 * MWC Gift Certificates
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade MWC Gift Certificates to newer
 * versions in the future. If you wish to customize MWC Gift Certificates for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2021, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\GiftCertificates\Frontend;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\GiftCertificates\MWC_Gift_Certificates_Product;
use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_available_google_fonts;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_template;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * The PDF Product Vouchers Frontend handler
 *
 * In 3.0.0 renamed from MWC_Gift_Certificates_My_Account
 * to MWC_Gift_Certificates_Frontend
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Frontend {


	/** @var \WP_Post a voucher post object that should be viewable on frontend **/
	private $visible_voucher_post;

	/** @var My_Account instance */
	private $my_account;


	/**
	 * Frontend constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		$this->my_account = wc_pdf_product_vouchers()->load_class( '/src/frontend/class-wc-pdf-product-vouchers-my-account.php', My_Account::class );

		add_action( 'wp_head', array( $this, 'print_voucher_template_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// clean up voucher template preview, removing actions, scripts and styles we don't need
		// be super aggressive with priority given this is only on our screens
		add_action( 'wp_head',            array( $this, 'clean_voucher_template_actions' ), -999999999 );
		add_action( 'wp_footer',          array( $this, 'clean_voucher_template_actions' ), -999999999 );
		// styles and scripts are removed right before those blocks are printed
		add_action( 'wp_print_styles',    array( $this, 'clean_voucher_template_styles_and_scripts' ), 10 );
		add_action( 'wp_print_scripts',   array( $this, 'clean_voucher_template_styles_and_scripts' ), 10 );

		// helpers for rendering a voucher HTML for PDF generation
		add_filter( 'posts_results', array( $this, 'remember_visible_private_voucher_post' ) );
		add_filter( 'the_posts',     array( $this, 'make_private_voucher_post_visible' ) );
	}


	/**
	 * Gets the My Account handler instance.
	 *
	 * @since 3.5.4
	 *
	 * @return My_Account
	 */
	public function get_my_account_instance() {

		return $this->my_account;
	}


	/**
	 * Enqueues the frontend product page stylesheet, if this is a voucher product
	 *
	 * In 3.0.0 moved from MWC_Gift_Certificates_Product to here.
	 *
	 * @since 1.2.0
	 */
	public function enqueue_scripts() {
		global $post;

		$is_voucher_product = false;

		if ( is_singular() ) {

			$product_has_voucher_template = array();
			$product_is_variable          = array();

			if ( is_product() && $product = wc_get_product( $post->ID ) ) {

				$product_has_voucher_template[] = MWC_Gift_Certificates_Product::has_voucher_template( $product );
				$product_is_variable[]          = $product->is_type( 'variable' );

			} elseif ( has_shortcode( $post->post_content, 'product_page' ) ) {

				// get the product id(s)
				preg_match_all( '/product_page id="(\d+)\"/', $post->post_content, $product_ids, PREG_PATTERN_ORDER );

				// check if any of the embedded products has a voucher
				if ( isset( $product_ids[1] ) && $product_ids[1] && is_array( $product_ids[1] ) ) {

					foreach ( $product_ids[1] as $product_id ) {

						if ( $product = wc_get_product( (int) $product_id ) ) {

							$product_has_voucher_template[] = MWC_Gift_Certificates_Product::has_voucher_template( $product );
							$product_is_variable[]          = $product->is_type( 'variable' );
						}
					}
				}
			}

			// add styles and scripts if there is at least one voucher product displayed
			if ( in_array( true, $product_has_voucher_template, true ) ) {

				$is_voucher_product = true;

				wp_enqueue_script( 'wc-pdf-product-vouchers-frontend-script', wc_pdf_product_vouchers()->get_plugin_url() . '/assets/js/frontend/wc-pdf-product-vouchers.min.js', array( 'jquery' ) );
			}
		}

		if ( $is_voucher_product || is_wc_endpoint_url( 'view-order' ) ) {
			wp_enqueue_style( 'wc-pdf-product-vouchers-product-styles', wc_pdf_product_vouchers()->get_plugin_url() . '/assets/css/frontend/wc-pdf-product-vouchers.min.css', array(), wc_pdf_product_vouchers()->get_version() );
		}
	}


	/**
	 * Prints voucher template styles.
	 *
	 * TODO: move this to a dedicated styles.php, like in PIP? {IT 2016-10-26}
	 *
	 * @since 3.0.0
	 */
	public function print_voucher_template_styles() {

		if ( ! is_singular( 'wc_voucher_template' ) && ! is_singular( 'wc_voucher' ) ) {
			return;
		}

		if ( is_singular( 'wc_voucher' ) ) {
			$wc_voucher          = wc_pdf_product_vouchers_get_voucher();
			$wc_voucher_template = $wc_voucher ? $wc_voucher->get_template() : null;
		} else {
			$wc_voucher_template = wc_pdf_product_vouchers_get_voucher_template();
		}

		if ( ! $wc_voucher_template ) {
			return;
		}

		$fields       = WC_Voucher_Template::get_voucher_fields();
		$google_fonts = wc_pdf_product_vouchers_get_available_google_fonts();

		// add Google Fonts sources
		foreach ( $google_fonts as $google_font_name ) :

			?>
			<link rel="stylesheet" href="<?php echo esc_url( 'https://fonts.googleapis.com/css2?family=' . urlencode( $google_font_name ) . ':ital,wght@0,400;0,700;1,400;1,700&display=swap' ); ?>" />
			<?php

		endforeach;

		?>
		<style type="text/css">

			@page {
				margin: 0;
				padding: 0;
			}

			html, body {
				height: 100%;
				padding: 0;
				margin: 0;
			}

			<?php if ( is_customize_preview() ) : ?>

			/* align voucher preview to center in customizer */
			body {
				text-align: center;
			}

			#voucher-container {
				display: inline-block;
				height: 100%;
			}

			.image-container {
				box-shadow: 0 1px 3px rgba( 0, 0, 0, 0.12 ), 0 1px 2px rgba( 0, 0, 0, 0.24 );
				margin-bottom: 8px;
			}

			/* make voucher preview "responsive" in customizer */
			.image-container img {
				max-width: 100%;
			}

			#no-image-message {
				margin: 0 auto;
				padding: 1em;
				display: none;
				font-family: Helvetica, Arial, sans-serif;
				font-size: 1.5em;
				color: #777;
			}

			body.voucher-no-image {
				display: flex;
				flex-direction: column;
				justify-content: center;
			}

			body.voucher-no-image #no-image-message {
				display: block;
			}

			body.voucher-no-image #voucher-container {
				display: none;
			}

			<?php else : ?>

			/* make sure there are absolutely no paddings or margins */
			html, body {
				margin:  0;
				padding: 0;
			}

			#voucher-image {
				/* position absolutely, so that fields won't be placed on second page in PDF */
				position: absolute;
			}
			<?php endif; ?>

			#voucher {
				position: relative;
				<?php $this->print_field_styles( $wc_voucher_template, 'voucher' ); ?>
			}

			.image-container {
				font-size: 0; /* squash white-space bugs */
			}

			#logo img {
				max-width: 100%;
			}
			#barcode img {
				max-width: 100%;
				height: 100%;
			}

			.new-page-break {
				page-break-before: always;
			}

			<?php foreach ( array_keys( $fields ) as $field_id ) : ?>
			#<?php echo $field_id; ?> {
				position:    absolute;
				<?php if ( $pos = get_post_meta( $wc_voucher_template->id, '_' . $field_id . '_pos', true ) ) : $pos = explode( ',', $pos ); ?>
				left:   <?php echo esc_html( $pos[0] ); ?>px;
				top:    <?php echo esc_html( $pos[1] ); ?>px;
				width:  <?php echo esc_html( $pos[2] ); ?>px;
				height: <?php echo esc_html( $pos[3] ); ?>px;
				<?php else : ?>
				display: none;
				<?php endif; ?>

				<?php $this->print_field_styles( $wc_voucher_template, $field_id ); ?>
			}
			<?php endforeach; ?>

			<?php foreach ( $google_fonts as $google_font_name ) : ?>
			@font-face {
				font-family: <?php sanitize_text_field( $google_font_name ); ?>;
				font-style: normal;
			}
			<?php endforeach; ?>
		</style>
		<?php
	}


	/**
	 * Prints voucher field CSS styles
	 *
	 * @since 3.0.0
	 * @param WC_Voucher_Template $voucher_template voucher template instance
	 * @param string $field_id field identifier
	 */
	private function print_field_styles( WC_Voucher_Template $voucher_template, $field_id ) {

		// don't print styles in customizer preview, so that the printed styles don't
		// override live preview styles
		if ( is_customize_preview() ) {
			return;
		}

		$css_config = WC_Voucher_Template::get_voucher_field_settings_css_config();

		if ( empty( $css_config ) ) {
			return;
		}

		foreach ( $css_config as $setting_key => $config ) {

			if ( empty( $config['property'] ) ) {
				continue;
			}

			if ( empty( $config['value'] ) ) {
				$config['value'] = '{$value}';
			}

			$value = get_post_meta( $voucher_template->id, '_' . $field_id . '_' . $setting_key, true );

			if ( ! $value ) {
				continue;
			}

			// adjust font size for DPI
			if ( 'font_size' === $setting_key ) {
				$value = $value / 72 * $voucher_template->get_dpi();
			}

			$value = str_replace( '{$value}', $value, $config['value'] );

			printf( '%s: %s;', esc_html( $config['property'] ), esc_html( $value ) );
		}

	}


	/**
	 * Cleans up the voucher template actions
	 *
	 * Removes unnecessary wp_head actions so that the voucher template preview
	 * is as clean as possible. Only leaves in WP Customizer actions, so that the
	 * live preview works. Also removes the admin bar.
	 *
	 * Note that this is hooked into wp_head at priority -1 which ensures that when
	 * we iterate through wp_head, all possible actions have been added.
	 *
	 * @since 3.0.0
	 */
	public function clean_voucher_template_actions() {
		global $wp_filter;

		$actions_to_remove = array();

		if ( is_singular( 'wc_voucher_template' ) || is_singular( 'wc_voucher' ) ) {

			$remove_action = current_action();

			if ( ! empty( $wp_filter[ $remove_action ] ) ) {

				foreach ( $wp_filter[ $remove_action ] as $priority => $actions ) {

					foreach ( $actions as $key => $action  ) {

						// remove action if it's not allowed for voucher templates
						if ( ! $this->allow_voucher_template_action( $remove_action, $action, $priority ) ) {

							$actions_to_remove[] = array(
								'callback' => $action['function'],
								'priority' => $priority
							);
						}
					}
				}
			}

			// actions cannot be removed while iterating through wp_filter above, it must be done afterwards
			foreach ( $actions_to_remove as $action ) {
				remove_action( $remove_action, $action['callback'], $action['priority'] );
			}
		}
	}


	/**
	 * Cleans up the voucher template styles and scripts
	 *
	 * Current theme should not affect the voucher template in any way, so we need to
	 * remove all but our own styles from frontend on voucher template preview page.
	 *
	 * As for scripts, we only want the minimal amount of scripts included on the page,
	 * which by default includes only the customize scripts.
	 *
	 * @since 3.0.0
	 */
	public function clean_voucher_template_styles_and_scripts() {
		global $wp_styles, $wp_scripts;

		if ( is_singular( 'wc_voucher_template' ) || is_singular( 'wc_voucher' ) ) {

			if ( ! empty( $wp_styles ) && is_array( $wp_styles->queue ) ) {

				foreach ( $wp_styles->queue as $handle ) {
					if ( ! $this->allow_voucher_template_style( $handle ) ) {
						wp_dequeue_style( $handle );
					}
				}
			}

			if ( ! empty( $wp_scripts ) && is_array( $wp_scripts->queue ) ) {

				foreach ( $wp_scripts->queue as $handle ) {
					if ( ! $this->allow_voucher_template_script( $handle ) ) {
						wp_dequeue_script( $handle );
					}
				}
			}

			// Jetpack uses wp_print_styles to add a CSS file which can't be removed above ಠ_ಠ
			add_filter( 'jetpack_implode_frontend_css', '__return_false' );
		}
	}


	/**
	 * Checks whether to allow an action to run in wp_head/wp_footer or not for voucher templates
	 *
	 * By default, only actions added by this class and WP_Cusomize_Manager for wp_head are allowed.
	 *
	 * @since 3.1.1
	 *
	 * @param string $action_hook action hook name, i.e., wp_head/wp_footer
	 * @param array $action associative array containing info about the action from $wp_filters
	 * @param int $priority action priority
	 *
	 * @return bool whether to allow an action or not
	 */
	private function allow_voucher_template_action( $action_hook, $action, $priority ) {

		$allow           = false;
		$allowed_actions = array();
		$allowed_classes = array();

		if ( 'wp_head' === $action_hook ) {

			$allowed_actions = array( 'wp_print_styles', 'wp_enqueue_scripts', '_wp_render_title_tag' );
			$allowed_classes = array( 'WP_Customize_Manager', __CLASS__ );

		} elseif ( 'wp_footer' === $action_hook ) {

			$allowed_actions = array( 'wp_print_footer_scripts', 'customize_preview_settings' );
			$allowed_classes = array( 'WP_Customize_Manager', 'WP_Customize_Selective_Refresh', 'WP_Customize_Widgets', __CLASS__ );
		}

		if ( is_string( $action['function'] ) && in_array( $action['function'], $allowed_actions, true ) ) {
			$allow = true;
		} elseif ( is_array( $action['function'] ) && is_object( $action['function'][0] ) && in_array( get_class( $action['function'][0] ), $allowed_classes, true ) ) {
			$allow = true;
		}

		/**
		 * Filters whether to allow an action to run in wp_head / wp_footer for voucher templates
		 *
		 * @since 3.0.0
		 *
		 * @param bool $allow whether to allow an action to run or not
		 * @param array $action associative array containing info about the action from $wp_filters
		 * @param int $priority action priority
		 */
		return apply_filters( "wc_pdf_product_vouchers_allow_wc_voucher_template_{$action_hook}_action", $allow, $action, $priority );
	}


	/**
	 * Checks whether to allow a style for voucher template preview or not
	 *
	 * By default, no stylesheets are allowed.
	 *
	 * @since 3.0.0
	 * @param string $handle style handle
	 * @return bool
	 */
	private function allow_voucher_template_style( $handle ) {

		$allowed_styles = array(
			'imgareaselect',
			'woocommerce-pdf-product-vouchers-customizer-preview-styles',
		);

		$allow = false;

		if ( in_array( $handle, $allowed_styles, true ) ) {
			$allow = true;
		}

		/**
		 * Filters whether to allow a style for voucher template preview or not
		 *
		 * @since 3.0.0
		 * @param bool $allow whether to allow the style or not
		 * @param string $handle style handle
		 */
		return apply_filters( 'wc_pdf_product_vouchers_allow_wc_voucher_template_style', $allow, $handle );
	}


	/**
	 * Checks whether to allow a script for voucher template preview or not
	 *
	 * By default, only customizer-related scripts are allowed.
	 *
	 * @since 3.0.0
	 * @param string $handle script handle
	 * @return bool whther to allow a script or not
	 */
	private function allow_voucher_template_script( $handle ) {

		$allowed_scripts = array(
			'imgareaselect',
			'customize-preview',
			'customize-selective-refresh',
			'woocommerce-pdf-product-vouchers-customizer-preview-scripts',
		);

		$allow = false;

		if ( in_array( $handle, $allowed_scripts, true ) ) {
			$allow = true;
		}

		/**
		 * Filters whether to allow a script for voucher template preview or not
		 *
		 * @since 3.0.0
		 * @param bool $allow whether to allow the script or not
		 * @param string $handle script handle
		 */
		return apply_filters( 'wc_pdf_product_vouchers_allow_wc_voucher_template_script', $allow, $handle );
	}


	/**
	 * Checks if the private voucher post should be visible
	 *
	 * @since 3.0.0
	 * @param \WP_Post $post the post object
	 * @return bool whether the post should be visible o not
	 */
	private function voucher_post_should_be_visible( \WP_Post $post ) {

		if ( 'wc_voucher' !== $post->post_type ) {
			return false;
		}

		if ( empty( $_GET['voucher_key'] ) ) {
			return false;
		}

		// if there was a voucher key provided and it matches, the voucher
		// should be visible
		return $_GET['voucher_key'] === get_post_meta( $post->ID, '_voucher_key', true );
	}


	/**
	 * Remembers a reference to a private voucher post
	 *
	 * This post will be re-injected to the posts array when
	 * the PDF generator is trying to access the private voucher post
	 * with a valid secret key.
	 *
	 * @since 3.0.0
	 * @param array $posts array of \WP_Post objects
	 * @return array array of \WP_Post objects
	 */
	public function remember_visible_private_voucher_post( $posts ) {

		if ( count( $posts ) !== 1 ) {
			return $posts;
		}

		$post = $posts[0];

		if ( $post && $this->voucher_post_should_be_visible( $post ) ) {
			$this->visible_voucher_post = $post;
		}

		return $posts;
	}


	/**
	 * Makes a private voucher visible when generating voucher PDF
	 *
	 * @since 3.0.0
	 * @param array $posts array of \WP_Post objects
	 * @return array array of \WP_Post objects
	 */
	public function make_private_voucher_post_visible( $posts ) {

		if ( empty( $posts ) && ! empty( $this->visible_voucher_post ) ) {

			return array( $this->visible_voucher_post );

		} else {

			$this->visible_voucher_post = null;

			return $posts;
		}
	}


}
