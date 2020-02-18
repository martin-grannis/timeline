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

defined( 'ABSPATH' ) or exit;

/**
 * The Seat Manager class handles the addition and removal of seats on teams.
 *
 * @since 1.1.0
 */
class Seat_Manager {


	/**
	 * Handles a seat change to a team.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team to change seats on
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public static function handle_team_seat_change( Team $team, $change_value ) {

		self::validate_seat_change( $team, $change_value );
		self::add_seat_change_product_to_cart( $team, $change_value );
	}


	/**
	 * Performs validations that are necessary for all seat changes.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public static function validate_seat_change( Team $team, $change_value ) {

		$new_seat_count = $team->get_seat_change_total( $change_value );

		// check for team existence
		if ( ! $team || ! $team instanceof Team ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team', 'woocommerce-memberships-for-teams' ) );
		}

		// check for seat count
		if ( ! $new_seat_count || $new_seat_count < 1 ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid seat count', 'woocommerce-memberships-for-teams' ) );
		}

		// make sure we are actually changing the seat count if not performing a manual renewal.
		// when performing a manual renewal from the admin, subscriptions starts off by duplicating
		// the most recent order, even if it was a seat change, so by all accounts it will appear like
		// a seat change to us, we just need to let it slide and the renewal should go through correctly
		if (    (int) $new_seat_count === $team->get_seat_count()
			 && ! doing_action( 'woocommerce_order_action_wcs_process_renewal' )
			 && ! doing_action( 'woocommerce_order_action_wcs_create_pending_renewal' )
			 && ! doing_action( 'woocommerce_order_action_wcs_retry_renewal_payment' ) ) {

			throw new Framework\SV_WC_Plugin_Exception( __( 'Seat count unchanged', 'woocommerce-memberships-for-teams' ) );
		}

		// perform seat reduction validation if needed
		if ( $team->get_seat_count() > $new_seat_count ) {
			self::validate_seat_reduction( $team, $change_value );
		}

		// perform seat increase validation if needed
		if ( $team->get_seat_count() < $new_seat_count ) {
			self::validate_seat_increase( $team, $change_value );
		}

		/**
		 * Filters whether or not a seat change should be performed.
		 *
		 * @since 1.1.0
		 *
		 * @param bool $should_perform_seat_change
		 * @param Team $team the team object
		 * @param int $new_seat_count the desired seat count
		 */
		if ( ! apply_filters( 'wc_memberships_for_teams_should_perform_seat_change', true, $team, $new_seat_count ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Unable to perform seat change', 'woocommerce-memberships-for-teams' ) );
		}
	}


