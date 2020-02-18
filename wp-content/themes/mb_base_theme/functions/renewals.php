<?php
// Changes the renewal link for a particular membership to purchase a new one
// Old membership will be deleted upon purchase if expired
/**
 * Changes the renewal URL for the trial membership
 *
 * @param string $url the renewal URL
 * @param \WC_Memberships_User_Membership $membership the user membership
 * @return string $url the updated renewal URL
 */


function mb_change_renewal_products( $flag, $product,$plan ) {
    
    // if plan = a subset - get all the purchase options 
	// Use the ID of the plan that we should change the "renew" link for
	
	if ( 311 === $membership->plan_id ) {
		// Enter the add to cart URL of the upgrade membership product that should be purchased
		$url = '/checkout/?add-to-cart=99';
	}
	return $url;
}
add_filter( 'wc_memberships_add_to_cart_renewal_product', 'mb_change_renewal_url', 10, 2 );

// add a renew button if expirty is within a month

function addRenewalButton($actions, $subscription) {
	
	// if ($subscription->can_be_renewed() ){
	// 	echo "do something";
	// }
	return $actions;

    }

//add_action( 'woocommerce_my_subscriptions_actions', 'addCancelButton', 10 );
add_filter( 'wcs_view_subscription_actions', 'addRenewalButton', 100, 2 );


 // function sv_change_renewal_url( $url, $membership ) {
    
//     // if plan = a subset - get all the purchase options 



// 	// Use the ID of the plan that we should change the "renew" link for
// 	if ( 311 === $membership->plan_id ) {
// 		// Enter the add to cart URL of the upgrade membership product that should be purchased
// 		$url = '/checkout/?add-to-cart=99';
// 	}
// 	return $url;
// }
// add_filter( 'wc_memberships_get_renew_membership_url', 'sv_change_renewal_url', 10, 2 );
// /**
//  * Changes the renewal button text
//  *
//  * @param array $actions the actions for the membership under My Memberships
//  * @param \WC_Memberships_User_Membership $membership the user membership
//  * @return array $actions the updated actions
//  */
// function sv_change_membership_renewal_text( $actions, $membership ) {
// 	// Use the ID of the trial membership plan to change the button text
// 	if ( 311 === $membership->plan_id && $membership->is_expired() ) {
// 		$actions['renew']['name'] = 'Renew for 1 year';
// 	}
// 	return $actions;
// }
// add_filter( 'wc_memberships_members_area_my-memberships_actions', 'sv_change_membership_renewal_text', 10, 2 );