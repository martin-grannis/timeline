<?php

function mb_get_contributors($pid)
{

    $myContribObjects = wp_get_object_terms($pid, 'video_contributor');
    $myContribList = "";
    foreach ($myContribObjects as $mco) {
        $myContribList .= ' ' . $mco->name . ',';
        // lost the last comma
    }
    return substr($myContribList, 0, strlen($myContribList) - 1);

}

function mb_get_categories($pid)
{
    $myCategoryObjects = wp_get_object_terms($pid, 'resource_category');
    $myCatList = "";
    foreach ($myCategoryObjects as $mco) {
        $myCatList .= ' ' . $mco->name . ',';
        // lost the last comma
    }
    return substr($myCatList, 0, strlen($myCatList) - 1);

}

// get products with posts categories
function mb_get_products_that_include_this_video($pid)
{

    $myCategoryObjects = wp_get_object_terms($pid, 'resource_category');
    $myCategories = [];
    foreach ($myCategoryObjects as $mco) {
        $myCategories[] = $mco->name;
    }

    // look through all plans for any that have products granting access to these categories
    $allPlans = wc_memberships_get_membership_plans();
    $products_to_purchase = array();

    foreach ($allPlans as $plan) {
        // $post_id = $plan->post->ID;
        $products = $plan->get_products();
        $plan_products = array();

        // get products that grant access tothis plan
        foreach ($products as $p):
            // save product name and link
            $plan_products[] = [
                'productName' => $p->get_title(),
                'productLink' => get_permalink($p->post->ID),
            ];
        endforeach;

        $rules = $plan->get_content_restriction_rules();
        foreach ($rules as $r) {
            if ($r->get_content_type_name() == "resource_category") {
                foreach ($r->get_object_ids() as $ruleObject) {

                    // raw sql to get the taxonomy name
                    $mySql = "SELECT t.*, tt.*
                    FROM wp_terms AS t
                    INNER JOIN wp_term_taxonomy AS tt
                    ON t.term_id = tt.term_id
                    WHERE t.term_id = " . $ruleObject;
                    global $wpdb;
                    $res_cat = $wpdb->get_row($mySql);
                    // $rule_categories[] = $res_cat->name;

                    // is this category in video category list?
                    // if so include the products in the productst to purchase array.
                    if (isset($res_cat) && in_array($res_cat->name, $myCategories, true)) {
                        $products_to_purchase = array_merge($products_to_purchase, $plan_products);

                    }

                }
            }
        }

    }

    return array_unique($products_to_purchase, SORT_REGULAR);

}

// get products with posts categories
function mb_can_user_access_video($uid, $pid)
{

// get the meta acf field holding the private link

    $privateLink = get_post_meta($pid, 'document_private__url', true);
    // we have a private link field entered
    if (!empty($privateLink)) {
        // get the url used
        $u = $_SERVER["REQUEST_URI"];
        $urlBits = explode('/', $_SERVER["REQUEST_URI"]);

        if ($urlBits[1] == "resource") {
            // does the 3rd section have a random string?
            // no
            if (!empty($urlBits[3])) {
                // do theymatch
                if ($urlBits[3] == $privateLink) {return true;}
            }

        }

    }

// no valid private link so continue

    $myCategoryObjects = wp_get_object_terms($pid, 'resource_category');
    $myCategories = [];
    foreach ($myCategoryObjects as $mco) {
        $myCategories[] = $mco->name;
    }

    // look through all plans for any that have products granting access to these categories

    $myUserMemberships = wc_memberships_get_user_active_memberships($uid);

    foreach ($myUserMemberships as $userM) {

        $plan = $userM->get_plan();
        $rules = $plan->get_content_restriction_rules();
        if (count($rules) == 0) {return true;} // means there is no restriction rule present
        foreach ($rules as $r) {
            if ($r->get_content_type_name() == "resource_category") {
                // if no objects then we allow all
                if (count($r->get_object_ids()) == 0) {return true;} // means there is rule but tis empty
                foreach ($r->get_object_ids() as $ruleObject) {

                    // raw sql to get the taxonomy name
                    $mySql = "SELECT t.*, tt.*
                    FROM wp_terms AS t
                    INNER JOIN wp_term_taxonomy AS tt
                    ON t.term_id = tt.term_id
                    WHERE t.term_id = " . $ruleObject;
                    global $wpdb;
                    $res_cat = $wpdb->get_row($mySql);
                    // $rule_categories[] = $res_cat->name;

                    // is this category in video category list?
                    // if so include the products in the productst to purchase array.
                    if (isset($res_cat) && in_array($res_cat->name, $myCategories, true)) {
                        return true;
                    }

                }
            }
        }
    }

    return false;

}

