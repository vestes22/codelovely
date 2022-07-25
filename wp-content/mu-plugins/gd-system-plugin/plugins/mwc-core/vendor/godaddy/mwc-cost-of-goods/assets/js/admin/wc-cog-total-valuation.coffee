"use strict"

jQuery ( $ ) ->

	$offset    = 0
	$at_cost   = 0
	$at_retail = 0
	$status    = 'pending'

	calculate_total_valuation = ->

		$.ajax
			type: 'POST'
			url: ajaxurl
			data: {
				action:   'wc_cog_do_ajax_total_valuation',
				offset:   $offset,
				cost:     $at_cost,
				retail:   $at_retail,
				status:   $status,
				security: wc_cog_total_valuation.total_valuation_nonce
			}
			dataType: 'json'
			success: ( response ) ->

				if response.success

					$offset    = response.data.offset
					$at_cost   = response.data.cost
					$at_retail = response.data.retail
					$status    = response.data.status

					$( '.wc-cog-progress' ).val( response.data.percentage )
					$( '.wc-cog-cost .amount' ).html( response.data.cost_html )
					$( '.wc-cog-retail .amount' ).html( response.data.retail_html )

					if 'done' == $status

						$( '.wc-cogs-total-valuation .loader' ).removeClass( 'show' ).addClass( 'hide' )

						setTimeout( ->
							$( '.wc-cog-progressbar-section' ).remove()
						, 500)

					else
						calculate_total_valuation()

	calculate_total_valuation()
