<?php

// // example add to cart more than once Notice
// add_filter('woocommerce_add_to_cart_validation', 'sd_bought_before_woocommerce_add_to_cart_validation', 20, 2);
// function sd_bought_before_woocommerce_add_to_cart_validation($valid, $product_id)
// {
//     $current_user = wp_get_current_user();
//     if (wc_customer_bought_product($current_user->user_email, $current_user->ID, $product_id)) {
//         wc_add_notice(__('SORRY YOU CANNOT BUY THIS TWICE!!', 'woocommerce'), 'error');
//         $valid = false;
//     }
//     return $valid;
// }

// // or a fuller version from here
// // https://gist.github.com/bekarice/a2da034d37dbc64d41be
// /**
//  * Disables repeat purchase for products / variations
//  *
//  * @param bool $purchasable true if product can be purchased
//  * @param \WC_Product $product the WooCommerce product
//  * @return bool $purchasable the updated is_purchasable check
//  */
// function sv_disable_repeat_purchase($purchasable, $product)
// {
//     // Don't run on parents of variations,
//     // function will already check variations separately
//     if ($product->is_type('variable')) {
//         return $purchasable;
//     }

//     // Get the ID for the current product (passed in)
//     $product_id = $product->is_type('variation') ? $product->variation_id : $product->id;

//     // return false if the customer has bought the product / variation
//     if (wc_customer_bought_product(wp_get_current_user()->user_email, get_current_user_id(), $product_id)) {
//         $purchasable = false;
//     }

//     // Double-check for variations: if parent is not purchasable, then variation is not
//     if ($purchasable && $product->is_type('variation')) {
//         $purchasable = $product->parent->is_purchasable();
//     }

//     return $purchasable;
// }
// add_filter('woocommerce_is_purchasable', 'sv_disable_repeat_purchase', 10, 2);
// /**
//  * Shows a "purchase disabled" message to the customer
//  */
// function sv_purchase_disabled_message()
// {

//     // Get the current product to see if it has been purchased
//     global $product;

//     if ($product->is_type('variable')) {

//         foreach ($product->get_children() as $variation_id) {
//             // Render the purchase restricted message if it has been purchased
//             if (wc_customer_bought_product(wp_get_current_user()->user_email, get_current_user_id(), $variation_id)) {
//                 sv_render_variation_non_purchasable_message($product, $variation_id);
//             }
//         }

//     } else {
//         if (wc_customer_bought_product(wp_get_current_user()->user_email, get_current_user_id(), $product->id)) {
//             echo '<div class="woocommerce"><div class="woocommerce-info wc-nonpurchasable-message">You\'ve already purchased this product! It can only be purchased once.</div></div>';
//         }
//     }
// }
// add_action('woocommerce_single_product_summary', 'sv_purchase_disabled_message', 31);
// /**
//  * Generates a "purchase disabled" message to the customer for specific variations
//  *
//  * @param \WC_Product $product the WooCommerce product
//  * @param int $no_repeats_id the id of the non-purchasable product
//  */
// function sv_render_variation_non_purchasable_message($product, $no_repeats_id)
// {

//     // Double-check we're looking at a variable product
//     if ($product->is_type('variable') && $product->has_child()) {

//         $variation_purchasable = true;

//         foreach ($product->get_available_variations() as $variation) {

//             // only show this message for non-purchasable variations matching our ID
//             if ($no_repeats_id === $variation['variation_id']) {
//                 $variation_purchasable = false;
//                 echo '<div class="woocommerce"><div class="woocommerce-info wc-nonpurchasable-message js-variation-' . sanitize_html_class($variation['variation_id']) . '">You\'ve already purchased this product! It can only be purchased once.</div></div>';
//             }
//         }
//     }

