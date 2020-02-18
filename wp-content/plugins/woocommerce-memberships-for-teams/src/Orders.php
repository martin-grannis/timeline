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
 * Orders handler class.
 *
 * @since 1.0.0
 */
class Orders {


	/** @var array $email_actions WooCommerce email send actions */
	protected $email_actions = array();


	/**
	 * Sets up the orders handler.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// trigger team access upon products purchases
		add_action( 'woocommerce_order_status_completed',  array( $this, 'process_team_actions_for_order' ), 10 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'process_team_actions_for_order' ), 10 );

		// format team meta for display
		add_filter( 'woocommerce_hidden_order_itemmeta',              array( $this, 'add_hidden_team_meta' ) );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'format_team_meta_for_display' ), 10, 2 );

		// get all emails to modify team links within them
		add_filter( 'woocommerce_email_actions', array( $this, 'set_email_actions' ), 99 );

		// handle order refunds
		add_action( 'woocommerce_create_refund',            array( $this, 'validate_team_purchase_refund' ), 10, 2 );
		add_action( 'woocommerce_order_fully_refunded',     array( $this, 'handle_team_purchase_refund' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'handle_team_purchase_refund' ), 10, 2 );

		// redirect to team settings on seat change orders
		add_filter( 'woocommerce_get_return_url',                      array( $this, 'return_seat_change_to_team_settings' ), 10, 2 );
		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'return_seat_change_to_team_settings' ), 10, 2 );
	}


	/**
	 * Creates teams when an order is processed or completed.
	 *
	 * TODO remove this deprecated method by version 2.0.0 or May 2020 {FN 2019-01-28}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated since 1.1.0
	 *
	 * @param int|\WC_Order $order WC_Order id or object
	 */
	public function create_teams_from_order( $order ) {

		Framework\SV_WC_Plugin_Compatibility::wc_doing_it_wrong(
			'Orders::create_teams_from_order()',
			'Use Orders::process_team_actions_for_order() instead.',
			'1.1.0'
		);

		$this->process_team_actions_for_order( $order );
	}


	/**
	 * Processes team actions such as creation, renewal, and seat changes when an order is processed or completed.
	 *
	 * Note: this method runs also when an order is manually added in WC admin.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param int|\WC_Order $order WC_Order id or object
	 */
	public function process_team_actions_for_order( $order ) {

		$order = is_numeric( $order ) ? wc_get_order( (int) $order ) : $order;

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$order_items = $order->get_items();
		$user_id     = $order->get_user_id();

		// skip if guest user or no order items to begin with
		if ( ! $user_id || empty( $order_items ) ) {
			return;
		}

		foreach ( $order_items as $item ) {
			$this->process_team_action_for_order_item( $item );
		}
	}


	/**
	 * Looks for a team action on the given order item and processes it if found.
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Order_Item $item the order item
	 */
	private function process_team_action_for_order_item( $item ) {

		$product = $item->get_product();

		// bail if we can't find a product or an attached membership on this item
		if ( ! $product || ! Product::has_team_membership( $product ) ) {
			return;
		}

		/**
		 * Filters the teams action that should take place for a given order item.
		 *
		 * Allows for custom teams actions to be defined, or for core actions to be changed/overridden.
		 * Override core actions at your own risk!
		 *
		 * @since 1.1.0
		 *
		 * @param string $action {create|renew|seat_change} the core team action
		 * @param \WC_Order_Item $item the order item
		 */
		$action = apply_filters( 'wc_memberships_for_teams_determine_order_item_action', $this->determine_team_action_for_order_item( $item ), $item );

		/**
		 * Fires before core team actions have been processed on an order item.
		 *
		 * @since 1.1.0
		 *
		 * @param string $action the team action
		 * @param \WC_Order_Item $item the order item
		 */
		do_action( 'wc_memberships_for_teams_before_process_team_action_for_order_item', $action, $item );

		switch ( $action ) {

			case 'create':
				$this->process_team_creation_action( $item );
			break;

			case 'renew':
				$this->process_team_renewal_action( $item );
			break;

			case 'seat_change':
				$this->process_team_seat_change_action( $item );
			break;
		}

		/**
		 * Fires after core team actions have been processed on an order item.
		 *
		 * @since 1.1.0
		 *
		 * @param string $action the team action
		 * @param \WC_Order_Item $item the order item
		 */
		do_action( 'wc_memberships_for_teams_after_process_team_action_for_order_item', $action, $item );
	}


