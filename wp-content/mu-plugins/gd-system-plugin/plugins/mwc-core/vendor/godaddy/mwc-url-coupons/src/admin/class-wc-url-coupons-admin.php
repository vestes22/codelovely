<?php
/**
 * WooCommerce URL Coupons
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce URL Coupons to newer
 * versions in the future. If you wish to customize WooCommerce URL Coupons for your
 * needs please refer to http://docs.woocommerce.com/document/url-coupons/ for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2020, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace GoDaddy\WordPress\MWC\UrlCoupons\Admin;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;
use function GoDaddy\WordPress\MWC\UrlCoupons\wc_url_coupons;

/**
 * Admin class
 *
 * @since 2.0.0
 */
class WC_URL_Coupons_Admin {


	/**
	 * Setup admin class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_coupon_data_tabs', [ $this, 'add_discount_links_tab' ] );
		add_action( 'woocommerce_coupon_data_panels', [ $this, 'render_discount_links_tab' ], 10, 2 );

		// save per-coupon options
		add_action( 'woocommerce_process_shop_coupon_meta', [ $this, 'save_coupon_options' ], 10, 2 );

		// purge unique URL from active list when parent coupon is trashed or deleted
		add_action( 'wp_trash_post', [ $this, 'purge_coupon_url' ] );

		// add coupon settings to hide coupon code field and customize the URL coupon prefix
		// displays on the General tab in WC 3.4+, or Checkout in older versions
		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4.0' ) ) {
			add_filter( 'woocommerce_general_settings', [ $this, 'admin_settings' ] );
		} else {
			add_filter( 'woocommerce_payment_gateways_settings', [ $this, 'admin_settings' ] );
		}

		// sanitizes the URL prefix to ensure it is a valid URL piece
		add_filter( 'pre_update_option_wc_url_coupons_url_prefix', 'sanitize_title' );

		// add a 'URL slug' column to the coupon list table
		add_filter( 'manage_edit-shop_coupon_columns',        [ $this, 'add_url_slug_column_header' ], 20 );
		add_action( 'manage_shop_coupon_posts_custom_column', [ $this, 'add_url_slug_column' ], 10, 2 );

		// filters out variable products from the available products to add to cart
		add_filter( 'woocommerce_json_search_found_products', [ $this, 'filter_variable_products' ] );
	}


	/**
	 * Adds the Discount Links tab to the coupon form.
	 *
	 * @internal
	 *
	 * @since 2.13.0
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_discount_links_tab( $tabs ) {

		$should_add_new_badge = $this->should_add_new_badge();
		$tab_css_selector     = '#woocommerce-coupon-data ul.wc-tabs li.discount_links_options';
		
		?>
		<style>
			<?php echo $tab_css_selector; ?> > a::before {
				content: "\f103";
			}

			<?php if( $should_add_new_badge ) : ?>

			<?php echo $tab_css_selector; ?>.mwc-new > a::after {
				content: "<?php esc_html_e( 'New', 'woocommerce-url-coupons' ); ?>";
				font-size: 12px;
				line-height: 0;
				color: #fff;
				display: inline-block;
				background: #E14949;
				border-radius: 12px;
				padding: 10px 8px;
			}

			body.mobile <?php echo $tab_css_selector; ?>.mwc-new > a::after {
				content: unset;
			}

			<?php endif; ?>

			#discount_links_coupon_data select + span.select2 {
				max-width: 80%;
			}
		</style>
		<?php

		$tabs['discount_links'] = [
			'label'  => __( 'Discount links', 'woocommerce-url-coupons' ),
			'target' => 'discount_links_coupon_data',
			'class'  => $should_add_new_badge ? 'mwc-new' : '',
		];

		return $tabs;
	}


	/**
	 * @TODO: Remove this method in V2 and its usages to print styles {@acastro1 2021-08-10}
     *
	 * Determines if the "New" badge should be added or not.
	 *
	 * @since 2.13.0
	 *
	 * @return bool
	 */
	protected function should_add_new_badge() : bool {

		// current date < 2021-09-16
		return current_time( 'timestamp' ) < 1631750400;
	}


	/**
	 * Renders the content of the Discount Links tab.
	 *
	 * @internal
	 *
	 * @since 2.13.0
	 *
	 * @param int $coupon_id
	 * @param \WC_Coupon $coupon
	 */
	public function render_discount_links_tab( $coupon_id, $coupon ) {

		?>
			<div id="discount_links_coupon_data" class="panel woocommerce_options_panel">
				<?php $this->add_coupon_options( $coupon_id, $coupon ); ?>
				<?php $this->render_coupon_option_js(); ?>
			</div>
		<?php
	}


