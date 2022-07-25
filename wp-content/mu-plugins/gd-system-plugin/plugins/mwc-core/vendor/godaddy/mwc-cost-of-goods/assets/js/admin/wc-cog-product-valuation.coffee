"use strict"

jQuery ( $ ) ->

	$offset   = 0
	$status   = 'pending'
	$csv_data = "Product,Parent,Value at Retail,Value at Cost,Units in stock,Stock status\n"
	$export   = $( '.export_product_valuation_csv' )
	$process  = $( '.wc-cog-product-valuation-progress' )

	calculate_product_valuation = ->

		$.ajax
			type: 'POST'
			url: ajaxurl
			data: {
				action:   'wc_cog_do_ajax_product_valuation',
				offset:   $offset,
				status:   $status,
				security: wc_cog_product_valuation.product_valuation_nonce
			}
			dataType: 'json'
			success: ( response ) ->

				if response.success

					$offset       = response.data.offset
					$status       = response.data.status
					$product_data = response.data.product_data

					# Append product data as a CSV string
					if '' != $product_data
						$csv_data += $product_data + "\n"

					$( '.wc-cog-progress' ).val( response.data.percentage )

					# Check if batching process is completed
					if 'done' == $status

						setTimeout( ->

							# Show Export button and hide progress bar
							$export.removeClass( 'hide' )
							$process.addClass( 'hide' ).removeClass( 'show' )

							# Create dynamic link to download CSV file by clicking this link
							$link          = document.createElement( 'a' )
							$link.download = $export.data( 'filename' )
							$link.href     = URL.createObjectURL( new Blob( [ $csv_data ], { type: 'text/csv' } ) )

							document.body.appendChild( $link )
							$link.click()
							document.body.removeChild( $link )

						, 500 )

					# If status is not completed then process for next batch
					else
						calculate_product_valuation()

	$export.on 'click', ( e ) ->
		e.preventDefault()

		# Hide Export button and show process bar
		$export.addClass( 'hide' )
		$process.removeClass( 'hide' ).addClass( 'show' )

		# Start product valuation calculation
		calculate_product_valuation()
