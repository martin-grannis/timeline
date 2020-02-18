<?php

    /**
     * WC_Gateway_Sagepay_Pi class.
     *
     * @extends WC_Payment_Gateway
     */
    class WC_Gateway_Sagepay_Pi extends WC_Payment_Gateway {
		/**
    	 * [$sage_cardtypes description]
    	 * Set up accepted card types for card type drop down
    	 * From Version 3.3.0
    	 * @var array
    	 */
        var $sage_cardtypes = array(
        			'MasterCard'		=> 'MasterCard',
					'MasterCard Debit'	=> 'MasterCard Debit',
					'Visa'				=> 'Visa',
					'Visa Debit'		=> 'Visa Debit',
					'Discover'			=> 'Discover',
					'American Express' 	=> 'American Express',
					'Maestro'			=> 'Maestro',
					'JCB'				=> 'JCB',
					'Laser'				=> 'Laser',
					'PayPal'			=> 'PayPal'
				);
        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

            $this->id                   = 'sagepaypi';
            $this->method_title         = __( 'SagePay Pi', 'woocommerce_sagepayform' );
            $this->method_description   = $this->sagepay_system_status();
            $this->icon                 = apply_filters( 'wc_sagepayform_icon', '' );
            $this->has_fields           = false;
            $this->liveurl              = 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
            $this->testurl              = 'https://test.sagepay.com/gateway/service/vspform-register.vsp';

            $this->successurl 			= WC()->api_request_url( get_class( $this ) );

            // Default values
			$this->default_enabled				= 'no';
			$this->default_title 				= __( 'Credit Card via SagePay', 'woocommerce_sagepayform' );
			$this->default_description  		= __( 'Pay via Credit / Debit Card with SagePay secure card processing.', 'woocommerce_sagepayform' );
			$this->default_order_button_text  	= __( 'Pay securely with SagePay', 'woocommerce_sagepayform' );
  			$this->default_status				= 'testing';
  			$this->default_cardtypes			= '';
  			$this->default_protocol				= '3.00';
  			$this->default_vendor				= '';
			$this->default_vendorpwd			= '';
			$this->default_testvendorpwd		= '';
			$this->default_simvendorpwd 		= '';
			$this->default_email				= get_bloginfo('admin_email');
			$this->default_sendemail			= '1';
			$this->default_txtype				= 'PAYMENT';
			$this->default_allow_gift_aid		= 'yes';
			$this->default_apply_avs_cv2		= '0';
			$this->default_apply_3dsecure		= '0';
			$this->default_debug 				= false;
			$this->default_sagelink				= 0;
			$this->default_sagelogo				= 0;
            $this->default_vendortxcodeprefix   = 'wc_';

            // ReferrerID
            $this->referrerid 			= 'F4D0E135-F056-449E-99E0-EC59917923E1';

            // Load the form fields
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Get setting values
            $this->enabled         		= isset( $this->settings['enabled'] ) && $this->settings['enabled'] == 'yes' ? 'yes' : $this->default_enabled;
            
            $this->title 				= isset( $this->settings['title'] ) ? $this->settings['title'] : $this->default_title;
			$this->description  		= isset( $this->settings['description'] ) ? $this->settings['description'] : $this->default_description;
			$this->order_button_text  	= isset( $this->settings['order_button_text'] ) ? $this->settings['order_button_text'] : $this->default_order_button_text;
  			$this->status				= isset( $this->settings['status'] ) ? $this->settings['status'] : $this->default_status;
            $this->cardtypes			= isset( $this->settings['cardtypes'] ) ? $this->settings['cardtypes'] : $this->default_cardtypes;
            $this->protocol 			= $this->default_protocol;
            $this->vendor           	= isset( $this->settings['vendor'] ) ? $this->settings['vendor'] : $this->default_vendor;
            $this->vendorpwd        	= isset( $this->settings['vendorpwd'] ) ? $this->settings['vendorpwd'] : $this->default_vendorpwd;
            $this->testvendorpwd    	= isset( $this->settings['testvendorpwd'] ) ? $this->settings['testvendorpwd'] : $this->default_testvendorpwd;
            $this->email            	= isset( $this->settings['email'] ) ? $this->settings['email'] : $this->default_email;
            $this->sendemail        	= isset( $this->settings['sendemail'] ) ? $this->settings['sendemail'] : $this->default_sendemail;
            $this->txtype           	= isset( $this->settings['txtype'] ) ? $this->settings['txtype'] : $this->default_txtype;
            $this->allow_gift_aid   	= isset( $this->settings['allow_gift_aid'] ) && $this->settings['allow_gift_aid'] == 'yes' ? 1 : 0;
            $this->apply_avs_cv2    	= isset( $this->settings['apply_avs_cv2'] ) ? $this->settings['apply_avs_cv2'] : $this->default_apply_avs_cv2;
            $this->apply_3dsecure   	= isset( $this->settings['apply_3dsecure'] ) ? $this->settings['apply_3dsecure'] : $this->default_apply_3dsecure;
            $this->debug				= isset( $this->settings['debugmode'] ) && $this->settings['debugmode'] == 'yes' ? true : $this->default_debug;
            $this->sagelink				= isset( $this->settings['sagelink'] ) && $this->settings['sagelink'] == 'yes' ? '1' : $this->default_sagelink;
            $this->sagelogo				= isset( $this->settings['sagelogo'] ) && $this->settings['sagelogo'] == 'yes' ? '1' : $this->default_sagelogo;
            $this->vendortxcodeprefix   = isset( $this->settings['vendortxcodeprefix'] ) ? $this->settings['vendortxcodeprefix'] : $this->default_vendortxcodeprefix;

			$this->link 				= 'http://www.sagepay.co.uk/support/online-shoppers/about-sage-pay';

            $this->basketoption         = isset( $this->settings['basketoption'] ) ? $this->settings['basketoption'] : "1";

            // Make sure $this->vendortxcodeprefix is clean
            $this->vendortxcodeprefix = str_replace( '-', '_', $this->vendortxcodeprefix );
			
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

			// Check this is enabled 
			if( $this->enabled == 'yes' ) {
				/**
				 *  API
				 *  woocommerce_api_{lower case class name}
				 */
	            add_action( 'woocommerce_api_wc_gateway_sagepay_pi', array( $this, 'check_response' ) );

	            add_action( 'valid_sagepaypi_request', array( $this, 'successful_request' ) );
	            add_action( 'woocommerce_receipt_sagepaypi', array( $this, 'receipt_page' ) );

	        }

            // Supports
            $this->supports = array(
            						'products',
							);

            // Logs
			if ( $this->debug ) {
				$this->log = new WC_Logger();
			}

			// WC version
			$this->wc_version = get_option( 'woocommerce_version' );

            // Add test card info to the description if in test mode
            if ( $this->status != 'live' ) {
                $this->description .= ' ' . sprintf( __( '<br /><br />TEST MODE ENABLED.<br />In test mode, you can use Visa card number 4929000000006 with any CVC and a valid expiration date or check the documentation (<a href="%s">Test card details for your test transactions</a>) for more card numbers.<br /><br />3D Secure password is "password"', 'woocommerce_sagepayform' ), 'http://www.sagepay.co.uk/support/12/36/test-card-details-for-your-test-transactions' );
                $this->description  = trim( $this->description );
            }

            // Set URLs for loading script files from Sage.
            $this->checkout_test_script_url	= 'https://pi-test.sagepay.com/api/v1/js/sagepay.js';
            $this->checkout_live_script_url	= 'https://pi-test.sagepay.com/api/v1/js/sagepay.js';

            // Set checkout script.
            $this->checkout_script_url = $this->status != 'live' ? $this->checkout_test_script_url : $this->checkout_live_script_url;

            // Add scripts and files for admin.
            add_action( 'admin_init', array( $this, 'admin_init' ) );

        } // END __construct

        /**
         * init_form_fields function.
         *
         * @access public
         * @return void
         */
        function init_form_fields() {
        	include ( SAGEPLUGINPATH . 'assets/php/sagepay-pi-admin.php' );
        }

		public function admin_init() {
			// Plugin Links
            // add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this,'plugin_links' ) );

            // Enqueue Admin Scripts and CSS
            // add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 99 );

            // Add 'Capture Authorised Payment' to WooCommerce Order Actions
        	// add_filter( 'woocommerce_order_actions', array( $this, 'worldpay_online_woocommerce_order_actions' ) );

        	// Set wc-authorized in WooCommerce order statuses.
        	// add_filter( 'wc_order_statuses', array( $this, 'worldpay_online_order_statuses' ) );
		}

		/**
		 * [get_icon description] Add selected card icons to payment method label, defaults to Visa/MC/Amex/Discover
		 * @return [type] [description]
		 */
		public function get_icon() {
			return WC_Sagepay_Common_Functions::get_icon( $this->cardtypes, $this->sagelink, $this->sagelogo, $this->id );
		}

		/**
    	 * Payment form on checkout page
    	 */
		public function payment_fields() {

			echo '<div id="sagepaypi-payment-data">';

			echo '<script src="https://pi-test.sagepay.com/api/v1/js/sagepay.js"></script>';

			// $this->checkout_script();

			if ( $this->description ) {
				echo apply_filters( 'wc_sagepaypi_description', wp_kses_post( $this->description ) );
			}

			echo '<div id="sp-container"></div>';

			echo '<script>
  					sagepayCheckout({ merchantSessionKey: "F42164DA-4A10-4060-AD04-F6101821EFC3" }).form();
				  </script>';

			echo '</div>';

		}

        /**
         * process_payment function.
         *
         * @access public
         * @param mixed $order_id
         * @return void
         */
        function process_payment( $order_id ) {
            $order = new WC_Order( $order_id );

            return array(
                'result'    => 'success',
            	'redirect'	=> $order->get_checkout_payment_url( true )
            );
            
        }

        /**
         * check_sagepay_response function.
         *
         * @access public
         * @return void
         */
        function check_response() {


        }

        /**
         * successful_request function.
         *
         * @access public
         * @param mixed $sagepay_return_values
         * @return void
         */
        function successful_request( $sagepay_return_values ) {


        }

		/**
		 * Returns the plugin's url without a trailing slash
		 */
		public function get_plugin_url() {
			return str_replace( '/classes/pi', '/', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
		}

        /**
         * [sagepay_system_status description]
         * @return [type] [description]
         */
        function sagepay_system_status() {
        	$description = __( 'SagePay Form works by sending the user to <a href="http://www.sagepay.com">SagePay</a> to enter their payment information.', 'woocommerce_sagepayform' );
			return $description;
		}

		/**
		 * Enqueues our SagePay Pi checkout script.
		 * @since 2.6.0
		 */
		public function checkout_script() {
			wp_enqueue_script(
				'sagepay-pi-checkout',
				$this->checkout_script_url,
				array( 'jquery' ),
				WC()->version
			);
		}

	} // END CLASS