	/**
	 * Determines the team action needed for the given order item.
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Order_Item $item the order item
	 * @return string team action (create|renew|seat_change)
	 */
	private function determine_team_action_for_order_item( $item ) {

		$item_id = $item->get_id();

		try {

			if ( wc_get_order_item_meta( $item_id, '_wc_memberships_for_teams_team_renewal', true ) ) {
				return 'renew';
			}

			if ( wc_get_order_item_meta( $item_id, '_wc_memberships_for_teams_team_seat_change', true ) ) {
				return 'seat_change';
			}

		} catch ( \Exception $e ) {}

		return 'create';
	}


	/**
	 * Processes a renewal action on the order item.
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Order_Item $item
	 */
	private function process_team_renewal_action( $item ) {

		try {
			$team = wc_memberships_for_teams_get_team_for_order_item( $item );
		} catch ( \Exception $e ) {
			$team = null;
		}

		// sanity check
		if ( ! $team || ! $team instanceof Team ) {
			return;
		}

		$plan = $team->get_plan();

		// need to have a plan to renew a plan
		if ( ! $plan ) {
			return;
		}

		/**
		 * Filters whether a team membership will be renewed.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $renew whether to renew
		 * @param \WC_Memberships_Membership_Plan $plan the membership plan to renew team access to
		 * @param array $args contextual arguments
		 */
		$renew = apply_filters( 'wc_memberships_for_teams_renew_team_membership', (bool) $plan->get_access_length_amount(), $plan, array(
			'team_id'    => $team->get_id(),
			'product_id' => $team->get_product_id(),
			'order_id'   => $item->get_order_id(),
		) );

		if ( $renew ) {
			$this->process_team_creation_action( $item, 'renew' );
		}
	}


	/**
	 * Processes a seat change action for the given order item.
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Order_Item $item the order item
	 */
	private function process_team_seat_change_action( $item ) {

		try {
			$change_amount = wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_seat_change', true );
		} catch ( \Exception $e ) {
			$change_amount = null;
		}

		try {
			$team = wc_memberships_for_teams_get_team_for_order_item( $item );
		} catch ( \Exception $e ) {
			$team = null;
		}

		if ( ! $team instanceof Team || ! $change_amount ) {
			return;
		}

		$seat_count = $team->get_seat_change_total( $change_amount );

		try {

			// since this same validation takes place before the seat change item
			// is added to the cart, this should not result in errors unless
			// something funky/fishy is going on
			Seat_Manager::validate_seat_change( $team, $change_amount );

			$team->set_seat_count( $seat_count );

		} catch( Framework\SV_WC_Plugin_Exception $exception ) {

			if ( is_admin() ) {
				wc_memberships_for_teams()->get_admin_instance()->get_message_handler()->add_error( $exception->getMessage() );
			} else {
				wc_add_notice( $exception->getMessage(), 'error' );
			}
		}
	}


