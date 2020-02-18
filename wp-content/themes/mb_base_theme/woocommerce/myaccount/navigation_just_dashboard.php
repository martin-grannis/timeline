<?php
/**
 * My Account navigation
 * MB CUSTOM dashboard list
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation">
	<ul>
		<?php 
		$ignore_array= ['Downloads', 'Teams', 'Orders', 'Subscriptions', 'Addresses', 'Logout', 'Account details','Payment methods','My Membership'];
		//$ignore_array= ['Downloads', 'Orders', 'Subscriptions', 'Addresses', 'Logout', 'Account details','My Membership'];
		
		foreach ( wc_get_account_menu_items() as $endpoint => $label):
			// mb remove downloads option
			if (!in_array($label,$ignore_array)){ 
				
				if ($label=="Account details"){ 
                    $label="Name, Email, Password";
                  }
                
                  if ($label=="Dashboard"){ 
                    $label="Back to Dashboard";
                  }

				?>

			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>"><?php echo esc_html( $label ); ?></a>
			</li>
			<?php }
		endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
