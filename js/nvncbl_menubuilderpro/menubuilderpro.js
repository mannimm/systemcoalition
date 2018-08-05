jQuery.noConflict();

jQuery( document ).ready( function(){

	drawTree();

	// Switch options tab content (item type)
	jQuery( '#mbItemType' ).change( function(){
		var tabTarget = jQuery( this ).val();
		jQuery( '.tabcontent' ).hide();
		jQuery( '.tabcontent-'+ tabTarget ).show();
	} );

	// Needed for focusing when a new item is added
	jQuery('#mbLabel').mouseup( function(e){ return false; } );

	//Scroll action needed for positioning the options menu
	jQuery( document ).on("mousewheel", function() {
		if( jQuery( document ).scrollTop() >= 315 ){
			jQuery( '.menubuilder-options' ).css( 'position', 'fixed' );
		} else {
			jQuery( '.menubuilder-options' ).css( 'position', 'inherit' );
		}
	});

	// If mbLinkCategory or mbLinkCmspage are changed, adjust the other to be blank
	jQuery('#mbLinkCategory').change( function(){
		if( jQuery( this ).val() != '' ){
			jQuery('#mbLinkCmspage').val('');
		}
	} );
	jQuery('#mbLinkCmspage').change( function(){
		if( jQuery( this ).val() != '' ){
			jQuery('#mbLinkCategory').val('');
		}
	} );

} );

