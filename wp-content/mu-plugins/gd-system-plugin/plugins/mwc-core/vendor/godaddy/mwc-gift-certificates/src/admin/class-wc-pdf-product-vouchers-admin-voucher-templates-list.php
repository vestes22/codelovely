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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin;

defined( 'ABSPATH' ) or exit;

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher_Template;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Voucher Templates List Admin
 *
 * In 3.0.0 renamed from MWC_Gift_Certificates_Admin_Voucher_List_Table
 * to MWC_Gift_Certificates_Admin_Voucher_Templates_List.
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Admin_Voucher_Templates_List {


	/**
	 * Initializes the voucher templates list admin
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		add_filter( 'bulk_actions-edit-wc_voucher_template', array( $this, 'edit_voucher_template_bulk_actions' ) );

		add_filter( 'views_edit-wc_voucher_template', array( $this, 'edit_voucher_template_views' ) );

		add_filter( 'manage_edit-wc_voucher_template_columns', array( $this, 'edit_voucher_template_columns' ) );

		add_action( 'manage_wc_voucher_template_posts_custom_column', array( $this, 'custom_voucher_template_columns' ), 2 );

		add_filter( 'display_post_states', array( $this, 'edit_voucher_template_post_states' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'edit_voucher_template_row_actions' ), 10, 2 );

		add_action( 'admin_action_duplicate_voucher_template', array( $this, 'duplicate_voucher_template' ) );
	}


	/**
	 * Removes the bulk edit action for voucher templates, it really isn't useful
	 *
	 * In 3.0.0 renamed from edit_voucher_bulk_actions to edit_voucher_template_bulk_actions.
	 *
	 * @since 1.2.0
	 * @param array $actions associative array of action identifier to name
	 * @return array associative array of action identifier to name
	 */
	public function edit_voucher_template_bulk_actions( $actions ) {

		unset( $actions['edit'] );

		return $actions;
	}


	/**
	 * Modifies the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
	 * shown above the voucher templates list table, to hide the publish/private states,
	 * which are not important and confusing for voucher templates.
	 *
	 * In 3.0.0 renamed from edit_voucher_views to edit_voucher_template_views.
	 *
	 * @since 1.2.0
	 * @param array $views associative-array of view state name to link
	 * @return array associative array of view state name to link
	 */
	public function edit_voucher_template_views( $views ) {

		// publish and private are not important distinctions for voucher templates
		unset( $views['publish'], $views['private'] );

		return $views;
	}


	/**
	 * Modifies the list table columns on the Voucher Templates page
	 *
	 * In 3.0.0 renamed from edit_voucher_columns to edit_voucher_template_columns.
	 *
	 * @since 1.2.0
	 *
	 * @return array associative-array of column identifier to header names for the voucher templates page
	 */
	public function edit_voucher_template_columns() {

		$columns = array();

		$columns['cb']                  = '<input type="checkbox" />';
		$columns['thumb']               = __( 'Image', 'woocommerce-pdf-product-vouchers' );
		$columns['title']               = __( 'Name', 'woocommerce-pdf-product-vouchers' );
		$columns['type']                = __( 'Type', 'woocommerce-pdf-product-vouchers' );
		$columns['redeemable_products'] = __( 'Redeemable products', 'woocommerce-pdf-product-vouchers' );
		$columns['days_to_expiry']      = __( 'Expiry days', 'woocommerce-pdf-product-vouchers' );

		return $columns;
	}


	/**
	 * Customizes post display states in the voucher templates list table
	 *
	 * @since 3.0.0
	 * @param array $post_states associative array in the form of [post status slug] => label
	 * @param \WP_Post $post the post object
	 * @return array
	 */
	public function edit_voucher_template_post_states( $post_states, $post ) {

		if ( 'wc_voucher_template' === $post->post_type ) {

			$post_states = array();
			$post_status = isset( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : '';

			// narrow post states display down to draft
			if ( 'draft' === $post->post_status && 'draft' !== $post_status ) {
				$post_states['draft'] = __( 'Draft' );
			}

		}

		return $post_states;
	}


	/**
	 * Customizes voucher template row actions
	 *
	 * @since 3.0.0
	 * @param array $actions associative array of row actions
	 * @param \WP_Post $post the post object
	 * @return array
	 */
	public function edit_voucher_template_row_actions( $actions, $post ) {

		if ( 'wc_voucher_template' === $post->post_type ) {

			unset( $actions['inline hide-if-no-js'], $actions['view'] );

			$actions = array( 'id' => sprintf( __( 'ID: %s', 'woocommerce-pdf-product-vouchers' ), $post->ID ) ) + $actions;

			$duplicate_link_open  = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=wc_voucher_template&action=duplicate_voucher_template&amp;post=' . $post->ID ), 'wc-pdf-product-vouchers-duplicate-voucher-template_' . $post->ID ) . '" title="' . __( 'Make a duplicate from this gift certificate template', 'woocommerce-pdf-product-vouchers' ) . '" rel="permalink">';
			$duplicate_link_close = '</a>';

			// add duplicate plan action
			$actions['duplicate'] = $duplicate_link_open . esc_html_x( 'Duplicate', 'Duplicate a Gift Certificate Template', 'woocommerce-pdf-product-vouchers' ) . $duplicate_link_close;

		}

		return $actions;
	}


	/**
	 * Customizes list table column values on the voucher templates page
	 *
	 * In 3.0.0 renamed from custom_voucher_columns to custom_voucher_template_columns
	 *
	 * @since 1.2.0
	 * @param string $column column identifier
	 */
	public function custom_voucher_template_columns( $column ) {

		global $post;

		$voucher_template = new WC_Voucher_Template( $post->ID );

		switch ( $column ) {

			case 'thumb':
				$edit_link = get_edit_post_link( $post->ID );
				echo '<a href="' . $edit_link . '">' . $voucher_template->get_image() . '</a>';
			break;

			case 'type':
				switch ( $voucher_template->get_voucher_type() ) {
					case 'multi':
						esc_html_e( 'Multi-purpose', 'woocommerce-pdf-product-vouchers' );
					break;
					case 'single':
						esc_html_e( 'Single-purpose', 'woocommerce-pdf-product-vouchers' );
					break;
				}
			break;

			case 'redeemable_products':
				$redeemable_products = $voucher_template->get_redeemable_products();

				if ( ! empty( $redeemable_products ) ) {
					echo '<ul>';
					foreach ( $redeemable_products as $product_id ) {
						if ( $product = wc_get_product( $product_id ) ) {
							echo '<li><a href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . esc_html( $product->get_name() ) . '</a></li>';
						}
					}
					echo '</ul>';
				}

				// Single Purpose Vouchers can only be redeemed online if a redeemable product is setup, so we need to display a warning for any online-redeemable SPVs that don't yet have redeemable products
				elseif ( $voucher_template->is_redeemable_online() && 'single' === $voucher_template->get_voucher_type() ) {

					$this->render_missing_redemption_products_tooltip();
				}
			break;

			case 'days_to_expiry':
				echo $voucher_template->get_field_setting_value( 'expiration_date', 'days_to_expiry' );
			break;
		}
	}

	/**
	 * Renders HTML to inform site admin that their Single Purpose Voucher needs redemption products
	 *
	 * @since 3.9.10
	 */
	public function render_missing_redemption_products_tooltip() {

		$url      = wc_pdf_product_vouchers()->get_documentation_url() . '#configuring-redemption-products';
		$tool_tip = esc_attr__( 'Single-purpose gift certificates that are redeemable online need redemption products. Edit a product to add redeemable gift certificates.', 'woocommerce-pdf-product-vouchers' );
		$status   = esc_html__( 'Missing Redemption Products', 'woocommerce-pdf-product-vouchers' );

		printf( '<a class="wc-voucher-tip-warning tips" href="%1$s" data-tip="%2$s"  target="_blank">%3$s</a>',
			esc_url( $url ),
			$tool_tip,
			$status
		);
	}

	/**
	 * Duplicates a voucher template
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function duplicate_voucher_template() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the original post
		$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'wc-pdf-product-vouchers-duplicate-voucher-template_' . $id );

		$post = $this->get_template_to_duplicate( $id );

		// copy the template and insert it
		if ( $post instanceof \WP_Post ) {

			$new_id = $this->duplicate_template( $post );

			/**
			 * Fires after a voucher template has been duplicated
			 *
			 * If you have written a plugin which uses non-WP database tables to save
			 * information about a template you can hook this action to duplicate that data.
			 *
			 * @since 3.0.0
			 * @param int $new_id new template id
			 * @param \WP_Post $post original template post object
			 */
			do_action( 'wc_pdf_product_vouchers_duplicate_voucher_template', $new_id, $post );

			wc_pdf_product_vouchers()->get_message_handler()->add_message( __( 'Gift certificate template copied.', 'woocommerce-pdf-product-vouchers' ) );

			// redirect to the edit (customizer) screen for the new template
			wp_safe_redirect( get_edit_post_link( $new_id ) );
			exit;

		} else {

			wp_die( __( 'Gift certificate template creation failed, could not find original template:', 'woocommerce-pdf-product-vouchers' ) . ' ' . $id );
		}
	}


	/**
	 * Creates a duplicate voucher template
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 * @param \WP_Post $post the post object
	 * @param int $parent (optional) defaults to 0
	 * @param string $post_status (optional) defaults to `private`
	 * @return int new voucher template id
	 */
	public function duplicate_template( \WP_Post $post, $parent = 0, $post_status = 'private' ) {
		global $wpdb;

		$new_post_author   = wp_get_current_user();
		$new_post_date     = current_time( 'mysql' );
		$new_post_date_gmt = get_gmt_from_date( $new_post_date );

		if ( $parent > 0 ) {
			$post_parent = $parent;
			$suffix      = '';
		} else {
			$post_parent = $post->post_parent;
			$suffix      = ' ' . __( '(Copy)', 'woocommerce-pdf-product-vouchers' );
		}

		// insert the new template in the post table
		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_author'           => $new_post_author->ID,
				'post_date'             => $new_post_date,
				'post_date_gmt'         => $new_post_date_gmt,
				'post_content'          => $post->post_content,
				'post_content_filtered' => $post->post_content_filtered,
				'post_title'            => $post->post_title . $suffix,
				'post_excerpt'          => $post->post_excerpt,
				'post_status'           => $post_status,
				'post_type'             => $post->post_type,
				'comment_status'        => $post->comment_status,
				'ping_status'           => $post->ping_status,
				'post_password'         => $post->post_password,
				'to_ping'               => $post->to_ping,
				'pinged'                => $post->pinged,
				'post_modified'         => $new_post_date,
				'post_modified_gmt'     => $new_post_date_gmt,
				'post_parent'           => $post_parent,
				'menu_order'            => $post->menu_order,
				'post_mime_type'        => $post->post_mime_type,
			)
		);

		$new_post_id = $wpdb->insert_id;

		// copy the meta information
		$this->duplicate_post_meta( $post->ID, $new_post_id );

		return $new_post_id;
	}


	/**
	 * Copies the meta information of a voucher template to another template
	 *
	 * @since 3.0.0
	 * @param mixed $id original template id
	 * @param mixed $new_id new (duplicate) template id
	 */
	private function duplicate_post_meta( $id, $new_id ) {
		global $wpdb;

		$post_meta_infos = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE post_id=%d
		", absint( $id ) ) );

		if ( count( $post_meta_infos ) > 0 ) {

			$sql_query_sel = array();
			$sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

			foreach ( $post_meta_infos as $meta_info ) {

				$meta_key        = $meta_info->meta_key;
				$meta_value      = $meta_info->meta_value;
				$sql_query_sel[] = $wpdb->prepare( "SELECT %d, '$meta_key', '$meta_value'", $new_id );
			}

			$sql_query .= implode( ' UNION ALL ', $sql_query_sel );

			$wpdb->query( $sql_query );
		}
	}


	/**
	 * Returns a voucher template post object from the database to duplicate.
	 *
	 * @since 3.0.0
	 * @param mixed $id template (post) id to duplicate
	 * @return \WP_Post|bool the voucher template post object if found, false otherwise
	 */
	private function get_template_to_duplicate( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $id ) );

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {

			$id   = $post->post_parent;
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE ID=%d", $id ) );
		}

		return new \WP_Post( $post );
	}


}
