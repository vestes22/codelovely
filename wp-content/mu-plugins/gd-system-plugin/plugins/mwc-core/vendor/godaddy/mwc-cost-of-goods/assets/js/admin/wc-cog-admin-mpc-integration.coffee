###*
# WooCommerce Cost of Goods - Measurement Price Calculator integration
#
# Copyright (c) 2013-2018, SkyVerge, Inc.
# Licensed under the GNU General Public License v3.0
# http://www.gnu.org/licenses/gpl-3.0.html
#
# @since 2.7.0
###

"use strict"

jQuery ( $ ) ->


	###*
	# Adds 'per unit' to the Cost of Good label for pricing calculator products.
	#
	# @since 2.7.0
	#
	# @param String pricing_label Cost of Good input pricing_label
	###
	add_cost_per_unit_label = ( pricing_label ) ->

		update_cost_of_goods_unit_pricing_label( $( 'label[for="_wc_cog_cost"]' ), pricing_label )
		update_cost_of_goods_unit_pricing_label( $( 'label[for="_wc_cog_cost_variable"]' ), pricing_label )

		$( '._wc_cog_variation_cost label' ).each ->
			update_cost_of_goods_unit_pricing_label( $( this ), pricing_label )


	###*
	# Removes 'per unit' from the Cost of Good label for pricing calculator products.
	#
	# @since 2.7.0
	###
	remove_cost_per_unit_label = ->

		update_cost_of_goods_unit_pricing_label( $( 'label[for="_wc_cog_cost"]' ), '' )
		update_cost_of_goods_unit_pricing_label( $( 'label[for="_wc_cog_cost_variable"]' ), '' )

		$( '._wc_cog_variation_cost label' ).each ->
			update_cost_of_goods_unit_pricing_label( $( this ), '' )


	###*
	# Extends a currency symbol in a label to indicate the amount is 'per unit'.
	#
	# @since 2.8.0
	#
	# @param Object $el jQuery object
	# @param String pricing_label Cost of Good input pricing_label
	###
	update_cost_of_goods_unit_pricing_label = ( $el, pricing_label ) ->

		# either the currency alone or the currency pricing per unit is wrapped in a span tag
		$span    = $el.find( 'span' )
		currency = String( wc_cog_admin.woocommerce_currency_symbol )

		if $span and currency

			# if replacing the pricing per unit or the plain currency with a pricing label, insert a slash in between,
			# otherwise, if pricing_label is blank, it will just restore the currency when removing the unit label
			if pricing_label.length > 0
				pricing_label = ' / ' + pricing_label

			$span.text( $span.text().replace( $span.text(), currency + pricing_label ) )


	# "Set Product Pricing Per Unit" checkbox handler: this enables the "pricing
	# calculator" mode by adding/removing the 'per unit' label for Cost of Good
	# for pricing calculator products, both on page load, and when the checkbox
	# element is toggled.
	$( '._measurement_pricing_calculator_enabled' ).on 'change', ->

		# if the current measurement pricing toggle is associated with the
		# currently selected measurement pricing calculator type (Dimensions, Area,
		# Area (LxW), etc) and the parent field 'Show Product Price Per Unit' is
		# also enabled
		if '_measurement_' + $( '#_measurement_price_calculator' ).val() + '_pricing_calculator_enabled' is $( this ).attr( 'id' )

			if $( this ).closest( 'div.measurement_fields' ).find( '._measurement_pricing' ).is( ':checked' ) and $( this ).is( ':checked' )
				# update the product label like 'Price ($)' to 'Price ($/sq ft)'
				add_cost_per_unit_label($(this).closest('div').find('._measurement_pricing_unit option:selected').text() )
			else
				# back to quantity calculator, unwind the above
				remove_cost_per_unit_label()

	# perhaps the pricing calculator was disabled
	$( '#_measurement_price_calculator' ).on 'change', ->

		measurement = $( this ).val()

		if measurement.length < 1
			remove_cost_per_unit_label()
		else
			$( '._measurement_pricing_calculator_enabled' ).trigger( 'change' )

	# the product type was changed
	$( '#product-type' ).on 'change', ->
		$( '#_measurement_price_calculator' ).trigger( 'change' )
