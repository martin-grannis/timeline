<?php

#update_option( 'siteurl', 'https://stjohnstimeline.uk' );
#update_option( 'home', 'https://stjohnstimeline.uk' );


/**
 * Created by PhpStorm.
 * User: m.barrett
 * Date: 10/09/2018
 * Time: 14:55
 */
// function theme_name_scripts() {
//     //wp_enqueue_style( 'style-name', get_stylesheet_uri() );

//     wp_enqueue_style( 'wireframe-min', get_template_directory_uri() . '/css/wireframe-theme.min.css' );
//     wp_enqueue_style( 'foundation-min', get_template_directory_uri() . '/css/foundation.min.css' );
//     wp_enqueue_style( 'main-styles', get_template_directory_uri() . '/style.css', array(), filemtime( get_template_directory() . '/style.css' ), false );
// }

// add_action( 'wp_enqueue_scripts', 'theme_name_scripts' );

// function login_stylesheet() {
//     wp_enqueue_style( 'custom-login', plugins_url( 'style-login.css', __FILE__ ) );
// }
// add_action( 'login_enqueue_scripts', 'login_stylesheet' );


require_once __DIR__ . '/functions/email_system.php';
//require_once __DIR__ . '/functions/renewals.php';  

function my_login_logo() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(/wp-content/uploads/2019/07/logo9.png);
		height:180px;
		width:320px;
		background-size: 320px 180px;
		background-repeat: no-repeat;
        	
        }
        .login .input[type=text] {
            font-size:1rem ! important;
            border-radius:6px;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_logo' );

// // test
if (false) {
    $emails = wc_memberships()->get_emails_instance()->get_email_classes();
//require_once '/var/www/mb/wp-content/plugins/woocommerce-memberships/includes/emails/class-wc-memberships-user-membership-ending-soon-email.php';
    $email_es = new WC_Memberships_User_Membership_Ending_Soon_Email();
    $email_es->trigger(2319);
}

// bulk action
add_filter('wc_memberships_team_teams_area_sections', 'add_bulk_section',10,1);
function add_bulk_section($arr){
    // insert into array at position 1
    $pos = 1;
    $arr = array_slice($arr, 0, $pos, true) +
    ['bulk'=>'Bulk Actions'] +
    array_slice($arr, $pos, NULL, true);
    
    return $arr;
}

add_action( 'login_form_middle', 'add_lost_password_link' );
function add_lost_password_link() {
    return 
    '<a href="/wp-login.php?action=lostpassword&amp;redirect_to=/" rel="nofollow" title="Forgot Password">Click to reset your password</a><br>';
}

require_once __DIR__ . '/functions/posts_taxonomies.php';
//require_once __DIR__ . '/functions/custom_fields.php';
require_once __DIR__ . '/functions/custom_search.php';
require_once __DIR__ . '/functions/products.php';
require_once __DIR__ . '/functions/video.php';
require_once __DIR__ . '/functions/private_links.php';
require_once __DIR__ . '/functions/post_save_button.php';
// wot is this? require_once __DIR__ . '/functions/rest.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/oneOff.php';
require_once __DIR__ . '/functions/ajax.php';
require_once __DIR__ . '/functions/exportTeam.php';

require_once __DIR__ . '/functions/import_timeline_users.php'; // this is for inception - one off timeline imnport - then comment out

// - possibly not needed - commented out NOT FINISHED OR TESTED EITHER
require_once __DIR__ . '/functions/backend-team-sync.php'; // this is for back end syncint of teams

require_once __DIR__ . '/functions/frontend-team-sync.php'; 

add_theme_support('woocommerce');

// fix to let simple page ordering work with admin columns
//add_filter( 'acp/sorting/default', '__return_false' );

#// working but comented for now
require_once __DIR__ . '/functions/private_links.php';
require_once __DIR__ . '/functions/validation.php';

/**
 * Proper way to enqueue scripts and styles
 */
function wpdocs_theme_name_scripts()
{
    // have take out foundation for a test !
    // styles
    wp_enqueue_style('montserrat', 'https://fonts.googleapis.com/css?family=Montserrat');
    wp_enqueue_style('foundation6', 'https://cdn.jsdelivr.net/npm/foundation-sites@6.5.0-rc.3/dist/css/foundation.min.css');
    wp_enqueue_style('motionUI', 'https://cdn.jsdelivr.net/npm/motion-ui@1.2.3/dist/motion-ui.min.css');
    //wp_enqueue_style('select2', get_template_directory_uri() . '/css/select2.min.css');
    wp_enqueue_style('myCompiledStyles', get_template_directory_uri() . '/css/fromMySass.css', false, filemtime(get_stylesheet_directory() . '/css/fromMySass.css'));
    wp_enqueue_style('myCssStyles', get_template_directory_uri() . '/css/my_css.css', false, filemtime(get_stylesheet_directory() . '/css/my_css.css'));

    // scripts

    wp_enqueue_script('whatInput', 'https://cdnjs.cloudflare.com/ajax/libs/what-input/5.1.2/what-input.min.js', [], null, true);
    wp_enqueue_script('founcation6Script', 'https://cdn.jsdelivr.net/npm/foundation-sites@6.5.0-rc.3/dist/js/foundation.min.js', [], null, true);
    //wp_enqueue_script('select2JS', get_template_directory_uri() . '/js/select2.min.js', [], false, true);

    wp_enqueue_script('newGridStyleJS', get_template_directory_uri() . '/js/myApp.js', [], filemtime(get_stylesheet_directory() . '/js/myApp.js'), true);
}

add_action('wp_enqueue_scripts', 'wpdocs_theme_name_scripts');

function wpse239532_load_admin_style()
{
    wp_enqueue_style('admin_css', get_stylesheet_directory_uri() . '/css/admin-css.css', false, '1.0.0');
    wp_enqueue_script('admin-js-mrb', get_stylesheet_directory_uri() . '/js/admin-js-mrb.js', [], null, true);
}

add_action('admin_enqueue_scripts', 'wpse239532_load_admin_style');

//function pagination($pages = '', $range = 4)
function pagination($pquery, $range = 4)
{
    $pages = '';
    if (is_a($pquery, 'WP_Query')) {
        $pages = $pquery->max_num_pages;
    } else {
        $pages = $pquery;
    }

    global $wp_query, $paged;

    $showitems = ($range * 2) + 1;

    if (empty($paged)) {
        $paged = 1;
    }

    if ($pages == '') {
        $pages = $wp_query->max_num_pages;
        if (!$pages) {
            $pages = 1;
        }
    }

    if (1 != $pages) {
        echo "<div class=\"pagination\"><span>Page " . $paged . " of " . $pages . "</span>";
        if ($paged > 2 && $paged > $range + 1 && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link(1) . "'>&laquo; First</a>";
        }

        if ($paged > 1 && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link($paged - 1) . "'>&lsaquo; Previous</a>";
        }

        for ($i = 1; $i <= $pages; $i++) {
            if (1 != $pages && (!($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems)) {
                //todo
                // peek ito the records at top of each page and get the dates instead of the page number
                // maybe something like this
                $query_vars = $pquery->query_vars;
                $query_vars['paged'] = $i;
                $tmpQ = new WP_Query($query_vars);

                $oneRecord = $tmpQ->posts[0]->ID;
                // get meta date;
                $d = get_post_meta($oneRecord, 'timeline_dates', true);
//                $g = $oneRecord->title;
                wp_reset_postdata();

//                echo ($paged == $i) ? "<span class=\"current\">" . $i . "</span>" : "<a href='" . mb_get_pagenum_link($i) . "' class=\"inactive ajaxPaginationLink\">" . $i . "</a>";
                echo ($paged == $i) ? "<span class=\"current\">" . $d . "</span>" : "<a href='" . mb_get_pagenum_link($i) . "' class=\"inactive ajaxPaginationLink\">" . $d . "</a>";

            }
        }

        if ($paged < $pages && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href=\"" . mb_get_pagenum_link($paged + 1) . "\">Next &rsaquo;</a>";
        }

        if ($paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link($pages) . "'>Last &raquo;</a>";
        }

        echo "</div>\n";
    }
}

function paginationORG($pquery, $range = 4)
{
    $pages = '';
    if (is_a($pquery, 'WP_Query')) {
        $pages = $pquery->max_num_pages;
    } else {
        $pages = $pquery;
    }

    global $wp_query, $paged;

    $showitems = ($range * 2) + 1;

    if (empty($paged)) {
        $paged = 1;
    }

    if ($pages == '') {
        $pages = $wp_query->max_num_pages;
        if (!$pages) {
            $pages = 1;
        }
    }

    if (1 != $pages) {
        echo "<div class=\"pagination\"><span>Page " . $paged . " of " . $pages . "</span>";
        if ($paged > 2 && $paged > $range + 1 && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link(1) . "'>&laquo; First</a>";
        }

        if ($paged > 1 && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link($paged - 1) . "'>&lsaquo; Previous</a>";
        }

        for ($i = 1; $i <= $pages; $i++) {
            if (1 != $pages && (!($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems)) {
                //todo
                // peek ito the records at top of each page and get the dates instead of the page number
                // maybe something like this
                $query_vars = $pquery->query_vars;
                $query_vars['paged'] = $i;
                $tmpQ = new WP_Query($query_vars);

                $oneRecord = $tmpQ->posts[0]->ID;
                // get meta date;
                $d = get_post_meta($oneRecord, 'timeline_dates', true);
//                $g = $oneRecord->title;
                wp_reset_postdata();

//                echo ($paged == $i) ? "<span class=\"current\">" . $i . "</span>" : "<a href='" . mb_get_pagenum_link($i) . "' class=\"inactive ajaxPaginationLink\">" . $i . "</a>";
                echo ($paged == $i) ? "<span class=\"current\">" . $d . "</span>" : "<a href='" . mb_get_pagenum_link($i) . "' class=\"inactive ajaxPaginationLink\">" . $d . "</a>";

            }
        }

        if ($paged < $pages && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href=\"" . mb_get_pagenum_link($paged + 1) . "\">Next &rsaquo;</a>";
        }

        if ($paged < $pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages) {
            echo "<a class='ajaxPaginationLink' href='" . mb_get_pagenum_link($pages) . "'>Last &raquo;</a>";
        }

        echo "</div>\n";
    }
}



function isRemoteAuth()
{

    return current_user_can("remoteauthorisedusercapability");

}

function mb_get_pagenum_link($p)
{
    return '//#Paged=' . intval($p);

}


// // login functions
// //if ( (isset($_GET['action']) && $_GET['action'] != 'logout') || (isset($_POST['login_location']) && !empty($_POST['login_location'])) ) {
//     add_filter('login_redirect', 'my_login_redirect', 10, 3);
//     function my_login_redirect() {
//         $location = $_SERVER['HTTP_REFERER'];
//         wp_safe_redirect($location);
//         exit();
//     }
// //}

// logout functions
add_action('wp_logout', 'auto_redirect_after_logout');
function auto_redirect_after_logout()
{
    wp_redirect(home_url());
}

// function custom_logout_message(){
//add_action('check_admin_referer', 'changed_logout_message', 10, 2);
add_action('check_admin_referer', 'changed_logout_message', 10, 2);
function changed_logout_message($action, $result)
{
    if ($result == 1) {return;} // we are actually logging out

    if ('log-out' == $action) {
        //$html = "Logging out of Timeline</p><p>";
        $html = "<p>";
        $redirect_to = "/";
        $html .= sprintf(
            /* translators: %s: logout URL */
            __('Click <a href="%s">here</a> to logout of Timeline - else use back button?'),
            wp_logout_url($redirect_to) // for Timeline always redirect to home page on logout
        );
        wp_die($html, __('Logout Timeline?'), 403);
    }

    // $html = sprintf(
    //     /* translators: %s: site name */
    //     __( 'You are attempting to log out of %s' ),
    //     get_bloginfo( 'name' )
    // );
    // $html .= '</p><p>';
    //
    // $html .= sprintf(
    //     /* translators: %s: logout URL */
    //     __( 'Do you really want to <a href="%s">log out</a>?' ),
    //     wp_logout_url( $redirect_to )
    // );

}

add_action('admin_head', 'hide_editor');
function hide_editor()
{
    $template_file = $template_file = basename(get_page_template());
    if ($template_file == 'front-page.php') { // template
        remove_post_type_support('page', 'editor');
    }
}

// make username the same as the email

// function wpse_filter_user_data( $data, $update, $id) {
//     if( isset( $data[ 'user_login' ] ) ) {
//       $data[ 'display_name' ] = $data[ 'user_login' ];
//       return $data;
//     }
//     $user = get_user_by( 'email', $data[ 'user_email' ] );
//     $data[ 'display_name' ] = $user->user_login;
//     return $data;
//   }
//   add_filter( 'wp_pre_insert_user_data', 'wpse_filter_user_data', 10, 3 );


// subscription cancellation yes/no

function wcs_cancel_subscription_confirmation() {
	if ( ! function_exists( 'is_account_page' ) ) {
		return;
	}
	
	$cancel_confirmation_required = apply_filters( 'wcs_cancel_confirmation_promt_enabled', ( 'yes' == get_option( "wcs-cancel-confirmation", 'no' ) ) ? true : false );
	
	if ( is_account_page() && 'yes' == $cancel_confirmation_required ) {
		wp_register_script( 'wcs-cancel-subscription-confirmation-script', get_template_directory_uri() .'/js/wcs-cancel-subscription-confirmation.js', array( 'jquery' ), '1.0.0', true );
        
        $script_atts = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
            'promt_msg' => apply_filters( "wcs_cancel_confirmation_promt_msg", 
            __("Are you sure you want to cancel your subscription?\nOPtionally - you could let us know your reason:",
            "wcs-cancel-confirmation" ) ) ,
			'error_msg' => apply_filters( "wcs_cancel_confirmation_error_msg", __("There has been an error when saving the cancellation reason. Please try again.","wcs-cancel-confirmation" ) )
		);
		wp_localize_script( 'wcs-cancel-subscription-confirmation-script', 'ajax_object', $script_atts );
		wp_enqueue_script( 'wcs-cancel-subscription-confirmation-script' );
    }
}
add_action( 'wp_enqueue_scripts', 'wcs_cancel_subscription_confirmation' );
function wcs_cancel_confirmation() {
	$subscription_id = intval( $_POST['subscription_id'] );
	$reason_to_cancel = sanitize_text_field( $_POST['reason_to_cancel'] );
	$subscription = wc_get_order( $subscription_id );
	$note_id = $subscription->add_order_note( apply_filters( "wcs_cancel_confirmation_note_header", __( "Cancellation Reason:", "wcs-cancel-confirmation" ) )."<br /><b><i>".$reason_to_cancel."</i></b>" );
	$subscription->save();
	echo $note_id;
    wp_die(); 
}
add_action( 'wp_ajax_wcs_cancel_confirmation', 'wcs_cancel_confirmation' );
function add_cancelation_settings( $settings ) {
	$misc_section_end = wp_list_filter( $settings, array( 'id' => 'woocommerce_subscriptions_miscellaneous', 'type' => 'sectionend' ) );
	$spliced_array = array_splice( $settings, key( $misc_section_end ), 0, array(
		array(
			'name'     => __( 'Ask for the cancellation reason', 'wcs-cancel-confirmation' ),
			'desc'     => __( 'Prompt the customer for a cancellation reason', 'wcs-cancel-confirmation' ),
			'id'       => 'wcs-cancel-confirmation',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' =>  __( 'Ask for the cancellation reason when the customer cancels a subscription from the My Account page. The provided reason will be added as a subscription note in the backend.' ),
		),
	) );
	return $settings;
}
add_filter( 'woocommerce_subscription_settings', 'add_cancelation_settings'  );

// END subscription cancellation yes/no

// enable fully cancelled subscription to buy product fresh - NOT pending-cancellations
function limit_if_active_and_cancelled_only( $is_limited_for_user, $product, $user_id  )
{
    // ignore this function if user_id is 0
    if ($user_id==0){return false;}
// if user has subscription in cancelled state return false
    $user_subscriptions  = wcs_get_subscriptions( array(
        'subscriptions_per_page' => -1,
        'customer_id'            => $user_id,
        'product_id'             => $product->get_id(),
    ) );
    
        foreach($user_subscriptions As $sub){
            if ($sub->data["status"]!="cancelled"){
                return true;
                      }
    }
    
    return false;

}

add_filter("woocommerce_subscriptions_product_limited_for_user","limit_if_active_and_cancelled_only",10,3);

// is team manager 
function is_team_manager(){
    $teams = wc_memberships_for_teams_get_teams();

    if (!$teams){return false;}

$user_id = get_current_user_id();

$team_manager=false;

foreach ($teams as $team){
    $owner = $team->get_owner_id();
    if ($user_id == $owner){
      return true;
      break;
    }
    return false;
}

}

// change the required password strength
add_filter( 'woocommerce_min_password_strength', 'reduce_min_strength_password_requirement' );
function reduce_min_strength_password_requirement( $strength ) {
    // 3 => Strong (default) | 2 => Medium | 1 => Weak | 0 => Very Weak (anything).
    return 2; 
}

// change the wording of the password hint.
add_filter( 'password_hint', 'smarter_password_hint' );
function smarter_password_hint ( $hint ) {
    $hint = 'Hint: longer is stronger, and consider using a sequence of random words (ideally non-English).';
    return $hint;
}