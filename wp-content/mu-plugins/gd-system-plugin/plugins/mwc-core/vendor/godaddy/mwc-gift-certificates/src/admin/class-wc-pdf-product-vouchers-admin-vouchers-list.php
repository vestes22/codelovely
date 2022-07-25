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

use GoDaddy\WordPress\MWC\GiftCertificates\WC_Voucher;
use WP_Query;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_status_name;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Vouchers List Admin
 *
 * @since 3.0.0
 */
class MWC_Gift_Certificates_Admin_Vouchers_List {


	/**
	 * Initializes the vouchers list admin
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		add_filter( 'bulk_actions-edit-wc_voucher',              [ $this, 'edit_voucher_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-wc_voucher',       [ $this, 'handle_voucher_bulk_actions' ], 10, 3 );
		add_action( 'admin_action_cancel_bulk_voucher_generate', [ $this, 'cancel_bulk_voucher_generate' ] );

		add_filter( 'views_edit-wc_voucher', [ $this, 'edit_voucher_views' ] );

		add_filter( 'manage_edit-wc_voucher_columns',          [ $this, 'edit_voucher_columns' ] );
		add_filter( 'manage_edit-wc_voucher_sortable_columns', [ $this, 'edit_voucher_sortable_columns' ] );

		add_action( 'manage_wc_voucher_posts_custom_column', [ $this, 'custom_voucher_columns' ], 2 );

		add_filter( 'display_post_states',   [ $this, 'edit_voucher_post_states' ], 10, 2 );
		add_filter( 'post_row_actions',      [ $this, 'edit_voucher_row_actions' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'add_voucher_filters' ], 10, 2 );
		add_filter( 'parse_query',           [ $this, 'filter_template_in_query' ] );

		// Filter & sort vouchers
		add_filter( 'request', [ $this, 'request_query' ] );

		// Search vouchers
		add_filter( 'get_search_query', [ $this, 'search_label' ] );
		add_filter( 'query_vars',       [ $this, 'add_custom_query_var' ] );
		add_action( 'parse_query',      [ $this, 'search_custom_fields' ] );
	}


	/**
	 * Removes the bulk edit action for vouchers and adds the bulk generate PDF action for vouchers.
	 *
	 * @since 3.0.0
	 * @param array $actions associative array of action identifier to name
	 * @return array associative array of action identifier to name
	 */
	public function edit_voucher_bulk_actions( $actions ) {

		unset( $actions['edit'] );

		// bulk generate requires background processing
		if ( wc_pdf_product_vouchers()->get_background_generator_instance()->test_connection() ) {
			$actions['wc_pdf_vouchers_generate_pdf'] = __( 'Generate PDF', 'woocommerce-pdf-product-vouchers' );
		}

		return $actions;
	}


	/**
	 * Handles the bulk actions for vouchers.
	 *
	 * @internal
	 *
	 * @since 3.6.0
	 *
	 * @param string $redirect_to URL to redirect to on finish
	 * @param string $doaction the bulk action being done
	 * @param int[] $post_ids the post IDs to perform the bulk action on
	 * @return string URL to redirect to on finish
	 */
	public function handle_voucher_bulk_actions( $redirect_to, $doaction, $post_ids ) {

		if ( 'wc_pdf_vouchers_generate_pdf' === $doaction ) {

			$background_generator = wc_pdf_product_vouchers()->get_background_generator_instance();
			$job_attrs            = [
				'voucher_ids' => array_map( 'intval', $post_ids ),
				'source'      => 'bulk_action'
			];

			$background_generator->create_job( $job_attrs );
			$background_generator->dispatch();
		}

		return $redirect_to;
	}


	/**
	 * Handles link clicks to cancel bulk voucher generate jobs.
	 *
	 * @internal
	 *
	 * @since 3.6.0
	 */
	public function cancel_bulk_voucher_generate() {

		$job_id = isset( $_GET['job_id'] ) ? $_GET['job_id'] : '';

		if ( empty( $job_id ) ) {
			return;
		}

		check_admin_referer( "cancel_bulk_voucher_generate_$job_id", 'security' );

		$background_generator = wc_pdf_product_vouchers()->get_background_generator_instance();

		$job = $background_generator->get_job( $job_id );

		if ( $job ) {
			$background_generator->fail_job( $job, __( 'Manually cancelled', 'woocommerce-pdf-product-vouchers' ) );
		}

		wp_redirect( add_query_arg( [ 'post_type' => 'wc_voucher' ], admin_url( 'edit.php' ) ) );
	}


	/**
	 * Modifies the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
	 * shown above the vouchers list table, to hide the publish/private states,
	 * which are not important and confusing for vouchers.
	 *
	 * @since 3.0.0
	 * @param array $views associative-array of view state name to link
	 * @return array associative array of view state name to link
	 */
	public function edit_voucher_views( $views ) {

		// publish and private are not important distinctions for vouchers
		unset( $views['publish'], $views['private'], $views['mine'] );

		return $views;
	}


