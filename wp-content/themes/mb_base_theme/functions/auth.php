<?php

//add_filter( 'authenticate', 'demo_auth', 10, 3 );

function demo_auth( $user, $username, $password ){
    // Make sure a username and password are present for us to work with
    if($username == '' || $password == '') return;

    $response = wp_remote_get( "http://localhost/auth_serv.php?user=$username&pass=$password" ); // Moodle post
    $ext_auth = json_decode( $response['body'], true );

     if( $ext_auth['result']  == 0 ) {
        // User does not exist,  send back an error message
        $user = new WP_Error( 'denied', __("ERROR: User/pass bad") );

     } else if( $ext_auth['result'] == 1 ) {
         // External user exists, try to load the user info from the WordPress user table
         $userobj = new WP_User();
         $user = $userobj->get_data_by( 'email', $ext_auth['email'] ); // Does not return a WP_User object 🙁
         $user = new WP_User($user->ID); // Attempt to load up the user with that ID

         if( $user->ID == 0 ) {
             // The user does not currently exist in the WordPress user table.
             // You have arrived at a fork in the road, choose your destiny wisely

             // If you do not want to add new users to WordPress if they do not
             // already exist uncomment the following line and remove the user creation code
             //$user = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );

             // Setup the minimum required user information for this example
             $userdata = array( 'user_email' => $ext_auth['email'],
                                'user_login' => $ext_auth['email'],
                                'first_name' => $ext_auth['first_name'],
                                'last_name' => $ext_auth['last_name']
                                );
             $new_user_id = wp_insert_user( $userdata ); // A new user has been created

             // Load the new user info
             $user = new WP_User ($new_user_id);
         } 

     }

     // Comment this line if you wish to fall back on WordPress authentication
     // Useful for times when the external service is offline
     remove_action('authenticate', 'wp_authenticate_username_password', 20);

     return $user;
}

// add_action('wp_login_failed', '_login_failed_redirect');

// function _login_failed_redirect( $username ){

//   //get your page by slug and then its permalink
//   $post = get_page_by_path('slug');

//   //Or you can get your page ID, if you are assigning a custom template to a page.
//   $redirect_page = !empty ( $post ) ? get_permalink ( $post->ID ) : site_url();

//   $user = get_user_by('login', $username );

//   if(!$user){
//     //Username incorrect
//     wp_redirect($redirect_page .'?login_error=1');

//   }else{
//    //Username Password combination incoorect
//     wp_redirect($redirect_page .'?login_error=2');
//   }

// }