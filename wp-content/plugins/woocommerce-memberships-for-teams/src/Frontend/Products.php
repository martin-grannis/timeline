<?php
/**
 * Teams for WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Teams for WooCommerce Memberships to newer
 * versions in the future. If you wish to customize Teams for WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/teams-woocommerce-memberships/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2017-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Memberships\Teams\Frontend;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;
use SkyVerge\WooCommerce\Memberships\Teams\Product;

defined( 'ABSPATH' ) or exit;

/**
 * Teams Products helper class.
 *
 * Provides team product utility methods and handles aspects of the plugin related to products.
 *
 * @since 1.0.0
 */
class Products {


	/** @var bool flag to avoid adding inline scripts twice on a page */
	private static $add_variation_switch_scripts = false;


	/**
	 * Sets up the products class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_get_price_html',            array( $this, 'price_per_member_html' ), 10, 2 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_team_options' ) );

		add_filter( 'woocommerce_quantity_input_args',       array( $this, 'team_quantity_input_args' ), 10, 2 );
		add_filter( 'woocommerce_available_variation',       array( $this, 'adjust_team_variation' ), 10, 3 );
	}


	/**
	 * Renders the per member price/sale price for display on the catalog/product pages.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $price_html the formatted sale price
	 * @param \WC_Product|\WC_Product_Variable $product the product
	 * @return string the formatted sale price, per unit
	 */
	public function price_per_member_html( $price_html, $product ) {

		if ( '' !== $price_html && Product::has_team_membership( $product ) && Product::has_per_member_pricing( $product ) ) {

			/* translators: suffix for per member prices, for example: $5 per member */
			$suffix = esc_html__( 'per member', 'woocommerce-memberships-for-teams' );
			$price_html .= ' ' . $suffix;

			/**
			 * Filters the team product price HTML.
			 *
			 * @since 1.0.0
			 *
			 * @param string $price_html the price HTML
			 * @param \WC_Product $product the product
			 * @param string $suffix e.g. / per member
			 */
			$price_html = apply_filters( 'wc_memberships_for_teams_get_price_html', $price_html, $product, $suffix );
		}

		return $price_html;
	}


	/**
	 * Adjusts quantity input args for team based products.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $args associative array of input arguments
	 * @param \WC_Product $product the product instance
	 * @return array
	 */
	public function team_quantity_input_args( $args, $product ) {

		if ( Product::has_team_membership( $product ) && Product::has_per_member_pricing( $product ) ) {

			if ( $min = Product::get_min_member_count( $product ) ) {
				$args['min_value'] = $min;
			}

			if ( $max = Product::get_max_member_count( $product ) ) {
				$args['max_value'] = $max;
			}
		}

		return $args;
	}


	/**
	 * Adjusts variation properties for team based products.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $args associative array of input arguments
	 * @param \WC_Product $product the product instance
	 * @param \WC_Product $variation the variation instance
	 * @return array
	 */
	public function adjust_team_variation( $args, $product, $variation ) {

		if ( Product::has_team_membership( $product ) && Product::has_per_member_pricing( $product ) ) {

			if ( $min = Product::get_min_member_count( $variation ) ) {
				$args['min_qty'] = $min;
			}

			if ( $max = Product::get_max_member_count( $variation ) ) {
				$args['max_qty'] = $max;
			}
		}

		return $args;
	}


	/**
	 * Renders any user-input team fields for a team membership product.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function render_team_options() {
		global $product;

		if ( Product::has_team_membership( $product ) ) {

			// if the product has variations, add some inline JS to ensure the preselected number of seats does not exceed boundaries, if set
			if ( ! self::$add_variation_switch_scripts && $product->is_type( array( 'variable', 'product_variation' ) ) ) {

				// note: a small delay is necessary as other scripts need to change the input's min/max attributes first, and our script needs to pull the updated information timely
				wc_enqueue_js( "
					jQuery( document ).ready( function( $ ) {

						var timeout; 

						$( 'form.variations_form .variations select, form.variations_form .variations input' ).on( 'change', function( e ) {

							clearTimeout( timeout ); 

							timeout = setTimeout( function() {

								qty = $( '.woocommerce-variation-add-to-cart input[name=quantity]' );
								min = qty.length > 0 ? parseInt( $( qty ).attr( 'min' ), 10 ) : false; 
								max = qty.length > 0 ? parseInt( $( qty ).attr( 'max' ), 10 ) : false;

								if ( ! isNaN( min ) && $( qty ).val() < min ) { 
									$( qty ).val( min ); 
								} else if ( ! isNaN( max ) && $( qty ).val() > max ) { 
									$( qty ).val( max ); 
								}

							}, 250 );

						} ).change();
					} );
				" );

				self::$add_variation_switch_scripts = true;
			}

			// load the template file
			wc_get_template(
				'single-product/product-team.php',
				array(
					'product'    => $product,
					'product_id' => Product::get_parent_id( $product ),
					'fields'     => Product::get_team_user_input_fields( $product ),
				),
				'',
				wc_memberships_for_teams()->get_plugin_path() . '/templates/'
			);
		}
	}


}
