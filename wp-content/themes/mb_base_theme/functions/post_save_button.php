<?php

add_action( 'post_submitbox_misc_actions', 'generateURL_button' );

function generateURL_button(){
	global $post;
	// Show only for resources
	if ( ! $post
//	     || 'publish' !== $post->post_status
	     || 'resource' !== $post->post_type ) {
		return;
	}

	$html  = '<div id="major-publishing-actions" style="overflow:hidden">';
	$html .= '<div id="publishing-action">';
	$html .= '<input type="submit" accesskey="p" tabindex="5" value="Generate Private URL" class="button-primary" id="customGenURL" name="publish">';
	$html .= '<input type="button" accesskey="p" tabindex="5" value="Clear Private URL" class="button-primary button-admin-clear" id="clearCustomGenURL" name="publish">';
	$html .= '</div>';
	$html .= '</div>';
	$html .= "<script>
		window.addEventListener('load', function(){ 
			jQuery('input#customGenURL').click(function(event){
    			event.preventDefault();
    			// make a random 40 char alpha num string
				randM = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
				//jQuery('input#acf-field-document_private__url').val(randM)
				jQuery('input#acf-field_5c4738432a03b').val(randM)
				//alert('You\'re editing " . $post->post_type . " " . $post->ID . "'); 
		//		echo '<br />';
				return true; 
			});
		
			jQuery('#clearCustomGenURL').click(function(event){
    			event.preventDefault();
				jQuery('input#acf-field_5c4738432a03b').val('');
				//alert('You\'re clearing " . $post->post_type . " " . $post->ID . "'); 
				return true; 
			});
		
		}); 
    </script>";
	echo $html;
}