	/**
	 * Gets "Redirect Page" field label
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	protected function get_redirect_page_field_label() : string {

		return __( 'Page Redirect', 'woocommerce-url-coupons' );
	}


	/**
	 * Gets "Redirect Page" field description
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	protected function get_redirect_page_field_description() : string {

		return __( 'Select the page to redirect the customer to after visiting the unique URL.', 'woocommerce-url-coupons' );
	}


	/**
	 * Gets "Choose Page" field label
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	protected function get_choose_page_field_label() : string {

		return __( 'Choose Page', 'woocommerce-url-coupons' );
	}


	/**
	 * Gets "Choose Page" field description
	 *
	 * @since 2.13.0
	 *
	 * @return string
	 */
	protected function get_choose_page_field_description() : string {

		return __( 'Select the page that a customer can visit to have this coupon / product added to their cart.', 'woocommerce-url-coupons' );
	}
	

	/**
	 * Adds coupon options to the Coupon edit page.
	 *
	 * @internal
	 *
	 * In 2.9.0 $coupon_id and $coupon are required
	 *
	 * @since 1.0
	 *
	 * @param int $coupon_id coupon identifier
	 * @param \WC_Coupon $coupon coupon object
	 */
	public function add_coupon_options( $coupon_id, $coupon ) {

		/**
		 * Existing Page target ID (Choose Page field)
		 *
		 * @since 2.13.0
		 * @param false|int $existing_page_id The content object ID (or false if none set).
		 * @param int $coupon_id The shop coupon ID.
		 */
		$existing_page_id = apply_filters( 'wc_url_coupons_existing_page_id', $coupon->get_meta( '_wc_url_coupons_existing_page' ), $coupon_id );

		/**
		 * Existing Page target type
		 *
		 * @since 2.13.0
		 * @param false|string $existing_page_type The content type (or false if none set).
		 * @param int $coupon_id The shop coupon ID.
		 */
		$existing_page_type = apply_filters( 'wc_url_coupons_existing_page_type', $coupon->get_meta( '_wc_url_coupons_existing_page_type' ), $coupon_id );

		/**
		 * Unique URL
		 *
		 * @since 2.2.1
		 * @param string $unique_url The unique URL for the coupon (defaults to empty string).
		 * @param int $coupon_id The shop coupon ID.
		 */
		$unique_url = apply_filters( 'wc_url_coupons_unique_url', $coupon->get_meta( '_wc_url_coupons_unique_url' ), $coupon_id );

		/**
		 * Redirect target ID
		 *
		 * @since 2.2.1
		 * @param false|int $redirect_content_id The content object ID (or false if none set).
		 * @param int $coupon_id The shop coupon ID.
		 */
		$redirect_page_id = apply_filters( 'wc_url_coupons_redirect_page_id', $coupon->get_meta( '_wc_url_coupons_redirect_page' ), $coupon_id );

		/**
		 * Redirect target type
		 *
		 * @since 2.2.1
		 * @param false|string $redirect_content_type The content type (or false if none set).
		 * @param int $coupon_id The shop coupon ID.
		 */
		$redirect_page_type = apply_filters( 'wc_url_coupons_redirect_page_type', $coupon->get_meta( '_wc_url_coupons_redirect_page_type' ), $coupon_id );

		/**
		 * Products to add to cart
		 *
		 * @since 2.2.1
		 * @param false|array $product_ids The product ids to add to cart
		 * @param int $coupon_id The shop coupon id
		 */
		$url_coupon_product_ids = apply_filters( 'wc_url_coupons_product_ids', $coupon->get_meta( '_wc_url_coupons_product_ids' ), $coupon_id );
		$url_coupon_product_ids = ! empty( $url_coupon_product_ids ) && is_array( $url_coupon_product_ids ) ? array_filter( array_map( 'absint', $url_coupon_product_ids ) ) : [];

		/**
		 * Defer coupon application
		 *
		 * @since 2.2.1
		 * @param false|string $defer_apply Checkbox option: 'yes', 'no' or false if not set
		 * @param int $coupon_id The shop coupon id
		 */
		$defer_apply = apply_filters( 'wc_url_coupons_defer_apply', $coupon->get_meta( '_wc_url_coupons_defer_apply' ), $coupon_id );

		// the Apply via URL field should be checked if any of the other fields are not empty
		$apply_via_url_enabled = ! empty( $existing_page_id ) || ! empty( $unique_url ) || ! empty( $redirect_page_id ) || ! empty( $url_coupon_product_ids ) || ! empty( $defer_apply );

		// the Apply coupon when field should be set to existing page if all fields are empty or if an existing page was selected
		$apply_when_default_value = ! $apply_via_url_enabled || ! empty( $existing_page_id ) ? 'existing_page' : 'unique_url';

		?>
		<div class="options_group">
			<?php

			// Apply via URL checkbox
			woocommerce_wp_checkbox( [
				'id'          => '_wc_url_coupons_apply_via_url',
				'label'       => __( 'Apply via URL', 'woocommerce-url-coupons' ),
				/* translators: Placeholders: %1$s - opening <a> link tag, %2$s - closing </a> link tag */
				'description' => sprintf( __( 'Enable to allow applying this coupon when a URL or page is visited in addition to the checkout form. %1$sLearn more%2$s', 'woocommerce-url-coupons-pro' ), '<a target="_blank" href="' . esc_url( wc_url_coupons()->get_documentation_url() ) . '">', '</a>' ),
				'value'       => $apply_via_url_enabled ? 'yes' : 'no',
			] );

			// Apply coupon when radio buttons
			woocommerce_wp_radio( [
				'id'      => '_wc_url_coupons_apply_when',
				'label'   => __( 'Apply coupon when', 'woocommerce-url-coupons' ),
				'options' => [
					'existing_page' => __( 'User visits an existing page', 'woocommerce-url-coupons' ),
					'unique_url'    => __( 'User visits a unique URL', 'woocommerce-url-coupons' ),
				],
				'value'   => $apply_when_default_value,
			] );

			if ( empty( $existing_page_id ) ) {
				// default to Cart page
				$existing_page_id   = wc_get_page_id( 'cart' );
				$existing_page_type = 'page';
			}

			$existing_page_title        = $this->get_selected_page_title( $existing_page_id, $existing_page_type );
			$existing_page_select_value = ! empty( $existing_page_title ) ? array( $existing_page_type . '|' . $existing_page_id => esc_html( $existing_page_title ) ) : [];

			// Choose Page dropdown field. ?>
			<p class="form-field _wc_url_coupons_existing_page_field">
				<label for="_wc_url_coupons_existing_page"><?php echo esc_html( $this->get_choose_page_field_label() ); ?></label>
				<select
					name="_wc_url_coupons_existing_page"
					id="_wc_url_coupons_existing_page"
					class="sv-wc-enhanced-search"
					style="width: 380px;"
					data-action="wc_url_coupons_json_search_page_redirects"
					data-nonce="<?php echo wp_create_nonce( 'search-page-redirects' ); ?>">
					<?php if ( ! empty( $existing_page_select_value ) ) : ?>
						<option value="<?php echo esc_attr( key( $existing_page_select_value ) ); ?>" selected><?php echo esc_html( $existing_page_title ); ?></option>
					<?php endif; ?>
				</select>
				<span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $this->get_choose_page_field_description() ); ?>"></span>
				<input
					type="hidden"
					name="_wc_url_coupons_existing_page_type"
					value=""
					id="_wc_url_coupons_existing_page_type"
				/>
			</p>