	/**
	 * Creates a team from the given order item.
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Order_Item_Product $item the order item
	 * @param string $action the order action, defaults to `create`
	 */
	private function process_team_creation_action( $item, $action = 'create' ) {

		try {

			$order     = $item->get_order();
			$product   = $item->get_product();
			$team_id   = wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id', true );
			$user_id   = $order->get_user_id();
			// in case of per-member priced products, seats = qty, per-team priced products seats = qty * max_seats
			$seats     = Product::has_per_member_pricing( $product ) ? $item->get_quantity() : $item->get_quantity() * Product::get_max_member_count( $product );
			$team_args = array(
				'team_id'    => $team_id,
				'product_id' => $product->get_id(),
				'order_id'   => $order->get_id(),
				'owner_id'   => $user_id,
				'seats'      => $seats,
			);

			// set the name is defined
			if ( $team_name = $item->get_meta( 'team_name' ) ) {
				$team_args['name'] = $team_name;
			}

			$team = wc_memberships_for_teams_create_team( $team_args, $action );

			wc_update_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id', $team->get_id() );

			/**
			 * Fires after a team has been created from an order.
			 *
			 * @since 1.0.0
			 *
			 * @param Team $team the team that was created
			 * @param array $args contextual arguments
			 */
			do_action( 'wc_memberships_for_teams_create_team_from_order', $team, array(
				'user_id'    => $user_id,
				'product_id' => $product->get_id(),
				'order_id'   => $order->get_id(),
				'item_id'    => $item->get_id(),
			) );

			// add owner as member if configured so, unless renewing - note that we do it _after_ the action hook above,
			// so that 3rd parties and integrations (such as Subscriptions) have a chance to attach their data to the team before
			if ( 'renew' !== $action && ( 'yes' === get_option( 'wc_memberships_for_teams_owners_must_take_seat' ) || $item->get_meta( 'team_owner_takes_seat' ) ) ) {
				$team->add_member( $user_id );
			}

		// catches Teams exceptions as well as WC core exceptions
		} catch ( \Exception $e ) {

			/* translators: Placeholders: %1$s - order number, %2$s - order item id, %3$s - error message */
			wc_memberships_for_teams()->log( sprintf( __( 'Could not create team from order %1$s item %2$s: %3$s', 'woocommerce-memberships-for-teams' ), ! empty( $order ) ? $order->get_order_number() : '', $item->get_id(), $e->getMessage() ) );
		}
	}


	/**
	 * Adds hidden team meta to the list of hidden order meta.
	 *
	 * @internal
	 *
	 * @asince 1.0.0
	 *
	 * @param array $hidden_meta an array of meta keys
	 * @return array
	 */
	public function add_hidden_team_meta( $hidden_meta ) {

		$hidden_meta[] = '_wc_memberships_for_teams_team_id';
		$hidden_meta[] = '_wc_memberships_for_teams_team_uid';
		$hidden_meta[] = '_wc_memberships_for_teams_team_renewal';
		$hidden_meta[] = '_wc_memberships_for_teams_team_seat_change';
		$hidden_meta[] = '_wc_memberships_for_teams_team_current_seat_count';
		$hidden_meta[] = 'team_owner_takes_seat';

		return $hidden_meta;
	}



	/**
	 * Set the accepted WooCommerce email actions to modify the email "view team" link.
	 *
	 * @since 1.0.2
	 *
	 * @param string[] $actions email actions
	 * @return string[] updated actions
	 */
	public function set_email_actions( $actions ) {

		if ( ! isset( $this->email_actions ) || empty( $this->email_actions ) ) {
			$this->email_actions = $actions;
		}

		return $actions;
	}


	/**
	 * Adjusts order item team meta for display.
	 *
	 * This affects order item meta display in frontend and admin, so that team name and other
	 * team-related meta keys & names are properly formatted for display.
	 *
	 * Note that the filter this method is hooked to only exists in WC 3.0+
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $formatted_meta formatted order item meta
	 * @param \WC_Order_Item_Product|null $item the order item
	 * @return array the formatted meta
	 */
	public function format_team_meta_for_display( $formatted_meta, $item ) {

		foreach ( $formatted_meta as $meta_id => $meta ) {

			if ( ! Framework\SV_WC_Helper::str_starts_with( $meta->key, 'team_' ) ) {
				continue;
			}

			// format the key (label)
			$product = $item->get_product();

			if ( $product && Product::has_team_membership( $product ) && $label = Product::get_team_user_input_field_label( $product, $meta->key ) ) {
				$formatted_meta[ $meta_id ]->display_key = $label;
			}

			// only format value for team name at the moment
			if ( 'team_name' !== $meta->key ) {
				continue;
			}

			// format the value
			$team_id = $item->get_meta( '_wc_memberships_for_teams_team_id' );
			$team    = $team_id ? wc_memberships_for_teams_get_team( $team_id ) : null;

			if ( $team instanceof Team ) {

				// for admin context, set the link, but override it if we're sending an email to the customer
				if ( is_admin() ) {

					$team_link = get_edit_post_link( $team_id );

					// all emails here that have item meta are customer transactional emails, so we can modify the link in them all
					foreach ( $this->email_actions as $action ) {

						if ( doing_action( $action ) ) {
							// the frontend instance isn't available from emails, which are in the admin context, so just link to the team endpoint
							$team_link = wc_get_account_endpoint_url( get_option( 'woocommerce_myaccount_teams_area_endpoint', 'teams' ) );
						}
					}

				} else {
					$team_link = wc_memberships_for_teams()->get_frontend_instance()->get_teams_area_instance()->get_teams_area_url( $team );
				}

				$team_link     = '<a href="' . esc_url( $team_link ) . '">' . esc_html__( '(view team)', 'woocommerce-memberships-for-teams' ) . '</a>';
				$display_value = wpautop( make_clickable( apply_filters( 'woocommerce_order_item_display_meta_value', sprintf( '%s %s', stripslashes( $meta->value ), $team_link ), $meta, $item ) ) );

				$formatted_meta[ $meta_id ]->display_value = $display_value;
			}
		}

		return $formatted_meta;
	}


