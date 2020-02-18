<?php
/**
 * My Account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices();

// work out what sprt of navigation to show
global $wp;

if ( ! empty( $wp->query_vars ) ) {
		foreach ( $wp->query_vars as $key => $value ) {
				// Ignore pagename param.
				if ( 'pagename' === $key ) {
						continue;
				}

		}
}

if ($key ==="pagename"){
// $key is the name of the page at this point
//do_action( 'woocommerce_account_navigation' ); 
wc_get_template( 'myaccount/navigation.php' );
} 
else {
	wc_get_template( 'myaccount/navigation_just_dashboard.php' );
	if ($key==="edit-account"){$key="Account details";}
	if ($key==="edit-address"){$key="Address details";}
	if ($key==="view-subscription"){
		$subID = $wp->query_vars['view-subscription'];
		$subscription = new WC_Subscription( $subID );
		$order_id = method_exists( $subscription, 'get_parent_id' ) ? $subscription->get_parent_id() : $subscription->order->id;
		$order = method_exists( $subscription, 'get_parent' ) ? $subscription->get_parent() : $subscription->order;
		$order_link = get_the_permalink($order_id);
		$order_link = "/my-account/view-order/".$order_id."/";
		$key="View Subscription <a href='".$order_link."'> order #".$order_id."</a>";
	}
	echo "<h2>".ucfirst($key)."</h2>"; // show title of this section
}

?>

<div class="woocommerce-MyAccount-content">
	<?php
		/**
		 * My Account content.
		 * @since 2.6.0
		 */
		do_action( 'woocommerce_account_content' );
	?>
</div>