			<?php
			// Unique URL field.
			woocommerce_wp_text_input( [
				'id'          => '_wc_url_coupons_unique_url',
				'label'       => __( 'Unique URL', 'woocommerce-url-coupons' ),
				'description' => __( 'The URL that a customer can visit to have this coupon / product added to their cart.', 'woocommerce-url-coupons' ),
				'desc_tip'    => true,
				'value'       => $unique_url,
				'style'       => 'width: 380px; max-width: 80%;',
			] );

			if ( empty( $redirect_page_id ) ) {
				// default to Cart page
				$redirect_page_id   = wc_get_page_id( 'cart' );
				$redirect_page_type = 'page';
			}

			// Redirect target selection formatted for enhanced input.
			$redirect_page_title = $this->get_selected_page_title( $redirect_page_id, $redirect_page_type );

			// Enhanced select value.
			$redirect_page_select_value = ! empty( $redirect_page_title ) ? array( $redirect_page_type . '|' . $redirect_page_id => esc_html( $redirect_page_title ) ) : array();

			// Redirect to page dropdown field. ?>
			<p class="form-field _wc_url_coupons_redirect_page_field">
				<label for="_wc_url_coupons_redirect_page"><?php echo esc_html( $this->get_redirect_page_field_label() ); ?></label>
				<select
					name="_wc_url_coupons_redirect_page"
					id="_wc_url_coupons_redirect_page"
					class="sv-wc-enhanced-search"
					style="width: 380px;"
					data-action="wc_url_coupons_json_search_page_redirects"
					data-nonce="<?php echo wp_create_nonce( 'search-page-redirects' ); ?>">
					<?php if ( ! empty( $redirect_page_select_value ) ) : ?>
						<option value="<?php echo esc_attr( key( $redirect_page_select_value ) ); ?>" selected><?php echo esc_html( $redirect_page_title ); ?></option>
					<?php endif; ?>
				</select>
				<span class="woocommerce-help-tip redirect" data-tip="<?php echo esc_attr( $this->get_redirect_page_field_description() ); ?>"></span>
				<input
					type="hidden"
					name="_wc_url_coupons_redirect_page_type"
					value=""
					id="_wc_url_coupons_redirect_page_type"
				/>
			</p>

			<?php Framework\SV_WC_Helper::render_select2_ajax(); ?>

			<?php
			// Dropdown for product(s) to add to cart. ?>
			<p class="form-field _wc_url_coupons_product_ids_field">
				<label for="_wc_url_coupons_product_ids"><?php esc_html_e( 'Products to Add to Cart', 'woocommerce-url-coupons' ); ?></label>
				<select
					name="_wc_url_coupons_product_ids[]"
					class="wc-product-search"
					style="width: 380px;"
					multiple="multiple"
					data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce-url-coupons' ); ?>"
					data-exclude="wc_url_coupons_variable_products"
					data-action="woocommerce_json_search_products_and_variations">
					<?php foreach( $url_coupon_product_ids as $product_id ) : ?>
						<?php if ( $product = wc_get_product( $product_id ) ) : ?>
							<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo esc_html( $product->get_formatted_name() ); ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>