	/**
	 * Modifies the list table columns on the Vouchers page
	 *
	 * @since 3.0.0
	 * @return array associative-array of column identifier to header names for the vouchers page
	 */
	public function edit_voucher_columns() {

		$status = ! empty( $_GET['post_status'] ) ? $_GET['post_status'] : null;

		$columns = array();

		$status_title = esc_attr__( 'Status', 'woocommerce-pdf-product-vouchers' );

		$columns['cb']              = '<input type="checkbox" />';
		$columns['voucher_status']  = '<span class="status_head tips" data-tip="' . $status_title . '">' . $status_title . '</span>';
		$columns['thumb']           = __( 'Image', 'woocommerce-pdf-product-vouchers' );
		$columns['title']           = __( 'Number', 'woocommerce-pdf-product-vouchers' );
		$columns['voucher_date']    = __( 'Date', 'woocommerce-pdf-product-vouchers' );

		if ( ! in_array( $status, array( 'wcpdf-redeemed', 'wcpdf-voided' ) ) ) {
			$columns['expiration_date']     = __( 'Expires', 'woocommerce-pdf-product-vouchers' );
			$columns['redeemable_products'] = __( 'Redeemable products', 'woocommerce-pdf-product-vouchers' );
		}

		if ( 'wcpdf-redeemed' !== $status ) {
			$columns['remaining_value'] = __( 'Remaining value', 'woocommerce-pdf-product-vouchers' );
		}

		$columns['voucher_actions'] = __( 'Actions', 'woocommerce-pdf-product-vouchers' );

		return $columns;
	}


	/**
	 * Modifies the list table sortable columns on the vouchers page
	 *
	 * @since 3.0.0
	 * @param array $columns associative-array of column identifier to header names
	 * @return array associative-array of column identifier to header names for the vouchers page
	 */
	public function edit_voucher_sortable_columns( $columns ){

		$columns['voucher_date']    = 'voucher_date';
		$columns['expiration_date'] = 'expiration_date';
		$columns['remaining_value'] = 'remaining_value';

		return $columns;
	}