	/**
	 * Validates team membership purchase refunds.
	 *
	 * The following scenarios are considered valid when refunding a team purchase:
	 *  - fully refund an order
	 *  - fully refund a line item (full amount or full quantity)
	 *  - refund a quantity less or equal than remaining seats on team
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order_Refund $refund the refund instance
	 * @param array $args refund args
	 * @throws \Exception
	 */
	public function validate_team_purchase_refund( $refund, $args ) {

		$team_ids = $this->get_unrefunded_team_ids_by_order_id( $args['order_id'] );

		if ( empty( $team_ids ) ) {
			return;
		}

		$order = wc_get_order( $args['order_id'] );

		foreach ( $refund->get_items() as $item ) {

			$parsed_item = $this->parse_refund_item( $item, $team_ids, $order );

			if ( false === $parsed_item ) {
				continue;
			}

			if  ( $parsed_item['has_partial_quantity'] || $parsed_item['has_partial_total'] ) {

				$remaining_seats = $parsed_item['team']->get_remaining_seat_count();
				$invalid         = ( $remaining_seats < $parsed_item['refund_seat_count'] ) || ( ! $parsed_item['has_partial_quantity'] && $parsed_item['has_partial_total'] );

				if ( $invalid ) {

					$message =  __( 'Only full refunds and refunds of unoccupied seats are allowed for team membership purchases.', 'woocommerce-memberships-for-teams' );

					if ( $remaining_seats < $parsed_item['refund_seat_count'] ) {

						$message .= ' ' . sprintf( _n( 'Team %1$s has currently %2$d unoccupied seat.', 'Team %1$s has currently %2$d unoccupied seats.', $remaining_seats ), $parsed_item['team']->get_name(), $remaining_seats );

						if ( $parsed_item['product'] instanceof \WC_Product ) {

							if ( ! Product::has_per_member_pricing( $parsed_item['product'] ) ) {

								$block_count = Product::get_max_member_count( $parsed_item['product'] );

								if ( $block_count ) {
									$message .= "\n\n" . sprintf( __( 'Because this team product has per-team pricing with a maximum number of members configured, a quantity of 1 will refund %d seats for this team.', 'woocommerce-memberships-for-teams' ), $block_count );
								}
							}
						}
					}

					// pre WC 3.3, refunds will still be persisted in the database even if validation fails,
					// so we're working around the issue by deleting it ourselves
					// TODO: remove once dropping support for WC < 3.3 {IT 2017-11-23}
					if ( $refund instanceof \WC_Order_Refund && Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt( '3.3' ) ) {

						wp_delete_post( $refund->get_id(), true );
					}

					throw new \Exception( $message );
				}
			}
		}
	}


