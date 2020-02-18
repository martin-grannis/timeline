<?php

require_once('importUsers.php');

//sometimes woocommerce functions can be slow esp the rest ones
set_time_limit(300);

if (is_admin()) {
    
    //require_once __DIR__ . '/functions/import_users.php';
    add_action('admin_menu', 'import_silverstripe', 20);
    add_action( 'init', 'process_timeline_csv', 20 ) ;

    function import_silverstripe()
    {
        add_management_page('Import Old Users', 'Import Timeline users', is_super_admin(), 'impoldss' . '-tool', 'show_upload_form_timeline');
    }
}


function show_upload_form_timeline(){
   
    ?>

<div class="wrap">
    <h2>
        <?php echo 'Import Timeline users from a CSV file'; ?>
    </h2>
    <?php
    
	if ( isset( $_GET['import'] ) ) {
		$error_log_msg = '';
		if ( file_exists( $error_log_file ) )
			$error_log_msg = sprintf( __( ', please <a href="%s">check the error log</a>' , 'import-users-from-csv'), $error_log_url );

		switch ( $_GET['import'] ) {
			case 'file':
				echo '<div class="error"><p><strong>' . __( 'Error during file upload.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'data':
				echo '<div class="error"><p><strong>' . __( 'Cannot extract data from uploaded file or no file was uploaded.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			case 'fail':
				echo '<div class="error"><p><strong>' . sprintf( __( 'No user was successfully imported%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'errors':
				echo '<div class="error"><p><strong>' . sprintf( __( 'Some users were successfully imported but some were not%s.' , 'import-users-from-csv'), $error_log_msg ) . '</strong></p></div>';
				break;
			case 'success':
				echo '<div class="updated"><p><strong>' . __( 'Users import was successful.' , 'import-users-from-csv') . '</strong></p></div>';
				break;
			default:
				break;
		}
	}
	?>
    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field( 'is-timeline-import-nonceField', '_wpnonce-is-timeline-import-page' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="users_csv">
                        <?php _e( 'CSV file' , 'import-users-from-csv'); ?></label></th>
                <td>
                    <input type="file" id="users_csv" name="users_csv" value="" class="all-options" /><br />
                    <span class="description">
                        <?php echo sprintf( __( 'You may want to see <a href="%s">the example of the CSV file</a>.' , 'import-users-from-csv'), plugin_dir_url(__FILE__).'examples/import.csv'); ?></span>
                </td>
            </tr>
            <!-- <tr valign="top">
				<th scope="row"><?php _e( 'Notification' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Notification' , 'import-users-from-csv'); ?></span></legend>
					<label for="new_user_notification">
						<input id="new_user_notification" name="new_user_notification" type="checkbox" value="1" />
						<?php _e('Send to new users', 'import-users-from-csv') ?>
					</label>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Password nag' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Password nag' , 'import-users-from-csv'); ?></span></legend>
					<label for="password_nag">
						<input id="password_nag" name="password_nag" type="checkbox" value="1" />
						<?php _e('Show password nag on new users signon', 'import-users-from-csv') ?>
					</label>
				</fieldset></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Users update' , 'import-users-from-csv'); ?></th>
				<td><fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'Users update' , 'import-users-from-csv' ); ?></span></legend>
					<label for="users_update">
						<input id="users_update" name="users_update" type="checkbox" value="1" />
						<?php _e( 'Update user when a username or email exists', 'import-users-from-csv' ) ;?>
					</label>
				</fieldset></td>
			</tr> -->
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Import' , 'import-users-from-csv'); ?>" />
        </p>
    </form>
    <?php
	}


function process_timeline_csv(){

    if ( isset( $_POST['_wpnonce-is-timeline-import-page'] ) ) {
        check_admin_referer( 'is-timeline-import-nonceField', '_wpnonce-is-timeline-import-page' );

        if ( !empty( $_FILES['users_csv']['tmp_name'] ) ) {
            // Setup settings variables
            $filename              = $_FILES['users_csv']['tmp_name'];
            // $password_nag          = isset( $_POST['password_nag'] ) ? $_POST['password_nag'] : false;
            // $users_update          = isset( $_POST['users_update'] ) ? $_POST['users_update'] : false;
            // $new_user_notification = isset( $_POST['new_user_notification'] ) ? $_POST['new_user_notification'] : false;

            // $results = self::import_csv( $filename, array(
                // 'password_nag' => $password_nag,
                // 'new_user_notification' => $new_user_notification,
                // 'users_update' => $users_update
            // ) );
            $results = import_csv( $filename, []);

            // No users imported?
            if ( ! $results['user_ids'] )
                wp_redirect( add_query_arg( 'import', 'fail', wp_get_referer() ) );

            // Some users imported?
            elseif ( $results['errors'] )
                wp_redirect( add_query_arg( 'import', 'errors', wp_get_referer() ) );

            // All users imported? :D
            else
                wp_redirect( add_query_arg( 'import', 'success', wp_get_referer() ) );

            exit;
        }

        wp_redirect( add_query_arg( 'import', 'file', wp_get_referer() ) );
        exit;
    }
}
       
function import_csv( $filename, $args ) {

// disable woocommerce emailing thank you emails 
    add_action( 'woocommerce_email', 'unhook_those_pesky_emails' );

    function unhook_those_pesky_emails( $email_class ) {
   
        remove_action( 'woocommerce_order_status_completed_notification', array( $email_class->emails['WC_Email_Customer_Completed_Order'], 'trigger' ) ); // cancels automatic email of order complete status update.
// am presuming - hoping this is overriden by an add action when run elsewhere!       
    }


    $errors = $user_ids = array();
   

    $defaults = array(
        'password_nag' => false,
        'new_user_notification' => false,
        'users_update' => false
    );
    extract( wp_parse_args( $args, $defaults ) );

    // User data fields list used to differentiate with user meta
    $userdata_fields       = array(
        'ID', 'user_login', 'user_pass',
        'user_email', 'user_url', 'user_nicename',
        'display_name', 'user_registered', 'first_name',
        'last_name', 'nickname', 'description',
        'rich_editing', 'comment_shortcuts', 'admin_color',
        'use_ssl', 'show_admin_bar_front', 'show_admin_bar_admin',
        'role'
    );


    
    require_once get_stylesheet_directory() . '/readCSV.php';

    
    // Loop through the file lines
    $file_handle = @fopen( $filename, 'r' );
    if($file_handle) {
        $csv_reader = new ReadCSV( $file_handle, ",", "\xEF\xBB\xBF" ); // Skip any UTF-8 byte order mark.
        $first = true;
        $rkey = 0;
        while ( ( $line = $csv_reader->get_row() ) !== NULL ) {

            // If the first line is empty, abort
            // If another line is empty, just skip it
            if ( empty( $line ) ) {
                if ( $first )
                    break;
                else
                    continue;
            }

            // If we are on the first line, the columns are the headers
            if ( $first ) {
                $headers = $line;
                $first = false;
                continue;
            }

            // Separate user data from meta
            $userdata = $usermeta = array();
            foreach ( $line as $ckey => $column ) {
                $column_name = $headers[$ckey];
                $column = trim( $column );

                if ( in_array( $column_name, $userdata_fields ) ) {
                    $userdata[$column_name] = $column;
                } else {
                    $usermeta[$column_name] = $column;
                }
            }

            // A plugin may need to filter the data and meta
            $userdata = apply_filters( 'is_iu_import_userdata', $userdata, $usermeta );
            $usermeta = apply_filters( 'is_iu_import_usermeta', $usermeta, $userdata );

         //   // If no user data, bailout!
         //   if ( empty( $userdata ) )
         //       continue;

            // try to get user in WP user
            $user = get_user_by( 'email', $usermeta['Email'] );
            if ( $user ) { // if user exists, bailout
                continue;
            }


            if (!isset($usermeta['Surname']) || $usermeta['Surname'] == 'null'){
                $usermeta['Surname']="";
            }

            if (!isset($usermeta['FirstName']) || $usermeta['firstName'] == 'null'){
                $usermeta['FirstName']="";
            }

            $userdata['user_login'] =  $userdata['user_email'] = $usermeta['Email'];
            $userdata['user_pass'] = $usermeta['Password'];
            $userdata['display_name'] = $usermeta['FirstName'].' '.$usermeta['Surname'];
            $userdata['first_name'] = $usermeta['FirstName'];
            $userdata['last_name'] = $usermeta['Surname'];

            $userdata['user_nicename'] = strtolower($usermeta['FirstName'].'-'.$usermeta['Surname']);
            $userdata['user_status'] = 0;

            $user_id = wp_insert_user( $userdata );
            $userdata['ID'] = $user_id;
            // go again - this updates the password to use the hash rather than plain text
            $user_id = wp_insert_user( $userdata );

            // Is there an error o_O?
            if ( is_wp_error( $user_id ) ) {
                $errors[$rkey] = $user_id;
            } 
            else {


            // setting up billing name
            update_user_meta( $user_id, 'billing_first_name', $usermeta['FirstName'] );
            update_user_meta( $user_id, 'billing_last_name', $usermeta['Surname'] );
            update_user_meta( $user_id, 'billing_address_1', $usermeta['Address'] );
            update_user_meta( $user_id, 'billing_address_2', $usermeta['Address2'] );
            update_user_meta( $user_id, 'billing_city', $usermeta['City'] );
            update_user_meta( $user_id, 'billing_state', $usermeta['County'] );
            update_user_meta( $user_id, 'billing_country', $usermeta['Country'] );
            update_user_meta( $user_id, 'billing_postcode', $usermeta['Postcode'] );
            update_user_meta( $user_id, 'billing_email', $usermeta['Email'] );

                // purchase the full product - dated today
                // create a new order
                global $woocommerce;

                $address = array(
                    'first_name' => $usermeta['FirstName'],
                    'last_name'  => $usermeta['Surname'],
                    'company'    => 'Auto Created for Customer',
                    'email'      => $usermeta['Email'],
                );
              
                // Now we create the order
                $order = wc_create_order();

                // The add_product() function below is located in /plugins/woocommerce/includes/abstracts/abstract_wc_order.php
                $product_page = get_page_by_path( 'annual-subscription-for-individuals', OBJECT, 'product' );
                $product_page = get_page_by_path( 'full-prod-sub', OBJECT, 'product' );
                
                $prod_obj = wc_get_product($product_page->ID);
                $period = WC_Subscriptions_Product::get_period( $prod_obj );
                $interval = WC_Subscriptions_Product::get_interval( $prod_obj );


                $order->add_product( $prod_obj, 1); // This is an existing SIMPLE product
                $order->set_address( $address, 'billing' );
                $order->set_customer_id($user_id);

                //
                // $payment_gateways = WC()->payment_gateways->payment_gateways();
                // $order->set_payment_method( $payment_gateways['bacs'] );
                //
                $order->calculate_totals();
                // $order->payment_complete();
                $order->add_order_note("REFUND INFO: this was NOT a payed transaction, but transfer of credit from old system");
                $order->update_status("completed", 'Imported order', TRUE);  
                $order->save();
                $orderID=$order->get_id();
                
                // do date stuff
                date_default_timezone_set('Europe/London');

                $expiryDate = $usermeta['ExpiryDate'];
                $startDate = $usermeta['StartDate'];
                
                $e1t=str_replace("/","-",$expiryDate);
                $e_unix=strtotime($e1t);
                $new_end_date = date('Y-m-d H:i:s',$e_unix);

                $e1t=str_replace("/","-",$startDate);
                $e_unix=strtotime($e1t);
                $new_start_date = date('Y-m-d H:i:s',$e_unix);

                // create a subscription for this order
                $sub = wcs_create_subscription(
                    array('order_id' => $orderID, 
                    'billing_period' => $period, 
                    'billing_interval' => $interval, 
                    'start_date' => $new_start_date,
                    'end'=>$new_end_date,
                    'customer_note' => "Created by import from old Timeline")
                );

                $sub->update_manual(true); // sets renewal to be manual
                $dates_to_update=array();
                $dates_to_update['end'] = $new_end_date;
                $sub->update_dates($dates_to_update);
                $sub->add_product( $prod_obj, 1);
                
                 $memberships = wc_memberships_get_user_active_memberships( $user_id );
                 $user_membership = $memberships[0]->post ;

                 $subID=$sub->get_id();
                 $subscription_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership );
                 $subscription_membership->set_subscription_id( $subID);
 

                // $user_membership->set_end_date( $user_membership->get_end_date() );
            
                WC_Subscriptions_Manager::activate_subscriptions_for_order($order);

// email the user a one off to advise of the transfer to new system
if (true){
//                $to = "martin.grannis@gmail.com";
                $to = $address['email'];
                $subject = "St John's Timeline subscription update";
                $body = file_get_contents(get_stylesheet_directory() . '/woocommerce/emails/transferred_timeline.php');
                // replace the subscription end date field
                $body = str_ireplace('[subend]',$new_end_date, $body); 
                $headersEmail = array('Content-Type: text/html; charset=UTF-8');
                $mail = wp_mail($to,$subject,$body,$headersEmail);
            }

           }
            $user_ids[] = $user_id;
            $rkey++;
        }

        fclose( $file_handle );
    } 
    else {
        $errors[] = new WP_Error('file_read', 'Unable to open CSV file.');
    }

    // One more thing to do after all imports?
    do_action( 'is_iu_post_users_import', $user_ids, $errors );

    // Let's log the errors
    log_errors( $errors );

    return array(
        'user_ids' => $user_ids,
        'errors'   => $errors
    );
}

function log_errors( $errors ) {
    $log_dir_path = get_template_directory()."/timeline-import-logs";
    if ( ! file_exists( $log_dir_path ) ) {
        mkdir( $log_dir_path);
    }
    //$error_log_file = $log_dir_path . '/import_timeline_users1.log';
    //$error_log_url  = $log_dir_path . '/import_timeline_users2.log';
    
    if ( empty( $errors ) )
        return;

    $log = @fopen( $log_dir_path . '/import_timeline_users1.log', 'a' );
    @fwrite( $log, sprintf( __( 'BEGIN %s' , 'import-users-from-timeline'), date( 'Y-m-d H:i:s', time() ) ) . "\n" );

    foreach ( $errors as $key => $error ) {
        $line = $key + 1;
        $message = $error->get_error_message();
        @fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'import-users-from-timeline'), $line, $message ) . "\n" );
    }

    @fclose( $log );
}