				<?php echo wc_help_tip( __( 'Add these products to the customers cart when they visit the URL.', 'woocommerce-url-coupons' ) ); ?>
			</p>

			<?php
			// defer apply option
			woocommerce_wp_checkbox( array(
				'id'          => '_wc_url_coupons_defer_apply',
				'label'       => __( 'Defer Apply', 'woocommerce-url-coupons' ),
				'description' => __( "Check this box to defer applying the coupon until the customer's cart meets the coupon's requirements.", 'woocommerce-url-coupons' ),
				'value'       => $defer_apply,
			) );

			do_action( 'mwc_coupon_options_discount_links', $coupon->get_id(), $coupon );
			?>
		</div>
		<?php
	}


	/**
	 * Gets selected page title.
	 *
	 * @since 2.1.5
	 * @param int $page_id Object id
	 * @param string $page_type Object type
	 * @return string Term name or post type title
	 */
	private function get_selected_page_title( $page_id, $page_type ) {

		if ( -1 === (int) $page_id ) {

			$page_title = __( 'Homepage', 'woocommerce-url-coupons' );

		} else {

			switch ( $page_type ) {

				case 'page':
				case 'pages':
				case 'post':
				case 'posts':
					$page_title = get_the_title( $page_id );
				break;

				case 'product':
				case 'products':

					$product = wc_get_product( $page_id );
					$page_title = $product ? $product->get_title() : '';

				break;

				case 'category':
				case 'tag':
				case 'post_tag':
				case 'product_cat':
				case 'product_tag':

					$taxonomy   = 'post_tag' === $page_type ? 'tag' : $page_type;
					$term       = get_term_by( 'id', $page_id, $taxonomy );

					$page_title = isset( $term->name ) ? $term->name : '';

				break;

				default:
					$page_title = '';
				break;
			}
		}

		return $page_title;
	}


	/**
	 * Render JS to add live preview.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 */
	public function render_coupon_option_js() {
		global $post, $current_screen;

		if ( ! $post || ( isset( $current_screen->id, $current_screen->action ) && 'shop_coupon' === $current_screen->id && 'add' === $current_screen->action ) ) {
			// new coupon: use the current prefix, if set
			$prefix = wc_url_coupons()->get_url_coupons_url_prefix();
			$prefix = '' !== $prefix ? trailingslashit( $prefix ) : $prefix;
		} else {
			// existing coupon: use the saved prefix, if set
			$coupon = new \WC_Coupon( $post->ID );
			$prefix = $coupon->get_meta( '_wc_url_coupons_url_prefix' );
			$prefix = '' !== $prefix && is_string( $prefix ) ? trailingslashit( trim( $prefix ) ) : '';
		}

		$coupon_root = trailingslashit( home_url( '/' ) ) . $prefix;

		wc_enqueue_js( "( function() {

			$( 'p._wc_url_coupons_unique_url_field' ).append( \"<span id='_wc_url_coupons_url_preview' class='description' style='clear: both; display: block; margin: 8px 0 0; font-family: monospace;'>{$coupon_root}</span>\" );

			$( 'input[id=_wc_url_coupons_unique_url]' ).on( 'keyup change input', function() {
				$( 'span#_wc_url_coupons_url_preview' ).text( '{$coupon_root}' + $( this ).val() );
			} ).change();

			$( '#_wc_url_coupons_redirect_page' ).change( function() {
				var page      = $( this ).val(),
				    page_type = '';
				if ( page ) {
					page_type = page.substr( 0, page.indexOf( '|' ) );
				}
				$( '#_wc_url_coupons_redirect_page_type' ).val( page_type );
			} ).change();

			$( '#_wc_url_coupons_existing_page' ).change( function() {
				var page      = $( this ).val(),
				    page_type = '';
				if ( page ) {
					page_type = page.substr( 0, page.indexOf( '|' ) );
				}
				$( '#_wc_url_coupons_existing_page_type' ).val( page_type );
			} ).change();

			const discount_links_fields = $( '#discount_links_coupon_data' ).find( 'p.form-field:not(._wc_url_coupons_apply_via_url_field), fieldset.form-field' );
			let apply_when_field = discount_links_fields.filter( '._wc_url_coupons_apply_when_field' );
			let existing_page_field = discount_links_fields.filter( '._wc_url_coupons_existing_page_field' );
			let unique_url_field = discount_links_fields.filter( '._wc_url_coupons_unique_url_field' );
			let redirect_page_field = discount_links_fields.filter( '._wc_url_coupons_redirect_page_field' );

			apply_when_field.on( 'change', 'input[type=radio]', function( e ) {

				let is_unique_url = 'unique_url' === e.currentTarget.value;

				existing_page_field.toggle( !is_unique_url );

				unique_url_field.toggle( is_unique_url ).find( 'input[type=text]' ).prop( 'required', is_unique_url );
				redirect_page_field.toggle( is_unique_url );

			} ).find( 'input[type=radio]:checked' ).trigger( 'change' );

			$( '#_wc_url_coupons_apply_via_url' ).on( 'change', function( e ) {

				discount_links_fields.toggle( e.currentTarget.checked );

				if ( e.currentTarget.checked ) {

					apply_when_field.find( 'input[type=radio]:checked' ).trigger( 'change' );

				}

			} ).trigger( 'change' );
		} )()" );
	}


	/**
	 * Get the redirect page data, used for the redirect page select.
	 *
	 * @since 2.0.0
	 * @param string $search Optional search keyword.
	 * @return array Associative array by content type and ID and content title as values.
	 */
	public function get_redirect_pages( $search = '' ) {

		$pages = array(
			'pages'       => array(),
			'posts'       => array(),
			'products'    => array(),
			'category'    => array(),
			'post_tag'    => array(),
			'product_cat' => array(),
			'product_tag' => array(),
		);

		// add homepage
		$pages['pages'][-1] = array( 'type' => 'page', 'title' => __( 'Homepage', 'woocommerce-url-coupons' ) );

		// add pages
		foreach ( get_pages( array( 'sort_column' => 'menu_order' ) ) as $page ) {

			// indent child page titles
			$title = ( 0 === $page->post_parent ) ? $page->post_title : '&nbsp;&nbsp;&nbsp;' . $page->post_title;

			$pages['pages'][ $page->ID ] = array( 'type' => 'page', 'title' => $title );
		}

		// add posts
		$args = array(
			'fields'      => 'ids',
			'post_status' => 'publish',
			'orderby'     => 'title',
			'order'       => 'ASC',
			'nopaging'    => true,
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		foreach ( get_posts( $args ) as $post_id ) {

			$pages['posts'][ $post_id ] = array(
				'type'  => 'post',
				'title' => get_the_title( $post_id ),
			);
		}

		// add products
		$args = array(
			'fields'      => 'ids',
			'post_type'   => array( 'product', 'product_variation' ),
			'post_status' => 'publish',
			'orderby'     => 'title',
			'order'       => 'ASC',
			'nopaging'    => true,
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$products = get_posts( $args );

		foreach ( $products as $product_id ) {

			if ( $product = wc_get_product( $product_id ) ) {
				$pages['products'][ $product_id ] = array( 'type' => 'product', 'title' => $product->get_formatted_name() );
			}
		}

		// Add taxonomies.
		foreach ( $pages as $page_group => $_ ) {

			// Bail for invalid or non-taxonomies (pages, products).
			if ( ! taxonomy_exists( $page_group ) || in_array( $page_group, array( 'pages', 'products' ), true ) ) {
				continue;
			}

			$terms = get_terms( $page_group, array(
				'hide_empty' => false,
				'number' => 250
			) );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

				foreach ( $terms as $term ) {
					$pages[ $page_group ][ $term->term_id ] = array(
						'type'  => $page_group,
						'title' => $term->name
					);
				}
			}
		}

		$groups = array(
			'pages'       => __( 'Pages', 'woocommerce-url-coupons' ),
			'posts'       => __( 'Posts', 'woocommerce-url-coupons' ),
			'products'    => __( 'Products', 'woocommerce-url-coupons' ),
			'category'    => __( 'Categories', 'woocommerce-url-coupons' ),
			'post_tag'    => __( 'Tags', 'woocommerce-url-coupons' ),
			'product_cat' => __( 'Product Categories', 'woocommerce-url-coupons' ),
			'product_tag' => __( 'Product Tags', 'woocommerce-url-coupons' ),
		);

		// Set translated group titles, this is done here,
		// in order to allow  simplify the taxonomy handling code.
		foreach ( $groups as $group => $title ) {
			if ( isset( $pages[ $group ] ) ) {
				$pages[ $title ] = $pages[ $group ];
				unset( $pages[ $group ] );
			}
		}

		/**
		 * Get redirect pages.
		 *
		 * @since 2.0.0
		 * @param array $pages Associative array.
		 * @param WC_URL_Coupons_Admin $url_coupons Instance of this class.
		 */
		return apply_filters( 'wc_url_coupons_redirect_pages', $pages, $this );
	}


	/**
	 * Saves coupon options on Coupon edit page.
	 *
	 * @internal
	 *
	 * @since 1.0
	 *
	 * @param int $post_id coupon ID
	 * @param \WP_Post coupon post object
	 */
	public function save_coupon_options( $post_id, $post ) {

		$coupon             = new \WC_Coupon( $post->ID );
		$apply_via_url      = ! empty( $_POST['_wc_url_coupons_apply_via_url'] );
		$apply_when         = filter_input( INPUT_POST, '_wc_url_coupons_apply_when', FILTER_SANITIZE_STRING );
		$existing_page      = filter_input( INPUT_POST, '_wc_url_coupons_existing_page', FILTER_SANITIZE_STRING );
		$existing_page_type = filter_input( INPUT_POST, '_wc_url_coupons_existing_page_type', FILTER_SANITIZE_STRING );
		$unique_url         = filter_input( INPUT_POST, '_wc_url_coupons_unique_url', FILTER_SANITIZE_STRING );
		$redirect_page      = filter_input( INPUT_POST, '_wc_url_coupons_redirect_page', FILTER_SANITIZE_STRING );
		$redirect_page_type = filter_input( INPUT_POST, '_wc_url_coupons_redirect_page_type', FILTER_SANITIZE_STRING );
		$product_ids        = $_POST['_wc_url_coupons_product_ids'] ?? [];
		$defer_apply        = filter_input( INPUT_POST, '_wc_url_coupons_defer_apply', FILTER_SANITIZE_STRING );

		if ( empty( $redirect_page_type ) ) {
			$redirect_page_type = 'page';
		}

		// if coupon is not going to be applied via URL, then clear up the values
		if ( ! $apply_via_url ) {

			$existing_page      = '';
			$existing_page_type = '';
			$unique_url         = '';
			$redirect_page      = '';
			$redirect_page_type = '';
			$product_ids        = [];
			$defer_apply        = '';
		}

		$page             = explode( '|', $existing_page );
		$existing_page_id = $page[1] ?? '';

		// if coupon is going to be applied when visiting an existing page, automatically set the Unique URL value
		if ( $apply_via_url && 'existing_page' === $apply_when ) {

			$unique_url = wc_url_coupons()->get_object_url( (int) $existing_page_id, $existing_page_type );
			// remove WP site URL from the Unique URL
			$unique_url = strtolower( str_replace( trailingslashit( home_url() ), '', $unique_url ) );

			// clear the redirect fields (the Redirect Page field is not available when Apply coupon when is set to existing page)
			$redirect_page = '';

		} else {
			// clear the existing page field
			$existing_page = '';
		}

		$page             = explode( '|', $redirect_page );
		$redirect_page_id = $page[1] ?? '';
		$url_prefix       = '';

		// existing page
		if ( empty( $existing_page ) ) {
			$coupon->delete_meta_data( '_wc_url_coupons_existing_page' );
			$coupon->delete_meta_data( '_wc_url_coupons_existing_page_type' );
		} elseif ( $existing_page_id && $existing_page_type ) {
			// save the existing page so we can display it on the edit form
			// - we are not using this value for anything else at the moment, we rely in the URL
			// - we cannot use the URL for the form because there is no reverse lookup for terms URLs
			$coupon->update_meta_data( '_wc_url_coupons_existing_page', (int) $existing_page_id );
			$coupon->update_meta_data( '_wc_url_coupons_existing_page_type', sanitize_key( $existing_page_type ) );
		}
		
		// unique URL
		if ( empty( $unique_url ) ) {

			$coupon->delete_meta_data( '_wc_url_coupons_url_prefix' );
			$coupon->delete_meta_data( '_wc_url_coupons_unique_url' );

		} else {

			if ( $coupon->meta_exists( '_wc_url_coupons_url_prefix' ) ) {

				$url_prefix = $coupon->get_meta( '_wc_url_coupons_url_prefix' );

			} else {

				$url_prefix = wc_url_coupons()->get_url_coupons_url_prefix();

				$coupon->update_meta_data( '_wc_url_coupons_url_prefix', sanitize_text_field( $url_prefix ) );
			}

			$coupon->update_meta_data( '_wc_url_coupons_unique_url', sanitize_text_field( $unique_url ) );
		}

		// redirect
		if ( empty( $redirect_page ) ) {
			$coupon->update_meta_data( '_wc_url_coupons_redirect_page', 0 ); // 0 is checked in maybe_apply_coupons() to redirect to shop page since redirect is empty.
			$coupon->delete_meta_data( '_wc_url_coupons_redirect_page_type' );
		} elseif ( $redirect_page_id && $redirect_page_type ) {
			$coupon->update_meta_data( '_wc_url_coupons_redirect_page', (int) $redirect_page_id );
			$coupon->update_meta_data( '_wc_url_coupons_redirect_page_type', sanitize_key( $redirect_page_type ) );
		}

		// products to add to cart
		if ( ! empty( $product_ids ) && is_array( $product_ids ) ) {
			$coupon->update_meta_data( '_wc_url_coupons_product_ids', array_map( 'absint', $product_ids ) );
		} else {
			$coupon->delete_meta_data( '_wc_url_coupons_product_ids' );
		}

		// defer apply option
		if ( ! empty( $defer_apply ) ) {
			$coupon->update_meta_data( '_wc_url_coupons_defer_apply', sanitize_text_field( $defer_apply ) );
		} else {
			$coupon->delete_meta_data( '_wc_url_coupons_defer_apply' );
		}

		$options = [
			'coupon_id'          => $post_id,
			'unique_url'         => $unique_url,
			'url_prefix'         => $url_prefix,
			'redirect_page'      => $redirect_page_id,
			'redirect_page_type' => $redirect_page_type,
			'product_ids'        => $product_ids,
			'defer_apply'        => $defer_apply,
		];

		$coupon->save_meta_data();

		// update active coupon array option
		$this->update_coupons( $options );

		update_user_meta( get_current_user_id(), 'wc_url_coupons_first_coupon_saved', 'yes' );
	}


	/**
	 * Helper function to update the active coupon option array.
	 *
	 * @since 1.0
	 *
	 * @param array $options coupon options
	 */
	public function update_coupons( $options ) {

		// load existing coupon urls
		$coupons = get_option( 'wc_url_coupons_active_urls', [] );

		// add coupon URL & Redirect page ID
		$coupons[ $options['coupon_id'] ] = [
			'url'                => strtolower( $options['unique_url'] ),
			'prefix'             => strtolower( $options['url_prefix'] ),
			'redirect'           => (int) $options['redirect_page'],
			'redirect_page_type' => $options['redirect_page_type'],
			'products'           => ! empty( $options['product_ids'] ) && is_array( $options['product_ids'] ) ? array_map( 'absint', (array) $options['product_ids'] ) : [],
			'defer'              => $options['defer_apply'],
		];

		// remove coupon URL if blank
		if ( ! $options['unique_url'] ) {
			unset( $coupons[ $options['coupon_id'] ] );
		}

		// update the array
		update_option( 'wc_url_coupons_active_urls', $coupons );

		// clear the transient
		delete_transient( 'wc_url_coupons_active_urls' );
	}


	/**
	 * Remove the unique URL associated with a coupon when the coupon is trashed. This prevents a "coupon does not exist"
	 * error message when the unique URL is visited but the coupon is trashed
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 * @param int $coupon_id Coupon ID.
	 */
	public function purge_coupon_url( $coupon_id ) {

		// only purge for coupons
		if ( 'shop_coupon' !== get_post_type( $coupon_id ) ) {
			return;
		}

		$coupons = get_option( 'wc_url_coupons_active_urls' );

		// remove from active list
		if ( isset( $coupons[ $coupon_id ] ) ) {
			unset( $coupons[ $coupon_id ] );
		}

		// update active list
		update_option( 'wc_url_coupons_active_urls', $coupons );

		// clear transient
		delete_transient( 'wc_url_coupons_active_urls' );
	}


	/**
	 * Injects additional URL Coupon settings into WooCommerce Coupons settings.
	 *
	 * @internal
	 *
	 * @since 1.2
	 *
	 * @param array $settings WooCommerce settings.
	 * @return array
	 */
	public function admin_settings( $settings ) {

		$updated_settings = [];

		foreach ( $settings as $section ) {

			$updated_settings[] = $section;

			if ( isset( $section['id'] ) && 'woocommerce_calc_discounts_sequentially' === $section['id'] ) {

				$updated_settings[] = [
					'title'       => __( 'Coupon URL prefix', 'woocommerce-url-coupons' ),
					'desc_tip'    => __( "Optionally add an automated prefix to all URL coupons. You can then exclude links with this prefix from your host's caching for reliable coupon performance.", 'woocommerce-url-coupons' ),
					'placeholder' => __( 'Enter a coupon prefix', 'woocommerce-url-coupons' ),
					'id'          => 'wc_url_coupons_url_prefix',
					'type'        => 'text',
					'default'     => '',
				];

				$updated_settings[] = [
					'title'         => __( 'Hide coupon code field', 'woocommerce-url-coupons' ),
					'desc'          => __( 'Hide on cart page.', 'woocommerce-url-coupons' ),
					'desc_tip'      => __( 'Enable to hide the coupon code field on the cart page.', 'woocommerce-url-coupons' ),
					'id'            => 'wc_url_coupons_hide_coupon_field_cart',
					'type'          => 'checkbox',
					'default'       => 'no',
					'checkboxgroup' => 'start'
				];

				$updated_settings[] = [
					'desc'          => __( 'Hide on checkout page.', 'woocommerce-url-coupons' ),
					'desc_tip'      => __( 'Enable to hide the coupon code field on the checkout page.', 'woocommerce-url-coupons' ),
					'id'            => 'wc_url_coupons_hide_coupon_field_checkout',
					'type'          => 'checkbox',
					'default'       => 'no',
					'checkboxgroup' => 'end'
				];
			}
		}

		$home_url    = trailingslashit( home_url( '/' ) );
		$placeholder = _x( '[coupon-url]', 'Placeholder for the unique coupon URL slug', 'woocommerce-url-coupons' );

		wc_enqueue_js( "
			( function( $ ) {

				$( 'p#wc_url_coupons_url_preview' ).remove();

				$( '#wc_url_coupons_url_prefix' )
					.after( \"<p id='wc_url_coupons_url_preview' class='description' style='clear: both; display: block; margin: 8px 0 0; font-family: monospace;'>{$home_url}</p>\" )
					.on( 'keyup change input', function() {
						var prefixVal = 0 === $( this ).val().length ? '{$placeholder}' : $( this ).val() + '/' +  '{$placeholder}';
						$( 'p#wc_url_coupons_url_preview' ).text( '{$home_url}' + prefixVal );
					} ).change();

				$( '#woocommerce_enable_coupons' ).on( 'change', function( e ) {
					if ( $( this ).is( ':checked' ) ) {
						$( '#wc_url_coupons_url_prefix, #wc_url_coupons_hide_coupon_field_cart' ).closest( 'tr' ).show();
					} else {
						$( '#wc_url_coupons_url_prefix, #wc_url_coupons_hide_coupon_field_cart' ).closest( 'tr' ).hide();
					}
				} );

			} ( jQuery ) )
		" );

		return $updated_settings;
	}


	/**
	 * Add 'URL Slug' column header to the Coupons list table
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 * @param array $column_headers
	 * @return array
	 */
	public function add_url_slug_column_header( $column_headers ) {

		$column_headers['url_slug'] = __( 'URL Slug', 'woocommerce-url-coupons' );

		return $column_headers;
	}


	/**
	 * Adds the 'URL Slug' column content to the Coupons list table.
	 *
	 * @internal
	 *
	 * @since 2.0.0
	 *
	 * @param string $column the name of the column to display
	 * @param int $coupon_id the current coupon ID
	 */
	public function add_url_slug_column( $column, $coupon_id ) {

		if ( 'url_slug' === $column ) {

			$coupon = new \WC_Coupon( $coupon_id );
			$slug   = $coupon->get_meta( '_wc_url_coupons_unique_url' );

			if ( ! empty( $slug ) ) {

				if ( $prefix = $coupon->get_meta( '_wc_url_coupons_url_prefix' ) ) {
					echo '<small>' . esc_html( trailingslashit( $prefix ) ) . '</small><br>';
				}

				echo esc_html( $slug );

			} else {

				echo '&ndash;';
			}
		}
	}


	/**
	 * Removes variable parent products from the available products to add to cart.
	 *
	 * @internal
	 *
	 * @since 2.11.0
	 *
	 * @param string[] $products an array of product names with their ids as indexes
	 * @return string[] a filtered array product names with their ids as indexes (excludes variable parents)
	 */
	public function filter_variable_products( $products ) {

		if ( 'wc_url_coupons_variable_products' === Framework\SV_WC_Helper::get_requested_value( 'exclude' ) ) {

			// remove variable parent products
			foreach( $products as $id => $title ) {

				$product = wc_get_product( $id );

				if ( ! $product || $product->is_type( 'variable' ) ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}


	/**
	 * May add a notice with help text when the user is adding their first coupon.
	 *
	 * @since 2.13.0
	 */
	public function maybe_add_first_coupon_notice() {

		$current_screen = get_current_screen();

		if ( isset( $current_screen->id ) && 'shop_coupon' === $current_screen->id &&
		     // if the user has not saved a coupon before, this is their first one
		     ! wc_string_to_bool( get_user_meta( get_current_user_id(), 'wc_url_coupons_first_coupon_saved', true ) ) ) {

			wc_url_coupons()->get_admin_notice_handler()->add_admin_notice(
				/* translators: Placeholders: %1$s - opening <a> link tag, %2$s - closing </a> link tag */
				sprintf( __( 'Need help setting up URL Settings? %1$sRead documentation%2$s', 'woocommerce-url-coupons-pro' ),
					'<a target="_blank" href="' . esc_url( wc_url_coupons()->get_documentation_url() ) . '">', '</a>' ),
				wc_url_coupons()->get_id_dasherized() . '-first-coupon',
				[
					'always_show_on_settings' => false,
					'notice_class'            => 'updated force-hide js-url-coupons-first-coupon-notice',
				]
			);
		}
	}


	/**
	 * Renders admin notices inline JavaScript.
	 *
	 * @see \GoDaddy\WordPress\MWC\UrlCoupons\WC_URL_Coupons::maybe_add_first_coupon_notice()
	 *
	 * @internal
	 *
	 * @since 2.13.0
	 */
	public function render_admin_notice_js() {

		$current_screen = get_current_screen();

		if ( isset( $current_screen->id ) && 'shop_coupon' === $current_screen->id ) {

			// remove force-hide class (which prevents message flicker on page load) and simply hide the hidden notice
			// and show it on tab switch
			wc_enqueue_js( "( function() {
				$( '.js-wc-plugin-framework-admin-notice.force-hide' ).removeClass( 'force-hide' ).hide();

				$( 'ul.wc-tabs li' ).find( ':not(.discount_links_tab), a' ).click( function( e ) {
					$( '.js-url-coupons-first-coupon-notice' ).hide();
				} );

				$( 'ul.wc-tabs li.discount_links_tab a' ).click( function( e ) {
					$( '.js-url-coupons-first-coupon-notice' ).show();
				} );
			} )()" );
		}
	}


}
