<?php

// Function to change email address
 
function mb_sender_email( $original_email_address ) {
    return 'noreply@stjohnstimeline.uk';
}
 
// Function to change sender name
function mb_sender_name( $original_email_from ) {
    return 'NoReply';
}
 
// Hooking up our functions to WordPress filters 
add_filter( 'wp_mail_from', 'mb_sender_email' );
add_filter( 'wp_mail_from_name', 'mb_sender_name' );