	/**
	 * Handles team purchase refunds.
	 *
	 * In case of fully refunded orders, teams will be marked as having refunded orders, which
	 * will block team management. In case of partial refunds, the number of seats in a team will be reduced
	 * by the refunded quantity.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id order ID
	 * @param int $refund_id refund ID
	 */
	public function handle_team_purchase_refund( $order_id, $refund_id ) {

		$team_ids = $this->get_unrefunded_team_ids_by_order_id( $order_id );

		if ( empty( $team_ids ) ) {
			return;
		}

		// if order has been fully refunded, mark all teams as having order refunded
		if ( 'woocommerce_order_fully_refunded' === current_filter() ) {

			foreach ( $team_ids as $team_id ) {
				update_post_meta( $team_id, '_order_refunded', 'yes' );
			}

			return;
		}

		// otherwise, look if the partial refund affects any teams, possibly resulting
		// in fully refunded team purchase or simply a seat count reduction
		$order = wc_get_order( $order_id );

		foreach ( $order->get_refunds() as $refund ) {

			// look for the refund that was just created
			if ( (int) $refund->get_id() !== (int) $refund_id ) {
				continue;
			}

			foreach ( $refund->get_items() as $item ) {

				$parsed_item = $this->parse_refund_item( $item, $team_ids, $order );

				if ( false === $parsed_item ) {
					continue;
				}

				// a partial quantity was refunded - check if this should result in a full team refund
				// (in case there have been partial refunds for the team before), or should we reduce
				// the number of seats for the team
				if ( $parsed_item['has_partial_quantity'] ) {

					$total_refunded_quantity = $order->get_qty_refunded_for_item( $parsed_item['refunded_item_id'] );

					if ( $total_refunded_quantity < $parsed_item['refunded_item']->get_quantity() ) {
						$remove_seats = true;
					}
				}

				if ( $remove_seats ) {
					$parsed_item['team']->adjust_seat_count( $parsed_item['refund_seat_count'], 'remove' );
				} else {
					update_post_meta( $parsed_item['team_id'], '_order_refunded', 'yes' );
				}
			}

			// since we found the matching refund, we can just break out of the loop after we're done
			break;
		}
	}


	/**
	 * Returns back to the team settings page after a seat change, rather than the normal thank-you page.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param string $return_url the return URL
	 * @param \WC_Order $order the order object
	 * @return string
	 */
	public function return_seat_change_to_team_settings( $return_url, $order ) {

		if ( $order instanceof \WC_Order ) {

			foreach ( $order->get_items() as $item ) {

				try {
					$team = wc_memberships_for_teams_get_team_for_order_item( $item );
				} catch ( \Exception $e ) {
					$team = null;
				}

				$seat_change_value = (int) $item->get_meta( '_wc_memberships_for_teams_team_seat_change', true );
				$old_seat_count    = (int) $item->get_meta( '_wc_memberships_for_teams_team_current_seat_count', true );

				if ( $team instanceof Team && 0 !== $seat_change_value && 0 !== $old_seat_count ) {

					$seat_change_count = $team->get_seat_count() - $old_seat_count;
					$seat_n            = _n( 'seat has', 'seats have', abs( $seat_change_count ), 'woocommerce-memberships-for-teams' );
					$seat_change_type  = $seat_change_count > 0 ? 'added to' : 'removed from';

					$seat_change_message = sprintf(
						/* translators: Placeholders: %1$d - number of seats, %2$s - seat(s), %3$s - seat change type */
						__( 'Thank you! %1$d %2$s been %3$s your team.', 'woocommerce-memberships-for-teams' ),
						abs( $seat_change_count ),
						$seat_n,
						$seat_change_type
					);

					/**
					 * Filters the notice message that is shown after a successful seat change.
					 *
					 * @since 1.1.0
					 *
					 * @param string $seat_change_message the notice message
					 * @param \WC_Order $order the order object
					 * @param \WC_Order_Item $item the order item object that contains the seat change data
					 * @param Team $team the team object
					 */
					$seat_change_message = apply_filters( 'wc_memberships_for_teams_seat_change_notice_message', $seat_change_message, $order, $item, $team );

					wc_add_notice( $seat_change_message );

					$return_url = wc_memberships_for_teams()->get_frontend_instance()->get_teams_area_instance()->get_teams_area_url( $team, 'settings' );
					break;
				}
			}
		}

		return $return_url;
	}