function drawTree(){

	cleanUpSubitemKeys( mbConfig );

	// Clear fields
	jQuery( '.menubuilder-options table input' ).val( '' );

	// Hide options
	jQuery( '.menubuilder-options' ).hide();

	// Clear tree
	jQuery('.menubuilder-tree').html('');

	// First droppable container
	jQuery('.menubuilder-tree').append( '<div class="first-droppable-container mb-level0"></div>' );

	// Draw new tree
	for( var item in mbConfig.subitems ){
		if( mbConfig.subitems.hasOwnProperty( item ) ){
			drawItem( mbConfig.subitems[item], 0, item );
		}
	}

	// Last droppable container
	jQuery('.menubuilder-tree').append( '<div class="last-droppable-container mb-level0"></div>' );

	// Click action on menu items
	jQuery( '.menubuilder-item' ).click( function(){

		// Selected CSS
		jQuery( '.menubuilder-item' ).removeClass( 'selected' );
		jQuery( this ).addClass( 'selected' );

		// Populate field values
		eval( 'var tmpItem = mbConfig.subitems['+ jQuery( this ).attr( 'mbid' ).replace( /-/gi, '].subitems[' ) +'];' );

		// Populate field values
		if( typeof tmpItem.item_type == 'undefined' ){
			tmpItem.item_type = 'basic';
		}
		// Set appropriate item_type, load appropriate tab
		jQuery( '#mbItemType option' ).removeAttr( 'selected' );
		jQuery( '#mbItemType option[value="'+ tmpItem.item_type +'"]' ).prop( 'selected', 'selected' );
		jQuery( '.tabcontent' ).hide();
		jQuery( '.tabcontent-'+ tmpItem.item_type ).show();

		// Basic
		jQuery( '#mbLabel' ).val( tmpItem.label );
		jQuery( '#mbLink' ).val( tmpItem.link );
		jQuery('#mbLinkBaseUrl').prop('checked',false);
		if( tmpItem.link_base_url == 1 ){
			jQuery( '#mbLinkBaseUrl' ).prop( 'checked', true );
		}
		jQuery( '#mbLinkTarget' ).val( tmpItem.link_target );
		jQuery( '#mbLinkCategory' ).val( tmpItem.link_category );
		jQuery( '#mbLinkCmspage' ).val( tmpItem.link_cmspage );
		jQuery( '#mbCss' ).val( tmpItem.css );
		jQuery( '#mbImage' ).val( tmpItem.image );

		// Custom HTML
		if( typeof tmpItem.custom_html == 'undefined' ){
			tmpItem.custom_html = '';
		}
		jQuery( '#mbCustomHtml' ).val( tmpItem.custom_html );

		// Subcategories
		if( typeof tmpItem.custom_html == 'undefined' ){
			tmpItem.subcatgories = '';
		}
		jQuery( '#mbSubcategories option' ).removeAttr( 'selected' );
		jQuery( '#mbSubcategories option[value="'+ tmpItem.subcategories +'"]' ).prop( 'selected', 'selected' );
		jQuery( '#mbSubcategoryBreak' ).val( tmpItem.subcategory_break );

		// Static Blocks
		jQuery( '#mbStaticblock' ).val( tmpItem.staticblock );

		// Show options
		jQuery( '.menubuilder-options' ).show();

	} );

	// Draggable menu items
	jQuery('.menubuilder-item').draggable( { axis : 'y', revert : true } );

	// Droppable menu items
	jQuery( ".menubuilder-item, .droppable-container" ).droppable({
		accept: ".menubuilder-item",
		activeClass: "ui-state-hover",
		hoverClass: "ui-state-active",
		drop: function( event, ui ) {

			var fromMbid = ui.draggable.attr( 'mbid' );
			var toMbid = jQuery( this ).attr( 'mbid' );

			if( ( toMbid.replace( 'after-', '' ).indexOf( fromMbid +'-' ) == 0 ) || ( toMbid.replace( 'after-', '' ) == fromMbid ) ){
				return false;
			}
			if( ( toMbid.replace( 'appendage-', '' ).indexOf( fromMbid +'-' ) == 0 ) || ( toMbid.replace( 'appendage-', '' ) == fromMbid ) ){
				return false;
			}

			if( toMbid.indexOf( 'after' ) == 0 ){

				eval( 'var targetItem = mbConfig.subitems['+ toMbid.replace('after-','').replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'var tmpItem = mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'delete mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );

				//targetItem.subitems.push( tmpItem );
				if( countObjectProperties( targetItem.subitems ) > 0 ){

					/* Add as the first child to targetItem */
					cleanUpSubitemKeys( targetItem );
					var siblings = targetItem.subitems.splice( 0, countObjectProperties( targetItem.subitems ) );
					var newSubitems = new Array();
					newSubitems.push( tmpItem );
					for( var i in siblings ){
						if( siblings.hasOwnProperty( i ) ){
							newSubitems.push( siblings[i] );
						}
					}
					targetItem.subitems = newSubitems;

					cleanUpSubitemKeys( targetItem );

				} else {

					/* From parent of targetItem, cut siblings after targetItem,
					add tmpItem, paste previously removed siblings back */

					// Get parent
					var targetItemParentMbId = toMbid.replace('after-','').split('-');
					if( targetItemParentMbId.length > 1 ){
						var targetItemIndex = parseInt( targetItemParentMbId.splice(-1, 1) );
						var evalStr = 'var targetItemParent = mbConfig.subitems['+ targetItemParentMbId.join('-').replace( /-/gi, '].subitems[' ) +'];';
						eval( evalStr );
					} else {
						var targetItemIndex = targetItemParentMbId.splice(-1, 1);
						var targetItemParent = mbConfig;
					}

					//console.log( 'targetItemIndex: '+ targetItemIndex +' and targetItemParent: '+

					targetItemIndex = parseInt( targetItemIndex );

					var siblings = targetItemParent.subitems.splice( ( targetItemIndex + 1 ), ( countObjectProperties( targetItemParent.subitems ) ) );

					targetItemParent.subitems.push( tmpItem );
					for( var i in siblings ){
						if( siblings.hasOwnProperty( i ) ){
							targetItemParent.subitems.push( siblings[i] );
						}
					}

					cleanUpSubitemKeys( targetItemParent );

				}

			} else if( toMbid.indexOf( 'appendage' ) == 0 ){

				eval( 'var targetItem = mbConfig.subitems['+ toMbid.replace('appendage-','').replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'var tmpItem = mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'delete mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );

				// Get parent
				var targetItemParentMbId = toMbid.replace('appendage-','').split('-');
				if( targetItemParentMbId.length > 1 ){

					// Appendage of a non-root level item
					var targetItemIndex = parseInt( targetItemParentMbId.splice(-1, 1) );
					var evalStr = 'var targetItemParent = mbConfig.subitems['+ targetItemParentMbId.join('-').replace( /-/gi, '].subitems[' ) +'];';
					eval( evalStr );

					targetItemParent.subitems.push( tmpItem );
					cleanUpSubitemKeys( targetItemParent );

				} else {

					// Appendage of a root level item
					var targetItemIndex = targetItemParentMbId.splice(-1, 1);
					var targetItemParent = mbConfig;

					console.log( targetItemIndex );
					cleanUpSubitemKeys( targetItemParent );
					var newSubitems = new Array();
					var ctr = 0;
					for( var i in targetItemParent.subitems ){
						if( targetItemParent.subitems.hasOwnProperty( i ) && ctr <= targetItemIndex ){
							newSubitems.push( targetItemParent.subitems[i] );
							delete targetItemParent.subitems[i];
							ctr++;
						}
					}
					newSubitems.push( tmpItem );
					cleanUpSubitemKeys( targetItemParent );
					for( var i in targetItemParent.subitems ){
						if( targetItemParent.subitems.hasOwnProperty( i ) ){
							newSubitems.push( targetItemParent.subitems[i] );
						}
					}
					targetItemParent.subitems = newSubitems;

					cleanUpSubitemKeys( targetItemParent );

				}

			} else {
				eval( 'var targetItem = mbConfig.subitems['+ toMbid.replace('after-','').replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'var tmpItem = mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
				eval( 'delete mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );

				targetItem.subitems.push( tmpItem );
				cleanUpSubitemKeys( targetItem );
			}

			cleanUpSubitemKeys( mbConfig );
			drawTree();

		}
	});

	// First droppable item
	jQuery( ".first-droppable-container" ).droppable({
		accept: ".menubuilder-item",
		activeClass: "ui-state-hover",
		hoverClass: "ui-state-active",
		drop: function( event, ui ) {
			var fromMbid = ui.draggable.attr( 'mbid' );
			eval( 'var tmpItem = mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
			eval( 'delete mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );

			var newSubitems = new Array();
			newSubitems.push( tmpItem );
			for( var i in mbConfig.subitems ){
				if( mbConfig.subitems.hasOwnProperty( i ) ){
					newSubitems.push( mbConfig.subitems[i] );
				}
			}
			mbConfig.subitems = newSubitems;

			cleanUpSubitemKeys( mbConfig );
			drawTree();
		}
	});

	// Last droppable item
	jQuery( ".last-droppable-container" ).droppable({
		accept: ".menubuilder-item",
		activeClass: "ui-state-hover",
		hoverClass: "ui-state-active",
		drop: function( event, ui ) {
			var fromMbid = ui.draggable.attr( 'mbid' );
			eval( 'var tmpItem = mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
			eval( 'delete mbConfig.subitems['+ fromMbid.replace( /-/gi, '].subitems[' ) +'];' );
			mbConfig.subitems.push( tmpItem );
			cleanUpSubitemKeys( mbConfig );
			drawTree();
		}
	});

}

function drawItem( theItem, levelToUse, idSegment ){
	if( typeof theItem.label != 'undefined' ){

		// Draw item
		jQuery('.menubuilder-tree').append('<div class="menubuilder-item mb-level'+ levelToUse +'" mbid="'+ idSegment +'">'+ theItem.label +''+ ( theItem.link != '' ? ' <span class="comment">('+ theItem.link +')</span>' : '' ) +'</div><div class="droppable-container mb-level'+ ( ( typeof theItem.subitems != 'undefined' && ( countObjectProperties( theItem.subitems ) > 0 ) ) ? parseInt( levelToUse + 1 ) : levelToUse ) +'" mbid="after-'+ idSegment +'"></div>');

		// Draw children
		if( countObjectProperties( theItem.subitems ) > 0 ){

			cleanUpSubitemKeys( theItem );

			for( var item in theItem.subitems ){
				if( theItem.subitems.hasOwnProperty( item ) ){
					drawItem( theItem.subitems[item], parseInt( levelToUse + 1 ), idSegment +'-'+ item );
				}
			}

			jQuery('.menubuilder-tree').append('<div class="droppable-container mb-level'+ levelToUse +'" mbid="appendage-'+ idSegment +'"></div>');

		}
	}
}

function cleanUpSubitemKeys( theItem ){
	var newSubitems = [];
	var ctr = 0;
	for( var i in theItem.subitems ){
		if( theItem.subitems.hasOwnProperty( i ) ){
			newSubitems[ ctr ] = theItem.subitems[i];
			ctr++;
		}
	}
	theItem.subitems = newSubitems;
	if( countObjectProperties( theItem.subitems ) == 0 ){
		theItem.subitems = [];
	}
}

function countObjectProperties(obj)
{
	var count = 0;
	for(var i in obj)
		if(obj.hasOwnProperty(i))
			count++;

	return count;
}

function addItem(){
	mbConfig.subitems.push( {
		'item_type' : 'basic',
		'label' : '',
		'link' : '',
		'link_base_url' : 0,
		'link_target' : '',
		'link_category' : '',
		'link_cmspage' : '',
		'subitems' : [],
		'css' : '',
		'image' : '',
		'custom_html' : '',
		'subcategories' : '',
		'subcategory_break' : '',
		'staticblock' : ''
	} );
	drawTree();

	// Select the new item
	jQuery('.menubuilder-tree .menubuilder-item:last').click();

	// Focus on the label field
	jQuery('#mbLabel').focus();
}

function saveItem(){
	eval( 'var targetItem = mbConfig.subitems['+ getCurrentSelectionMbid().replace( /-/gi, '].subitems[' ) +'];' );
	targetItem.item_type = jQuery( '#mbItemType' ).val();
	targetItem.label = jQuery( '#mbLabel' ).val();
	targetItem.link = jQuery( '#mbLink' ).val();
	if( jQuery( '#mbLinkBaseUrl' ).prop( 'checked' ) == true ){
		targetItem.link_base_url = 1;
	} else {
		targetItem.link_base_url = 0;
	}
	targetItem.link_target = jQuery( '#mbLinkTarget' ).val();
	targetItem.link_category = jQuery( '#mbLinkCategory' ).val();
	targetItem.link_cmspage = jQuery( '#mbLinkCmspage' ).val();
	targetItem.css = jQuery( '#mbCss' ).val();
	targetItem.image = jQuery( '#mbImage' ).val();
	targetItem.custom_html = jQuery( '#mbCustomHtml' ).val();
	targetItem.subcategories = jQuery( '#mbSubcategories' ).val();
	targetItem.subcategory_break = jQuery( '#mbSubcategoryBreak' ).val();
	targetItem.staticblock = jQuery( '#mbStaticblock' ).val();

	var currentSelectionMbid = getCurrentSelectionMbid();
	drawTree();

	jQuery('.menubuilder-item[mbid=\"'+ currentSelectionMbid +'\"]').click();
}

function deleteItem(){
	eval( 'var tmpItem = mbConfig.subitems['+ getCurrentSelectionMbid().replace( /-/gi, '].subitems[' ) +'];' );
	eval( 'delete mbConfig.subitems['+ getCurrentSelectionMbid().replace( /-/gi, '].subitems[' ) +'];' );

	var tmpMbid = getCurrentSelectionMbid().split('-');
	if( tmpMbid.length > 1 ){
		var tmpItemIndex = parseInt( tmpMbid.splice(-1, 1) );
		var evalStr = 'var tmpItemParent = mbConfig.subitems['+ tmpMbid.join('-').replace( /-/gi, '].subitems[' ) +'];';
		eval( evalStr );
	} else {
		var tmpItemIndex = tmpMbid.splice(-1, 1);
		var tmpItemParent = mbConfig;
	}

	if( countObjectProperties( tmpItemParent.subitems ) == 0 ){
		tmpItemParent.subitems = [];
	}

	drawTree();
}

function getCurrentSelectionMbid(){
	return jQuery( '.menubuilder-item.selected' ).attr('mbid');
}

function saveMenu(){
	jQuery( '#hiddenMenu' ).val( JSON.stringify( mbConfig ) );
	jQuery( '#mainMenuForm' ).submit();
}