	/**
	 * Customizes post display states in the vouchers list table
	 *
	 * @since 3.0.0
	 * @param array $post_states associative array in the form of [post status slug] => label
	 * @param \WP_Post $post the post object
	 * @return array
	 */
	public function edit_voucher_post_states( $post_states, $post ) {

		if ( 'wc_voucher' === $post->post_type ) {

			$post_states = array();
			$post_status = isset( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : '';

			// narrow post states display down to draft
			if ( 'draft' == $post->post_status && 'draft' != $post_status ) {
				$post_states['draft'] = __( 'Draft' );
			}

		}

		return $post_states;
	}


	/**
	 * Customizes voucher row actions
	 *
	 * @since 3.0.0
	 * @param array $actions associative array of row actions
	 * @param \WP_Post $post the post object
	 * @return array
	 */
	public function edit_voucher_row_actions( $actions, $post ) {

		if ( 'wc_voucher' === $post->post_type ) {

			unset( $actions['inline hide-if-no-js'], $actions['view'] );

			$actions = array( 'id' => sprintf( __( 'ID: %s', 'woocommerce-pdf-product-vouchers' ), $post->ID ) ) + $actions;
		}

		return $actions;
	}


	/**
	 * Adds custom filter to the list table for vouchers.
	 *
	 * @internal
	 *
	 * @since 3.6.0
	 *
	 * @param string $post_type the post type
	 * @param string $which location of the filter
	 */
	public function add_voucher_filters( $post_type, $which ) {

		if ( 'wc_voucher' === $post_type ) {

			$voucher_templates    = wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_templates();
			$filtered_template_id = isset( $_GET['voucher_template'] ) ? $_GET['voucher_template'] : '';

			?>
			<select
				name='voucher_template'
				class='wc-pdf-product-vouchers-voucher-template js-filter-voucher-template'
				name='_voucher_template'
				data-placeholder='<?php esc_attr_e( 'Filter by gift certificate template', 'woocommerce-pdf-product-vouchers' ); ?>'
				data-allow-clear='true' >

				<option value='' <?php selected( $filtered_template_id, '' ); ?>></option>

				<?php foreach ( $voucher_templates as $voucher_template ) : ?>
					<option value='<?php echo esc_attr( $voucher_template->get_id() ); ?>' <?php selected( $filtered_template_id, $voucher_template->get_id() ); ?> >
						<?php echo esc_html( $voucher_template->get_name() ); ?>
					</option>
				<?php endforeach; ?>

			</select>

			<?php
		}
	}


	/**
	 * Adds a filter for the voucher template to the query for the voucher list page.
	 *
	 * @internal
	 *
	 * @since 3.6.0
	 *
	 * @param WP_Query $query the query
	 * @return WP_Query
	 */
	public function filter_template_in_query( $query ) {
		global $post_type, $pagenow;

		if (    'edit.php' === $pagenow
		     && 'wc_voucher' === $post_type
		     && isset( $_GET['voucher_template'], $query->query_vars['post_type'] )
		     && '' !== $_GET['voucher_template']
		     && 'wc_voucher' === $query->query_vars['post_type'] ) {

			$query->query_vars['post_parent__in'] = [ (int) $_GET['voucher_template'] ];
		}

		return $query;
	}


	/**
	 * Customizes list table column values on the vouchers page
	 *
	 * @since 3.0.0
	 * @param string $column column identifier
	 */
	public function custom_voucher_columns( $column ) {

		global $post;

		$voucher = new WC_Voucher( $post->ID );

		switch ( $column ) {

			case 'voucher_status':

				$status      = $voucher->get_status();
				$status_name = wc_pdf_product_vouchers_get_voucher_status_name( $status );

				printf( '<mark class="%1$s tips" data-tip="%2$s">%2$s</mark>', sanitize_title( $status ), $status_name );

			break;

			case 'thumb':
				$edit_link = get_edit_post_link( $post->ID );
				$image     = $voucher->has_preview_image() ? $voucher->get_preview_image() : $voucher->get_image();

				echo '<a href="' . $edit_link . '">' . $image . '</a>';
			break;

			case 'voucher_date':
				echo $voucher->get_formatted_date();
			break;

			case 'redeemable_products':
				$redeemable_products = $voucher->get_redeemable_products();

				if ( ! empty( $redeemable_products ) ) {
					echo '<ul>';
					foreach ( $redeemable_products as $product_id ) {
						if ( $product = wc_get_product( $product_id ) ) {
							echo '<li><a href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . esc_html( $product->get_name() ) . '</a></li>';
						}
					}
					echo '</ul>';
				}
			break;

			case 'remaining_value':

				$display_value     = wc_price( $voucher->get_voucher_value_for_display(), [ 'currency' => $voucher->get_voucher_currency() ] );
				$display_remaining = wc_price( $voucher->get_remaining_value_for_display( false ), [ 'currency' => $voucher->get_voucher_currency() ] );

				if ( $voucher->get_voucher_tax() > 0 && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
					$display_value .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				/* translators: Placeholders: %1$s and %2$s - monetary value. Example: $30.50 of $50.00 */
				printf( esc_html__( '%1$s of %2$s', 'woocommerce-pdf-product-vouchers' ), $display_remaining, $display_value );

				// show how may uses the voucher still has
				if ( 'single' === $voucher->get_voucher_type() ) {

					$maximum_uses = $voucher->get_product_quantity();

					if ( $maximum_uses > 1 ) {

						$remaining_uses = $voucher->get_remaining_value() / $voucher->get_product_price();

						/* translators: Placeholders: %1$s - remaining uses of a voucher (number), %2$s - total uses a voucher can have (number) */
						echo '<br><small class="remaining-quantity">' . sprintf( esc_html__( '%1$s of %2$s uses', 'woocommerce-pdf-product-vouchers' ), max( 0, (int) round( $remaining_uses ) ), $maximum_uses ) . '</small>';
					}
				}

			break;

			case 'expiration_date':
				$expiration_date = $voucher->get_formatted_expiration_date();

				echo $expiration_date ? $expiration_date : esc_html__( 'Never', 'woocommerce-pdf-product-vouchers' );
			break;

			case 'voucher_actions':
				?><p>

					<input type="hidden" class="voucher-value" value="<?php echo esc_attr( $voucher->get_voucher_value() ); ?>" />
					<input type="hidden" class="voucher-tax" value="<?php echo esc_attr( $voucher->get_voucher_tax() ); ?>" />
					<input type="hidden" class="voucher-type" value="<?php echo esc_attr( $voucher->get_voucher_type() ); ?>" />
					<input type="hidden" class="voucher-product-price" value="<?php echo esc_attr( $voucher->get_product_price() ); ?>" />
					<input type="hidden" class="voucher-product-price-for-display" value="<?php echo esc_attr( $voucher->get_product_price_for_display() ); ?>" />
					<input type="hidden" class="voucher-remaining-value" value="<?php echo esc_attr( $voucher->get_remaining_value() ); ?>" />
					<input type="hidden" class="voucher-remaining-value-incl-tax" value="<?php echo esc_attr( $voucher->get_remaining_value_incl_tax() ); ?>" />
					<input type="hidden" class="voucher-remaining-value-for-display" value="<?php echo esc_attr( $voucher->get_remaining_value_for_display() ); ?>" />

					<?php
						/**
						 * Fires before the voucher actions in vouchers list view
						 *
						 * @since 3.0.0
						 * @param WC_Voucher $voucher the voucher object
						 */
						do_action( 'wc_pdf_product_vouchers_admin_voucher_actions_start', $voucher );

						$actions = array();

						if ( $voucher->has_status( 'active' ) ) {

							$actions['redeem'] = array(
								'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_pdf_product_vouchers_list_redeem_voucher&voucher_id=' . $post->ID ), 'vouchers-list-redeem-voucher' ),
								'name'   => __( 'Redeem', 'woocommerce-pdf-product-vouchers' ),
								'action' => 'redeem',
							);

							$actions['void'] = array(
								'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_pdf_product_vouchers_list_void_voucher&voucher_id=' . $post->ID ), 'vouchers-list-void-voucher' ),
								'name'   => _x( 'Void', 'verb', 'woocommerce-pdf-product-vouchers' ),
								'action' => 'void',
							);
						}

