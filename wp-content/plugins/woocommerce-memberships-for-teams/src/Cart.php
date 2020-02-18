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

namespace SkyVerge\WooCommerce\Memberships\Teams;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;
use SkyVerge\WooCommerce\Memberships\Teams\Product;

defined( 'ABSPATH' ) or exit;

/**
 * Teams Cart helper class. Handles cart-related functionality.
 *
 * @since 1.0.0
 */
class Cart {


	/**
	 * Sets up the cart class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_loop_add_to_cart_link',      array( $this, 'loop_add_to_cart_link' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation',     array( $this, 'validate_team_product_add_to_cart'), 10, 6 );
		add_filter( 'woocommerce_update_cart_validation',     array( $this, 'validate_team_product_cart_update'), 10, 4 );
		add_filter( 'woocommerce_add_cart_item_data',         array( $this, 'add_new_cart_item_team_data'), 10, 3 );
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'add_order_again_cart_item_team_data'), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session'), 10, 2 );
		add_filter( 'woocommerce_get_item_data',              array( $this, 'display_team_data_in_cart'), 10, 2 );
		add_action( 'woocommerce_add_cart_item',              array( $this, 'enforce_seat_change_cart' ), 10, 2 );
		add_action( 'woocommerce_new_order_item',             array( $this, 'add_order_item_team_data'), 10, 2 );
	}


	/**
	 * Modifies the loop 'add to cart' button class for team products.
	 *
	 * Adds required input fields to link directly to the product page like a variable product.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag the 'add to cart' button tag html
	 * @param \WC_Product $product the product
	 * @return string the add to cart tag
	 */
	public function loop_add_to_cart_link( $tag, $product ) {

		if (    $product
		     && Product::has_team_membership( $product )
		     && Product::has_required_team_user_input_fields( $product ) ) {

			// otherwise, for simple type products, the page javascript would take over and
			// try to do an ajax add-to-cart, when really we need the customer to visit the
			// product page to supply whatever input fields they require
			$tag = sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button add_to_cart_button product_type_%s">%s</a>',
				get_permalink( $product->get_id() ),
				esc_attr( $product->get_id() ),
				esc_attr( $product->get_sku() ),
				'variable',
				esc_html__( 'Select options', 'woocommerce-memberships-for-teams' )
			);
		}

