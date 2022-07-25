"use strict"

###*
# WooCommerce PDF Product Vouchers admin scripts
#
# @since 3.0.0
###
jQuery ( $ ) ->

	pdf_vouchers = window.wc_pdf_product_vouchers_admin ? {}

	typenow = window.typenow ? ''
	pagenow = window.pagenow ? ''

	jQuery( document ).ready ( $ ) ->

		if pdf_vouchers.is_edit_voucher_page
			pag_title_action = $( '.page-title-action' ).first()
			redeem_link = $( '<a></a>' )
				.attr( 'href', pdf_vouchers.redeem_voucher_url )
				.addClass( 'page-title-action' )
				.text( pdf_vouchers.i18n.redeem_voucher )
			pag_title_action.after( redeem_link )

		redeem_voucher      = $( '#redeem-voucher' )
		redeem_form         = $( '#redeem-voucher-form' )
		redeem_amount_row   = $( '#redemption-amount-row' )
		redeem_amount_input = $( '#redemption-amount' )
		barcode_value       = $( '#barcode-value' )

		redeem_voucher.on( 'click', ( event ) ->

			event.preventDefault()

			redeem_form.block(
				message: null
				overlayCSS:
					background: '#fff'
					opacity:    0.6
			)

			handle_redeem_with_timeout()
		)

		redeem_form.on( 'submit', ( event ) ->

			event.preventDefault()

			redeem_form.block(
				message: null
				overlayCSS:
					background: '#fff'
					opacity:    0.6
			)

			handle_redeem_with_timeout()
		)

		###*
		# Send AJAX request with minor delay.
		#
		# @since 3.5.0
		###
		handle_redeem_with_timeout = () ->
			setTimeout(handle_redeem, Math.floor((Math.random() * 1000) + 500))


		###*
		# Handle AJAX request.
		#
		# @since 3.5.0
		###
		handle_redeem = () ->

			if redeem_voucher.data('inProgress')
				return

			if not barcode_value.val()

				message_container = $( '.js-redeem-message' )
				message_container.addClass( 'hidden' )
				message_container.html( '<p>' + wc_pdf_product_vouchers_admin.i18n.empty_barcode_error + '</p>'  )
					.removeClass( 'notice-success notice-warning' )
					.addClass( 'notice-error' )
				message_container.removeClass( 'hidden' )
				barcode_value.focus()
				redeem_form.unblock()

				return

			redeem_voucher.data('inProgress', true)

			data =
				action: 'wc_pdf_product_vouchers_barcode_redeem_voucher'
				security: wc_pdf_product_vouchers_admin.barcode_redeem_voucher_nonce
				barcode_value: barcode_value.val()
				amount: redeem_amount_input.val()


			$.post(wc_pdf_product_vouchers_admin.ajax_url, data, handle_redeem_response ).always( () ->

				redeem_form.unblock()

				redeem_voucher.data( 'inProgress', false )
			)

		###*
		# Handle AJAX response and show appropriate notice.
		#
		# @since 3.5.0
		###
		handle_redeem_response = ( response ) ->

			message_container = $( '.js-redeem-message' )
			message_container.addClass( 'hidden' )
			if true is response.success
				if true is response.data.is_multi and true is response.data.missing_amount
					message_container.html( '<p>' + response.data.message + '</p>'  )
						.removeClass( 'notice-success notice-error' )
						.addClass( 'notice-warning' )
					redeem_amount_input.attr('max', response.data.available)
					redeem_amount_input.attr('value', response.data.available)
					redeem_amount_input.attr('required', true)
					redeem_amount_row.show()
				else
					barcode_value.val( '' ).focus()
					message_container
						.html( '<p>' + response.data.message + '</p>' )
						.removeClass( 'notice-error notice-warning' )
						.addClass( 'notice-success' )
					redeem_amount_input.val( '' )
					redeem_amount_input.attr('required', false)
					redeem_amount_row.hide()
			else
				message_container.html( '<p>' + response.data.message + '</p>'  )
					.removeClass( 'notice-success notice-warning' )
					.addClass( 'notice-error' )

			message_container.removeClass( 'hidden' )
			return

	###*
	# Initialize tooltips
	#
	# @since 3.0.0
	###
	init_tiptip = ->
		$( document.body ).trigger( 'init_tooltips' )


	###*
	# Voucher edit & list screens
	###
	if 'wc_voucher' is pagenow or 'edit-wc_voucher' is pagenow

		init_tiptip()

		# Add Voucher (.page-title-action for WP 4.3+)
		$( '.page-title-action, .add-new-h2' ).on( 'click', ( event ) ->

			event.preventDefault()
			$( this ).pointer( 'toggle' )

		).on( 'pointercreate', ->

			$el      = $( this )
			$pointer = $el.data( 'wp-pointer' ).pointer

			$pointer.on 'click', '.close', ( event ) ->
				event.preventDefault()
				$el.pointer( 'close' )

			$pointer.on 'click', '.button-primary', ( event ) ->
				event.preventDefault()
				$pointer.find('form').submit()

		).on( 'pointeropened', ( event, t ) ->

			t.pointer.find( 'input:first' ).focus()

			$( document.body ).trigger( 'wc-enhanced-select-init' )

		).pointer(

			pointerClass: 'create-voucher-pointer'

			content: ->

				fields = '
						<p><label for="search_product_id">' + pdf_vouchers.i18n.product + '</label><select
								class="wc-product-search"
								name="product"
								id="search_product_id"
								style="width:100%"
								data-exclude="wc_pdf_product_vouchers_non_voucher_products"
								data-placeholder="' + pdf_vouchers.i18n.search_for_product + '"
								data-allow_clear="true"
						></select></p>
						<p><label for="search_product_id">' + pdf_vouchers.i18n.purchaser + '</label><select
								class="wc-customer-search"
								name="customer"
								id="search_customer_id"
								style="width:100%"
								data-placeholder="' + pdf_vouchers.i18n.guest + '"
								data-allow_clear="true"
						></select></p>
					'

				form = '
					<form method="get" action="' + pdf_vouchers.new_voucher_url + '">
						<input type="hidden" name="post_type" value="wc_voucher" />
						<h3 class="wc-pdf-product-vouchers-voucher-create-voucher">' + pdf_vouchers.i18n.select_product_and_purchaser + '</h3>
						<p>' + pdf_vouchers.i18n.add_new_voucher_description + '</p>
						' + fields + '
					</form>
				'

				return form

			buttons: ->

				return $( '
					<div>
						<a class="close wc-pdf-product-vouchers-redeem-voucher-pointer-close" href="#">' + pdf_vouchers.i18n.cancel + '</a>
						<button class="button button-primary">' + pdf_vouchers.i18n.add_voucher + '</button>
					</div>
				' )

		)


	###*
	# Voucher edit screen
	###
	if 'wc_voucher' is pagenow

		$( '.date-picker-field, .date-picker' ).datepicker
			dateFormat:      'yy-mm-dd',
			numberOfMonths:  1,
			showButtonPanel: true

		voucher_product = null

		blockui_options =
			message: null
			overlayCSS:
				background: '#fff'
				opacity:    0.6

		ModalView = $.WCBackboneModal.View

		###*
		# Show a modal, closing any previously open modals
		#
		# @since 3.0.0
		# @param {string} template_id Modal template id
		# @param {object} template_data Data for the modal template
		###
		show_modal = ( template_id, template_data ) ->

			# close any previously open modals
			$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' )

			# open new modal
			new ModalView
				target: template_id
				string: template_data

		###*
		# Load puchaser (customer) details via AJAX
		#
		# @since 3.0.0
		###
		load_customer_details = ->
			# Get user ID to load data for
			user_id = $( '#customer_id' ).val()

			if ( not user_id )
				window.alert( pdf_vouchers.i18n.no_customer_selected )
				return false

			data =
				user_id:  user_id,
				action:   'wc_pdf_product_vouchers_get_customer_details',
				security: pdf_vouchers.get_customer_details_nonce

			$( '.voucher-purchaser-details div.edit-details' ).block( blockui_options )

			$.get wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->

				if ( response.success )
					$.each response.data, ( key, data ) ->
						$( ':input#_purchaser_' + key ).val( data ).change()

				$( '.voucher-purchaser-details div.edit-details' ).unblock()

		###*
		# Copy purchaser details to recipient details
		#
		# @since 3.0.0
		###
		copy_purchaser_details = ->
			$('.voucher-purchaser-details :input[name^="_purchaser_"]').each ->
				input_name = $( this ).attr( 'name' )
				input_name = input_name.replace( '_purchaser_', '_recipient_' )
				$( ':input#' + input_name ).val( $( this ).val() ).change()


		###*
		# Voucher Image Media Frame
		#
		# @since 3.0.0
		###
		VoucherImageFrame = wp.media.view.MediaFrame.Select.extend

			# Overriding this method allows us to remove the "Upload Files"
			# tab, so that the image selection ins restricted to the voucher
			# template images
			browseRouter: ( routerView ) ->
				routerView.set
					browse:
						text:     _wpMediaViewsL10n.mediaLibraryTitle
						priority: 20


		# listen to events
		$( document.body )

			.on( 'wc_backbone_modal_loaded', ( e, target ) ->

				if 'wc-voucher-modal-edit-product' isnt target
					return

				# Enhance selects when modal is open
				$( document.body ).trigger( 'wc-enhanced-select-init' )

				# Update product price when changing product
				$( document.body ).on 'change.edit-product-modal', '#voucher_product_id', ( event ) ->

					$( '#modal_voucher_product_price' ).closest( 'p' ).block( blockui_options )

					data =
						action:     'wc_pdf_product_vouchers_get_product_details'
						product_id: event.target.value
						security:   wc_pdf_product_vouchers_admin.get_product_details_nonce

					$.get wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->

						if response.success and response.data

							voucher_product = response.data
							formatted_price = window.accounting.format( voucher_product.price, 2, null, woocommerce_admin.mon_decimal_point )

							$( '#modal_voucher_product_price' ).val( formatted_price ).closest( 'p' ).unblock()

			)

			.on( 'wc_backbone_modal_removed', ( e, target ) ->

				return if 'wc-voucher-modal-edit-product' isnt target

				$( document.body ).off 'change.edit-product-modal'
			)

			# Set product ID and update balance & preview
			.on( 'wc_backbone_modal_response', ( event, target, posted_data ) ->

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				data =
					action:        'wc_pdf_product_vouchers_update_voucher_product'
					security:      wc_pdf_product_vouchers_admin.update_voucher_product_nonce
					voucher_id:    $( '#post_ID' ).val()
					product_id:    posted_data.product_id
					product_price: posted_data.product_price

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->

					if response.success and response.data

						$( '#wc-pdf-product-vouchers-voucher-balance .inside' ).html( response.data.balance_html )

						# update preview if product (and thus, template) has changed
						if response.data.preview_html
							$( '#wc-pdf-product-vouchers-voucher-preview .inside' ).html( response.data.preview_html )

						init_tiptip()

					$( '#wc-pdf-product-vouchers-voucher-balance' ).unblock()
			)
			.on 'keyup', '.js-wc-pdf-vouchers-redeem-amount', ->

				# validate against the display remaining value (which may include tax)
				remaining_value = window.accounting.unformat( $( '#voucher_remaining_value_for_display' ).val(), woocommerce_admin.mon_decimal_point )
				product_price   = window.accounting.unformat( $( '#voucher_product_price_for_display' ).val(), woocommerce_admin.mon_decimal_point )
				voucher_type    = $( '#voucher_type' ).val()

				validate_redemption_value( $( this ), remaining_value, product_price, voucher_type )

			.on 'blur', '.js-wc-pdf-vouchers-redeem-amount', ->
				$( '.wc_error_tip' ).fadeOut( '100', -> $( this ).remove() )

		# Toggle voucher customer/recipient details form
		$( 'a.edit-voucher-details' ).on 'click', ( e ) ->
			e.preventDefault()
			$( this ).hide()
			$( this ).parent().find( 'a:not(.edit-voucher-details)' ).show()
			$( this ).closest( '.voucher_data_column' ).find( 'div.view-details' ).hide()
			$( this ).closest( '.voucher_data_column' ).find( 'div.edit-details' ).show()

		# Load customer details
		$( 'a.load-customer-details' ).on 'click', ( e ) ->
			e.preventDefault()

			if window.confirm( pdf_vouchers.i18n.confirm_load_customer_details )
				load_customer_details()

		# Copy purchaser details
		$( 'a.copy-purchaser-details' ).on 'click', ( e ) ->
			e.preventDefault()

			if window.confirm( pdf_vouchers.i18n.confirm_copy_purchaser_details )
				copy_purchaser_details()

		$( '#customer_id' )
			.data( 'original_customer_id', $( '#customer_id' ).val() )
			.on( 'change', ->

				# disable calculate taxes
				if $( this ).val() isnt $( this ).data( 'original_customer_id' )
					$( 'button.js-calculate-tax-action, button.js-redeem-action' ).prop( 'disabled', true )
					$( '.js-customer-changed-notice' ).show()
				# enable calculate taxes
				else
					$( 'button.js-calculate-tax-action, button.js-redeem-action' ).prop( 'disabled', false )
					$( '.js-customer-changed-notice' ).hide()

			).change()

		# Voucher notes
		$( '#wc-pdf-product-vouchers-voucher-notes' )
			.on 'click', '.js-add-note', ( e ) ->

				e.preventDefault()

				note = $( 'textarea#voucher-note' ).val()

				return unless note

				$( '#wc-pdf-product-vouchers-voucher-notes' ).block( blockui_options)

				data =
					action:     'wc_pdf_product_vouchers_add_voucher_note'
					voucher_id: $( '#post_ID' ).val()
					note:       note
					notify:     $( '#note-notify' ).is( ':checked' )
					security:   wc_pdf_product_vouchers_admin.add_voucher_note_nonce

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->
					if response.data
						if ( response.success )
							$( 'ul.voucher-notes' ).prepend( response.data.note_html ).find( 'ul.no-notes' ).hide()
						else
							alert( response.data )

					$( '#wc-pdf-product-vouchers-voucher-notes' ).unblock()
					$( 'textarea#voucher-note' ).val( '' )

			.on 'click', 'a.js-delete-note', ( e ) ->

				e.preventDefault()

				note = $( this ).closest( 'li.note' )

				$( note ).block( blockui_options )

				data =
					action:   'wc_pdf_product_vouchers_delete_voucher_note',
					note_id:  $( note ).attr( 'rel' ),
					security: wc_pdf_product_vouchers_admin.delete_voucher_note_nonce,

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->
					$( note ).remove()

					if ( $( 'ul.voucher-notes' ).find( 'li:not(.no-notes)' ).length < 1 )
						$( 'ul.voucher-notes' ).find( 'li.no-notes' ).show()


		$( '#wc-pdf-product-vouchers-voucher-balance' )
			.on 'click', '.js-edit-voucher-product', ( event ) ->
				event.preventDefault()

				show_modal( 'wc-voucher-modal-edit-product', {
					product_id:    $( 'input[name="_product_id"]' ).val()
					product_title: $( '.product .wc-voucher-item-name' ).text()
					product_price: $( 'input[name="_product_price"]' ).val()
				} )

			.on 'click', '.js-edit-voucher-redemption',  ( event ) ->
				event.preventDefault()

				$( this ).closest( 'tr' ).find( '.view' ).hide()
				$( this ).closest( 'tr' ).find( '.edit' ).show()

				$( 'div.wc-voucher-data-row.wc-voucher-edit-redemption-actions' ).slideDown()
				$( 'div.wc-voucher-data-row-toggle' ).not( 'div.wc-voucher-data-row.wc-voucher-edit-redemption-actions' ).slideUp()
				$( 'div.wc-voucher-data-row.wc-voucher-edit-redemption-actions button.js-cancel-action' ).attr( 'data-reload', true )
				$( 'div.wc-voucher-totals-wrapper' ).slideDown()

				$( this ).hide()

			.on 'click', '.js-redeem-action', ( event ) ->
				event.preventDefault()

				$( 'div.wc-voucher-data-row.wc-voucher-redeem-actions' ).slideDown()
				$( 'div.wc-voucher-data-row-toggle' ).not( 'div.wc-voucher-data-row.wc-voucher-redeem-actions' ).slideUp()

				$( 'div.wc-voucher-totals-wrapper' ).slideUp()
				$( 'div.wc-voucher-redeem-wrapper' ).slideDown()

				$( '.edit-voucher-item' ).hide()

			.on 'click', '.js-void-action', ( event ) ->
				event.preventDefault()

				$( 'div.wc-voucher-data-row.wc-voucher-void-actions' ).slideDown()
				$( 'div.wc-voucher-data-row-toggle' ).not( 'div.wc-voucher-data-row.wc-voucher-void-actions' ).slideUp()

				$( 'div.wc-voucher-totals-wrapper' ).slideUp()
				$( 'div.wc-voucher-void-wrapper' ).slideDown()

				$( '.edit-voucher-item' ).hide()

			.on 'click', '.js-calculate-tax-action', ( event ) ->
				event.preventDefault()

				return unless window.confirm( pdf_vouchers.i18n.confirm_calculate_taxes )

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				data =
					action:     'wc_pdf_product_vouchers_calculate_voucher_product_tax'
					security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id: $( '#post_ID' ).val()

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response

			.on 'click', '.js-delete-voucher-redemption', ( event ) ->
				event.preventDefault()

				return unless window.confirm( pdf_vouchers.i18n.confirm_delete_redemption )

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				data =
					action:         'wc_pdf_product_vouchers_delete_voucher_redemption'
					security:       wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id:     $( '#post_ID' ).val()
					redemption_key: $( event.target ).closest( 'tr' ).data( 'key' )

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response

			.on 'click', '.js-cancel-action', ( event ) ->
				$( 'div.wc-voucher-data-row-toggle' ).not( 'div.wc-voucher-balance-actions' ).slideUp()
				$( 'div.wc-voucher-balance-actions' ).slideDown()
				$( 'div.wc-voucher-totals-wrapper' ).slideDown()
				$( '.edit-voucher-item' ).show()

				$button = $( this )

				# Reload the redemptions
				if 'true' is $button.attr( 'data-reload' )

					$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

					data =
						action:     'wc_pdf_product_vouchers_load_voucher_redemptions'
						security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
						voucher_id: $( '#post_ID' ).val()

					$.post wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->

						if response.success and response.data

							$( 'tbody#voucher-redemptions' ).replaceWith( response.data.redemptions_html )

							init_tiptip()

							$button.attr( 'data-reload', 'false' )

						$( '#wc-pdf-product-vouchers-voucher-balance' ).unblock()


			# redeem a voucher (triggers form submission and validation)
			.on 'click', '.js-redeem-voucher-action', ( event ) ->
				event.preventDefault()

				voucher_value     = $( '#voucher_value' ).val()
				voucher_tax       = $( '#voucher_tax' ).val()
				redemption_amount = $( '#redemption_amount' ).val()
				redemption_amount = if redemption_amount and redemption_amount.length > 0 then parseFloat( window.accounting.unformat( redemption_amount, woocommerce_admin.mon_decimal_point ) ) else redemption_amount

				# we want the actual redemption amount without tax
				if voucher_tax and voucher_value and redemption_amount and 'incl' is wc_pdf_product_vouchers_admin.tax_display_shop
					voucher_tax       = parseFloat( voucher_tax )
					tax_ratio         = if voucher_tax > 0 then parseFloat( voucher_tax / voucher_value ) else 0
					redemption_amount = redemption_amount / ( 1 + tax_ratio )

				data =
					action:     'wc_pdf_product_vouchers_redeem_voucher'
					security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id: $( '#post_ID' ).val()
					amount:     window.accounting.unformat( redemption_amount, woocommerce_admin.mon_decimal_point )
					notes:      $( '#redemption_notes' ).val()

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response


			.on 'click', '.js-void-voucher-action', ( event ) ->
				event.preventDefault()

				return unless window.confirm( pdf_vouchers.i18n.confirm_void_voucher )

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				data =
					action:     'wc_pdf_product_vouchers_void_voucher'
					security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id: $( '#post_ID' ).val()
					reason:     $( '#void_reason' ).val()

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response

			.on 'click', '.js-restore-action', ( event ) ->
				event.preventDefault()

				return unless window.confirm( pdf_vouchers.i18n.confirm_restore_voucher )

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				data =
					action:     'wc_pdf_product_vouchers_restore_voucher'
					security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id: $( '#post_ID' ).val()

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response

			.on 'click', '.js-save-redemptions-action', ( event ) ->
				event.preventDefault()

				fields = $( 'tbody#voucher-redemptions :input[name]' ).serializeArray()

				# we want the redemption amounts without tax
				if 'incl' is wc_pdf_product_vouchers_admin.tax_display_shop and $( '#voucher_tax' ).val()

					tax_ratio = $( '#voucher_tax' ).val() / $( '#voucher_value' ).val()

					fields.forEach ( field, i ) ->

						if field.name.lastIndexOf( '[amount]' ) > 0
							fields[ i ].value = field.value / ( 1 + tax_ratio )

				data =
					action:     'wc_pdf_product_vouchers_save_voucher_redemptions'
					security:   wc_pdf_product_vouchers_admin.voucher_balance_nonce
					voucher_id: $( '#post_ID' ).val()
					data:       $.param( fields )

				$( '#wc-pdf-product-vouchers-voucher-balance' ).block( blockui_options )

				$.post wc_pdf_product_vouchers_admin.ajax_url, data, handle_balance_response

		# store references to file_frame and file_frame_images
		file_frame        = null
		file_frame_images = $( '#_voucher_image_options' ).val()

		# open custom file frame modal when changing voucher image
		$( '#wc-pdf-product-vouchers-voucher-preview' )
			.on 'click', '.js-select-voucher-image', ( event ) ->
				event.preventDefault()

				l10n   = _wpMediaViewsL10n
				images = $( '#_voucher_image_options' ).val()

				# Todo: try to figure out a way to reuse the existing media modal (file_frame)
				# even if the voucher image options change to reduce memory usage when changing
				# the voucher product. Currently, a new file_frame is created every time the product
				# is changed, leaving the previous one hanging somewhere. {IT 2017-02-03}

				# open the existing frame if image options have not changed
				if file_frame and file_frame_images is images

					file_frame.open()
					return

				# otherwise, (re-)create the file frame with the correct query
				file_frame_images = images

				file_frame = new VoucherImageFrame
					button:
						text: l10n.chooseImage
					states:
						new wp.media.controller.Library
							title:      l10n.chooseImage
							library:    wp.media.query({ type: 'image', include: file_frame_images.split(',') })
							multiple:   false
							searchable: false
							date:       false
							content:    'browse'

				file_frame.on 'open', ->

					selection = file_frame.state().get('selection')
					selected  = $( '#_thumbnail_id' ).val()

					selection.add( wp.media.attachment( selected ) )

				file_frame.on 'select', ->

					selection = file_frame.state().get('selection').first().toJSON()

					return unless selection and selection.id

					data =
						voucher_id: $( '#post_ID' ).val()
						image_id:   selection.id
						action:     'wc_pdf_product_vouchers_get_voucher_preview',
						security:   pdf_vouchers.get_voucher_preview_nonce

					$( '#wc-pdf-product-vouchers-voucher-preview .inside' ).block( blockui_options )

					$.get wc_pdf_product_vouchers_admin.ajax_url, data, ( response ) ->

						if response.success
							$( '#wc-pdf-product-vouchers-voucher-preview .inside' ).html( response.data.preview_html )

						$( '#wc-pdf-product-vouchers-voucher-preview .inside' ).unblock()

				file_frame.open()

	###*
	# Voucher list screen (counter-intuitively, edit-wc_voucher is actually the list screen)
	###
	if 'edit-wc_voucher' is pagenow

		# init select2 filter
		$( '.js-filter-voucher-template' ).select2()

		# Redeem voucher
		$( '.redeem' ).on( 'click', ( event ) ->

			event.preventDefault()
			$( this ).pointer( 'toggle' )

		).on( 'pointercreate', ->

			$el             = $( this )
			$pointer        = $el.data( 'wp-pointer' ).pointer
			voucher_type    = $el.closest( '.voucher_actions' ).find( '.voucher-type' ).val()
			product_price   = $el.closest( '.voucher_actions' ).find( '.voucher-product-price-for-display' ).val()
			value           = $el.closest( '.voucher_actions' ).find( '.voucher-value' ).val()
			tax             = $el.closest( '.voucher_actions' ).find( '.voucher-tax' ).val()
			remaining_value = $el.closest( '.voucher_actions' ).find( '.voucher-remaining-value-for-display' ).val()

			$pointer.data( 'voucher-type', voucher_type )
			$pointer.data( 'voucher-product-price', product_price )
			$pointer.data( 'voucher-value', value )
			$pointer.data( 'voucher-tax', tax )
			$pointer.data( 'voucher-remaining-value', remaining_value )

			$pointer.on 'submit', 'form', ( event ) ->
				event.preventDefault()
				handle_redeem_form_submit( $pointer )

			$pointer.on 'click', '.button-primary', ( event ) ->
				event.preventDefault()
				handle_redeem_form_submit( $pointer )

			$pointer.on 'click', '.close', ( event ) ->
				event.preventDefault()
				$el.pointer( 'close' )

		).on( 'pointeropened', ( event, t ) ->

			t.pointer.find( 'input:first' ).focus()

		).pointer(

			pointerClass: 'redeem-voucher-pointer'
			position:
				edge: 'top'
				at:   'center bottom'
				my:   'right+63 top' # arrow position from right edge (50px) + half of it's witdh (13px)

			content: ->

				$el             = $( this )
				url             = $el.attr('href')
				remaining_value = $el.closest( 'p' ).find( '.voucher-remaining-value-for-display' ).val()

				return '
					<form method="get" action="' + url + '">
						<h3 class="wc-pdf-product-vouchers-voucher-redeem-voucher">' + pdf_vouchers.i18n.redeem_voucher + '</h3>
						<p>
							<label>' + pdf_vouchers.i18n.amount_label + '
								<input type="text" name="amount" class="large-text js-wc-pdf-vouchers-redeem-amount" value="' + window.accounting.format( remaining_value, 2, null, woocommerce_admin.mon_decimal_point ) + '" />
							</label>
						</p>
						<p>
							<label>' + pdf_vouchers.i18n.notes_label + '
								<textarea name="notes" class="large-text"></textarea>
							</label>
						</p>
					</form>
				'

			buttons: ->

				return $( '
					<div>
						<a class="close wc-pdf-product-vouchers-redeem-voucher-pointer-close" href="#">' + pdf_vouchers.i18n.cancel + '</a>
						<button class="button button-primary">' + pdf_vouchers.i18n.redeem + '</button>
					</div>
				' )

		)

		# validate redeem voucher pointer form as the user is typing
		$( document.body )
			.on 'keyup', '.js-wc-pdf-vouchers-redeem-amount', ->

				$pointer = $( this ).closest( '.redeem-voucher-pointer' )

				validate_redemption_value( $( this ), $pointer.data( 'voucher-remaining-value' ), $pointer.data( 'voucher-product-price' ), $pointer.data( 'voucher-type' ) )

			.on 'blur', '.js-wc-pdf-vouchers-redeem-amount', ->
				$( '.wc_error_tip' ).fadeOut( '100', -> $( this ).remove() )


		# Void voucher // TODO: create void voucher pointer?! or simply ask for confirmation?
		$( '.void' ).on( 'click', ( event ) ->

			event.preventDefault()
			$( this ).pointer( 'toggle' )

		).on( 'pointercreate', ->

			$el             = $( this )
			$pointer        = $el.data( 'wp-pointer' ).pointer

			$pointer.on 'submit', 'form', ( event ) ->
				event.preventDefault()
				handle_void_form_submit( $pointer )

			$pointer.on 'click', '.button-primary', ( event ) ->

				event.preventDefault()

				# prevent any error tips from fading out when validating on submit
				setTimeout ->
					handle_void_form_submit( $pointer )
				, 0

			$pointer.on 'click', '.close', ( event ) ->
				event.preventDefault()
				$el.pointer( 'close' )

		).on( 'pointeropened', ( event, t ) ->

			t.pointer.find( 'input:first' ).focus()

		).pointer(

			pointerClass: 'void-voucher-pointer'
			position:
				edge: 'top'
				at:   'center bottom'
				my:   'right+63 top' # arrow position from right edge (50px) + half of it's width (13px)

			content: ->

				$el             = $( this )
				url             = $el.attr('href')
				remaining_value = $el.closest( 'p' ).find( '.voucher-remaining-value' ).val()

				return '
					<form method="get" action="' + url + '">
						<h3 class="wc-pdf-product-vouchers-voucher-void-voucher">' + pdf_vouchers.i18n.void_remaining_value + '</h3>
						<p>
							<label>' + pdf_vouchers.i18n.reason_label + '
								<textarea name="notes" class="large-text"></textarea>
							</label>
						</p>
					</form>
				'

			buttons: ->

				return $( '
					<div>
						<a class="close wc-pdf-product-vouchers-void-voucher-pointer-close" href="#">' + pdf_vouchers.i18n.cancel + '</a>
						<button class="button button-primary">' + pdf_vouchers.i18n.void + '</button>
					</div>
				' )

		)


	###*
	# Product Edit screen
	###
	if 'product' is pagenow

		toggle_voucher_panels = ->

			has_voucher = $( 'input#_has_voucher:checked' ).length

			$( '.hide_if_has_voucher' ).show()
			$( '.show_if_has_voucher' ).hide()

			$( '.show_if_has_voucher' ).show() if has_voucher

		$( document.body ).on 'woocommerce-product-type-change', ( event, type ) ->

			if 'variable' is type or 'grouped' is type or 'external' is type or 'variable-subscription' is type
				$( 'input#_has_voucher' ).prop( 'checked', false ).change()

			toggle_voucher_panels()

		$( 'input#_has_voucher' ).on 'change', ->
			toggle_voucher_panels()

		$( 'input#_has_voucher' ).change()

		$( '#variable_product_options' ).on 'change', 'input.variable_has_voucher', ->
			$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_has_voucher' ).hide()

			if $( this ).is( ':checked' )
				$( this ).closest( '.woocommerce_variation' ).find( '.show_if_variation_has_voucher' ).show()

		$( 'input.variable_has_voucher' ).change()

		$( '#woocommerce-product-data' ).on 'woocommerce_variations_loaded', ( event, needsUpdate ) ->
			needsUpdate = needsUpdate or false

			$( 'input.variable_has_voucher' ).change() unless needsUpdate


	# Extend woocommerce validation messages with custom validation messages for voucher redemption fields
	$.extend( woocommerce_admin, {
		pdf_product_vouchers_i18n_amount_greater_than_zero_error:          pdf_vouchers.i18n.amount_greater_than_zero_error
		pdf_product_vouchers_i18n_amount_less_or_equal_to_remaining_error: pdf_vouchers.i18n.amount_less_or_equal_to_remaining_error
		pdf_product_vouchers_i18n_amount_multiple_of_product_price_error:  pdf_vouchers.i18n.amount_multiple_of_product_price_error
	} )

	###*
	# Validate redemption value
	#
	# @since 3.0.0
	# @param {object} $el the input element to validate
	# @param {float} remaining_value remaining voucher value
	# @param {float} product_price (optional) the voucher product price, used to validate single-purpose voucher redemption values
	# @param {string} voucher_type (optional) the voucher type, defaults to 'multi'
	# @return bool true if valid, false otherwise
	###
	validate_redemption_value = ( $el, remaining_value, product_price = null, voucher_type = 'multi' ) ->

		# only allow positive numbers
		regex    = new RegExp( '[^0-9\\' + woocommerce_admin.mon_decimal_point + ']+', 'gi' )
		value    = $el.val()
		newvalue = value.replace( regex, '' )

		# validate amount
		amount = parseFloat( window.accounting.unformat( value, woocommerce_admin.mon_decimal_point ) )

		# define our validation rules
		validations = [
			# validate format
			{ error: 'i18n_mon_decimal_error', invalid: value isnt newvalue },
			# in case of a single-purpose voucher, the amount must always be a multiple of the voucher product price
			{ error: 'pdf_product_vouchers_i18n_amount_multiple_of_product_price_error', invalid: 'single' is voucher_type and amount % product_price },
			# amount must be greater than 0
			{ error: 'pdf_product_vouchers_i18n_amount_greater_than_zero_error', invalid: not amount or amount < 0 },
			# amount must be less or equal than remaining value
			{ error: 'pdf_product_vouchers_i18n_amount_less_or_equal_to_remaining_error', invalid: amount > remaining_value }
		]

		# see if any validations fail
		invalid = validations.filter ( rule ) ->
			return rule.invalid

		# see if any validations pass
		valid = validations.filter ( rule ) ->
			return not rule.invalid

		# first remove any error tips for validations that pass
		valid.forEach ( rule ) ->

			if invalid.length
				# in case there are other validation error, make sure that the error tip for this
				# rule is removed instantly, so that wc_add_error_tip handler in woocommerce_admin.js
				# can add a new error tip
				$el.parent().find( '.wc_error_tip.' + rule.error ).remove()
			else
				# otherwise fade out the error tip as usual
				$( document.body ).triggerHandler( 'wc_remove_error_tip', [ $el, rule.error ] )

		# in case there are errors, simply show the error tip for the first one and return false
		if invalid.length
			$( document.body ).triggerHandler( 'wc_add_error_tip', [ $el, invalid.shift().error ] )
			return false

		return true


	###*
	# Handle redemption form submit
	#
	# @since 3.0.0
	# @param {object} $pointer
	###
	handle_redeem_form_submit = ( $pointer ) ->
		$el = $pointer.find( 'input[name="amount"]' )

		# use setTimeout to prevent WooCommerce 'click' handler from hiding validation errors
		setTimeout ->

			if ( not validate_redemption_value( $el, $pointer.data( 'voucher-remaining-value' ), $pointer.data( 'voucher-product-price' ), $pointer.data( 'voucher-type' ) ) )
				return $el.focus()

			$form  = $pointer.find('form')
			url    = $form.attr('action')
			value  = $pointer.data( 'voucher-value' )
			tax    = $pointer.data( 'voucher-tax' )

			data =
				amount: $form.find( 'input[name="amount"]' ).val()
				notes:  $form.find( 'input[name="notes"], textarea[name="notes"]' ).val()

			data.amount = parseFloat( window.accounting.unformat( data.amount, woocommerce_admin.mon_decimal_point ) )

			# we want the redemption amount without tax
			if 'incl' is wc_pdf_product_vouchers_admin.tax_display_shop and tax

				tax_ratio = tax / value

				data.amount = data.amount / ( 1 + tax_ratio )

			params = $.param data

			url = url + '&' + params

			# all is well, "submit" the form
			window.location.href = url


	###*
	# Handle void form submit
	#
	# @since 3.0.0
	# @param {object} $pointer
	###
	handle_void_form_submit = ( $pointer ) ->

		$form = $pointer.find('form')
		url   = $form.attr('action')
		data  = $form.serialize()

		url = url + '&' + data

		# all is well, "submit" the form
		window.location.href = url


	##
	# Handle redeem/edit redemption AJAX response
	#
	# @since 2.6.-1
	# @param {object} response
	##
	handle_balance_response = ( response ) ->

		# handle errors
		if typeof response is 'object' and not response.success
			alert( response.data.message )

		# handle success
		else if response.success and response.data

			data = response.data

			$( '#wc-pdf-product-vouchers-voucher-balance .inside' ).html( data.balance_html )

			current_status = $( '#post_status' ).val().replace( 'wcpdf-', '' )

			if data.status and data.status isnt current_status

				$( '#post_status' ).val( 'wcpdf-' + data.status ).change()

				if data.notes_html

					$( '#wc-pdf-product-vouchers-voucher-notes ul.voucher-notes' ).replaceWith( data.notes_html )

			init_tiptip()

		$( '#wc-pdf-product-vouchers-voucher-balance' ).unblock()