// hide restricted message at front end for not logged in users
function mb_memberships_restricted_fudge($content)
{
    return ""; // this removes restricted category taxonomy messages from front end - we handle these ourselves!
}
add_filter('wc_memberships_product_taxonomy_viewing_restricted_message', 'mb_memberships_restricted_fudge');

// sort product loop descriptions
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_action('woocommerce_shop_loop_item_title', 'woocommerce_shop_loop_item_title', 10);
function woocommerce_shop_loop_item_title()
{
    echo '<h2 class="woocommerce-loop-product__title">' . get_the_title() . '</h2>';
    //echo "<span class=mb_product>" . get_the_excerpt() . "</span>";
    echo "<span class=mb_product>" . get_the_content() . "</span>";
}

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

// remove "continue shopping" button after ading item to cart
add_filter('wc_add_to_cart_message_html','remove_continue_shoppping_button',10,2);

function remove_continue_shoppping_button($message, $products) {
    if (strpos($message, 'Continue shopping') !== false) {
        return preg_replace('/<a.*<\/a>/m','', $message);
    } else {
        return $message;
    }
}

// disable to test subscriptions
//add_action('woocommerce_after_cart_item_name', 'mb_afterCartTitle', 10, 2);

function mb_afterCartTitle($cart_item, $cart_item_key)
{
    // defined in before cart loop
    global $mb_expiry_loop, $mb_name_loop;

    $prod_id = $cart_item['product_id'];
    $product = get_post($prod_id);
    //$product_meta = get_post_meta($prod_id);
    //    $expiry = [];
    //    $p_title = [];
    $allPlans = wc_memberships_get_membership_plans();

    // search all plans for a qualifiying product
    //if found test this user has that plan - if so get expiry date!
    foreach ($allPlans as $plan) {
        // $post_id = $plan->post->ID;
        $products = $plan->get_products();
        foreach ($products as $p) {
            if ($p->id == $prod_id) {
                $myUserMemberships = wc_memberships_get_user_active_memberships(get_current_user_id());
                foreach ($myUserMemberships as $mm) {
                    if ($plan->id == $mm->plan_id) {
                        // one off to fidge expiry date for TESTING
                        // $mm->set_end_date("2018-10-23 19:00:00");

                        $str2DateStr = "+".$cart_item['quantity']." year";
                        // replace the new expiry dates of this plan in all instances by 1 year
                        if (in_array($mm->plan->name, $mb_name_loop)) {
                            $x = mb_get_latestDate($mm->plan->name);
                            //mb_replace_all_latest_dates($mm->plan->name, strtotime("+1 year", strtotime($x)));
                            mb_replace_all_latest_dates($mm->plan->name, strtotime($str2DateStr, strtotime($x)));
                            
                            // ad this new entry
                            $mb_name_loop[] = $mm->plan->name;
                            //$mb_expiry_loop[] = strtotime("+1 year", strtotime($x)); // save as unix date nuber
                            $mb_expiry_loop[] = strtotime($str2DateStr, strtotime($x)); // save as unix date nuber
                            
                        } else {
                            $mb_name_loop[] = $mm->plan->name;
                            //$mb_expiry_loop[] = strtotime("+1 year", strtotime($mm->get_end_date())); // save as unix date nuber
                            $mb_expiry_loop[] = strtotime($str2DateStr, strtotime($mm->get_end_date())); // save as unix date nuber
                            
                        }

                    }
                }
                // fudge to force expiry soon
                //  $mm->set_end_date("2018-10-23 19:00:00");
                //break 3; // exit loop
            }

        }
    }

    echo "<span class=mb_product_cart><br>" . $product->post_content . "</span>";

    // if (count($mb_expiry_loop)) {
    //     for ($i = 0; $i < count($mb_expiry_loop); $i++) {
    //         // add 12 months to expiry date

    //         //$new_expiry = date('d/m/Y', strtotime("+1 year", strtotime($expiry[$i])));
    //         $new_expiry = date('d/m/Y', $mb_expiry_loop[$i]);
    //         echo "<br><span class='mb_renewal_product'>FYI This order extend your existing `" . $mb_name_loop[$i] . "` plan by 12 months<br>The new expiry date would be " . $new_expiry . "</span><br>";
    //     }
    // }

    // just good manners!
    $mb_expiry_loop=[];
    $mb_name_loop=[];

}

