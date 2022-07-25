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

namespace GoDaddy\WordPress\MWC\GiftCertificates\Admin\MetaBoxes\Views;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_10 as Framework;

/**
 * Voucher note template
 *
 * @type \WP_Comment $note voucher note (comment) instance
 *
 * @since 3.0.0
 * @version 3.5.0
 */
?>

<li rel="<?php echo absint( $note->comment_ID ); ?>" class="<?php echo implode( ' ', array_map( 'sanitize_html_class', $note_classes ) ); ?>">

	<div class="note-content">
		<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
	</div>

	<p class="meta">
		<?php

		$abbr_start = '<abbr class="exact-date" title="' . esc_attr( $note->comment_date ) . '">';
		$abbr_end   = '</abbr>';

		/* translators: Placeholders: Date (%1$s) and time (%2$s) when a voucher note was added */
		$note_meta = sprintf( $abbr_start . __( 'added on %1$s at %2$s', 'woocommerce-pdf-product-vouchers' ) . $abbr_end,
			date_i18n( wc_date_format(), strtotime( $note->comment_date ) ),
			date_i18n( wc_time_format(), strtotime( $note->comment_date ) )
		);

		if ( $note->comment_author !== __( 'WooCommerce', 'woocommerce-pdf-product-vouchers' ) ) {

			/* translators: Placeholders: %1$s - voucher note published date and time; %2$s voucher note published by - for example "On 1 October 2020 at 10:25am by John Doe" */
			$note_meta = sprintf( __( '%1$s by %2$s', 'woocommerce-pdf-product-vouchers' ),
				$note_meta,
				$note->comment_author
			);
		}

		echo $note_meta;

		?>
		<a href="#" class="delete-note js-delete-note"><?php esc_html_e( 'Delete note', 'woocommerce-pdf-product-vouchers' ); ?></a>
	</p>

</li>
