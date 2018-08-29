function initNavigation(){
	jQuery( '.main-container' ).css( 'padding-top', jQuery('#header').outerHeight() +'px' );
}
jQuery( document ).ready( initNavigation );
jQuery( window ).scroll( initNavigation );
jQuery( window ).resize( initNavigation );