function mb_get_latestDate($name)
{
    global $mb_expiry_loop, $mb_name_loop;
    $cum = 0;
    for ($z = 0; $z < count($mb_name_loop); $z++) {
        if ($name == $mb_name_loop[$z]) {
            if ($mb_expiry_loop[$z] > $cum) {
                $cum = $mb_expiry_loop[$z];
            }
        }
    }
    return $cum;
}

function mb_replace_all_latest_dates($name, $dt)
{
    global $mb_expiry_loop, $mb_name_loop;
    for ($z = 0; $z < count($mb_name_loop); $z++) {
        if ($name == $mb_name_loop[$z]) {
            $mb_expiry_loop[$z] = $dt;
        }}

}

// set up global counter for cart expiry dates of simliar products
add_action('woocommerce_before_cart_contents', 'mb_cart_loop_counter');
function mb_cart_loop_counter()
{
    global $mb_expiry_loop, $mb_name_loop;
    $mb_expiry_loop = [];
    $mb_name_loop = [];
}

// /**
//  * Auto Complete all WooCommerce orders.
//  */
// add_action('woocommerce_thankyou', 'custom_woocommerce_auto_complete_order');
// function custom_woocommerce_auto_complete_order($order_id)
// {
//     if (!$order_id) {
//         return;
//     }

//     $order = wc_get_order($order_id);
//     $order->update_status('completed');
// }

//auto complete order after subscription renewal payment
add_action('woocommerce_subscription_payment_complete', 'subscription_payment_complete_hook_callback', 10, 1);
function subscription_payment_complete_hook_callback( $subscription ) {
    // Get the current order
    $current_order = $subscription->get_last_order( 'all', 'any' );
    $current_order->update_status('completed');
}

// // for testing removal message
// function eg_show_product_removed_message( $url ) {
// 	global $woocommerce, $eg_set_product_removed_message;
// 	if ( isset( $eg_set_product_removed_message ) && is_numeric( $eg_set_product_removed_message ) ) {
// 		wc_add_notice( sprintf( _n( '%s product has been removed from your cart. Products and subscriptions can not be purchased at the same time.', '%s products have been removed from your cart. Products and subscriptions can not be purchased at the same time.', $eg_set_product_removed_message, 'wcsprm' ), $eg_set_product_removed_message ), 'error' );
// 	}
 
// 	return $url;
// }
// add_filter( 'add_to_cart_redirect', 'eg_show_product_removed_message', 11, 1 );
function remove_added_to_cart_notice()
{
    $notices = WC()->session->get('wc_notices', array());

    foreach( $notices['error'] as $key => &$notice){
        if( strpos( $notice, 'removed' ) !== false){
            wc_clear_notices();
            wc_add_notice( 'The subscription has been removed from your cart because you already have an active subscription', 'error' );
        }
    }
}
//add_action('woocommerce_before_single_product','remove_added_to_cart_notice',1);
//add_action('woocommerce_shortcode_before_product_cat_loop','remove_added_to_cart_notice',1);
//add_action('woocommerce_before_shop_loop','remove_added_to_cart_notice',1);
add_action('woocommerce_remove_cart_item_from_session','remove_added_to_cart_notice',1);

function has_active_subscription( $user_id='' ) {
    // When a $user_id is not specified, get the current user Id
    if( '' == $user_id && is_user_logged_in() ) 
        $user_id = get_current_user_id();
    // User not logged in we return false
    if( $user_id == 0 ) 
        return false;

    return wcs_user_has_subscription( $user_id, '', 'active' );
}

// do not let users upgrade or downgrade their subscription whatever the dashboard says!
function remove_upgrade_downgrade_options() {
    return false;
}
add_filter( 'woocommerce_subscriptions_can_item_be_switched_by_user', 'remove_upgrade_downgrade_options');

// do not let users resubscribe once cancelled - they have to wait until available again - sagepay rule!
function remove_resubscribe_option() {
    return false;
}
add_filter( 'wcs_can_user_resubscribe_to_subscription', 'remove_resubscribe_option');


