<?php

    /**
     * WC_Gateway_Sagepay_Third_Man class.
     *
     */
    class WC_Gateway_Sagepay_Third_Man {

        /**
         * __construct function.
         *
         * @access public
         * @return void
         */
        public function __construct() {

        	$this->id 			= "thirdman";

        	$this->testurl 		= "https://test.sagepay.com/access/access.htm";
			$this->liveurl 		= "https://live.sagepay.com/access/access.htm";

			// Array of payment methods that can use thirdman
			$this->sagepay_payment_methods = array( 'sagepayform', 'sagepaydirect' );

			// Add Invoice meta box to completed orders
			if( function_exists( 'simplexml_load_string' ) ) {
				add_action( 'add_meta_boxes', array( $this,'thirdman_add_meta_box' ), 10, 2 );
			}


        } // END __construct

        /**
         * thirdman_add_meta_box
         *
         * Add a meta box to the edit order screen
         * 
         * @param  [type] $post_type [description]
         * @param  [type] $post      [description]
         * @return [type]            [description]
         */
		function thirdman_add_meta_box( $post_type, $post ) {

			if( $post_type == 'shop_order' ) {

				$order = new WC_Order( $post->ID );

				$payment_method = $order->get_payment_method();
				$settings 		= get_option( 'woocommerce_'.$payment_method.'_settings' );

				// Show the meta box if ThirdMan checks are required.
				if( isset( $settings['thirdmanvendorname'] ) && strlen( $settings['thirdmanvendorname'] ) != 0 && in_array( $order->get_payment_method(), $this->sagepay_payment_methods ) ) {
					add_meta_box( 'sagepay-thirdman-details', __('Thirdman Score', 'woocommerce_sagepayform'), array( $this,'thirdman_details_meta_box' ), 'shop_order', 'side', 'high');
				}

			}

		}

        /**
         * thirdman_details_meta_box
         *
         * Add the thirdman result to the meta box.
         * 
         * @param  [type] $post [description]
         * @return [type]       [description]
         */
		function thirdman_details_meta_box( $post ) {
			global $woocommerce;

			$order  	= new WC_Order( $post->ID );
			$sageresult = get_post_meta( $order->get_id(), '_sageresult', TRUE );

			// if( get_post_meta( $post->ID, '_sage_thirdman', TRUE ) == NULL ) {
				$output = $this->sagepay_thirdman( $order, $sageresult );

				// Add the response to the order meta if there is no error
				if( isset( $output['errorcode'] ) && $output['errorcode'] == '0000' ) {
					// update_post_meta( $post->ID, '_sage_thirdman', $output );
				}

			// } else {
			//	$output = get_post_meta( $post->ID, '_sage_thirdman', TRUE );
			// }

			// TEMP!
			// $xml 	= simplexml_load_string( $this->sample_output(), "SimpleXMLElement", LIBXML_NOCDATA );
			// $json 	= json_encode( $xml );
			// $output = json_decode( $json, TRUE );

			if( isset( $output['t3mresults'] ) ) {
				$score = $this->get_thirdman_score( $output['t3mresults']['rule'], $order );
				// Store the score
				// update_post_meta( $post->ID, '_sage_thirdman_score', $score );
			}

			?>
			<div class="invoice_details_group">
				<ul class="totals">
				<?php if( isset( $output['errorcode'] ) && $output['errorcode'] == '0000' ) { ?>		
					<li class="left">
						<?php echo 'Score : ' . $score; ?>
					</li>
					<li class="left">
						<?php echo 'Risk : ' . $this->get_risk( $score, $post->ID ) ; ?>
					</li>
				<?php } else { ?>
					<li class="left">
						<?php echo __('Error Code : ', 'woocommerce_sagepayform') . $output['errorcode']; ?>
					</li>
					<li class="left">
						<?php echo __('Error Description : ', 'woocommerce_sagepayform') . $output['error']; ?>
					</li>
				<?php } ?>
				</ul>
				<div class="clear"></div>
			</div><?php

			$sageresult = get_post_meta( $order->get_id(), '_sageresult', TRUE );

			foreach( $sageresult as $key => $value ) {
				$note .= ucwords( str_replace( '_', ' ', $key) ) . ' : ' . $value . "\r\n";
			}

			echo '<pre>';
			echo print_r( $sageresult, TRUE );
			echo '</pre>';

			
			
		}

        /**
         * sagepay_thirdman
         *
         * Make the thirdman request
         * 
         * @param  [type] $order [description]
         * @return [type]        [description]
         */
        function sagepay_thirdman( $order ) {

        	$output		= NULL;
        	$order_id 	= $order->get_id();

        	// getFraudScreenDetail or getT3MDetail
        	$command = $this->get_command( $order_id );

        	// VPSTxId or fraudid
        	$node	 = $this->get_node( $order_id );

        	// Get the VPSTxId or fraudid from the order.
        	$element = $this->get_transaction_id( $order_id );

    		// This is to check the thirdman result.
        	$params = "<".$node.">" . $element . "</".$node.">";
        	$xml 	= $this->get_xml( $command, $params, $order );

	        $output = $this->sagepay_post( $xml, $this->testurl );

	        // Log the result
	        $xml 	= simplexml_load_string( $output['body'], "SimpleXMLElement", LIBXML_NOCDATA );
			$json 	= json_encode($xml);
			$array 	= json_decode($json,TRUE);
			$this->log( $array );

	        return $array;

		}

		function get_command( $order_id ) {

			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );

			if( isset($sageresult) && isset($sageresult['fraudid']) ) {
				$command = 'getT3MDetail';
			} else {
				$command = 'getFraudScreenDetail';
			}

			return $command;
		}

		function get_node( $order_id ) {

			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );

			if( isset($sageresult) && isset($sageresult['fraudid']) ) {
				$node = 't3mtxid';
			} else {
				$node = 'vpstxid';
			}

			return $node;			
		}

		function get_transaction_id( $order_id ) {

			$sageresult = get_post_meta( $order_id, '_sageresult', TRUE );

			if( isset($sageresult) && isset($sageresult['fraudid']) ) {
				$return = str_replace( array('{','}'),'',$sageresult['fraudid'] );
			} else {
				$return = str_replace( array('{','}'),'',$sageresult['VPSTxId'] );
			}

			return $return;
		}

        /**
         * get_xml
         *
         * Create the XML to send to Sage
         * 
         * @param  [type] $command [description]
         * @param  [type] $params  [description]
         * @return [type]          [description]
         *
         * https://www.sagepay.co.uk/file/1186/download-document/reportingandapiprotocol102v0.5.pdf
         *
         * Sample XML Input
         * <vspaccess>
         * <command>getFraudScreenDetail</command> 
         * <vendor>onlinecheese</vendor>
         * <user>barry</user> 
         * <vendortxcode>01Jan2010Transaction12345</vendortxcode> 
         * <signature>799B11DFF4275AEE76531AEC625FADE0</signature>
         * </vspaccess>
         * 
         * Sample XML Output
         * <vspaccess> 
         * <errorcode>0000</errorcode>
         * <recommendation>CHALLENGE</recommendation>
         * <fraudid>123456789</fraudid>
         * <fraudcode>0600</fraudcode>
         * <fraudcodedetail>Card found in stolen card database</fraudcodedetail> 
         * <timestamp>07/10/2010 10:03:39</timestamp>
         * </vspaccess>
         */
		function get_xml( $command, $params = null, $order ) {

			$payment_method = $order->get_payment_method();
			$settings 		= get_option( 'woocommerce_'.$payment_method.'_settings' );

			$vendor_details = array( 
					'thirdmanvendorname' => $settings['thirdmanvendorname'],
					'thirdmanusername' => $settings['thirdmanusername'],
					'thirdmanpassword' => $settings['thirdmanpassword']
				);

			$xml  = '';
			$xml .= '<vspaccess>';
			$xml .= '<command>' . $command . '</command>';
			$xml .= '<vendor>' . $vendor_details['thirdmanvendorname'] . '</vendor>';
			$xml .= '<user>' . $vendor_details['thirdmanusername'] . '</user>';

			if(!is_null($params)){
				$xml .= $params;
			}

			$xml .= '<signature>' . $this->get_xml_signature( $command, $params, $vendor_details ) . '</signature>';
			$xml .= '</vspaccess>';

			// Log the XML
			$this->log( $xml );

			return $xml;

		}

        /**
         * sagepay_post
         *
         * Post to Sage
         * 
         * @param  [type] $data [description]
         * @param  [type] $url  [description]
         * @return [type]       [description]
         */
        function sagepay_post( $data, $url ) {

        	$res  = wp_remote_post( 
        				$url, array(
							'method' 		=> 'POST',
							'timeout' 		=> 45,
							'redirection' 	=> 5,
							'httpversion' 	=> '1.0',
							'blocking' 		=> true,
							'headers' 		=> array('Content-Type'=> 'application/x-www-form-urlencoded'),
							'body' 			=> "XML=".$data,
							'cookies' 		=> array()
						)
					);

			return $res;

        }

        /**
         * get_xml_signature
         *
         * Build the XML signature
         * 
         * @param  [type] $command [description]
         * @param  [type] $params  [description]
         * @return [type]          [description]
         */
		function get_xml_signature( $command, $params, $vendor_details ) {

			$xml  = '<command>' . $command . '</command>';
			$xml .= '<vendor>' . $vendor_details['thirdmanvendorname'] . '</vendor>';
			$xml .= '<user>' . $vendor_details['thirdmanusername'] . '</user>';
			$xml .= $params;
			$xml .= '<password>' . $vendor_details['thirdmanpassword'] . '</password>';

			return md5( $xml );
		}

		/**
         * get_score
         *
         * Build the XML signature
         * 
         * @param  [type] $t3mresults [description]
         * @param  [type] $order  	  [description]
         * @return [type]          	  [description]
         */
		function get_thirdman_score( $t3mresults, $order ) {

			$scores = 0;

			$this->log( $t3mresults );

			foreach ( $t3mresults as $score ) {
				$scores += intval( $score['score'] ); 
			}

			return $scores;

		}

		/**
         * get_risk
         *
         * Get the order risk, based on https://www.sagepay.co.uk/file/9986/download-document/SagePay_Fraud_Prevention_Guide.pdf
         * 
         * @param  [type] $t3mresults [description]
         * @param  [type] $order  	  [description]
         * @return [type]          	  [description]
         */
		function get_risk( $score, $order_id ) {

			if( $score <= 29 ) {
				// update_post_meta( $order_id, '_sage_thirdman_risk', 'low' );
				return __( 'Low Risk', 'woocommerce_sagepayform' );
			}

			if( $score >= 30 && $score <= 49 ) {
				// update_post_meta( $order_id, '_sage_thirdman_risk', 'med' );
				return __( 'Medium Risk', 'woocommerce_sagepayform' );
			}

			if( $score >= 50 ) {
				// update_post_meta( $order_id, '_sage_thirdman_risk', 'high' );
				return __( 'High Risk', 'woocommerce_sagepayform' );
			}

		}

        /**
         * log
         *
         * Log things
         * 
         * @param  [type] $to_log [description]
         * @return [type]         [description]
         */
		function log( $to_log ) {

			if( !isset( $logger ) ) {
                $logger      = new stdClass();
                $logger->log = new WC_Logger();
            }

            $logger->log->add( $this->id, print_r( $to_log, TRUE ) );

		}

	} // End class
	
	$WC_Gateway_Sagepay_Third_Man = new WC_Gateway_Sagepay_Third_Man;
