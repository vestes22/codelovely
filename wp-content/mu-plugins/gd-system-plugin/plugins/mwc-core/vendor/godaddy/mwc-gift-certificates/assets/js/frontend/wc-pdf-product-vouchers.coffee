jQuery ( $ ) ->

	'use strict'

	$( document ).bind 'reset_image', ->

		$( '.voucher-fields-wrapper-variation' ).hide()

	$( 'form.variations_form' ).on 'show_variation', ( event, variation ) ->

		$( '.voucher-fields-wrapper-variation' ).hide()
		$( '#voucher-fields-wrapper-' + variation.variation_id ).show()

	# in WC >= 3.0, voucher images do not open in prettyPhoto anymore, so we need to use the new Photoswipe
	if typeof PhotoSwipe isnt 'undefined'

		$( 'body' ).on 'click', '.voucher-image-option a', ( event ) ->
			event.preventDefault()

			pswpElement = $( '.pswp' )[0]
			items       = get_voucher_images( event.target )
			index       = $( event.target ).closest( '.voucher-image-option' ).index()

			options = {
				index:                 index
				shareEl:               false
				closeOnScroll:         false
				history:               false
				hideAnimationDuration: 0
				showAnimationDuration: 0
			}

			# initialize and open PhotoSwipe
			photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options )
			photoswipe.init()

		get_voucher_images = ( el ) ->
			$images = $( el ).closest( '.voucher-image-options' ).find( '.voucher-image-option img' )
			items   = []

			if $images.length > 0
				$images.each ( i, el ) ->

					link            = $( el ).closest( 'a' )
					large_image_src = link.attr( 'href' )
					large_image_w   = link.attr( 'data-large_image_width' )
					large_image_h   = link.attr( 'data-large_image_height' )

					item =
						src:   large_image_src
						w:     large_image_w
						h:     large_image_h
						title: $( el ).attr( 'title' )

					items.push( item )

			return items