	/**
	 * Performs validations necessary for seat reductions.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected static function validate_seat_reduction( Team $team, $change_value ) {

		$new_seat_count = $team->get_seat_change_total( $change_value );

		// check if seats can be removed
		if ( ! $team->can_remove_seats() ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Team cannot remove seats', 'woocommerce-memberships-for-teams' ) );
		}

		// sanity check - make sure we are actually decreasing seat count
		if ( $team->get_seat_count() <= $new_seat_count ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid seat count', 'woocommerce-memberships-for-teams' ) );
		}

		$product = $team->get_product();

		// check for product existence
		if ( ! $product ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team product', 'woocommerce-memberships-for-teams' ) );
		}

		$max = Product::get_max_member_count( $product );
		$min = Product::get_min_member_count( $product );

		// check for min seat count
		if ( $min && 0 < $min && $new_seat_count < $min ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				/* translators: Placeholder: %1$d - number of seats */
				__( 'Seat count for this team must be no less than %1$d', 'woocommerce-memberships-for-teams' ),
				$min
			) );
		}

		// check for max seat count, in case the maximum seat limit has
		// been added / decreased since the team was created
		if ( $max && 0 < $max && $new_seat_count > $max ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				/* translators: Placeholder: %1$d - number of seats */
				__( 'Seat count for this team must be no more than %1$d', 'woocommerce-memberships-for-teams' ),
				$max
			) );
		}

		$remaining_seats = $team->get_remaining_seat_count();
		$seats_to_remove = $team->get_seat_count() - $new_seat_count;
		$difference      = $seats_to_remove - $remaining_seats;

		// check that we have enough free seats on the plan to remove the desired amount
		if ( $difference > 0 ) {

			/* translators: Placeholder: %1$d - number of seats */
			$seats_to_remove_msg = sprintf( _n( '1 seat',       '%1$d seats',       $seats_to_remove, 'woocommerce-memberships-for-teams' ), $seats_to_remove );
			$empty_seats_msg     = sprintf( _n( '1 empty seat', '%1$d empty seats', $remaining_seats, 'woocommerce-memberships-for-teams' ), $remaining_seats );
			$difference_msg      = sprintf( _n( '1 member',     '%1$d members',     $difference,      'woocommerce-memberships-for-teams' ), $difference );

			$exception = sprintf(
				/* translators: Placeholders: %1$s - seats to remove message, %2$s - empty seats message, %3$s - seat difference message */
				__( 'Whoops! You tried removing %1$s from a team that has %2$s. Please remove at least %3$s from your team and try again.', 'woocommerce-memberships-for-teams' ),
				$seats_to_remove_msg,
				$empty_seats_msg,
				$difference_msg
			);

			throw new Framework\SV_WC_Plugin_Exception( $exception );
		}
	}


	/**
	 * Performs validations necessary for seat increases.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	protected static function validate_seat_increase( Team $team, $change_value ) {

		$new_seat_count = $team->get_seat_change_total( $change_value );

		// check if seats can be added
		if ( ! $team->can_add_seats() ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Seats cannot be added to this team.', 'woocommerce-memberships-for-teams' ) );
		}

		// sanity check - make sure we are actually increasing seat count
		if ( $team->get_seat_count() >= $new_seat_count ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid seat count', 'woocommerce-memberships-for-teams' ) );
		}

		$product = $team->get_product();

		// check for product existence
		if ( ! $product ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team product', 'woocommerce-memberships-for-teams' ) );
		}

		$max = Product::get_max_member_count( $product );
		$min = Product::get_min_member_count( $product );

		// check for max seat count on per-member products
		if ( $max && 0 < $max && $new_seat_count > $max && Product::has_per_member_pricing( $product ) ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				/* translators: Placeholder: %1$d - number of seats */
				__( 'Seat count for this team must be no more than %1$d', 'woocommerce-memberships-for-teams' ),
				$max
			) );
		}

		// check for min seat count on per-member products, in case the minimum
		// seats have been added / increased since the team was created
		if ( $min && 0 < $min && $new_seat_count < $min && Product::has_per_member_pricing( $product ) ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				/* translators: Placeholder: %1$d - number of seats */
				__( 'Seat count for this team must be at least %1$d', 'woocommerce-memberships-for-teams' ),
				$min
			) );
		}

		$seats_to_add = $new_seat_count - $team->get_seat_count();

		// check that we are adding seats in the correct batch size for per-team products
		if ( $max && 0 < $max && $seats_to_add % $max > 0 && ! Product::has_per_member_pricing( $product )  ) {

			throw new Framework\SV_WC_Plugin_Exception( sprintf(
				/* translators: Placeholder: %1$d - number of seats */
				__( 'Seats for this team must be added in batches of %1$d', 'woocommerce-memberships-for-teams' ),
				$max
			) );
		}
	}


	/**
	 * Adds a seat change product to the cart.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public static function add_seat_change_product_to_cart( Team $team, $change_value ) {

		$product  = $team->get_product();

		$new_seat_count = $team->get_seat_change_total( $change_value );

		/**
		 * Filters the quantity of the seat change product.
		 *
		 * Seat reductions must use this filter to set a valid quantity.
		 * Otherwise, an exception will be thrown.
		 *
		 * @since 1.1.0
		 *
		 * @param int $change_value the quantity to set on the seat change product
		 * @param Team $team the team object
		 */
		$quantity = (int) apply_filters( 'wc_memberships_for_teams_get_seat_change_product_quantity', $change_value, $team );

		// make sure we have a reasonable quantity
		if ( $quantity < 1 ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid seat change quantity', 'woocommerce-memberships-for-teams' ) );
		}

		// set up variation data (if needed) before adding to the cart
		$product_id           = $product->is_type( 'variation' ) ? Framework\SV_WC_Product_Compatibility::get_prop( $product, 'parent_id' ) : $product->get_id();
		$variation_id         = $product->is_type( 'variation' ) ? $product->get_id() : 0;
		$variation_attributes = $product->is_type( 'variation' ) ? wc_get_product_variation_attributes( $variation_id ) : array();

		/**
		 * Filters the cart item data for the seat change product.
		 *
		 * @since 1.1.0
		 *
		 * @param array $cart_item_data the cart item data
		 * @param Team $team the team object
		 * @param int $new_seat_count the desired seat count
		 */
		$cart_item_data = apply_filters( 'wc_memberships_for_teams_seat_change_product_cart_data', array(
			'team_meta_data' => array(
				'_wc_memberships_for_teams_team_current_seat_count' => $team->get_seat_count(),
				'_wc_memberships_for_teams_team_seat_change'        => $change_value,
				'_wc_memberships_for_teams_team_id'                 => $team->get_id(),
				'team_name'                                         => $team->get_name(),
			),
		), $team, $new_seat_count );

		// empty the cart to prepare for the seat change product
		wc_empty_cart();

		// add the product to the cart
		$cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attributes, $cart_item_data );

		/**
		 * Filters the URL to redirect to after adding the seat change product to the cart.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url URL to redirect to
		 * @param Team $team the team object
		 * @param int $new_seat_count the desired seat count
		 * @param string $cart_item_key the cart item key just added to the cart
		 */
		$redirect_url = apply_filters( 'wc_memberships_for_teams_seat_change_product_cart_data', wc_get_checkout_url(), $team, $new_seat_count, $cart_item_key );

		if ( $redirect_url ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}


	/**
	 * Determines if a seat change should be prorated or not.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @return bool
	 */
	public static function should_prorate_seat_change( Team $team, $change_value ) {

		/**
		 * Filters whether a seat change should be prorated or not.
		 *
		 * @since 1.1.0
		 *
		 * @param Team $team the team object
		 * @param int $change_value the change value, based on the team's seat change mode
		 */
		return apply_filters( 'wc_memberships_for_teams_should_prorate_seat_change', false, $team, $change_value );
	}
}