	/**
	 * Parses the refund item to get some useful data based on it.
	 *
	 * Sorry, I tried to come up with a better method name and description,
	 * but my mind was numb. {IT 2017-11-23}
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order_Item_product $item the refund item
	 * @param array $team_ids an array of team ids the refunded item may be related to
	 * @param \WC_Order $order the order instance this refund item is related to
	 * @return array|false an associative array of refund related data or false on failure
	 */
	private function parse_refund_item( $item, $team_ids, $order ) {

		$refunded_item_id = (int) $item->get_meta( '_refunded_item_id' );
		$team_id          = null;

		if ( $refunded_item_id ) {
			try {
				$team_id = wc_get_order_item_meta( $refunded_item_id, '_wc_memberships_for_teams_team_id', true );
			} catch ( \Exception $e ) {
				$team_id = null;
			}
		}

		// bail if no original item id exists or item is not related to a team
		if ( ! $refunded_item_id || ! in_array( $team_id, $team_ids, true ) ) {
			return false;
		}

		$team = wc_memberships_for_teams_get_team( $team_id );

		// if the team is no more, we don't can't won't give ya any meaningful info
		if ( ! $team instanceof Team ) {
			return false;
		}

		$refund_quantity  = abs( $item->get_quantity() );
		$refund_total     = abs( $item->get_total() );
		$refund_total_tax = abs( $item->get_total_tax() );

		// we need one of these to handle a team refund
		if ( ! $refund_quantity && ! $refund_total && ! $refund_total_tax ) {
			return false;
		}

		$total_refunded_quantity = abs( $order->get_qty_refunded_for_item( $refunded_item_id ) );
		$refunded_item           = new \WC_Order_Item_Product( $refunded_item_id );

		// determine if this is a partial quantity refund - note that if this refund will result in all of the purchased
		// quantity being refunded, it's not considered a partial quantity refund, but rather a full refund
		$has_partial_quantity = $refund_quantity && $refund_quantity < $refunded_item->get_quantity() && $total_refunded_quantity !== $refunded_item->get_quantity();

		// determine if this is a refund with a partial total - currently partial total refunds are not supported,
		// note that this is ignored if the refund quantity is not partial
		$has_partial_total = ! $refund_quantity && round( $refund_total + $refund_total_tax, wc_get_rounding_precision() ) != round( $refunded_item->get_total() + $refunded_item->get_total_tax(), wc_get_rounding_precision() );

		// this will only be set if doing a partial refund, as otherwise it's not applicable
		$refund_seat_count = null;
		$product           = $refunded_item->get_product();

		// determine the number of seats being refunded
		if ( $has_partial_quantity ) {
			$refund_seat_count = $product ? ( Product::has_per_member_pricing( $product ) ? $refund_quantity : $refund_quantity * Product::get_max_member_count( $product ) ) : $refund_quantity;
		}

		return array(
			'refunded_item_id'     => $refunded_item_id,
			'refunded_item'        => $refunded_item,
			'team_id'              => $team_id,
			'team'                 => $team,
			'product'              => $product,
			'refund_quantity'      => $refund_quantity,
			'refund_total'         => $refund_total,
			'refund_total_tax'     => $refund_total_tax,
			'refund_seat_count'    => $refund_seat_count,
			'has_partial_quantity' => $has_partial_quantity,
			'has_partial_total'    => $has_partial_total,
		);
	}


	/**
	 * Returns a list of team ids tied to an order, skipping those that are refunded.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id the order id to check
	 * @return array an array of team ids (may be empty)
	 */
	private function get_unrefunded_team_ids_by_order_id( $order_id ) {
		global $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "
			SELECT pm.post_id FROM $wpdb->postmeta pm
			INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
			LEFT JOIN $wpdb->postmeta pm2 ON pm.post_id = pm2.post_id AND pm2.meta_key = '_order_refunded'
			WHERE p.post_type = 'wc_memberships_team'
			AND pm.meta_key = '_order_id'
			AND pm.meta_value = %d
			AND ( pm2.meta_value IS NULL OR pm2.meta_value != 'yes' )
		", $order_id ) );
	}

}
