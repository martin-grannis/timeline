<?php

/**
 *   Performs custom validation on custom post type "Site"
 */
function custom_post_site_save( $post_id, $post_data ) {
# If this is just a revision, don't do anything.
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( $post_data['post_type'] == 'resource' ) {
# In this example, we will deny post titles with less than 5 characters

		// get the meta

		$myPrivKey = $GLOBALS['_POST']['fields']['field_5b8fc483749218'];
		//$myTest    = preg_replace( "/[^A-Za-z0-9 ]/", '', $myPrivKey );
		$myTest    = preg_replace("/\W+/", '', $myPrivKey );

		$GLOBALS['_POST']['fields']['field_5b8fc483749218'] = $myTest;

//		if ( $myPrivKey != $myTest ) {
//# Add a notification
//			update_option( 'my_notifications', json_encode( array(
//				'error',
//				'Private URL can only have alpha numerics.'
//			) ) );
//# And redirect
//			header( 'Location: ' . get_edit_post_link( $post_id, 'redirect' ) );
//			exit;
//		}
	}
}

add_action( 'pre_post_update', 'custom_post_site_save', 10, 2 );

/**
 *   Shows custom notifications on wordpress admin panel
 */
function my_notification() {
	$notifications = get_option( 'my_notifications' );

	if ( ! empty( $notifications ) ) {
		$notifications = json_decode( $notifications );
#notifications[0] = (string) Type of notification: error, updated or update-nag
#notifications[1] = (string) Message
#notifications[2] = (boolean) is_dismissible?
		switch ( $notifications[0] ) {
			case 'error': # red
			case 'updated': # green
			case 'update-nag': # ?
				$class = $notifications[0];
				break;
			default:
# Defaults to error just in case
				$class = 'error';
				break;
		}

		$is_dismissable = '';
		if ( isset( $notifications[2] ) && $notifications[2] == true ) {
			$is_dismissable = 'is_dismissable';
		}

		echo '<div class="' . $class . ' notice ' . $is_dismissable . '">';
		echo '<p>' . $notifications[1] . '</p>';
		echo '</div>';

# Let's reset the notification
		update_option( 'my_notifications', false );
	}
}

add_action( 'admin_notices', 'my_notification' );