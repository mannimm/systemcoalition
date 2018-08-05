function initNavigation(){
	jQuery( '.main-container' ).css( 'padding-top', jQuery('#header').outerHeight() +'px' );
}
jQuery( window ).scroll( initNavigation );
jQuery( window ).resize( initNavigation );