						if ( $voucher->file_exists() ) {

							$actions['download'] = array(
								'url'    => $voucher->get_download_url( 'admin' ),
								'name'   => _x( 'Download', 'verb', 'woocommerce-pdf-product-vouchers' ),
								'action' => 'download',
							);
						}

						$actions['view'] = array(
							'url'    => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
							'name'   => _x( 'View', 'verb', 'woocommerce-pdf-product-vouchers' ),
							'action' => 'view',
						);

						/**
						 * Filters the voucher actions in voucher list view
						 *
						 * @since 3.0.0
						 * @param array $actions
						 * @param WC_Voucher $voucher the voucher object
						 */
						$actions = apply_filters( 'wc_pdf_product_vouchers_admin_voucher_actions', $actions, $voucher );

						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}

						/**
						 * Fires after the voucher actions in vouchers list view
						 *
						 * @since 3.0.0
						 * @param WC_Voucher $voucher the voucher object
						 */
						do_action( 'wc_pdf_product_vouchers_admin_voucher_actions_end', $voucher );
					?>
				</p><?php
			break;
		}
	}


	/**
	 * Handles custom filters and sorting for the vouchers screen
	 *
	 * @since 3.0.0
	 * @param array $vars query vars for \WP_Query
	 * @return array modified query vars
	 */
	public function request_query( $vars ) {
		global $typenow;

		if ( 'wc_voucher' === $typenow ) {

			// Sorting
			if ( isset( $vars['orderby'] ) ) {

				if ( 'voucher_date' === $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'orderby' => 'post_title',
					) );
				}

				if ( 'remaining_value' === $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'meta_query' => $this->get_meta_order_query( '_remaining_value' ),
						'orderby'    => 'meta_value',
					) );
				}

				if ( 'expiration_date' === $vars['orderby'] ) {
					$vars = array_merge( $vars, array(
						'meta_query' => $this->get_meta_order_query( '_expiration_date' ),
						'orderby'    => 'meta_value',
					) );
				}
			}
		}

		return $vars;
	}


	/**
	 * Returns meta query for ordering by meta value
	 *
	 * Handles cases where meta key on a post may not
	 * exist.
	 *
	 * @since 3.0.0
	 * @param string $meta_key
	 * @return array
	 */
	private function get_meta_order_query( $meta_key ) {

		return array(
			'relation' => 'OR',
			array(
				'key'     => $meta_key,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => $meta_key,
				'compare' => 'EXISTS',
			),
		);
	}


	/**
	 * Searches voucher custom fields as well as content when parsing the query on vouchers screen
	 *
	 * @since 3.0.0
	 * @param WP_Query $wp query instance, passed by reference
	 */
	public function search_custom_fields( WP_Query $wp ) {
		global $pagenow;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] != 'wc_voucher' ) {
			return;
		}

		$post_ids = wc_pdf_product_vouchers()->get_voucher_handler_instance()->search_vouchers( $_GET['s'] );

		if ( ! empty( $post_ids ) ) {

			// remove "s" - we don't want to search voucher number if we have a match from oher fields
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['wc_voucher_search'] = true;

			// search by found posts
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}


	/**
	 * Adds query vars for custom searches
	 *
	 * @since 3.0.0
	 * @param mixed $public_query_vars the array of whitelisted query variables
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'wc_voucher_search';

		return $public_query_vars;
	}


	/**
	 * Modifies the search label when searching vouchers
	 *
	 * @since 3.0.0
	 * @param mixed $query
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow ) {
			return $query;
		}

		if ( $typenow != 'wc_voucher' ) {
			return $query;
		}

		if ( ! get_query_var( 'wc_voucher_search' ) ) {
			return $query;
		}

		return wp_unslash( $_GET['s'] );
	}


}
