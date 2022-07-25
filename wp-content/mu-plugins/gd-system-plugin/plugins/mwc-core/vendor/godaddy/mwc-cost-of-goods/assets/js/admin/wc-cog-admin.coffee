"use strict"

jQuery ( $ ) ->


	# Variable Products Handling:
	$( 'select#field_to_edit' ).on( 'variable_cost_of_good_ajax_data', ->
		return value : window.prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value )
	)


	# Auto-fill the quick-edit fields with the product data:
	$( '#the-list' ).on 'click', '.editinline', ( e ) ->

		post_id = $( @ ).closest( 'tr' ).attr( 'id' )

		post_id = post_id.replace( 'post-', '' )

		inline_data = $( '#wc_cog_inline_' + post_id )

		cost = inline_data.find( '.cost' ).text()

		$( 'input[name="_wc_cog_cost"]' ).val( cost )


	# Cost of goods suggestion on changing quantity in order back-end.
	$( '#woocommerce-order-items' ).on 'change', 'input.quantity', ( e ) ->

		e.preventDefault()

		$row            = $( this ).closest( 'tr.item' )
		$qty            = $( this ).val()
		$o_qty          = $( this ).attr( 'data-qty' )
		$cog_total      = $( 'input.cog-total', $row )
		$cog_suggestion = $( 'input.cog-suggestion', $row )

		$unit_total = accounting.unformat( $cog_total.attr( 'data-cog-total' ), '.' )
		$unit_total = $unit_total / $o_qty

		$cog_suggestion.val(
			accounting.formatNumber( $unit_total * $qty, woocommerce_admin_meta_boxes.currency_format_num_decimals, '', woocommerce_admin_meta_boxes.currency_format_decimal_sep ).toString()
		)
