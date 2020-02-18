<?php
			$this->form_fields = array(
				'enabled'           => array(
				    'title'         => __( 'Enable/Disable', 'woocommerce_sagepayform' ),
				    'label'         => __( 'Enable SagePay Pi for WooCommerce', 'woocommerce_sagepayform' ),
				    'type'          => 'checkbox',
				    'description'   => '',
				    'default'       => 'no'
				),
				'title'             => array(
				    'title'         => __( 'Title', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( 'This controls the title which the user sees during checkout.', 'woocommerce_sagepayform' ),
				    'default'       => __( 'Credit Card via Sage', 'woocommerce_sagepayform' )
				),
				'description'       => array(
				    'title'         => __( 'Description', 'woocommerce_sagepayform' ),
				    'type'          => 'textarea',
				    'description'   => __( 'This controls the description which the user sees during checkout.', 'woocommerce_sagepayform' ),
				    'default'       => 'Pay via Credit / Debit Card with Sage secure card processing.'
				),
				'vendor'      		=> array(
				    'title'         => __( 'SagePay Vendor Name', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( 'Used to authenticate your site. This should contain the Sage Pay Vendor Name supplied by Sage Pay when your account was created.', 'woocommerce_sagepayform' ),
				    'default'       => ''
				),
				'status'            => array(
				    'title'         => __( 'Status', 'woocommerce_sagepayform' ),
				    'type'          => 'select',
				    'options'       => array('live'=>'Live','testing'=>'Testing'),
				    'description'   => __( 'Set SagePay Direct Live/Testing Status.', 'woocommerce_sagepayform' ),
				    'default'       => 'testing'
				),
				'txtype'            => array(
				    'title'         => __( 'Status', 'woocommerce_sagepayform' ),
				    'type'          => 'select',
				    'options'       => array('PAYMENT'=>'Take Payment Immediately','DEFERRED'=>'Deferred Payment','AUTHENTICATE'=>'Authenticate Only'),
				    'description'   => __( 'Normally this should be set to "Take Payment Immediately"', 'woocommerce_sagepayform' ),
				    'default'       => 'PAYMENT'
				),
				'cardtypes'			=> array(
					'title' 		=> __( 'Accepted Cards', 'woocommerce_sagepayform' ), 
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;', 
										'description' 	=> __( 'Select which card types to accept.If you choose to include PayPal then make sure your PayPal account is setup correctly. See <a href="https://docs.woocommerce.com/document/sagepay-form/#section-5" target="_blank">https://docs.woocommerce.com/document/sagepay-form/#section-5</a> for more information.', 'woocommerce_sagepayform' ), 
					'default' 		=> '',
					'options' 		=> $this->sage_cardtypes,
				),		
				'cvv' 				=> array(
					'title' 		=> __( 'CVV', 'woocommerce_sagepayform' ), 
					'label' 		=> __( 'Require customer to enter credit card CVV code', 'woocommerce_sagepayform' ), 
					'type' 			=> 'checkbox', 
					'description' 	=> __( '', 'woocommerce_sagepayform' ), 
					'default' 		=> 'no'
				),
				'cvv_script' 		=> array(
					'title' 		=> __( 'Require CVV for Token Payments', 'woocommerce_sagepayform' ), 
					'label' 		=> __( 'Require customer to enter credit card CVV code when paying with a token (does not affect subscription renewals or authorization payments)', 'woocommerce_sagepayform' ), 
					'type' 			=> 'checkbox', 
					'description' 	=> __( '', 'woocommerce_sagepayform' ), 
					'default' 		=> 'no'
				),
				'3dsecure' 			=> array(
					'title' 		=> __( '3D Secure', 'woocommerce_sagepayform' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( '3D Secure Settings.', 'woocommerce_sagepayform' ), 
					'default' 		=> '',
					'options' 		=> array(
							'0'		=> 'If 3D-Secure checks are possible and rules allow, perform the checks and apply the authorisation rules. (default)',
							'1'		=> 'Force 3D-Secure checks for this transaction if possible and apply rules for authorisation. ',
							'2'		=> 'Do not perform 3D-Secure checks for this transaction and always authorise.',
							'3'		=> 'Force 3D-Secure checks for this transaction if possible but ALWAYS obtain an auth code, irrespective of rule base.'
						),
				),
				'threeDSMethod' 	=> array(
					'title' 		=> __( '3D Secure Method', 'woocommerce_sagepayform' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( '3D Secure Method.', 'woocommerce_sagepayform' ), 
					'default' 		=> '',
					'options' 		=> array(
							'0'		=> 'Use iFrames',
							'1'		=> 'Do not use iFrames'
						),
				),
				'secure_token' 	=> array(
					'title' 		=> __( 'Require 3D Secure for Token Payments', 'woocommerce_sagepayform' ), 
					'label' 		=> __( 'Require 3D Secure if rules allow when paying with a token (does not affect subscription renewals or authorization payments)', 'woocommerce_sagepayform' ), 
					'type' 			=> 'checkbox', 
					'description' 	=> __( '', 'woocommerce_sagepayform' ), 
					'default' 		=> 'no'
				),
				'tokens'     		=> array(
				    'title'         => __( 'Enable Tokens', 'woocommerce_sagepayform' ),
				    'type' 			=> 'select',
					'options' 		=> array('yes'=>'Yes','no'=>'No'),
				    'label'     	=> __( '', 'woocommerce_sagepayform' ),
				    'description' 	=> __( 'Enable Tokens, used for saving cards at Sage - makes checking out faster and useful for Subscriptions and Pre-Orders etc. <strong>IMPORTANT: To use this option please contact Sage to confirm that tokens are enabled on your account.</strong>', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_tokens
				),		
				'tokensmessage'     => array(
				    'title'         => __( 'Show customers a message about tokens', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'options'       => array('no'=>'No','yes'=>'Yes'),
				    'label'     	=> __( 'Leave empty for no message', 'woocommerce_sagepayform' ),
				    'description' 	=> __( 'Optionally show a message to your customers explaining how saved cards works. An example message might be : <br />"Saving your card details allows you to checkout faster in the future. Card details are stored securely at Sage, we do not have access, and you can delete them from your account at anytime."', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_tokens_message
				),
				'basketoption'     	=> array(
				    'title'         => __( 'Basket Option', 'woocommerce_sagepayform' ),
				    'type' 			=> 'select',
					'options' 		=> array('0'=>'Do not send the basket to Sage','1'=>'Send the basket in standard format','2'=>'Send the basket in XML format'),
				    'label'     	=> __( '', 'woocommerce_sagepayform' ),
				    'description' 	=> __( 'Optionally you can send the contents of the shopping cart to Sage, this will show up in MySagePay and in certain emails.', 'woocommerce_sagepayform' ),
				    'default'       => array('product','shipping','discount')
				),
				'basketarray'     	=> array(
				    'title'         => __( 'Select which fields you want to include in the basket', 'woocommerce_sagepayform' ), 
					'type' 			=> 'multiselect',
					'class'			=> 'chosen_select',
					'css'         	=> 'width: 350px;', 
					'options' 		=> array('product'=>'Send product lines','shipping'=>'Send shipping details','discount'=>'Send any coupon or discount details'),
				    'label'     	=> __( '', 'woocommerce_sagepayform' ),
				    'description' 	=> __( 'Optionally you remove certain fields from the basket, this may help with any issues you are seeing with importing in to Sage Accounts', 'woocommerce_sagepayform' ),
				    'default'       => array('product','shipping','discount')
				),	
				'debug'     		=> array(
				    'title'         => __( 'Debug Mode', 'woocommerce_sagepayform' ),
				    'type'          => 'checkbox',
				    'options'       => array('no'=>'No','yes'=>'Yes'),
				    'label'     	=> __( 'Enable Debug Mode', 'woocommerce_sagepayform' ),
				    'default'       => 'no'
				),
				'notification'		=> array(
				    'title'         => __( 'Notification Email Address', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( 'Add an email address that will be notified in the event of a failure', 'woocommerce_sagepayform' ),
				    'default'       => get_bloginfo( 'admin_email' )
				),
				'advanced'          => array(
				    'title'         => __( 'Advanced Settings', 'woocommerce_sagepayform' ),
				    'type'          => 'title',
				    'description'	=> '<div style="display:block; border-bottom:1px dotted #000; width:100%;"></div>'
				),
				'sagelinebreak' 	=> array(
					'title' 		=> __( 'Line Break', 'woocommerce_sagepayform' ),
					'type'			=> 'select',
					'css'         	=> 'width: 350px;', 
					'description' 	=> __( 'Line Break settings, used for decrypting messages from Sage. Do not change unless you are having issues, see docs for more information.', 'woocommerce_sagepayform' ), 
					'default' 		=> '0',
					'options' 		=> array(
							'0'		=> 'Default',
							'1'		=> 'Use PHP_EOL',
							'2'		=> 'Use n',
							'3'		=> 'Use r'
						),
				),
				'defaultpostcode'	=> array(
				    'title'         => __( 'Default Postcode for Elavon users', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( 'Leave this blank unless you are using Elavon - See docs for more information.', 'woocommerce_sagepayform' ),
				    'default'       => ''
				),
				'vendortxcodeprefix'=> array(
				    'title'         => __( 'VendorTXCode Prefix', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( 'Add a custom prefix to the VendorTXCode. Only use letters, numbers and _ (underscores) any other characters will be stripped from the field.', 'woocommerce_sagepayform' ),
				    'default'       => $this->default_vendortxcodeprefix
				),
				'sagepaytransinfo'	=> array(
				    'title'         => __( 'Additional Transaction Information', 'woocommerce_sagepayform' ),
				    'description'   => __( 'Include the transaction information received from Sage in the admin emails', 'woocommerce_sagepayform' ),
				    'type'          => 'checkbox',
				    'default'       => false
				),
/*				'thirdmansettings'  => array(
				    'title'         => __( 'ThirdMan Settings', 'woocommerce_sagepayform' ),
				    'type'          => 'title',
				    'description'	=> '<div style="display:block; border-bottom:1px dotted #000; width:100%;">'.__( 'Enter LIVE MySagePay account details to allow ThirdMan Security Checks', 'woocommerce_sagepayform' ).'</div>'
				),
				'thirdmanvendorname'=> array(
				    'title'         => __( 'Vendorname for ThirdMan Checks', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce_sagepayform' ),
				    'default'       => ''
				),
				'thirdmanusername'	=> array(
				    'title'         => __( 'Username for ThirdMan Checks', 'woocommerce_sagepayform' ),
				    'type'          => 'text',
				    'description'   => __( '', 'woocommerce_sagepayform' ),
				    'default'       => ''
				),
				'thirdmanpassword'	=> array(
				    'title'         => __( 'Password for ThirdMan Checks', 'woocommerce_sagepayform' ),
				    'type'          => 'password',
				    'description'   => __( '', 'woocommerce_sagepayform' ),
				    'default'       => ''
				),
*/			);