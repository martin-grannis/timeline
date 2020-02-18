<?php

//quick one off function to update all posts - nothing to do with `rest` just parked here


// // disable wp/v2/users endpoints
// add_filter( 'rest_endpoints', function( $endpoints ){
//     if ( isset( $endpoints['/wp/v2/users'] ) ) {
//         unset( $endpoints['/wp/v2/users'] );
//     }
//     if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
//         unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
//     }
//     return $endpoints;
// });

add_action('rest_api_init', 'myplugin_register_endpoint');
function myplugin_register_endpoint()
{

    register_rest_route('myplugin_api/v2', 'test_endpoint', array(
        'methods' => 'GET,POST',
        'callback' => 'myplugin_test_endpoint',
    ));
}

function myplugin_test_endpoint()
{

    if ($_SERVER['PHP_AUTH_USER'] !== 'admin' || $_SERVER['PHP_AUTH_PW'] !== 'admin') {

        header("WWW-Authenticate: Basic realm=\"thetutlage\"");
        header("HTTP\1.0 401 Unauthorized");

        $response = array(
            "result" => false,
            "message" => 'Authenticate failed',
        );

        return $response;
        exit;

    }

    $response = array(
        "result" => true,
        "message" => 'Success',
    );

    return $response;
}

// add_action( 'rest_api_init', 'myplugin_register_endpoint' );
// function myplugin_register_endpoint() {

//  //   $abc="99";
//     register_rest_route( 'myplugin_api/v1', 'test_endpoint', array(
//         'methods'  => 'GET,POST',
//         'callback' => 'myplugin_test_endpoint',
//     ) );
// }

// function myplugin_test_endpoint()
// {

//     if($_SERVER['PHP_AUTH_USER'] !== 'admin' || $_SERVER['PHP_AUTH_PW'] !== 'admin')
//     {

//     header("WWW-Authenticate: Basic realm=\"thetutlage\"");
//     header("HTTP\1.0 401 Unauthorized");

//            $response = array(
//             "result"=>false,
//             "message"=>'Authenticate failed'
//             );

//         return $response;
//     exit;

//     }

//     $response = array(
//         "result" => true,
//         "message"=>'Success'
//         );

//      return $response;
// }