		return $tag;
	}


	/**
	 * Checks whether a team product is valid to be added to the cart.
	 *
	 * This is used to ensure any required user input fields are supplied. When ordering again, user input will be
	 * copied over from the previous order.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $valid whether the product as added is valid
	 * @param int $product_id the product identifier
	 * @param int $quantity the amount being added
	 * @param int|string $variation_id optional variation id
	 * @param array $variations optional variation configuration
	 * @param array $cart_item_data optional cart item data. This will only be
	 *        supplied when an order is being ordered again, in which case the
	 *        required fields will not be available from the REQUEST array
	 * @return true if the product is valid to add to the cart
	 */
	public function validate_team_product_add_to_cart( $valid, $product_id, $quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		$_product_id = $variation_id ? $variation_id : $product_id;
		$product     = wc_get_product( $_product_id );

		// is this a team product?
		if ( Product::has_team_membership( $product ) ) {

			// validate any user-input fields
			$fields = Product::get_team_user_input_fields( $product );

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $field ) {

					if ( ! empty( $field['required'] ) ) {

						// user input may be provided via GET/POST or already attached to cart item data when ordering again
						if ( empty( $_REQUEST[ $key ] ) && empty( $cart_item_data['team_meta_data'][ $key ] ) ) {

							/* translators: Placeholder: %s - field label */
							wc_add_notice( sprintf( __( "Field '%s' is required.", 'woocommerce-memberships-for-teams' ), $field['label'] ), 'error' );
							$valid = false;
						}
					}
				}
			}

			// validate min/max member count criteria is met
			if ( ! $this->is_team_product_quantity_valid( $product, $quantity ) ) {
				$valid = false;
			}
		}

		return $valid;
	}


	/**
	 * Checks whether a team product is still valid when the cart is updated.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $valid whether the product as updated is valid
	 * @param string $cart_item_key the cart item key
	 * @param array $values cart item values
	 * @param int $quantity the amount being added
	 * @return true if the product is valid to after updated in cart
	 */
	public function validate_team_product_cart_update( $valid, $cart_item_key, $values, $quantity ) {

		$_product_id = ! empty( $values['variation_id'] ) ? $values['variation_id'] : $values['product_id'];
		$product     = wc_get_product( $_product_id );

		// is this a team product? skip validation if renewing a subscription
		if ( ! empty( $_POST ) && ! isset( $cart_item_data['subscription_renewal'] ) && Product::has_team_membership( $product ) ) {

			// validate min/max member count criteria is met
			if ( ! $this->is_team_product_quantity_valid( $product, $quantity ) ) {
				$valid = false;
			}
		}

		return $valid;
	}


	/**
	 * Checks whether the team product cart quantity meets min/max member count criteria.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @param int $quantity product quantity
	 * @return bool whether the product passes validation or not
	 */
	private function is_team_product_quantity_valid( $product, $quantity ) {

		// per team pricing doesn't care about quantity in cart
		if ( ! Product::has_per_member_pricing( $product ) ) {
			return true;
		}

		$valid     = true;
		$min_count = Product::get_min_member_count( $product );
		$max_count = Product::get_max_member_count( $product );

		if ( $min_count > $quantity ) {
			/* translators: Placeholders: %1$s - number of members, %2$s - team product name */
			wc_add_notice( sprintf( _n( 'At least %1$d member must be added to %2$s.', 'A minimum of %1$d members must be added to %2$s.', $min_count, 'woocommerce-memberships-for-teams' ), $min_count, $product->get_title() ), 'error' );
			$valid = false;
		} elseif ( $max_count && $max_count < $quantity ) {
			/* translators: Placeholders: %1$s - number of members, %2$s - team product name */
			wc_add_notice( sprintf( _n( 'Only %1$d member can be added to %1$s.', 'A maximum of %1$d members can be added to %2$s.', $max_count, 'woocommerce-memberships-for-teams' ), $max_count, $product->get_title() ), 'error' );
			$valid = false;
		}

		return $valid;
	}


	/**
	 * Adds any user-supplied team field data to the cart item data, to set in the session.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param int $product_id the product identifier
	 * @param int $variation_id optional product variation identifier
	 * @return array associative array of name/value pairs of cart item data to set in the session
	 */
	public function add_new_cart_item_team_data( $cart_item_data, $product_id, $variation_id ) {

		$_product_id = $variation_id ? $variation_id : $product_id;
		$product     = wc_get_product( $_product_id );

		return $this->add_cart_item_team_data( $cart_item_data, $product );
	}


	/**
	 * Copies any user-supplied team field data to the cart item data when ordering again.
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param \WC_Order_Item_Product $item the order item to order again
	 * @return array associative array of name/value pairs of cart item data to set in the session
	 */
	public function add_order_again_cart_item_team_data( $cart_item_data, $item ) {

		return $this->add_cart_item_team_data( $cart_item_data, $item->get_product(), $item );
	}


	/**
	 * Adds any user-supplied team field data to the cart item data from either user input or an order item.
	 *
	 * @since 1.0.2
	 *
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param \WC_Product $product the product being added to cart
	 * @param \WC_Order_Item_Product (optional) $item the order item, if ordering again
	 * @return array associative array of name/value pairs of cart item data to set in the session
	 */
	private function add_cart_item_team_data( $cart_item_data, $product, $item = null ) {

		// is this a team product?
		if ( Product::has_team_membership( $product ) ) {

			// set any user-input fields, which will end up in the order item meta data (which can be displayed on the frontend)
			$fields = Product::get_team_user_input_fields( $product );

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $field ) {

					if ( $item instanceof \WC_Order_Item_Product ) {
						// if we're ordering again, copy the meta from the previous order's item
						$value = $item->get_meta( $key, true, 'edit' );
					} else {
						// otherwise, use whatever user input was supplied
						$value = isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : null;
					}

					if ( $value ) {
						$cart_item_data['team_meta_data'][ $key ] = $value;
					}
				}
			}

			// add a random so that multiple of the same product can be added to the cart when "sold individually" is enabled
			$cart_item_data['team_random'] = uniqid( 'team_', true );
		}

		return $cart_item_data;
	}


	/**
	 * Persists our cart item team data to the session, if any.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_item associative array of data representing a cart item (product)
	 * @param array $values associative array of data for the cart item, currently in the session
	 * @return array
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		if ( isset( $values['team_random'] ) ) {
			$cart_item['team_random'] = $values['team_random'];
		}

		return $cart_item;
	}


	/**
	 * Displays any user-input team data in the cart.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $data array of name/display pairs of data to display in the cart
	 * @param array $item associative array of a cart item (product)
	 * @return array of name/display pairs of data to display in the cart
	 */
	public function display_team_data_in_cart( $data, $item ) {

		if ( ! empty( $item['team_meta_data'] ) ) {

			$product = $item['data'];
			$fields  = Product::get_team_user_input_fields( $product );

			// TODO: consider displaying something like "team renewal" in cart item meta, when appropriate {IT 2017-09-22}
			if ( ! empty( $fields ) ) {
				foreach ( $item['team_meta_data'] as $name => $value ) {

					if ( ! empty( $fields[ $name ] ) && $value ) {

						$label = ! empty( $fields[ $name ]['label'] ) ? $fields[ $name ]['label'] : $name;
						$value = stripslashes( $value );

						if ( isset( $fields[ $name ]['type'] ) && 'checkbox' === $fields[ $name ]['type'] ) {
							$value = '✔';
						}

						$data[] = array(
							'name'    => $label,
							'display' => $value,
							'hidden'  => false,
						);
					}
				}
			}

			$change_amount = isset( $item['team_meta_data']['_wc_memberships_for_teams_team_seat_change'] ) ? $item['team_meta_data']['_wc_memberships_for_teams_team_seat_change'] : null;
			$team_id       = isset( $item['team_meta_data']['_wc_memberships_for_teams_team_id'] )          ? $item['team_meta_data']['_wc_memberships_for_teams_team_id']          : null;

			if ( $team_id && $change_amount ) {

				$team = wc_memberships_for_teams_get_team( $team_id );

				if ( $team instanceof Team ) {

					$new_total = $team->get_seat_change_total( $change_amount );

					$data[] = array(
						'name'    => __( 'Seat Change', 'woocommerce-memberships-for-teams' ),
						'display' => sprintf(
							/* translators: Placeholders: %1$d - current seat count, %2$s - <br/>, %3$d - new seat count */
							__( 'Current Seat Count: %1$d%2$sNew Seat Count: %3$d', 'woocommerce-memberships-for-teams' ),
							$team->get_seat_count(),
							'</br>',
							$new_total
						),
						'hidden'  => false,
					);
				}
			}
		}

		return $data;
	}


	/**
	 * Prevents other items from being added to the cart if it already has a seat-change item in it.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param array $cart_item_data cart item data
	 * @param string $cart_item_key the cart item key
	 * @return array
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function enforce_seat_change_cart( $cart_item_data, $cart_item_key ) {

		foreach( WC()->cart->cart_contents as $key => $data ) {

			if ( isset( $data['team_meta_data']['_wc_memberships_for_teams_team_seat_change'] ) ) {

				throw new Framework\SV_WC_Plugin_Exception( __( 'Oops! It looks like you’re currently changing the seat count for your team. Please complete checkout or remove that item from the cart in order to continue.', 'woocommerce-memberships-for-teams' ) );
			}
		}

		return $cart_item_data;
	}


	/**
	 * Stores team data on the order item.
	 *
	 * TODO: refactor this to use `woocommerce_checkout_create_order_line_item` and `$item->add_meta()` {IT 2017-08-22}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $item_id item identifier
	 * @param \WC_Order_Item_Product|array $item order item instance or an array of order item checkout values
	 * @throws \Exception
	 */
	public function add_order_item_team_data( $item_id, $item ) {

		// since WC 3.0+, checkout values are available in the legacy_values property
		if ( is_object( $item ) && property_exists( $item, 'legacy_values' ) ) {
			$values = $item->legacy_values;
		} else {
			$values = $item;
		}

		if ( ! empty( $values['team_meta_data'] ) ) {
			foreach ( $values['team_meta_data'] as $key => $value ) {
				wc_add_order_item_meta( $item_id, $key, $value );
			}
		}

		// store the team unique id on order item meta, so that WooCommerce Subscriptions has a reference to the
		// team that is being created later in the checkout process (once the payment is made)
		if ( ! empty( $values['team_random'] ) ) {
			wc_add_order_item_meta( $item_id, '_wc_memberships_for_teams_team_uid', $values['team_random'] );
		}
	}


}
