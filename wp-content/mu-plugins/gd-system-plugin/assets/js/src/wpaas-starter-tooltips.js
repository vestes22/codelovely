/**
 * WordPress dependencies
 */
const { DotTip } = wp.nux;

/**
 * External dependencies
 */
var isCollision = require( 'box-collide' );

( function( $ ) {

  $( document ).ready( function() {

    /**
     * Local vars
     */
    var tipData = wpaasStarterTips.tipData,
        tips    = [];

    tipData.map( function( tip, index ) {

      if ( ! Object.keys( tip ).length ) {
        return;
      }

      var target     = document.querySelector( tip.target ),
          $linkClone = jQuery( target ).clone().prop( 'id', jQuery( target ).attr( 'id' ) + '-tip' ),
          position   = target.getBoundingClientRect();

      $linkClone.attr( 'class', 'tip-target' )
                .html( '' )
                .css( {
                  'height': '0px',
                  'left': parseInt( position.x + ( position.width / 2 ) ) + 'px',
                  'list-style': 'none',
                  'position': 'fixed',
                  'top': position.height + 'px',
                  'width': '0px',
                  'z-index': 99999,
                } )
                .appendTo( jQuery( 'body' ) );

      tips.push( 'wpaas/starter-tip-' + index );

      document.querySelectorAll( '#wpadminbar li' ).forEach( li => {

        if ( ! wp.data.select( 'core/nux' ).isTipVisible( 'wpaas/starter-tip-' + index ) ) {
          return;
        }

        // Hide the tip when a dropdown overlaps with the tip content
        li.addEventListener( 'mouseenter', function( e ) {
          // No dropdown, or no tips visible. Do nothing.
          if ( ! jQuery( e.target ).hasClass( 'menupop' ) || ! wp.data.select( 'core/nux' ).areTipsEnabled() || ! jQuery( '.tip-target:not(:empty)' ).length ) {
            return;
          }

           // Temporarily show the block to get proper height/width atts in getBoundingClientRect()
          jQuery( e.target.children[1] ).css( {
            position:   'absolute',
            visibility: 'hidden',
            display:    'block'
          } );

          // Dropdown overlaps with tip
          if( isCollision( e.target.children[1].getBoundingClientRect(), document.querySelector( '.tip-target:not(:empty) .components-popover__content' ).getBoundingClientRect() ) ) {
            jQuery( '.tip-target:not(:empty)' ).stop().fadeTo( 'medium', 0 );
          }

          jQuery( e.target.children[1] ).attr( 'style', '' );
        } );

        // Re-show the hidden tip
        li.addEventListener( 'mouseleave', function( e ) {
          /**
           * Does not show a tip when any of the following conditionals is tru:
           * - Mouse over from admin bar menu item with dropdown to another with dropdown
           * - Hover over child menu link whos parent has a dropdown
           * - Hover over dropdown element
           * - Hover over hidden tip
           */
          if ( ( jQuery( e.fromElement ).hasClass( 'menupop' ) && jQuery( e.toElement ).is( '[aria-haspopup]' ) ) || ( jQuery( e.toElement ).hasClass( 'ab-item' ) && jQuery( e.toElement ).parents( 'li' ).hasClass( 'menupop' ) ) || jQuery( e.toElement ).hasClass( 'ab-submenu' ) || jQuery( e.toElement ).closest( 'li.tip-target' ).length ) {
            return;
          }
          jQuery( '.tip-target:not(:empty)' ).stop().fadeTo( 'medium', 1 );
        } );

      } );

      wp.element.render(
        <DotTip
          tipId={'wpaas/starter-tip-' + index}
        >
          { tip.text }
        </DotTip>,
        document.getElementById( jQuery( target ).attr( 'id' ) + '-tip' )
      );

    } );

    wp.data.dispatch( 'core/nux' ).triggerGuide( tips );

    /**
     * Reposition tip targets on resize
     */
    window.onresize = function() {
      jQuery( '.tip-target' ).each( function() {
        if (window.matchMedia('(max-width: 782px)').matches) {
          jQuery( '.tip-target' ).addClass( 'hidden' );
          return;
        }

        jQuery( '.tip-target' ).removeClass( 'hidden' );

        var parent    = document.querySelector( '#' + jQuery( this ).prop( 'id' ).replace( '-tip', '' ) ),
            parentPos = parent.getBoundingClientRect();

        jQuery( this ).css( {
          'left': parseInt( parentPos.x + ( parentPos.width / 2 ) ) + 'px',
        } );
      } );
    };

  } );
} )( jQuery );
