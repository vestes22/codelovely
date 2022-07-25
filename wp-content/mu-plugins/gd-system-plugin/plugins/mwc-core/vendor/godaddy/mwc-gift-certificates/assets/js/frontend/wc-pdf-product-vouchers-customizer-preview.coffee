"use strict"

###*
# WooCommerce PDF Product Voucher Templates Customizer Preview scripts
#
# @since 3.0.0
###
( ( wp, $ ) ->

	if ( ! wp || ! wp.customize )
		return

	api     = wp.customize
	preview = wc_voucher_template_preview

	# Note on the image area select:  I have to be very brute force
	# with this thing unfortunately and create/remove it with every
	# selection start, because otherwise I can't get the thing to
	# update the selection position, or to resize properly if the
	# browser window is resized.
	# And it still doesn't resize the selection box as the image is
	# resized due to the browser window shrinking/growing, but oh well
	# can't have it all
	ias = undefined

	# draw any positioned voucher fields on the primary image & adjust
	# the position & font size based on the primary image scale
	redrawVoucherFieldPlaceholders = ->

		image  = api( 'wc_voucher_template_voucher_primary_image' ).get()
		$image = $( '#voucher-image img' )

		# calculate image scale, when image is resized due to browser being shrunk
		# or using a high-resolution image
		if image.width isnt $image.width()
			preview.image_scale = $image.width() / image.width
		else
			preview.image_scale = 1

		# scale default font size
		setFontSize( 'voucher', calculateScaledFontSize( api( 'wc_voucher_template_voucher_font_size' ).get() ) )

		# adjust voucher field placeholder positions
		$.each preview.voucher_fields, ( index, field_id ) ->

			$field = $( '#voucher #' + field_id )

			# if the image is removed, hide the field
			return $field.hide() if '' is $image.attr( 'src' )


			position = api( 'wc_voucher_template_' + field_id + '_pos' ).get()

			# get the scaled field position
			position = if position then position.split(',').map(( (n) ->
				parseInt( n, 10 ) * preview.image_scale
			)) else null

			if position

				pos_atts =
					'left':      position[0] + 'px'
					'top':       position[1] + 'px'
					'width':     position[2] + 'px'
					'height':    position[3] + 'px'

				$field.css( pos_atts ).show()

				# update scaled font size
				font_size      = null
				font_size_prop = api( 'wc_voucher_template_' + field_id + '_font_size' )

				if font_size_prop
					font_size = font_size_prop.get()

				if font_size
					setFontSize( field_id, calculateScaledFontSize( font_size ) )
			else
				$field.hide()

	calculateScaledFontSize = ( font_size ) ->
		font_scale = api( 'wc_voucher_template_voucher_image_dpi' ).get() / 72 * preview.image_scale
		font_size * font_scale

	setFontSize = ( field_id, value ) ->

		config = preview.css_config.font_size

		setFieldCssValue( field_id, config, value )

	setFieldCssValue = ( field_id, config, value ) ->

		css_value = if value then config.value.replace( '{$value}', value ) else ''

		$( '#' + field_id ).css( config.property, css_value	)

	removeImgAreaSelect = ->
		$( '#voucher-image img' ).imgAreaSelect remove: true
		redrawVoucherFieldPlaceholders()

	voucherFieldAreaSelect = ( field_id, aspect_ratio = null ) ->

		# no voucher image
		return unless $( '#voucher-image img' ).attr( 'src' )

		# always clear the image select area, if any
		removeImgAreaSelect()

		# clicked 'done', return the button to normal and remove the area select overlay
		# if $( '#' + field_id ).val() == woocommerce_vouchers_params.done_label
		# 	$( '#' + field_id ).val woocommerce_vouchers_params.set_position_label

		$field = $( '#voucher #' + field_id )

		# make sure the voucher field placeholder for this field is hidden
		$field.hide()

		position = api( 'wc_voucher_template_' + field_id + '_pos' ).get()
		image    = api( 'wc_voucher_template_voucher_primary_image' ).get()

		coords = if position then position.split(',').map(( (n) ->
			parseInt n, 10
		)) else [
			null
			null
			null
			null
		]

		# create the image area select element
		ias = $('#voucher-image img').imgAreaSelect
			show:        true
			handles:     true
			instance:    true
			imageWidth:  image.width
			imageHeight: image.height
			x1:          coords[0]
			y1:          coords[1]
			x2:          coords[0] + coords[2]
			y2:          coords[1] + coords[3]
			aspectRatio: aspect_ratio
			onSelectEnd: ( img, selection ) ->
				areaSelect selection, field_id
				return

	areaSelect = ( selection, field_id ) ->
		position = [ selection.x1, selection.y1, selection.width, selection.height ]
		api.preview.send 'voucher:update-position:' + field_id, position.join(',')

	bindFieldStyleUpdates = ( field_id ) ->

		return unless preview.css_config

		$.each preview.css_config, ( setting_key, config ) ->

			return unless config.property

			config.value = '{$value}' unless config.value

			# update field css property value based on css config
			updateFieldCssValue = ( value ) ->

				# special case: handle font-size scaling and default value
				if 'font_size' is setting_key
					value = if '0' is value then null else value

					value = calculateScaledFontSize( value ) if value

				setFieldCssValue( field_id, config, value )

			# update field css on load and when value changes
			api 'wc_voucher_template_' + field_id + '_' + setting_key, ( setting ) ->

				updateFieldCssValue( setting.get() )

				setting.bind( updateFieldCssValue )

	api 'wc_voucher_template_voucher_primary_image', ( setting ) ->

		setting.bind ( newval ) ->

			$( '#voucher-image img' ).prop( 'src', newval.src ).load ->

				if ! newval.src
					$( 'body' ).addClass( 'voucher-no-image' )
				else
					$( 'body' ).removeClass( 'voucher-no-image' )

				redrawVoucherFieldPlaceholders()

	api 'wc_voucher_template_voucher_additional_image', ( setting ) ->

		setting.bind ( newval ) ->

			$( '#voucher-additional-image img' ).prop( 'src', newval )

	api 'wc_voucher_template_voucher_image_dpi', ( setting ) ->

		setting.bind( redrawVoucherFieldPlaceholders )

	$ ->

		# listen to previewer events
		api.preview.bind 'voucher:start-positioning', ( data ) ->
			voucherFieldAreaSelect data.field_id, data.aspect_ratio

		api.preview.bind 'voucher:stop-positioning', ( val ) ->
			removeImgAreaSelect()

		api.preview.bind 'voucher:remove-position', ( val ) ->
			redrawVoucherFieldPlaceholders()

		api.preview.bind 'voucher:update-position', ( val ) ->
			console.log('update...', val)
			redrawVoucherFieldPlaceholders()

		# redraw the positioned voucher fields on the primary image as the browser is scaled
		$( window ).resize ->
			redrawVoucherFieldPlaceholders()

		setTimeout ->

			# recalculate image scale and adjust placeholders
			redrawVoucherFieldPlaceholders()

			# listen to voucher default value updates - we can't do it earlier, as
			# the image scale must be calculated before
			bindFieldStyleUpdates( 'voucher' )

			$.each preview.voucher_fields, (index, field_id) ->
				bindFieldStyleUpdates( field_id )
		, 0

		$( '#voucher' ).click '.js-voucher-field-pos', ( event ) ->
			api.preview.send 'voucher:click-position:' + event.target.id

)( window.wp, jQuery )
