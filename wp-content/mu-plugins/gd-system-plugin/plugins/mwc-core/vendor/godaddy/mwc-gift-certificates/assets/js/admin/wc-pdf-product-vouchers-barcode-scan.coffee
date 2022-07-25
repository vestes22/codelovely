"use strict"

jQuery ( $ ) ->

	window.pdf_vouchers_barcode_scanning_unsupported_browser = false

	pdf_vouchers  = window.wc_pdf_product_vouchers_admin ? {}
	video         = document.createElement( 'video' )
	canvasElement = document.getElementById( 'canvas' )
	canvas        = canvasElement.getContext( '2d' )
	barcodeValue  = $( '#barcode-value' )
	redeemForm    = $( '#redeem-voucher-form' )


	###*
	# Draw a line around the scanned barcode in preview.
	#
	# @since 3.5.0
	###
	drawLine = ( begin, end, color ) ->

		canvas.beginPath()
		canvas.moveTo(begin.x, begin.y)
		canvas.lineTo(end.x, end.y)
		canvas.lineWidth = 4
		canvas.strokeStyle = color
		canvas.stroke()

	###*
	# Render video stream inside the canvas.
	#
	# @since 3.5.0
	###
	tick = ->

		if ( video.readyState is video.HAVE_ENOUGH_DATA and 0 < video.videoHeight and 0 < video.videoWidth )
			canvasElement.hidden = false
			canvasElement.height = video.videoHeight
			canvasElement.width = video.videoWidth
			canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height)
			imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height)
			code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" })

		if ( code )
			outlineColor = '#FF3B58'
			drawLine( code.location.topLeftCorner, code.location.topRightCorner, outlineColor )
			drawLine( code.location.topRightCorner, code.location.bottomRightCorner, outlineColor )
			drawLine( code.location.bottomRightCorner, code.location.bottomLeftCorner, outlineColor )
			drawLine( code.location.bottomLeftCorner, code.location.topLeftCorner, outlineColor )
			stream = video.srcObject
			tracks = stream.getTracks()
			tracks.forEach( (track) -> track.stop() )
			video.srcObject = null
			barcodeValue.val( code.data )
			setTimeout( () ->
				canvasElement.hidden = true
				redeemForm.submit()
			, Math.floor( ( Math.random() * 500 ) + 200 ) )

		setTimeout ( () -> requestAnimationFrame( tick ) ), ( 1000 / 25 )


	###*
	# Polyfills navigator.mediaDevices.getUserMedia to support older browsers.
	#
	# @see https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia#Using_the_new_API_in_older_browsers
	#
	# @since 3.5.8
	###
	polyfill_get_user_media = ->

		navigator.mediaDevices = {} if undefined is navigator.mediaDevices

		if undefined is navigator.mediaDevices.getUserMedia

			window.pdf_vouchers_barcode_scanning_unsupported_browser = true unless navigator.webkitGetUserMedia or navigator.mozGetUserMedia

			navigator.mediaDevices.getUserMedia = ( constraints ) ->

				getUserMedia = navigator.webkitGetUserMedia || navigator.mozGetUserMedia

				return Promise.reject( new Error( 'getUserMedia is not implemented in this browser' ) ) if not getUserMedia
				return new Promise ( resolve, reject ) ->
					getUserMedia.call( navigator, constraints, resolve, reject )


	###*
	# Displays an error notice.
	#
	# @since 3.5.8
	###
	display_error_notice = ( message ) ->

		message_container = $( '.js-redeem-message' )
		message_container.html( '<p>' + message + '</p>'  )
			.removeClass( 'notice-success notice-waring' )
			.addClass( 'notice-error' )
		message_container.removeClass( 'hidden' )

	###*
	# Add hooks on document ready.
	#
	# @since 3.5.0
	###
	$(document).ready () ->

		# check for HTTPS
		if 'https:' isnt window.location.protocol
			display_error_notice( pdf_vouchers.i18n.barcode_requires_https_error)
			$( '#scan-qr' ).attr( 'disabled', 'disabled' )
			return

		$('#scan-qr').on('click', (e) ->

			e.preventDefault()

			# check for Promise support
			window.pdf_vouchers_barcode_scanning_unsupported_browser = true if "undefined" is typeof Promise

			polyfill_get_user_media()

			return display_error_notice( pdf_vouchers.i18n.barcode_unsupported_browser_error ) if window.pdf_vouchers_barcode_scanning_unsupported_browser

			navigator.mediaDevices.getUserMedia( { video: { facingMode: "environment" } } ).then( ( stream ) ->

				video.srcObject = stream
				video.setAttribute( "playsinline", true )
				video.play()
				requestAnimationFrame( tick )

			).catch( ( e ) ->

				display_error_notice( pdf_vouchers.i18n.barcode_scanner_generic_error )
				console.log( 'PDF Vouchers barcode scanner error: ' + e.message )
			)
		)
