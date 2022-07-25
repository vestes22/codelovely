"use strict"

###*
# WooCommerce PDF Product Voucher Templates Customizer Controls scripts
#
# @since 3.0.0
###
( ( wp, $ ) ->

	if ( ! wp || ! wp.customize )
		return

	api = wp.customize

	###*
	# Voucher field position control
	#
	# @since 3.0.0
	###
	api.PositionControl = api.Control.extend

		ready: ->
			control = this

			_.bindAll( control, 'startPositioning', 'cancelPositioning', 'stopPositioning', 'removePosition' )

			# Bind events, with delegation to facilitate re-rendering.
			control.container.on( 'click keydown', '.start-button',  control.startPositioning )
			control.container.on( 'click keydown', '.done-button',   control.stopPositioning )
			control.container.on( 'click keydown', '.cancel-button', control.cancelPositioning )
			control.container.on( 'click keydown', '.remove-button', control.removePosition )

			control.state       = new api.Value( 'default' )
			control.aspectRatio = new api.Value( null )

			# re-render the control if the setting value changes
			control.setting.bind ( value ) ->
				control.params.position = if value then value.split(',') else null
				control.renderContent()

			# re-render the control if the control state changes
			control.state.bind (value) ->
				control.params.state = value
				control.renderContent()

			# update position value when changed via previewer
			api.previewer.bind 'voucher:update-position:' + control.params.voucher_field_id, ( value ) ->
				control.setting.set value

			# update position value when changed via previewer
			api.previewer.bind 'voucher:click-position:' + control.params.voucher_field_id, ->
				control.focus()
				control.startPositioning()

			$( document ).on 'keydown', ( event ) ->

				# cancel positioning on Esc key
				if 27 is event.keyCode && 'positioning' is control.state.get()
					control.cancelPositioning()

				# stop positioning on Enter/Return key
				if 13 is event.keyCode && 'positioning' is control.state.get()
					control.stopPositioning()

			api.bind 'save', ->
				control.stopPositioning()

		startPositioning: ->
			this.current_value = this.setting.get()
			this.state.set('positioning' )

			data =
				field_id: this.params.voucher_field_id
				aspect_ratio: this.aspectRatio.get()

			api.previewer.send 'voucher:start-positioning', data

		removePosition: ->
			this.setting.set( '' )
			this.state.set('default')
			api.previewer.send 'voucher:remove-position', this.params.voucher_field_id

		cancelPositioning: ->
			if this.current_value
				this.setting.set( if this.current_value then this.current_value else '' )
			this.stopPositioning()

		stopPositioning: ->
			this.state.set('default')
			api.previewer.send 'voucher:stop-positioning', this.params.voucher_field_id


	###*
	# Special field control for barocde/QR to keep aspect ratio.
	#
	# @since 3.5.0
	###
	api.BarcodePositionControl = api.PositionControl.extend
		ready: ->
			control = this
			api.PositionControl.prototype.ready.call( control )
			control.aspectRatio.set(control.params.aspect_ratio[api.get().wc_voucher_template_barcode_barcode_type])
			api.bind( 'change', ( setting ) ->

				if -1 != setting.id.indexOf( 'barcode_type' )
					selected_type = api.get().wc_voucher_template_barcode_barcode_type
					# Set aspect ratio and reselect the barcode to apply change.
					control.aspectRatio.set(control.params.aspect_ratio[selected_type])

					if 'qr' != selected_type
						api.notifications.add( new api.Notification( 'only_qr_scanning', {
							message: woocommerce_vouchers_admin_customizer.i18n.something,
							type: 'warning',
							dismissible: true
						} ) )
					else
						api.notifications.remove( 'only_qr_scanning' )

					api.PositionControl.prototype.stopPositioning.call( control )
					api.PositionControl.prototype.startPositioning.call( control )
			)


	###*
	# Voucher Image control
	#
	# Based on HeaderControl, with modifications to make it suitable for handling
	# voucher images. Uses subclassed models and views.
	#
	# @since 3.0.0
	###
	api.VoucherImageControl = api.HeaderControl.extend

		ready: ->

			this.btnNew    = $('#customize-control-wc_pdf_product_vouchers_voucher_image .actions .new' )

			_.bindAll( this, 'openMedia' )

			this.btnNew.on( 'click', this.openMedia )

			api.HeaderTool.currentHeader = this.getInitialVoucherImage()

			new api.VoucherCurrentImageView
				model: api.HeaderTool.currentHeader
				el: '#customize-control-wc_pdf_product_vouchers_voucher_image .current .container'

			new api.HeaderTool.ChoiceListView
				collection: api.HeaderTool.UploadsList = new api.VoucherImageChoiceList()
				el: '#customize-control-wc_pdf_product_vouchers_voucher_image .choices .uploaded .list'

			new api.HeaderTool.ChoiceListView
				collection: api.HeaderTool.DefaultsList = new api.VoucherImageDefaultsList(),
				el: '#customize-control-wc_pdf_product_vouchers_voucher_image .choices .default .list'

			api.HeaderTool.combinedList = api.HeaderTool.CombinedList = new api.HeaderTool.CombinedList([
				api.HeaderTool.UploadsList,
				api.HeaderTool.DefaultsList
			])

			# Ensure custom-header-crop Ajax requests bootstrap the Customizer to activate the previewed theme.
			wp.media.controller.Cropper.prototype.defaults.doCropArgs.wp_customize = 'on'
			wp.media.controller.Cropper.prototype.defaults.doCropArgs.customize_theme = api.settings.theme.stylesheet

		getInitialVoucherImage: ->

			primary_image = api.get().wc_voucher_template_voucher_primary_image

			if ! primary_image || ! _wpCustomizeHeader.uploads[ primary_image.id ]
				return new api.VoucherImageModel()

			# Get the matching uploaded image object.
			currentHeaderObject = _wpCustomizeHeader.uploads[ primary_image.id ]

			return new api.VoucherImageModel
				header: currentHeaderObject,
				choice: currentHeaderObject.url.split( '/' ).pop()

		openMedia: (event) ->
			l10n = _wpMediaViewsL10n

			event.preventDefault()

			this.frame = new VoucherImagesFrame
				button:
					text:  l10n.select,
					close: false
				states: [
					new wp.media.controller.Library
						title:           l10n.chooseImage
						library:         wp.media.query({ type: 'image' })
						defaults:        wp.media.query({ type: 'image', include: _wpCustomizeHeader._pdf_vouchers.default_images })
						multiple:        false
						date:            false
						priority:        20
						suggestedWidth:  _wpCustomizeHeader.data.width
						suggestedHeight: _wpCustomizeHeader.data.height
				]

			this.frame.on( 'select', this.onSelect, this )

			this.frame.open()

		onSelect: ->
			attachment = this.frame.state().get( 'selection' ).first().toJSON()

			this.setImageFromURL( attachment.url, attachment.id, attachment.width, attachment.height )

			this.frame.close()

		setImageFromURL: ( url, attachmentId, width, height ) ->
			data = {}

			data.url           = url
			data.thumbnail_url = url
			data.timestamp     = _.now()

			if attachmentId
				data.attachment_id = attachmentId

			if width
				data.width = width

			if height
				data.height = height

			# user our own image model
			choice = new api.VoucherImageModel
				header: data
				choice: url.split('/').pop()

			api.HeaderTool.UploadsList.add( choice )
			api.HeaderTool.currentHeader.set( choice.toJSON() )

			choice.save()
			choice.importImage()

	###*
	# Voucher Image Model
	#
	# @since 3.0.0
	###
	api.VoucherImageModel = api.HeaderTool.ImageModel.extend

		# handle removing an uploaded voucher image
		destroy: ->

			data = this.get('header')
			curr = api.HeaderTool.currentHeader.get('header').attachment_id

			# remove the image from voucher images
			images = _.clone( api( 'wc_voucher_template_voucher_images' ).get() ) || []
			index  = images.indexOf( data.attachment_id )

			if ( index > -1 )
				images.splice( index, 1 )

			api( 'wc_voucher_template_voucher_images' ).set( images )

			# If the image we're removing is also the current primary image, get the next
			# image and set it as the primary image
			if curr && data.attachment_id is curr
				nextImage = this.collection.at(1)

				if ( nextImage )
					nextImage.save()
					api.HeaderTool.currentHeader.set( nextImage.toJSON() )
				else
					api.HeaderTool.currentHeader.trigger 'hide'

			this.trigger( 'destroy', this, this.collection )

		# set the image as the primary voucher image
		save: ->
			image = this.get('header')

			api( 'wc_voucher_template_voucher_primary_image' ).set
				id:     image.attachment_id
				src:    image.url
				width:  image.width
				height: image.height

			api.HeaderTool.combinedList.trigger( 'control:setImage', this )

		# add a new image to the list of possible voucher images
		importImage: ->

			data = this.get( 'header' )

			return if data.attachment_id is undefined

			images = _.clone( api( 'wc_voucher_template_voucher_images' ).get() ) || []

			images.push( data.attachment_id )

			api( 'wc_voucher_template_voucher_images' ).set( images )

	###*
	# Voucher Image Choice List
	#
	# @since 3.0.0
	###
	api.VoucherImageChoiceList = api.HeaderTool.ChoiceList.extend

		model: api.VoucherImageModel

		# Prevent WP from adding a random image choice
		addRandomChoice: ->
			# no-op

	###*
	# Voucher Image Defaults List
	#
	# @since 3.0.0
	###
	api.VoucherImageDefaultsList = api.HeaderTool.DefaultsList.extend

		model: api.VoucherImageModel

		# Prevent WP from adding a random image choice
		addRandomChoice: ->
			# no-op

	###*
	# Current (primary) Voucher Image View
	#
	# @since 3.0.0
	###
	api.VoucherCurrentImageView = api.HeaderTool.CurrentView.extend

		# make sure that voucher images have dynamic height
		getHeight: ->
			image = this.$el.find( 'img' )

			if image.length
				this.$el.find( '.inner' ).hide()
			else
				this.$el.find( '.inner' ).show()
				return 40

			height = image.height()

			# happens at ready
			height = 'auto' if ! height

			return height

	api.bind 'ready', ->
		original_query = api.previewer.query

		api.previewer.query = ->
			query = original_query.call( this )

			preview_url = parse_params( window.location.search.substr( 1 ) ).url
			a = document.createElement( 'a' )
			a.href = preview_url
			query.voucher_template_id = parse_params( a.search ).p

			return query

		api 'wc_voucher_template_allow_online_redemptions', ( setting ) ->

			maybe_mark_number_dirty = ->
				api 'wc_voucher_template_voucher_number_pos', ( number_pos_setting ) ->
					number_pos_setting._dirty = true unless number_pos_setting.get()

			# make sure that if the voucher template uses online redemptions, the voucher number
			# is required - WP Customizer only validates settings that have been modified (are dirty)
			maybe_mark_number_dirty() if setting.get()

			setting.bind ( newval ) ->
				maybe_mark_number_dirty() if newval


	# Let WP know of our custom controls
	$.extend api.controlConstructor,
		wc_pdf_product_vouchers_position:         api.PositionControl
		wc_pdf_product_vouchers_voucher_image:    api.VoucherImageControl
		wc_pdf_product_vouchers_barcode_position: api.BarcodePositionControl

	# Parses a query string into an object hash of query params,
	# multiple values for the same key will overwrite the previous value
	parse_params = ( qs ) ->
		pairs = qs.split( '&' )
		params = {}

		pairs.forEach ( pair ) ->
			parts = pair.split( '=', 2 )
			return unless parts.length is 2
			params[ parts[0] ] = decodeURIComponent( parts[1].replace( /\+/g, ' ') )

		params

	###*
	# Initialize tooltips
	#
	# @since 3.0.0
	###
	init_tiptip = ->
		$( '#tiptip_holder' ).removeAttr( 'style' )
		$( '#tiptip_arrow' ).removeAttr( 'style' )
		$( '.font-style-button' ).tipTip
			'attribute': 'data-tip'
			'fadeIn':    0
			'fadeOut':   0
			'delay':     0

	$ ->

		init_tiptip()

		# use template name as site title
		$( '.customize-info .customize-panel-description' ).html( woocommerce_vouchers_admin_customizer.i18n.customizer_description )

		# listen to template name updates and update the fake site title accordingly
		api 'wc_voucher_template_post_title', ( setting ) ->

			initial_value = setting.get()

			# make sure that if the voucher template title is empty, it will be validated
			# & required on save, regardless if the user touched the control or not
			setting._dirty = true unless initial_value

			# listen to updates
			setting.bind ( newval ) ->

				val = newval || woocommerce_vouchers_admin_customizer.i18n.untitled_template
				$( '.customize-info .site-title' ).text( val )

		# determine logo aspect ratio on load
		$( '#customize-control-wc_voucher_template_logo_image_id img.attachment-thumb' ).on 'load', ->

			ratio = $( this ).width() + ':' + $( this ).height()

			api.control.value( 'wc_voucher_template_logo_pos' ).aspectRatio.set( ratio )

		# update logo aspect ratio & position when changing the image
		api 'wc_voucher_template_logo_image_id', ( value ) ->

			pos_control = api.control.value( 'wc_voucher_template_logo_pos' )

			# if the logo has no initial value, make sure the position control is hidden
			if ( ! value.get() )
				pos_control.aspectRatio.set( '' )
				pos_control.state.set( '' )

			value.bind ( newval ) ->

				# clear position if image removed
				if ( ! newval )
					pos_control.aspectRatio.set( '' )
					pos_control.state.set( '' )
					pos_control.setting.set('')
					api.previewer.send 'voucher:remove-position', 'wc_voucher_template_logo_pos'

				# reveal position control & update aspect ration & position when a new image is selected
				else
					pos_control.state.set( 'default' )

					# since the img element will be re-created every time a new image is uploaded, we have to
					# re-attach the event listener each time, as the `load` event does not bubble
					$( '#customize-control-wc_voucher_template_logo_image_id img.attachment-thumb' ).on 'load', ->

						width    = $( this ).width()
						height   = $( this ).height()
						ratio    = width + ':' + height
						position = pos_control.setting.get()

						pos_control.aspectRatio.set( ratio )

						# calculate new position coordinates for the logo field
						if ( position )

							coords = pos_control.setting.get().split(',').map(( (n) ->
								parseInt n, 10
							))

							coords[3] = Math.round( height / width * coords[2] )
							position  = coords.join(',')

							pos_control.setting.set( position )

							# tell the previewer to update/redraw the logo position
							api.previewer.send 'voucher:update-position', 'wc_voucher_template_logo_pos'


		# listen to font control checkbox/radio changes
		$( '.customize-control-wc_pdf_product_vouchers_font_style input' ).on 'change', (e) ->

			$btn = $( e.target ).closest( '.font-style-button' )

			if 'radio' is e.target.type
				$btn.siblings().removeClass( 'active' )

			toggle = if $( e.target ).is( ':checked' ) then 'addClass' else 'removeClass'

			$btn[toggle]( 'active' )

		.on 'click', (e) ->

			# allow unselecting font style radio buttons, to enable falling back to default
			if 'radio' is e.target.type

				$btn = $( e.target ).closest( '.font-style-button' )

				if $btn.hasClass( 'active' )
					$( e.target ).removeAttr( 'checked' )
					$btn.removeClass('active').siblings('.font-style-text-align-empty').prop( 'checked', true ).change()


		# Add value information to range sliders
		# (Default WP range input does not provide this
		$rangeSliders = $( 'input[type="range"]' )

		if $rangeSliders

			$rangeSliders.each ->
				$( this ).closest( 'label' ).append( '<span class="wc-pdf-product-vouchers-range-index"></span>' )

			set_range_text = ( el ) ->
				value = $( el ).val()
				label = if value > 0 then value + 'px' else woocommerce_vouchers_admin_customizer.i18n.default

				$( el ).parent().find( '.wc-pdf-product-vouchers-range-index' ).text( label )

			$rangeSliders.on 'input', ->
				set_range_text( this )

			# we can't trigger a change or input event, as this will cause the customizer to think that
			# there are actual changes made to the values and will prompt the user to save before they
			# try to leave the screen
			$rangeSliders.each ( index, el ) ->
				set_range_text( el )


	###*
	# Voucher Images Media Frame
	#
	# @since 3.0.0
	###
	VoucherImagesFrame = wp.media.view.MediaFrame.Select.extend

		# Listen to router changes and render appopriate content
		bindHandlers: ->

			wp.media.view.MediaFrame.Select.prototype.bindHandlers.apply( this, arguments )

			this.on( 'content:render:defaults', this.defaultsContent, this )

		# Add our custom Default images (defaults) tab to the media frame
		browseRouter: ( routerView ) ->

			routerView.set
				upload:
					text:     _wpMediaViewsL10n.uploadFilesTitle,
					priority: 20
				browse:
					text:     _wpMediaViewsL10n.mediaLibraryTitle,
					priority: 40
				defaults:
					text:     _wpCustomizeHeader._pdf_vouchers.i18n.default_images_title
					priority: 60

		###*
		# Render callback for the content region in the `defaults` mode.
		#
		# This will render the tab content for the "Default Images", showing only
		# default voucher images.
		#
		# @param {wp.media.controller.Region} contentRegion
		###
		defaultsContent: ( contentRegion ) ->
			state = this.state()

			# Browse our library of attachments with only defaults showing
			this.content.set new wp.media.view.AttachmentsBrowser
				controller: this,
				collection: state.get('defaults')
				selection:  state.get('selection')
				model:      state
				sortable:   false
				search:     false
				filters:    false
				date:       state.get('date')
				display:    if state.has('display') then state.get('display') else state.get('displaySettings')
				dragInfo:   state.get('dragInfo')

				idealColumnWidth: state.get('idealColumnWidth')
				suggestedWidth:   state.get('suggestedWidth')
				suggestedHeight:  state.get('suggestedHeight')

				AttachmentView: state.get('AttachmentView')

)( window.wp, jQuery )
