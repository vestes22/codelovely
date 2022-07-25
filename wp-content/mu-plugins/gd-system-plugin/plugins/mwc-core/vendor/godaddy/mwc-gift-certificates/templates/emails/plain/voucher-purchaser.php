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

/**
 * Voucher purchaser plain email
 *
 * @type \WC_Order $order the order object associated with this email
 * @type string $email_heading the configurable email heading
 * @type int $voucher_count the number of vouchers being attached
 *
 * @version 3.2.2
 * @since 3.2.2
 */

defined( 'ABSPATH' ) or exit;

echo $email_heading . "\n\n";

printf( _n( "Hi there. Your gift certificate is ready!", "Hi there. You have %d gift certificates from your order ready!", $voucher_count, 'woocommerce-pdf-product-vouchers' ), $voucher_count );

echo "\n\n";

echo _n( 'You can find your gift certificate attached to this email.', 'You can find your gift certificates attached to this email.', $voucher_count, 'woocommerce-pdf-product-vouchers' );

echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