//     if (!$variation_purchasable) {
//         wc_enqueue_js("
//             jQuery('.variations_form')
//                 .on( 'woocommerce_variation_select_change', function( event ) {
//                     jQuery('.wc-nonpurchasable-message').hide();
//                 })
//                 .on( 'found_variation', function( event, variation ) {
//                     jQuery('.wc-nonpurchasable-message').hide();
//                     if ( ! variation.is_purchasable ) {
//                         jQuery( '.wc-nonpurchasable-message.js-variation-' + variation.variation_id ).show();
//                     }
//                 })
//             .find( '.variations select' ).change();
//         ");
//     }
// }

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
                    if (in_array($res_cat->name, $myCategories, true)) {
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
                    if (in_array($res_cat->name, $myCategories, true)) {
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

// add_action( 'woocommerce_after_shop_loop_item_title', 'mb_product_image', 9 );
//  function mb_product_image($what){
// // add the product short description
// global $post;

//     echo the_excerpt();
//  }

// function output_product_excerpt()
// {
//     global $post;
//     echo "<span class=mb_product>" . $post->post_excerpt . "</span>";
// }

// // //Add description to product loop on home shop

// // function output_excerpt_in_products()
// // {
// //     global $product;

// //     if ($product->is_in_stock()) {
// //         echo '<div class="stock" >' . $product->get_stock_quantity() . __(' in stock', 'envy') . '</div>';
// //     } else {
// //         echo '<div class="out-of-stock" >' . __('out of stock', 'envy') . '</div>';
// //     }
// // }
// add_action('woocommerce_after_shop_loop_item_title', 'output_product_excerpt');
// //add_action('woocommerce_after_shop_loop_item_title', 'output_product_excerpt');

// sort product loop descriptions

remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_action('woocommerce_shop_loop_item_title', 'woocommerce_shop_loop_item_title', 10);
function woocommerce_shop_loop_item_title()
{
    echo '<h2 class="woocommerce-loop-product__title">' . get_the_title() . '</h2>';
    echo "<span class=mb_product>" . get_the_excerpt() . "</span>";
}

// // sort single product descriptions
// remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
// //put it backwith description
// add_action('woocommerce_single_product_summary', 'mb_single_title', 5 );
// function mb_single_title(){
//     echo "HELLO";
// }
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

add_action('woocommerce_after_cart_item_name', 'mb_afterCartTitle', 10, 2);

function mb_afterCartTitle($cart_item, $cart_item_key)
{

    $prod_id = $cart_item['product_id'];
    $product = get_post($prod_id);
    //$product_meta = get_post_meta($prod_id);
    $expiry = null;
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
                        $expiry = $mm->get_end_date();
                        break 3; // exit loop
                    }

                }
            }
        }
    }

    if ($expiry) {
        echo "YAY EXPIRY EXTENSION";
    }

    // print excerpt
    echo "<span class=mb_product_cart>" . $product->post_content . "</span>";

    // and notice re extension to license if logged in etc

    if (wc_customer_bought_product(wp_get_current_user()->user_email, get_current_user_id(), $prod_id)) {
        echo "Already yours mate!";
    }


}


/**
 * Auto Complete all WooCommerce orders.
 */
add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) { 
    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );
    $order->update_status( 'completed' );
}


function mb_delay_woocommerce_grant_alterations()
{

    $fakeClass= new WC_Memberships_Membership_Plans;

    remove_action( 'woocommerce_order_status_completed',  array( $fakeClass, 'grant_access_to_membership_from_order' ), 11 );
    remove_action( 'woocommerce_order_status_processing', array( $fakeClass, 'grant_access_to_membership_from_order' ), 11 );

    add_action( 'woocommerce_order_status_completed',  'mb_grant_access_to_membership_from_order' , 11 );
    add_action( 'woocommerce_order_status_processing',  'mb_grant_access_to_membership_from_order' , 11 );

}
add_action('template_redirect','mb_delay_woocommerce_grant_alterations',2);


function mb_grant_access_to_membership_from_order( $order ) {

// right lets process this incoming order against what we know!

    $order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;

    if ( ! $order instanceof WC_Order ) {
        return;
    }

    $order_items      = $order->get_items();
    $user_id          = $order->get_user_id();
    //$membership_plans = $this->get_membership_plans();
    $membership_plans = wc_memberships_get_membership_plans();
    

    // skip if guest user, no order items or no membership plans to begin with
    if ( ! $user_id || empty( $order_items ) || empty( $membership_plans ) ) {
        return;
    }

    // loop over all available membership plans
    foreach ( $membership_plans as $plan ) {

        // skip if no products grant access to this plan
        if ( ! $plan->has_products() ) {
            continue;
        }

        $access_granting_product_ids = wc_memberships_get_order_access_granting_product_ids( $plan, $order, $order_items );

        if ( ! empty( $access_granting_product_ids ) ) {

            // We check if the order has granted access already before looping products,
            // so we can allow the purchase of multiple access granting products to extend the duration of a plan,
            // should multiple products grant access to the same plan having a specific end date (relative to now).
            /** @see wc_memberships_cumulative_granting_access_orders_allowed() */
            /** @var \WC_Memberships_Membership_Plan::grant_access_from_purchase() */
            $order_granted_access_already = wc_memberships_has_order_granted_access( $order, array( 'membership_plan' => $plan ) );

            foreach ( $access_granting_product_ids as $product_id ) {

                // sanity check: make sure the selected product ID in fact does grant access
                if ( ! $plan->has_product( $product_id ) ) {
                    continue;
                }

                /**
                 * Confirm grant access from new purchase to paid plan.
                 *
                 * @since 1.3.5
                 *
                 * @param bool $grant_access by default true unless the order already granted access to the plan
                 * @param array $args {
                 *      @type int $user_id customer id for purchase order
                 *      @type int $product_id ID of product that grants access
                 *      @type int $order_id order ID containing the product
                 * }
                 */
                // $grant_access = (bool) apply_filters( 'wc_memberships_grant_access_from_new_purchase', ! $order_granted_access_already, array(
                //     'user_id'    => (int) $user_id,
                //     'product_id' => (int) $product_id,
                //     'order_id'   => (int) SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
                // ) );
                $grant_access = (bool) mb_copy_grant_access_filter_As_function(
                    (int) $user_id,
                     (int) $product_id,
                     (int) SV_WC_Order_Compatibility::get_prop( $order, 'id'));

                


                if ( $grant_access ) {
                    // delegate granting access to the membership plan instance
                    $plan->grant_access_from_purchase( $user_id, $product_id, (int) SV_WC_Order_Compatibility::get_prop( $order, 'id' ) );
                }
            }
        }
    }
}

