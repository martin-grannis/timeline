<?php
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

// email preview function
/**
* Preview WooCommerce Emails.
* @author WordImpress.com
* @url https://github.com/WordImpress/woocommerce-preview-emails
* If you are using a child-theme, then use get_stylesheet_directory() instead
*/

// require_once __DIR__ . '/functions/email_system.php';

// // test
// $emails        = wc_memberships()->get_emails_instance()->get_email_classes();
// //require_once '/var/www/mb/wp-content/plugins/woocommerce-memberships/includes/emails/class-wc-memberships-user-membership-ending-soon-email.php';
// $email_es = new WC_Memberships_User_Membership_Ending_Soon_Email();
// $email_es->trigger(1967);


require_once __DIR__ . '/functions/posts_taxonomies.php';
//require_once __DIR__ . '/functions/custom_fields.php';
require_once __DIR__ . '/functions/custom_search.php';
require_once __DIR__ . '/functions/products.php';
require_once __DIR__ . '/functions/video.php';
require_once __DIR__ . '/functions/private_links.php';
require_once __DIR__ . '/functions/rest.php';
require_once __DIR__ . '/functions/auth.php';
require_once __DIR__ . '/functions/oneOff.php';
require_once __DIR__ . '/functions/ajax.php';


add_theme_support('woocommerce');

// fix to let simple page ordering work with admin columns
//add_filter( 'acp/sorting/default', '__return_false' );

#// working but comented for now
// require_once __DIR__ . '/functions/private_links.php';
// require_once __DIR__ . '/functions/validation.php';
// require_once __DIR__ . '/functions/post_save_button.php';

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
    wp_enqueue_style('myCompiledStyles', get_template_directory_uri() . '/css/fromMySass.css', false, filemtime(get_stylesheet_directory() . '/scss/fromMySass.scss'));
    wp_enqueue_style('myCssStyles', get_template_directory_uri() . '/css/my_css.css', false, filemtime(get_stylesheet_directory() . '/css/my_css.css'));

    // scripts

    wp_enqueue_script('whatInput', 'https://cdnjs.cloudflare.com/ajax/libs/what-input/5.1.2/what-input.min.js', [], null, true);
    wp_enqueue_script('founcation6Script', 'https://cdn.jsdelivr.net/npm/foundation-sites@6.5.0-rc.3/dist/js/foundation.min.js', [], null, true);
    //wp_enqueue_script('select2JS', get_template_directory_uri() . '/js/select2.min.js', [], false, true);

    wp_enqueue_script('newGridStyleJS', get_template_directory_uri() . '/js/myApp.js', [], filemtime(get_stylesheet_directory() . '/js/myApp.js'), true);
}

add_action('wp_enqueue_scripts', 'wpdocs_theme_name_scripts');


//function pagination($pages = '', $range = 4)
function pagination($pquery, $range = 4)
{
    $pages=$pquery->max_num_pages;
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
                 $query_vars['paged']=$i;
                 $tmpQ = new WP_Query($query_vars);
                
                
                
                
                
                
                 // $oneRecord = $tmpQ->the_post();
                // $g = $oneRecord->title;
                // wp_reset_postdata();

                echo ($paged == $i) ? "<span class=\"current\">" . $i . "</span>" : "<a href='" . mb_get_pagenum_link($i) . "' class=\"inactive ajaxPaginationLink\">" . $i . "</a>";
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

function mb_get_pagenum_link($p)
{
    return '//#Paged=' . intval($p);

}

// logout functions
add_action('wp_logout', 'auto_redirect_after_logout');
function auto_redirect_after_logout()
{
    wp_redirect(home_url());
}

// function custom_logout_message(){
    //add_action('check_admin_referer', 'changed_logout_message', 10, 2);
    add_action('check_admin_referer', 'changed_logout_message',10,2);
function changed_logout_message($action, $result)
{
    if ($result==1)
    {return;} // we are actually logging out

    if ('log-out' == $action) {
        //$html = "Logging out of Timeline</p><p>";
        $html = "<p>";
        $redirect_to = "/";
        $html .= sprintf(
            /* translators: %s: logout URL */
            __('Click <a href="%s">here</a> to logout of Timeline - else use back button?'),
            wp_logout_url($redirect_to)// for Timeline always redirect to home page on logout
        );
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
    wp_die( $html, __( 'Logout Timeline?' ), 403 );
}