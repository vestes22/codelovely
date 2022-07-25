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

use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers;
use function GoDaddy\WordPress\MWC\GiftCertificates\wc_pdf_product_vouchers_get_voucher_template;
use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * PDF Product Vouchers Products Admin
 *
 * @since 1.2.0
 */
class MWC_Gift_Certificates_Admin_Products {


	/**
	 * Initializes the voucher products admin
	 *
	 * @since 1.2.0
	 */
	public function __construct() {

		// assign voucher to a product
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_meta' ), 15 );

		// assign voucher to a variable product
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'process_product_meta_variable' ), 15 );
		add_action( 'woocommerce_ajax_save_product_variations',  array( $this, 'process_product_meta_variable' ), 15 );

		// voucher select input in simple product data meta box
		add_filter( 'product_type_options', array( $this, 'add_voucher_option' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'product_voucher_options' ) );

		// voucher select input in variable product data meta box
		add_action( 'woocommerce_variation_options', array( $this, 'add_variation_voucher_option' ), 10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'product_variation_voucher_options' ), 10, 3 );

		add_filter( 'woocommerce_product_filters', array( $this, 'add_voucher_product_filter' ) );

		// parse product list filters query
		add_filter( 'parse_query', array( $this, 'product_filters_query' ) );
	}


	/**
	 * Assigns a voucher template to a simple product, from the Admin Product edit page
	 *
	 * @since 1.2.0
	 * @param int $post_id the product id
	 */
	public function process_product_meta( $post_id ) {

		$has_voucher = isset( $_POST['_has_voucher'] ) ? 'yes' : 'no';

		update_post_meta( $post_id, '_has_voucher', $has_voucher );

		// set the voucher id
		if ( 'yes' === $has_voucher && isset( $_POST['_voucher_template_id'] ) ) {

			update_post_meta( $post_id, '_voucher_template_id', (int) $_POST['_voucher_template_id'] );

			$voucher_template = wc_pdf_product_vouchers_get_voucher_template( $_POST['_voucher_template_id'] );

			if ( wc_tax_enabled() && $voucher_template ) {

				$product = wc_get_product( $post_id );

				if ( 'taxable' !== $product->get_tax_status() && 'single' === $voucher_template->get_voucher_type() ) {
					wc_pdf_product_vouchers()->get_message_handler()->add_message( sprintf( '%s %s', __( 'Quick note: This product has a single-purpose gift certificate, so it may need to be taxable.', 'woocommerce-pdf-product-vouchers' ), '<a href="https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/#types-and-taxes">' . __( 'View Documentation', 'woocommerce-pdf-product-vouchers' ) . '</a>' ) );
				} elseif ( 'taxable' === $product->get_tax_status() && 'multi' === $voucher_template->get_voucher_type() ) {
					wc_pdf_product_vouchers()->get_message_handler()->add_message( sprintf( '%s %s', __( 'Quick note: This product has a multi-purpose gift certificate, so it may need to be non-taxable.', 'woocommerce-pdf-product-vouchers' ), '<a href="https://docs.woocommerce.com/document/woocommerce-pdf-product-vouchers/#types-and-taxes">' . __( 'View Documentation', 'woocommerce-pdf-product-vouchers' ) . '</a>' ) );
				}

			}
		}
	}


	/**
	 * Assigns a voucher template to a product variation
	 *
	 * @since 1.2.0
	 * @param int $post_id the product id
	 */
	public function process_product_meta_variable( $post_id ) {

		if ( isset( $_POST['variable_sku'] ) ) {

			$variable_post_id             = $_POST['variable_post_id'];
			$variable_voucher_template_id = isset( $_POST['variable_voucher_template_id'] ) ? $_POST['variable_voucher_template_id'] : array();
			$variable_has_voucher         = isset( $_POST['variable_has_voucher'] ) ? $_POST['variable_has_voucher'] : array();
			$max_loop                     = max( array_keys( $_POST['variable_post_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i++ ) {

				if ( ! isset( $variable_post_id[ $i ] ) ) {
					continue;
				}

				$variation_id = (int) $variable_post_id[ $i ];

				$has_voucher = isset( $variable_has_voucher[ $i ] ) ? 'yes' : 'no';

				update_post_meta( $variation_id, '_has_voucher', wc_clean( $has_voucher ) );

				// set or remove the voucher id
				if ( 'yes' === $has_voucher && isset( $variable_voucher_template_id[ $i ] ) ) {
					update_post_meta( $variation_id, '_voucher_template_id', (int) $variable_voucher_template_id[ $i ] );
				}
			}
		}
	}


	/**
	 * Adds the voucher product type option to product data meta box
	 *
	 * @since 3.0.0
	 * @param array $options array of product type options
	 * @return array $options
	 */
	public function add_voucher_option( $options ) {

		$options['has_voucher'] = array(
			'id'            => '_has_voucher',
			'wrapper_class' => 'show_if_simple',
			'label'         => __( 'Has Gift Certificate', 'woocommerce-pdf-product-vouchers' ),
			'description'   => __( 'Gift certificate products give access to a PDF gift certificate upon purchase.', 'woocommerce-pdf-product-vouchers' ),
			'default'       => 'no',
		);

		return $options;
	}


	/**
	 * Displays the voucher select box in the Product Data meta box on the
	 * product edit page for the Simple Product type
	 *
	 * In 3.0.0 renamed from product_options_downloads() to product_voucher_options().
	 *
	 * @since 1.2.0
	 */
	public function product_voucher_options() {

		$options = array( '' => '' );

		// get all the published vouchers
		foreach ( wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_templates() as $voucher_template ) {
			$options[ $voucher_template->get_id() ] = $voucher_template->get_name();
		}

		echo '<div class="options_group show_if_has_voucher" style="display:none;">';

		woocommerce_wp_select( array(
			'id'          => '_voucher_template_id',
			'label'       => __( 'Gift Certificate Template', 'woocommerce-pdf-product-vouchers' ),
			'description' => __( 'Select a gift certificate template to make this into a gift certificate product.', 'woocommerce-pdf-product-vouchers' ),
			'options'     => $options,
			'desc_tip'    => true,
		) );

		echo '</div>';
	}


	/**
	 * Adds voucher product type option to variation options
	 *
	 * @since 3.0.0
	 * @param int $loop loop counter
	 * @param array $variation_data associative array of variation data
	 * @param \WP_Post $variation variation post object
	 */
	public function add_variation_voucher_option( $loop, $variation_data, \WP_Post $variation ) {

		$has_voucher = get_post_meta( $variation->ID, '_has_voucher', true );

		?>
		<label><input type="checkbox" class="checkbox variable_has_voucher" name="variable_has_voucher[<?php echo $loop; ?>]" <?php checked( isset( $has_voucher ) ? $has_voucher : '', 'yes' ); ?> /> <?php esc_html_e( 'Has Gift Certificate?', 'woocommerce-pdf-product-vouchers' ); ?> <?php echo wc_help_tip( __( 'Enable this option if you want to attach a gift certificate to this variation', 'woocommerce-pdf-product-vouchers' ) ); ?></label>
		<?php
	}


	/**
	 * Displays the voucher select box in the product variation meta box for
	 * variable products
	 *
	 * In 3.0.0 renamed from product_after_variable_attributes() to product_variation_voucher_options().
	 *
	 * @since 1.2.0
	 * @param int $loop loop counter
	 * @param array $variation_data associative array of variation data
	 * @param \WP_Post $variation variation post object
	 */
	public function product_variation_voucher_options( $loop, $variation_data, \WP_Post $variation ) {

		// add meta data to the array
		$variation_data = array_merge( get_post_meta( $variation->ID ), $variation_data );
		$options        = array( '' => '' );

		// get all the voucher templates
		foreach ( wc_pdf_product_vouchers()->get_voucher_handler_instance()->get_voucher_templates() as $voucher_template ) {
			$options[ $voucher_template->get_id() ] = $voucher_template->get_name();
		}

		?>
		<div class="show_if_variation_has_voucher" style="display: none;">
			<p class="form-row form-row-first">
				<label>
					<?php
						esc_html_e( 'Gift Certificate Template:', 'woocommerce-pdf-product-vouchers' );
						echo wc_help_tip( __( 'Select a gift certificate template to make this variation into a gift certificate product.', 'woocommerce-pdf-product-vouchers' ) );
					?>
				</label>
				<select class="variable_voucher" name="variable_voucher_template_id[<?php echo $loop; ?>]">
					<?php
						foreach ( $options as $voucher_template_id => $name ) {
							echo '<option value="' . $voucher_template_id . '" ';
							if ( isset( $variation_data['_voucher_template_id'][0] ) ) selected( $voucher_template_id, $variation_data['_voucher_template_id'][0] );
							echo '>' . $name . '</option>';
						}
					?>
				</select>
			</p>
		</div>
		<?php
	}


	/**
	 * Adds the voucher product type to products filter
	 *
	 * @since 3.0.0
	 * @param string $output
	 * @return string $output
	 */
	public function add_voucher_product_filter( $output ) {
		global $wp_query;

		// construct virtual product option, based on \WC_Admin_Post_Types::product_filters()
		$virtual_option = '<option value="virtual" ';

		if ( isset( $wp_query->query['product_type'] ) ) {
			$virtual_option .= selected( 'virtual', $wp_query->query['product_type'], false );
		}

		$virtual_option .= '> &rarr; ' . esc_html__( 'Virtual', 'woocommerce' ) . '</option>';

		// construct voucher product option, based on \WC_Admin_Post_Types::product_filters()
		$voucher_option = '<option value="voucher" ';

		if ( isset( $wp_query->query['product_type'] ) ) {
			$voucher_option .= selected( 'voucher', $wp_query->query['product_type'], false );
		}

		$voucher_option .= '> &rarr;  ' . esc_html__( 'Has Gift Certificate', 'woocommerce-pdf-product-vouchers' ) . '</option>';

		// inject voucher product option after virtual product option
		$output = str_replace( $virtual_option, $virtual_option . $voucher_option, $output );

		return $output;
	}


	/**
	 * Filters the products in admin based on options
	 *
	 * @since 3.0.0
	 * @param mixed $query
	 */
	public function product_filters_query( $query ) {
		global $typenow;

		if ( 'product' == $typenow && isset( $query->query_vars['product_type'] ) ) {

			// Subtypes
			if ( 'voucher' == $query->query_vars['product_type'] ) {
				$query->query_vars['product_type'] = '';
				$query->query_vars['meta_value']   = 'yes';
				$query->query_vars['meta_key']     = '_has_voucher';
				$query->is_tax = false;
			}
		}
	}


}
