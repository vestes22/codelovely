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

namespace GoDaddy\WordPress\MWC\GiftCertificates;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * WooCommerce Voucher Template class
 *
 * The WooCommerce PDF Product Voucher Template class gets voucher template.
 * The voucher template can be thought of as the blueprint for a voucher, it
 * contains everything needed to create a voucher (one or more images, the
 * coordinates for a number of fields, expiry days, etc).
 *
 * @since 3.0.0
 */
class WC_Voucher_Template extends MWC_Gift_Certificate_Base {


	/** @var int voucher template (post) id */
	public $id;

	/** @var string voucher template name */
	public $name;

	/** @var \WP_Post Voucher Template post object */
	public $post;


	/**
	 * Constructs voucher template with $id
	 *
	 * @since 3.0.0
	 * @param int|\WP_Post|WC_Voucher_Template $id voucher template id or post object
	 */
	public function __construct( $id ) {

		parent::__construct( $id );

		if ( $this->post ) {
			$this->name = $this->post->post_title;
		}
	}


	/**
	 * Returns the template name
	 *
	 * @since 3.0.0
	 * @return string voucher template name
	 */
	public function get_name() {
		return $this->name;
	}


	/** Image methods ******************************************************/


	/**
	 * Returns the primary image id for the voucher template
	 *
	 * @since 3.0.0
	 * @return int image (attachment) id
	 */
	public function get_image_id() {

		$image_id = parent::get_image_id();

		/**
		 * Filters the primary image id for the voucher template
		 *
		 * This filter exists mainly to allow the Customizer to load
		 * the image_id from the changeset. Use with caution!
		 *
		 * @since 3.0.0
		 * @param int $image_id image (attachment) id
		 * @param WC_Voucher_Template $voucher_template voucher template object
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_template_image_id', $image_id, $this );
	}


	/**
	 * Returns the additional image ID for the voucher template
	 *
	 * @since 3.0.0
	 *
	 * @return int image (attachment id)
	 */
	public function get_additional_image_id() {

		$image_id = get_post_meta( $this->id, '_additional_image_id', true );

		/**
		 * Filters the additional image ID for the voucher template
		 *
		 * This filter exists mainly to allow the Customizer to load
		 * the image_id from the changeset. Use with caution!
		 *
		 * @since 3.0.0
		 *
		 * @param int $image_id image (attachment) id
		 * @param WC_Voucher_Template $voucher_template voucher template object
		 */
		return (int) apply_filters( 'wc_pdf_product_vouchers_voucher_template_additional_image_id', $image_id, $this );
	}


	/**
	 * Returns the logo image ID for the voucher template.
	 *
	 * @since 3.0.0
	 *
	 * @return int logo (attachment id)
	 */
	public function get_logo_id() {

		$logo_id = get_post_meta( $this->id, '_logo_image_id', true );

		/**
		 * Filters the logo image ID for the voucher template
		 *
		 * This filter exists mainly to allow the Customizer to load
		 * the image_id from the changeset. Use with caution!
		 *
		 * @since 3.0.0
		 *
		 * @param int $logo_id logo (attachment) id
		 * @param WC_Voucher_Template $voucher_template voucher template object
		 */
		return (int) apply_filters( 'wc_pdf_product_vouchers_voucher_template_logo_image_id', $logo_id, $this );
	}


	/**
	 * Returns all the available image IDs for the voucher template
	 *
	 * @since 3.0.0
	 * @return int[] available image ids
	 */
	public function get_image_ids() {
		return get_post_meta( $this->get_id(), '_image_ids', true );
	}


	/**
	 * Returns image and thumbnail url and dimensions of all the available images for this voucher template.
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.0.0
	 * @return array[] associative array in format of image id => array()
	 */
	public function get_image_urls( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {

		$images = array();

		foreach ( $this->get_image_ids() as $image_id ) {

			$image_src = wp_get_attachment_image_src( $image_id, 'full' );
			$thumb_src = wp_get_attachment_image_src( $image_id, $size );

			if ( $image_src ) {
				$images[ $image_id ]['image']        = $image_src[0];
				$images[ $image_id ]['image_width']  = $image_src[1];
				$images[ $image_id ]['image_height'] = $image_src[2];
				$images[ $image_id ]['thumb']        = $thumb_src[0];
				$images[ $image_id ]['thumb_width']  = $thumb_src[1];
				$images[ $image_id ]['thumb_height'] = $thumb_src[2];
			}
		}

		return $images;
	}


	/**
	 * Returns voucher template image DPI.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	public function get_dpi() {

		$dpi = get_post_meta( $this->id, '_voucher_image_dpi', true );

		// fall back to default DPI of 300
		if ( ! $dpi || ! is_numeric( $dpi ) ) {
			$dpi = 300;
		}

		/**
		 * Filters the voucher image DPI
		 *
		 * @since 2.3.0
		 *
		 * @param int the voucher image DPI (default value: 300)
		 * @param WC_Voucher $voucher the voucher template object
		 */
		return (int) apply_filters( 'wc_pdf_product_vouchers_voucher_image_dpi', $dpi, $this );
	}


	/** Voucher field methods ******************************************************/


	/**
	 * Returns the number of days a voucher generated from this template should be valid for
	 *
	 * @since 3.0.0
	 * @return int|null any non-number or zero value implies infinite validity
	 */
	public function get_days_to_expiry() {

		return get_post_meta( $this->get_id(), '_expiration_date_days_to_expiry', true );
	}


	/**
	 * Returns field setting value
	 *
	 * @since 3.0.0
	 * @param string $field_id field identifier
	 * @param string $setting setting identifier
	 * @return mixed field value
	 */
	public function get_field_setting_value( $field_id, $setting ) {
		return get_post_meta( $this->get_id(), '_' . $field_id . '_' . $setting, true );
	}


	/**
	 * Returns the data type for the field identified by name
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.2.0
	 * @param string $name the field name
	 * @return string the field data type, one of 'property' or 'user_input'
	 */
	public function get_field_data_type( $name ) {

		$fields = self::get_voucher_fields();

		return isset( $fields[ $name ]['data_type'] ) ? $fields[ $name ]['data_type'] : 'property';
	}


	/**
	 * Returns the label for the field identified by name
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.2.0
	 * @param string $name the field name
	 * @return string the field label
	 */
	public function get_field_label( $name ) {

		$label = $this->get_field_setting_value( $name, 'label' );

		if ( ! $label ) {

			$fields = self::get_voucher_fields();
			$label  = isset( $fields[ $name ]['label'] ) ? $fields[ $name ]['label'] : null;
		}

		return $label;
	}


	/**
	 * Returns true if the field identified by name is a 'user_input' data type
	 *
	 * In 3.0.0 moved here from WC_Voucher, renamed from is_user_input_type_field()
	 * to is_user_input_field().
	 *
	 * @since 1.2.0
	 * @param string $name the field name
	 * @return boolean true if the field identified by $name is a 'user_input' data type
	 */
	public function is_user_input_field( $name ) {
		return 'user_input' === $this->get_field_data_type( $name );
	}


	/**
	 * Return an array of user-input voucher fields
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.0.0
	 * @return array associative array of user-input voucher fields
	 */
	public function get_user_input_voucher_fields() {

		$fields = array();

		foreach ( self::get_voucher_fields() as $name => $voucher_field ) {

			if ( empty( $voucher_field['data_type'] ) ) {
				continue;
			}

			if ( $this->is_user_input_field( $name ) && $this->user_input_field_is_enabled( $name ) ) {

				$voucher_field['label']     = $this->get_field_label( $name );
				$voucher_field['required']  = $this->user_input_field_is_required( $name );
				$voucher_field['maxlength'] = $this->get_user_input_field_max_length( $name );

				$fields[ $name ] = $voucher_field;
			}
		}

		return $fields;
	}


	/**
	 * Returns the maximum length for the user input field named $name.  This is
	 * enforced on the frontend so that the voucher text doesn't overrun the
	 * field area
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.0.0
	 * @param string $name the field name
	 * @return int the max length of the field, or empty string if there is no limit
	 */
	public function get_user_input_field_max_length( $name ) {
		return $this->get_field_setting_value( $name, 'max_length' );
	}


	/**
	 * Returns true if the user input field named $name is required, false otherwise
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.1.0
	 * @param string $name the field name
	 * @return boolean true if $name is required, false otherwise
	 */
	public function user_input_field_is_required( $name ) {
		return (bool) $this->get_field_setting_value( $name, 'is_required' );
	}


	/**
	 * Returns true if the user input field named $name is enabled, false otherwise
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 2.0.2
	 * @param string $name the field name
	 * @return boolean true if $name is enabled, false otherwise
	 */
	public function user_input_field_is_enabled( $name ) {
		return (bool) $this->get_field_setting_value( $name, 'is_enabled' );
	}


	/**
	 * Returns true if this voucher has any user input fields that are required
	 *
	 * In 3.0.0 moved here from WC_Voucher.
	 *
	 * @since 1.1.0
	 * @return boolean true if there is a required field
	 */
	public function has_required_input_fields() {

		foreach ( array_keys( self::get_voucher_fields() ) as $field_id ) {
			if ( $this->user_input_field_is_required( $field_id ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns voucher sample fields.
	 *
	 * Returns an array of field ids and their labels for Customizer preview purposes.
	 *
	 * @since 3.0.0
	 *
	 * @return array with field ids and labels
	 */
	public function get_sample_fields() {

		$voucher_fields = array();

		foreach ( self::get_voucher_fields() as $field_id => $attrs ) {
			$voucher_fields[ $field_id ] = $attrs['label'];
		}

		return $voucher_fields;
	}


	/**
	 * Checks if the template uses the voucher quantity field
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public function has_quantity_field() {

		$pos = get_post_meta( $this->id, '_product_quantity_pos', true );
		$pos = $pos ? explode( ',', $pos ) : null;

		// check if the field position is correctly configured. if yes, then
		// the template supports the quantity field
		return $pos ? count( $pos ) === 4 : false;
	}


	/**
	 * Checks whether vouchers using this template are redeemable online.
	 *
	 * @since 3.4.0
	 *
	 * @return bool
	 */
	public function is_redeemable_online() {

		return (bool) get_post_meta( $this->id, '_allow_online_redemptions', true );
	}


	/**
	 * Gets a list of product ids this voucher can be used to redeem.
	 *
	 * @since 3.4.0
	 *
	 * @return int[] an array of product ids
	 */
	public function get_redeemable_products() {
		global $wpdb;

		if ( 'multi' === $this->get_voucher_type() ) {
			return array();
		}

		$sql = $wpdb->prepare("
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key = '_wc_pdf_product_vouchers_redeemable_by'
			AND FIND_IN_SET( %s, meta_value )
		", $this->get_id() );

		$product_ids = $wpdb->get_col( $sql );

		return $product_ids ? array_map( 'absint', $product_ids ) : array();
	}


	/** Static methods ******************************************************/


	/**
	 * Returns voucher fields
	 *
	 * Returns an associative array of voucher fields - ie, the fields
	 * that appear on the voucher. Some of them may require user input,
	 * others not.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function get_voucher_fields() {

		$fields = array(

			'logo' => array(
				'data_type' => 'property',
				'label'     => __( 'Logo', 'woocommerce-pdf-product-vouchers' ),
			),

			'product_name' => array(
				'data_type' => 'property',
				'label'     => __( 'Product Name', 'woocommerce-pdf-product-vouchers' ),
			),

			'product_sku' => array(
				'data_type' => 'property',
				'label'     => __( 'Product SKU', 'woocommerce-pdf-product-vouchers' ),
			),

			'product_price' => array(
				'data_type' => 'property',
				'label'     => __( 'Product Price', 'woocommerce-pdf-product-vouchers' ),
			),

			'product_quantity' => array(
				'data_type' => 'property',
				'label'     => __( 'Product Quantity', 'woocommerce-pdf-product-vouchers' ),
			),

			'voucher_number' => array(
				'data_type' => 'property',
				'label'     => __( 'Gift Certificate Number', 'woocommerce-pdf-product-vouchers' ),
			),

			'expiration_date' => array(
				'data_type' => 'property',
				'label'     => __( 'Expiration Date', 'woocommerce-pdf-product-vouchers' ),
			),

			'purchaser_name' => array(
				'data_type' => 'user_input',
				'type'      => 'text',
				'label'     => __( 'Purchaser Name', 'woocommerce-pdf-product-vouchers' ),
			),

			'recipient_name' => array(
				'data_type' => 'user_input',
				'type'      => 'text',
				'label'     => __( 'Recipient Name', 'woocommerce-pdf-product-vouchers' ),
			),

			'recipient_email' => array(
				'data_type' => 'user_input',
				'type'      => 'email',
				'label'     => __( 'Recipient Email', 'woocommerce-pdf-product-vouchers' ),
			),

			'message' => array(
				'data_type' => 'user_input',
				'type'      => 'textarea',
				'label'     => __( 'Message', 'woocommerce-pdf-product-vouchers' ),
			),

			'barcode' => array(
				'data_type' => 'property',
				'label'     => __( 'Barcode', 'woocommerce-pdf-product-vouchers' ),
			),
		);

		/**
		 * Filters voucher fields
		 *
		 * @since 3.0.0
		 * @param array $fields associative array of voucher fields
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_fields', $fields );
	}


	/**
	 * Returns voucher user-input fields
	 *
	 * Returns an associative array of those voucher fields that
	 * expect user input.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function get_voucher_user_input_fields() {

		$fields      = self::get_voucher_fields();
		$user_fields = array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field_id => $field_data ) {

				if ( ! empty( $field_data['data_type'] ) && 'user_input' === $field_data['data_type'] ) {
					$user_fields[ $field_id ] = $field_data;
				}
			}
		}

		return $user_fields;
	}


	/**
	 * Returns default customizer settings for a voucher field
	 *
	 * @since 3.0.0
	 * @param string $data_type Optional. One of `property` or `user_input`. Defaults to `property`.
	 *                          Using `user_input` adds a few more settings for the field.
	 * @return array Field settings
	 */
	public static function get_voucher_field_default_settings( $data_type = 'property' ) {

		$settings = array(
			'pos',
			'font_family',
			'font_size',
			'font_style_b',
			'font_style_i',
			'text_align',
			'font_color',
		);

		if ( 'user_input' === $data_type )	{
			$settings = array_merge( $settings, array(
				'label',
				'max_length',
				'is_enabled',
				'is_required',
			) );
		}

		/**
		 * Filters default settings for a voucher field
		 *
		 * @since 3.0.0
		 * @param array $settings default field settings
		 * @param string $data_type field data type
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_field_default_settings', $settings, $data_type );
	}


	/**
	 * Returns customizer settings for a voucher field
	 *
	 * @since 3.0.0
	 * @param string $field_id
	 * @param string $data_type (optional) one of `property` or `user_input`
	 * @return array
	 */
	public static function get_voucher_field_settings( $field_id, $data_type ) {

		$settings = self::get_voucher_field_default_settings( $data_type );

		// recipient email does not have max length control
		if ( 'recipient_email' === $field_id ) {
			if ( ( $key = array_search( 'max_length', $settings ) ) !== false ) {
				unset( $settings[ $key ] );
			}
		}

		// special case - expiration date - has an extra setting for expiry days
		elseif ( 'expiration_date' === $field_id ) {
			$settings[] = 'days_to_expiry';
		}

		// special case - logo - has only logo & position settings
		elseif ( 'logo' === $field_id ) {
			$settings = array( 'image_id', 'pos' );
		} elseif ( 'barcode' === $field_id ) {
			$settings = array( 'barcode_type', 'pos', 'background_color', 'foreground_color' );
		}

		/**
		 * Filters settings for a voucher field
		 *
		 * @since 3.0.0
		 * @param array $settings default field settings
		 * @param string $field_id field identifier
		 * @param string $data_type field data type
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_field_settings', $settings, $field_id, $data_type );

	}


	/**
	 * Returns voucher field settings CSS configuration
	 *
	 * Returns an associative array with CSS configuration for each
	 * voucher field setting. The configuation must, at a minimum,
	 * include the CSS property to adjust.
	 * Additionally, it may include instructions for transofrming the value.
	 * The value will only be printed if it's non-falsy.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public static function get_voucher_field_settings_css_config() {

		$css_config = array(
			'font_family' => array(
				'property' => 'font-family',
			),
			'font_size' => array(
				'property' => 'font-size',
				'value'    => '{$value}px'
			),
			'font_style_b' => array(
				'property' => 'font-weight',
				'value'    => 'bold'
			),
			'font_style_i' => array(
				'property' => 'font-style',
				'value'    => 'italic'
			),
			'text_align' => array(
				'property' => 'text-align',
			),
			'font_color' => array(
				'property' => 'color',
			),
		);

		/**
		 * Filters voucher field settings CSS configuration
		 *
		 * @since 3.0.0
		 * @param $css_config
		 */
		return apply_filters( 'wc_pdf_product_vouchers_field_settings_css_config', $css_config );
	}


	/**
	 * Returns default customizer controls for a voucher field
	 *
	 * @since 3.0.0
	 * @param string $data_type (optional) one of `property` or `user_input`
	 * @return array associative array of field controls
	 */
	public static function get_voucher_field_default_controls( $data_type = 'property' ) {

		$controls = array(
			'pos' => array(
				'type'  => 'wc_pdf_product_vouchers_position',
				'label' => __( 'Position', 'woocommerce-pdf-product-vouchers' ),
			),

			'font_family' => array(
				'type'    => 'select',
				'label'   => __( 'Font', 'woocommerce-pdf-product-vouchers' ),
				'choices' => self::get_voucher_font_family_options(),
			),

			'font_size' => array(
				'type'        => 'range',
				'label'       => __( 'Font Size', 'woocommerce-pdf-product-vouchers' ),
				'input_attrs' => array(
					'min'   => 0,
					'max'   => 36,
					'step'  => 1,
				),
			),

			'font_settings' => array(
				'type'     => 'wc_pdf_product_vouchers_font_style',
				'label'    => __( 'Font Style', 'woocommerce-pdf-product-vouchers' ),
				'settings' => array(
					'font_style_b',
					'font_style_i',
					'text_align',
				),
			),

			'font_color' => array(
				'type'    => 'color',
				'label'   => __( 'Font Color', 'woocommerce-pdf-product-vouchers' ),
			),
		);

		if ( 'user_input' === $data_type )	{

			$controls = array_merge( $controls, array(

				'label' => array(
					'type'        => 'text',
					'label'       => __( 'Label', 'woocommerce-pdf-product-vouchers' ),
					'description' => __( 'The field label to show on the frontend/emails', 'woocommerce-pdf-product-vouchers' ),
				),

				'max_length' => array(
					'type'        => 'number',
					'label'       => __( 'Max Length', 'woocommerce-pdf-product-vouchers' ),
					'description' => __( 'The maximum number of characters of the field', 'woocommerce-pdf-product-vouchers' ),
					'input_attrs' => array(
						'min'   => 1,
						'step'  => 1,
						'placeholder' => __( 'No limit', 'woocommerce-pdf-product-vouchers' ),
					),
				),

				'is_enabled' => array(
					'type'        => 'checkbox',
					'label'       => __( 'Enabled', 'woocommerce-pdf-product-vouchers' ),
					'description' => __( 'Display this field on the product page', 'woocommerce-pdf-product-vouchers' ),
				),

				'is_required' => array(
					'type'        => 'checkbox',
					'label'       => __( 'Required', 'woocommerce-pdf-product-vouchers' ),
					'description' => __( 'Make this field required in order to add a gift certificate product to the cart', 'woocommerce-pdf-product-vouchers' ),
				),
			) );
		}

		/**
		 * Filters default Customizer controls for a voucher field
		 *
		 * @since 3.0.0
		 * @param array $controls default field controls
		 * @param string $data_type field data type
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_field_default_controls', $controls, $data_type );
	}


	/**
	 * Returns Customizer controls for a voucher field
	 *
	 * @since 3.0.0
	 * @param string $field_id field identifier
	 * @param array $attrs voucher field attributes
	 * @return array
	 */
	public static function get_voucher_field_controls( $field_id, $attrs ) {

		$data_type = ! empty( $attrs['data_type'] ) ? $attrs['data_type'] : 'property';
		$controls  = self::get_voucher_field_default_controls( $data_type );

		// set placeholder value for user-input label field
		if ( 'user_input' === $data_type && isset( $controls['label'] ) ) {

			if ( ! isset( $controls['label']['input_attrs'] ) ) {
				$controls['label']['input_attrs'] = array();
			}

			$controls['label']['input_attrs']['placeholder'] = $attrs['label'];
		}

		// recipient email does not have max length control
		if ( 'recipient_email' === $field_id && isset( $controls['max_length'] ) ) {
			unset( $controls['max_length'] );
		}

		// special case - expiration date - has an extra control for expiry days
		elseif ( 'expiration_date' === $field_id ) {
			$controls['days_to_expiry'] = array(
				'type'        => 'number',
				'label'       => __( 'Days to expiration', 'woocommerce-pdf-product-vouchers' ),
				'description' => __( 'Optional number of days after purchase until the gift certificate expires', 'woocommerce-pdf-product-vouchers' ),
				'input_attrs' => array(
					'min'         => 0,
					'step'        => 1,
					'placeholder' => __( 'days', 'woocommerce-pdf-product-vouchers' ),
				),
			);
		}

		// special case - product quantity - has an extra control for the quantity amount
		elseif ( 'product_quantity' === $field_id ) {
			$controls['amount'] = array(
				'type'        => 'number',
				'label'       => __( 'Amount', 'woocommerce-pdf-product-vouchers' ),
				'description' => __( 'Number of products/tickets/passes this gift certificate is eligible for', 'woocommerce-pdf-product-vouchers' ),
				'input_attrs' => array(
					'min'         => 1,
					'step'        => 1,
				),
			);
		}

		elseif ( 'logo' === $field_id ) {
			$controls = array(
				'image_id' => array(
					'type'          => 'image',
					'label'         => __( 'Logo', 'woocommerce-pdf-product-vouchers' ),
					'description'   => __( 'Optional image that can be displayed as a logo on the gift certificate.', 'woocommerce-pdf-product-vouchers' ),
					'button_labels' => array(
						'select'       => __( 'Select image', 'woocommerce-pdf-product-vouchers' ),
						'change'       => __( 'Change image', 'woocommerce-pdf-product-vouchers' ),
						'remove'       => __( 'Remove', 'woocommerce-pdf-product-vouchers' ),
						'default'      => __( 'Default', 'woocommerce-pdf-product-vouchers' ),
						'placeholder'  => __( 'No image selected', 'woocommerce-pdf-product-vouchers' ),
						'frame_title'  => __( 'Select gift certificate logo', 'woocommerce-pdf-product-vouchers' ),
						'frame_button' => __( 'Choose image', 'woocommerce-pdf-product-vouchers' ),
					),
				),
				'pos' => array(
					'type'  => 'wc_pdf_product_vouchers_position',
					'label' => __( 'Position', 'woocommerce-pdf-product-vouchers' ),
				)
			);
		} elseif ( 'barcode' === $field_id ) {
			$controls = array(
				'barcode_type' => array(
					'type'    => 'select',
					'label'   => esc_html__( 'Barcode Type', 'woocommerce-pdf-product-vouchers' ),
					'choices' => array(
						'qr'       => 'QR',
						'code-39'  => 'Code 39',
						'code-128' => 'Code 128',
						'dmtx'     => 'Data Matrix',
						'code-93'  => 'Code 93',
					),
				),
				'pos' => array(
					'type'  => 'wc_pdf_product_vouchers_barcode_position',
					'label' => esc_html__( 'Position', 'woocommerce-pdf-product-vouchers' ),
				),
				'background_color' => array(
					'type'    => 'color',
					'label'   => esc_html__( 'Background Color', 'woocommerce-pdf-product-vouchers' ),
				),
				'foreground_color' => array(
					'type'    => 'color',
					'label'   => esc_html__( 'Foreground Color', 'woocommerce-pdf-product-vouchers' ),
				),
			);
		}

		/**
		 * Filters Customizer controls for a voucher field
		 *
		 * @since 3.0.0
		 * @param array $controls field controls
		 * @param string $field_id field identifier
		 * @param string $data_type field data type
		 */
		return apply_filters( 'wc_pdf_product_vouchers_voucher_field_controls', $controls, $field_id, $data_type );
	}


	/**
	 * Gets available font family options.
	 *
	 * @since 3.0.0
	 *
	 * @param bool $include_default (optional) whether to include default option or not, defaults to true
	 * @return array
	 */
	private static function get_voucher_font_family_options( bool $include_default = true ) : array {

		$google_fonts  = wc_pdf_product_vouchers_get_available_google_fonts();
		$google_fonts  = array_combine( $google_fonts, $google_fonts );
		$default_fonts = [
			'Helvetica' => 'Helvetica',
			'Courier'   => 'Courier',
			'Times'     => 'Times',
		];

		/**
		 * Filters vouchers font family options.
		 *
		 * Allows actors to add additional font family options for voucher templates.
		 *
		 * @since 3.0.0
		 *
		 * @param array $fonts associative array of font options
		 */
		$options = (array) apply_filters( 'wc_pdf_product_vouchers_font_family_options', array_merge( $default_fonts, $google_fonts ) );

		if ( ! empty( $options ) ) {
			ksort( $options );
		}

		if ( $include_default ) {
			$options = [ '' => __( '(Default)', 'woocommerce-pdf-product-vouchers' ) ] + $options;
		}

		return $options;
	}


}
