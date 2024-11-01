/*******************************************************************************
 *
 * mbGallery-init.js
 * Author: pupunzi
 * Creation date: 21/06/15
 *
 ******************************************************************************/

jQuery(function(){
	jQuery(".thumbGallery").each(function(){
		var gallery = this;
		var $gallery = jQuery(gallery);
		$gallery.mbGallery();
	});
});