function mb_copy_grant_access_filter_As_function($u,$p,$o){

    $args['order_id'] = $o;
    $args['product_id'] = $p;
    $args['user_id'] = $u;

    if ( isset( $args['order_id'] ) && is_numeric( $args['order_id'] ) && wcs_order_contains_renewal( $args['order_id'] ) ) {

        // subscription renewals cannot grant access
        $grant_access = false;

    } elseif ( isset( $args['order_id'], $args['product_id'], $args['user_id'] ) ) {

        // reactivate a cancelled/pending cancel User Membership,
        // when re-purchasing the same Subscription that grants access

        $product = wc_get_product( $args['product_id'] );

        if ( $product && WC_Subscriptions_Product::is_subscription( $product ) ) {

            $user_id = (int) $args['user_id'];
            $order   = wc_get_order( (int) $args['order_id'] );
            $plans   = wc_memberships()->get_plans_instance()->get_membership_plans();

            // loop over all available membership plans
            foreach ( $plans as $plan ) {

                // skip if no products grant access to this plan
                if ( ! $plan->has_products() ) {
                    continue;
                }

                $access_granting_product_ids = wc_memberships_get_order_access_granting_product_ids( $plan, $order );

                foreach ( $access_granting_product_ids as $access_granting_product_id ) {

                    // sanity check: make sure the selected product ID in fact does grant access
                    if ( ! $plan->has_product( $access_granting_product_id ) ) {
                        continue;
                    }

                    if ( (int) $product->get_id() === (int) $access_granting_product_id ) {

                        $user_membership = wc_memberships_get_user_membership( $user_id, $plan );

                        // check if the user purchasing is already member of a plan
                        // but the membership is cancelled or pending cancellation
                        if ( wc_memberships_is_user_member( $user_id, $plan ) && $user_membership->has_status( array( 'pending', 'cancelled' ) ) ) {

                            $order_id                = SV_WC_Order_Compatibility::get_prop( $order, 'id' );
                            $subscription_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

                            /* translators: Placeholders: %1$s is the subscription product name, %2%s is the order number */
                            $note = sprintf( __( 'Membership re-activated due to subscription re-purchase (%1$s, Order %2$s).', 'woocommerce-memberships' ),
                                $product->get_title(),
                                '<a href="' . admin_url( 'post.php?post=' . $order_id  . '&action=edit' ) .'" >' . $order_id. '</a>'
                            );

                            $subscription_membership->activate_membership( $note );

                            $subscription = wc_memberships_get_order_subscription( SV_WC_Order_Compatibility::get_prop( $order, 'id' ), $product->get_id() );
                            $subscription_membership->set_subscription_id( SV_WC_Order_Compatibility::get_prop( $subscription, 'id' ) );
                        }
                    }
                }
            }
        }
    }

    return $grant_access;


}



// amended version of save subscription details - after purchase and adding new membership

remove_action( 'wc_memberships_grant_membership_access_from_purchase','save_subscription_data', 10, 2 );
add_action( 'wc_memberships_grant_membership_access_from_purchase', 'mb_save_subscription_data' , 10, 2 );

function mb_save_subscription_data( WC_Memberships_Membership_Plan $plan, $args ) {

    $product     = wc_get_product( $args['product_id'] );
    $integration = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();

    // handle access from subscriptions
    if (    $product
         && $integration
         && WC_Subscriptions_Product::is_subscription( $product )
         && $integration->has_membership_plan_subscription( $plan->get_id() ) ) {

        $subscription = wc_memberships_get_order_subscription( $args['order_id'], $product->get_id() );

        if ( $subscription ) {

            $subscription_membership = new WC_Memberships_Integration_Subscriptions_User_Membership( $args['user_membership_id'] );

            $subscription_membership->set_subscription_id( SV_WC_Order_Compatibility::get_prop( $subscription, 'id' ) );

            $subscription_plan  = new WC_Memberships_Integration_Subscriptions_Membership_Plan( $subscription_membership->get_plan_id() );
            $access_length_type = $subscription_plan->get_access_length_type();

            if ( 'subscription' === $access_length_type && $this->grant_access_while_subscription_active( $plan ) ) {
                $membership_end_date = $integration->get_subscription_event_date( $subscription, 'end' );
            } else {
                $membership_end_date = $subscription_plan->get_expiration_date( current_time( 'mysql', true ), $args );
            }

            // maybe update the trial end date
            if ( $trial_end_date = $integration->get_subscription_event_date( $subscription, 'trial_end' ) ) {
                $subscription_membership->set_free_trial_end_date( $trial_end_date );
            }

            $subscription_membership->set_end_date( $membership_end_date );
        }
    }
}

// add_filter('wc_memberships_user_membership','mb_filter_membership');
// function mb_filter_membership($user_membership){

//     if ($user_membership->plan->id==1124) {
//     $expiry_date = $user_membership->plan->get_access_end_date();
//     }
//     return $user_membership;
// }

