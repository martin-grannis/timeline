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
 * Team class. Represents a single team.
 *
 * @since 1.0.0
 */
class Team {


	/** @var int team (post) ID */
	private $id;

	/** @var string team name */
	private $name;

	/** @var string team creation date */
	private $date;

	/** @var string team creation date in UTC */
	private $date_gmt;

	/** @var int team owner (author) id */
	private $owner_id;

	/** @var int team plan id */
	private $plan_id;

	/** @var string team registration key */
	private $registration_key;

	/** @var \WC_Memberships_Membership_Plan team plan */
	private $plan;

	/** @var \WP_Post team post object */
	private $post;

	/** @var \WC_Product the product that granted access */
	private $product;

	/** @var string seat count meta */
	protected $seat_count_meta = '_seat_count';

	/** @var string product id meta */
	protected $product_id_meta = '_product_id';

	/** @var string order id meta */
	protected $order_id_meta = '_order_id';

	/** @var string member id meta */
	protected $member_id_meta = '_member_id';

	/** @var string membership end date meta */
	protected $membership_end_date_meta = '_membership_end_date';

	/** @var string meta data key for storing a lock when performing operation sensitive to race conditions */
	protected $locked_meta = '_locked';

	/** @var string meta data key for storing a login token for automatic login */
	protected $renewal_login_token_meta = '_renewal_login_token';

	/** @var string user team role meta */
	protected $user_team_role_meta;

	/** @var string user team role meta */
	protected $user_team_added_date_meta;


	/**
	 * Creates the team instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post $id team id or related post object
	 * @throws Framework\SV_WC_Plugin_Exception when post object isn't set
	 */
	public function __construct( $id ) {

		if ( is_numeric( $id ) ) {
			$this->post = get_post( $id );
		} elseif ( $id instanceof \WP_Post ) {
			$this->post = $id;
		}

		if ( ! $this->post || 'wc_memberships_team' !== $this->post->post_type ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid id or post', 'woocommerce-memberships-for-teams' ) );
		}

		// load in post data...
		$this->id               = (int) $this->post->ID;
		$this->name             = $this->post->post_title;
		$this->date             = $this->post->post_date;
		$this->date_gmt         = $this->post->post_date_gmt;
		$this->owner_id         = (int) $this->post->post_author;
		$this->plan_id          = (int) $this->post->post_parent;
		$this->registration_key = $this->post->post_password;

		$this->user_team_role_meta       = "_wc_memberships_for_teams_team_{$this->id}_role";
		$this->user_team_added_date_meta = "_wc_memberships_for_teams_team_{$this->id}_added_date";
	}


	/**
	 * Returns the team ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int team ID
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the owner's ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int user ID
	 */
	public function get_owner_id() {
		return $this->owner_id;
	}


	/**
	 * Returns the owner user.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User|false
	 */
	public function get_owner() {
		return get_userdata( $this->get_owner_id() );
	}


	/**
	 * Sets the plan ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $plan_id membership plan ID
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function set_plan_id( $plan_id ) {

		$plan_id = is_numeric( $plan_id ) ? (int) $plan_id : 0;

		if ( ! $plan_id || ! wc_memberships_get_membership_plan( $plan_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid membership plan', 'woocommerce-memberships-for-teams' ) );
		}

		if ( $this->has_active_members() ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Cannot change plan while team has active members', 'woocommerce-memberships-for-teams' ) );
		}

		wp_update_post( array(
			'ID'          => $this->id,
			'post_parent' => $plan_id,
		) );

		$this->plan_id           = $plan_id;
		$this->post->post_parent = $plan_id;

		unset( $this->plan );
	}


	/**
	 * Returns the plan ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int membership plan ID
	 */
	public function get_plan_id() {
		return $this->plan_id;
	}


	/**
	 * Returns the plan object.
	 *
	 * TODO: Note that the `$this` arg passed to wc_memberships_get_membership_plan() and the
	 * wc_memberships_membership_plan filter is the team instance, which isn't officially supported,
	 * but it works for now. We might want to refactor core to support custom context arguments. {IT 2017-11-13}
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_Membership_Plan
	 */
	public function get_plan() {

		if ( ! $this->plan ) {

			// get the plan if not already set
			$this->plan = $plan = wc_memberships_get_membership_plan( $this->plan_id, $this );

		} else {

			// get the plan already set but make sure it comes out filtered
			$plan = $this->plan;
			$post = ! empty( $this->plan ) ? $plan->post : null;

			/** this filter is documented in woocommerce-memberships/includes/class-wc-memberships-membership-plans.php */
			$plan = apply_filters( 'wc_memberships_membership_plan', $plan, $post, $this );
		}

		return $plan;
	}


	/**
	 * Sets the team name.
	 *
	 * @since 1.0.1
	 *
	 * @param string $name new team name
	 */
	public function set_name( $name ) {

		$name = (string) $name;

		if ( $this->get_name() !== $name ) {
			wp_update_post( array(
				'ID'         => $this->get_id(),
				'post_title' => sanitize_title( $name ),
			) );
		}
	}


	/**
	 * Returns the team name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Gets the team name with its ID.
	 *
	 * @since 1.1.2
	 *
	 * @return string
	 */
	public function get_formatted_name() {

		return sprintf( '%1$s (#%2$s)', $this->get_name(), $this->get_id() );
	}


	/**
	 * Gets the team slug.
	 *
	 * @since 1.1.2
	 *
	 * @return string
	 */
	public function get_slug() {

		$post_name = $this->post ? $this->post->post_name : '';

		return empty( $post_name ) ? sanitize_title( $this->get_name() ) : $post_name;
	}


	/**
	 * Returns the local team creation date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string
	 */
	public function get_local_date( $format = 'mysql' ) {
		return wc_memberships_format_date( $this->date, $format );
	}


	/**
	 * Returns the team creation date in UTC.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string
	 */
	public function get_date( $format = 'mysql' ) {
		return wc_memberships_format_date( $this->date_gmt, $format );
	}


	/**
	 * Returns the team membership end date in UTC.
	 *
	 * TODO: this method could be improved by allowing to specify the plan ID, to support multiple plans per team in the future {IT 2017-09-15}
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $date end date either as a unix timestamp or mysql datetime string - defaults to empty string (unlimited membership, no end date)
	 * @param bool $adjust_user_memberships (optional) whether to adjust end dates of user membership that are part of this team as well, defaults to true
	 */
	public function set_membership_end_date( $date, $adjust_user_memberships = true ) {

		$end_timestamp = '';
		$end_date      = '';

		if ( is_numeric( $date ) ) {
			$end_timestamp = (int) $date;
		} elseif ( is_string( $date ) ) {
			$end_timestamp = strtotime( $date );
		}

		if ( ! empty( $end_timestamp ) ) {

			// for fixed date memberships set end date to the end of the day
			$end_timestamp = $this->get_plan() && $this->get_plan()->is_access_length_type( 'fixed' ) ? wc_memberships_adjust_date_by_timezone( strtotime( 'midnight', $end_timestamp ), 'timestamp', wc_timezone_string() ) : $end_timestamp;
			$end_date      = date( 'Y-m-d H:i:s', (int) $end_timestamp );
		}

		$previous_end_timestamp = $this->get_membership_end_date( 'timestamp' );

		// update end date in post meta
		update_post_meta( $this->id, $this->membership_end_date_meta, $end_date );

		// set expiration scheduled events
		$this->schedule_expiration_events( $end_timestamp );

		if ( $adjust_user_memberships ) {

			$now        = current_time( 'timestamp', true );
			$difference = $previous_end_timestamp ? $end_timestamp - $previous_end_timestamp : 0; // if was unlimited length, use 0 as difference, otherwise user membership end dates will be pushed back the full unix timestamp

			foreach ( $this->get_user_memberships() as $user_membership ) {

				if ( ! $end_timestamp ) {

					// team has unlimited membership length, apply it to user memberships as well
					$user_membership_end_timestamp = '';

				} else {

					$end   = $user_membership->get_end_date( 'timestamp' );
					$since = ! empty( $end ) && $end > $now ? $end : $now;

					// adjust user membership end date by the difference in team membership end date
					$user_membership_end_timestamp = $since + $difference;
				}

				$user_membership->set_end_date( $user_membership_end_timestamp );

				// reactivate if expired (note that cancelled memberships are not reactivated), set as expired if new end date is in the past
				if ( ( ! $user_membership_end_timestamp || $user_membership_end_timestamp > $now ) && $user_membership->is_expired() ) {
					$user_membership->update_status( 'active' );
				} elseif ( $user_membership_end_timestamp && $user_membership_end_timestamp <= $now ) {
					$user_membership->update_status( 'expired' );
				}
			}
		}
	}


	/**
	 * Returns the team membership end date in UTC.
	 *
	 * TODO: this method could be improved by allowing to specify the plan ID, to support multiple plans per team in the future {IT 2017-09-15}
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string|null
	 */
	public function get_membership_end_date( $format = 'mysql' ) {

		$date = get_post_meta( $this->id, $this->membership_end_date_meta, true );

		return $date ? wc_memberships_format_date( $date, $format ) : null;
	}


	/**
	 * Returns the team membership end date in local timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string|null
	 */
	public function get_local_membership_end_date( $format = 'mysql' ) {

		$date = $this->get_membership_end_date( 'timestamp' );

		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Checks whether the team membership is expired or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if expired, false otherwise
	 */
	public function is_membership_expired() {

		$date = $this->get_membership_end_date( 'timestamp' );

		return $date ? $date <= current_time( 'timestamp', true ) : false;
	}


	/**
	 * Returns the number of remaining seats on team.
	 *
	 * @since 1.0.0
	 *
	 * @return int remaining seat count
	 */
	public function get_remaining_seat_count() {
		return max( $this->get_seat_count() - $this->get_used_seat_count(), 0 );
	}


	/**
	 * Returns the number of used seats.
	 *
	 * @since 1.0.0
	 *
	 * @return int used seat count
	 */
	public function get_used_seat_count() {

		return $this->get_member_count() + $this->get_invitation_count();
	}


	/**
	 * Returns the total number of members in the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $role (optional) - when provided, total number of members with the role will be returned
	 * @return int number of members
	 */
	public function get_member_count( $role = null ) {

		$member_ids = $this->get_member_ids();

		if ( empty( $member_ids ) ) {
			return 0;
		}

		$args = array(
			'include' => $member_ids,
			'number'  => -1,
			'fields'  => 'ID',
		);

		if ( ! empty( $role ) && wc_memberships_for_teams_is_valid_team_member_role( $role ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => $this->user_team_role_meta,
					'value' => $role
				),
			);
		}

		// we use get_users instead of a direct SQL query to benefit from WP's internal caching
		$user_ids = get_users( $args );

		return count( $user_ids );
	}


	/**
	 * Checks whether the team has active members or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if has active members, false otherwise
	 */
	public function has_active_members() {
		return $this->get_member_count() > 0;
	}


	/**
	 * Returns the total number of invitations for the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status (optional) - the invitation status to return the count for, defaults to 'pending'
	 * @return int invitation count
	 */
	public function get_invitation_count( $status = 'pending' ) {

		$args = array(
			'post_type'   => 'wc_team_invitation',
			'post_status' => 'wcmti-pending',
			'post_parent' => $this->id,
			'numberposts' => -1,
			'fields'      => 'ids',
		);

		if ( 'pending' !== $status ) {
			$args['post_status'] = Framework\SV_WC_Helper::str_starts_with( $status, 'wcmti-' ) ? $status : 'wcmti-' . $status;
		}

		// we use get_posts instead of a direct SQL query to benefit from WP's internal caching
		$post_ids = get_posts( $args );

		return count( $post_ids );
	}


	/**
	 * Sets the seat count for the team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $count seat count, or null if unlimited
	 */
	public function set_seat_count( $count = null ) {

		$count = is_numeric( $count ) ? (int) $count : '';

		update_post_meta( $this->id, $this->seat_count_meta, $count );
	}


	/**
	 * Adjusts the seat count for the team.
	 *
	 * Will not allow seat count go below 0.
	 *
	 * @since 1.0.0
	 *
	 * @param int $amount seat count to adjust by
	 * @param string $action one of 'add' or 'remove'
	 */
	public function adjust_seat_count( $amount, $action ) {

		$seat_count = $this->get_seat_count();

		if ( 'add' === $action ) {
			$seat_count = $seat_count + $amount;
		} else {
			$seat_count = max( 0, $seat_count - $amount );
		}

		$this->set_seat_count( $seat_count );
	}


	/**
	 * Returns the seat count for the team.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null
	 */
	public function get_seat_count() {

		$seat_count = (int) get_post_meta( $this->id, $this->seat_count_meta, true );

		return $seat_count ? $seat_count : null;
	}


	/**
	 * Sets the order id that created the team.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id \WC_Order ID
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function set_order_id( $order_id ) {

		$order_id = is_numeric( $order_id ) ? (int) $order_id : 0;

		// check that the id belongs to an actual product
		if ( ! $order_id || ! wc_get_order( $order_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid order', 'woocommerce-memberships-for-teams' ) );
		}

		update_post_meta( $this->id, $this->order_id_meta, $order_id );
	}


	/**
	 * Returns the order id that created this team.
	 *
	 * @since 1.0.0
	 *
	 * @return null|int order id
	 */
	public function get_order_id() {

		$order_id = get_post_meta( $this->id, $this->order_id_meta, true );

		return $order_id ? (int) $order_id : null;
	}


	/**
	 * Returns the order that created the team.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Order|false|null
	 */
	public function get_order() {

		$order_id = $this->get_order_id();

		return $order_id ? wc_get_order( $order_id ) : null;
	}


	/**
	 * Removes the order information.
	 *
	 * @since 1.0.0
	 */
	public function delete_order_id() {

		delete_post_meta( $this->id, $this->order_id_meta );
	}


	/**
	 * Checks whether the team order has been refunded or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_order_refunded() {
		return 'yes' === get_post_meta( $this->id, '_order_refunded', true );
	}


	/**
	 * Returns the product id that crated the team.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null product id
	 */
	public function get_product_id() {

		$product_id = get_post_meta( $this->id, $this->product_id_meta, true );

		return $product_id ? (int) $product_id : null;
	}


	/**
	 * Returns the product that created the team.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Product|false|null
	 */
	public function get_product() {

		$product_id = $this->get_product_id();

		if ( ! isset( $this->product ) ) {
			$this->product = $product_id ? wc_get_product( $product_id ) : null;
		}

		return $this->product;
	}


	/**
	 * Sets the product ID that granted access.
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id \WC_Product ID
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function set_product_id( $product_id ) {

		$product_id = is_numeric( $product_id ) ? (int) $product_id : 0;

		// check that the id belongs to an actual product
		if ( ! $product_id || ! wc_get_product( $product_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid product', 'woocommerce-memberships-for-teams' ) );
		}

		update_post_meta( $this->id, $this->product_id_meta, $product_id );

		unset( $this->product );
	}


	/**
	 * Removes information about the product that created the team.
	 *
	 * @since 1.0.0
	 */
	public function delete_product_id() {

		delete_post_meta( $this->id, $this->product_id_meta );
		unset( $this->product );
	}


	/**
	 * Returns a list of ids of all users who are members of this team.
	 *
	 * @since 1.0.0
	 *
	 * @return int[] an array of user ids
	 */
	public function get_member_ids() {
		return array_filter( array_map( 'intval', (array) get_post_meta( $this->id, $this->member_id_meta ) ) ); // must return an array
	}


	/**
	 * Returns a list of team members.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args query arguments
	 * @param string $return (optional) what to return - set to 'query' to return the \WP_User_Query instance instead of a list of team member instances
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member[]|\array|false $team_members an array of team members, associative array of query results or false on failure
	 */
	public function get_members( $args = array(), $return = null ) {
		return wc_memberships_for_teams_get_team_members( $this, $args, $return );
	}


	/**
	 * Returns a list of ids of all user memberships that are part of this team.
	 *
	 * @since 1.0.0
	 *
	 * @return int[] an array of user membership ids
	 */
	public function get_user_membership_ids() {
		global $wpdb;

		return $wpdb->get_col( $wpdb->prepare( "
			SELECT p.ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm
			ON p.ID = pm.post_id
			WHERE p.post_type = 'wc_user_membership'
			AND pm.meta_key = '_team_id'
			AND pm.meta_value = %d
		", $this->id ) );
	}


	/**
	 * Returns a list of all user memberships that are part of this team.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_User_Membership[] an array of user memberships
	 */
	public function get_user_memberships() {

		$user_memberships = array();

		foreach ( $this->get_user_membership_ids() as $user_membership_id ) {

			if ( $user_membership = wc_memberships_get_user_membership( $user_membership_id ) ) {

				$user_memberships[ $user_membership->get_id() ] = $user_membership;
			}
		}

		return $user_memberships;
	}


	/**
	 * Adds a member to the team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_User $user_id the user to add as a member
	 * @param string $role (optional) user's role in team, either `member` or `manager`, defaults to `member`, if adding the owner, role will me set to 'owner' automatically and cannot be overridden
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member the team member instance
	 * @throws Framework\SV_WC_Plugin_Exception|\Exception
	 */
	public function add_member( $user_id, $role = 'member' ) {

		$user_id = $user_id instanceof \WP_User ? $user_id->ID : $user_id;

		// sanity check - can't add the same user twice
		if ( $this->is_user_member( $user_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'User is already a member of the team', 'woocommerce-memberships-for-teams' ) );
		}

		// if a falsy value was provided for role, default to 'member'
		if ( ! $role ) {
			$role = 'member';
		}

		if ( ! wc_memberships_for_teams_is_valid_team_member_role( $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid role', 'woocommerce-memberships-for-teams' ) );
		}

		$seat_count      = $this->get_seat_count();
		$used_seat_count = $this->get_used_seat_count();

		// adjust used seat count when adding a member by accepting an invitation, as to not
		// throw when invite + member count >= seat count, as this would make it impossible
		// to accept invites
		if ( $seat_count > 0 && $used_seat_count >= $seat_count ) {

			$user = get_userdata( $user_id );
			$invitation = $this->get_invitation( $user->user_email );

			if ( $invitation && $invitation->has_status( 'pending' ) ) {
				$used_seat_count--;
			}
		}

		// ensure there's a free seat available for the member
		if ( $seat_count > 0 && $used_seat_count >= $seat_count ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'No more seats left', 'woocommerce-memberships-for-teams' ) );
		}

		if ( $this->is_user_owner( $user_id ) ) {
			$role = 'owner';
		}

		if ( ! $this->can_add_member( $user_id, $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( $this->get_management_decline_reason() );
		}

		/**
		 * Fires before a member is added to a team.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id user id
		 * @param string $role the member's role in team
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		do_action( 'wc_memberships_for_teams_before_add_team_member', $user_id, $role, $this );

		add_post_meta( $this->id, $this->member_id_meta, $user_id );

		// set user meta - note that this data ius stored on user meta instead of the team meta so that it's easier to use with \WP_User_Query
		$date = date( 'Y-m-d H:i:s', current_time( 'timestamp', true ) );

		update_user_meta( $user_id, $this->user_team_role_meta, $role );
		update_user_meta( $user_id, $this->user_team_added_date_meta, $date );

		// look for an existing user membership or create a new one
		if ( $user_membership = $this->get_existing_user_membership( $user_id ) ) {

			// if this user membership was part of another team, remove the user from that team, but
			// keep the user membership
			// TODO: this will need refactoring if/when adding support for multiple plans per team {IT 2017-09-26}
			if ( $previous_team = wc_memberships_for_teams_get_user_membership_team( $user_membership->get_id() ) ) {
				$previous_team->remove_member( $user_id, true, false ); // don't add a note, yet
			}

			// update user membership
			$user_membership->set_product_id( $this->get_product_id() );
			$user_membership->set_order_id( $this->get_order_id() );

			$team_membership_end_date = $this->get_membership_end_date( 'timestamp' );

			// extend length if team provides longer access
			if ( ! $team_membership_end_date || $user_membership->get_end_date( 'timestamp' ) < $team_membership_end_date ) {
				$user_membership->set_end_date( $team_membership_end_date );
			}

			// add a note about the membership transition
			if ( $previous_team ) {
				/* translators: Placeholders: %1$s - previous team name, %2$s - new team name */
				$note = sprintf( __( 'Team membership moved from %1$s to %2$s.', 'woocommerce-memberships-for-teams' ), $previous_team->get_name(), $this->get_name() );
			} else {
				/* translators: Placeholder: %s team name */
				$note = sprintf( __( 'Individual membership converted to %s team membership.', 'woocommerce-memberships-for-teams' ), $this->get_name() );
			}

			// activate the membership, unless end date is in past - this will activate expired or cancelled
			// memberships, as well as unpause any paused memberships
			if ( $user_membership->get_end_date( 'timestamp' ) > current_time( 'timestamp', true ) ) {
				$user_membership->update_status( 'active' );
			}

		} else {

			$args = array(
				'user_id'    => $user_id,
				'plan_id'    => $this->get_plan_id(),
				'order_id'   => $this->get_order_id(),
				'product_id' => $this->get_product_id(),
			);

			// create user membership
			$user_membership = wc_memberships_create_user_membership( $args );

			$note = sprintf( __( 'Membership access granted from %s team.', 'woocommerce-memberships-for-teams' ), $this->get_name() );
		}

		$user_membership->add_note( $note );

		// store a reference to the team on the user membership
		update_post_meta( $user_membership->id, '_team_id', $this->id );

		$member     = wc_memberships_for_teams_get_team_member( $this, $user_id );
		$invitation = $member ? $this->get_invitation( $member->get_email() ) : null;

		// sanity check in case an invitation for a newly added user couldn't be resolved while registering
		if ( $invitation && 'pending' === $invitation->get_status() ) {
			$invitation->accept( $member->get_user(), false );
		}

		/**
		 * Fires after a member is added to a team.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member $member the team member instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 * @param \WC_Memberships_User_Membership $user_membership the related user membership instance
		 */
		do_action( 'wc_memberships_for_teams_add_team_member', $member, $this, $user_membership );

		return $member;
	}


	/**
	 * Removes a member from the team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $user_id the user id to remove, or an email if cancelling an invitation
	 * @param bool $keep_user_memberships (optional) set to true to keep user memberships as a standalone memberships outside the team, defaults to false
	 * @param bool $add_note (optional) whether to add a note to the membership if keeping it, defaults to true
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function remove_member( $user_id, $keep_user_memberships = false, $add_note = true ) {

		// cancel invitation in case an email was provided
		if ( is_email( $user_id ) ) {
			$this->cancel_invitation( $user_id );
			return;
		}

		// sanity check - don't try remove someone who's not a member
		if ( ! $this->is_user_member( $user_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'User is not a member of the team', 'woocommerce-memberships-for-teams' ) );
		}

		$member = wc_memberships_for_teams_get_team_member( $this, $user_id );

		if ( ! $this->can_remove_member( $member ) ) {
			throw new Framework\SV_WC_Plugin_Exception( $this->get_management_decline_reason() );
		}

		// sanity check in case of deleted memberships
		$user_membership_ids = is_callable( array( $member, 'get_user_memberships' ) ) ? $member->get_user_memberships( 'ids' ) : array();

		/**
		 * Fires right before a member is removed from a team.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id the id of the user (team member) that is about to be removed
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member the team member instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team the team instance
		 */
		do_action( 'wc_memberships_for_teams_before_remove_team_member', $user_id, $member, $this );

		// remove reference to user id from team
		delete_post_meta( $this->id, $this->member_id_meta, $user_id );

		// remove user meta
		delete_user_meta( $user_id, $this->user_team_role_meta );
		delete_user_meta( $user_id, $this->user_team_added_date_meta );

		// untie or delete user memberships
		if ( ! empty( $user_membership_ids ) ) {
			foreach ( $user_membership_ids as $user_membership_id ) {

				if ( $keep_user_memberships ) {

					delete_post_meta( $user_membership_id, '_team_id' );

					if ( $add_note ) {
						$user_membership = wc_memberships_get_user_membership( $user_membership_id );

						if ( $user_membership ) {
							/* translators: Placeholder: %s - team name */
							$user_membership->add_note( sprintf( __( 'Member removed from %s team.', 'woocommerce-memberships-for-teams' ), $this->get_name() ) );
						}
					}

				} else {
					wp_delete_post( $user_membership_id, true );
				}
			}
		}

		/**
		 * Fires right after a member is removed from a team.
		 *
		 * @since 1.0.0
		 *
		 * @param int $user_id the id of the user (team member) that was removed
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team the team instance
		 */
		do_action( 'wc_memberships_for_teams_after_remove_team_member', $user_id, $this );
	}


	/**
	 * Checks whether a user is the owner of the team or not.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id the user id to check
	 * @return bool
	 */
	public function is_user_owner( $user_id ) {

		$user_id = (int) $user_id;

		return $user_id === $this->get_owner_id();
	}


	/**
	 * Checks whether a user is a member of the team or not.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id the user id to check
	 * @return bool
	 */
	public function is_user_member( $user_id ) {

		return in_array( (int) $user_id, $this->get_member_ids(), true );
	}


	/**
	 * Invites a person to join the team via email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email email to invite
	 * @param string $role (optional) role to assign to the member, defaults to 'member'
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitation invitation instance
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function invite( $email, $role = 'member' ) {

		if ( ! is_email( $email ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid email', 'woocommerce-memberships-for-teams' ), 1 );
		}

		// if a falsy value was provided for role, default to 'member'
		if ( ! $role ) {
			$role = 'member';
		}

		if ( ! wc_memberships_for_teams_is_valid_team_member_role( $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid role', 'woocommerce-memberships-for-teams' ), 2 );
		}

		// only one invitation per email, no +1's ;)
		$existing_invitation = $this->get_invitation( $email );

		if ( $existing_invitation && $existing_invitation->has_status( 'pending' ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Already invited', 'woocommerce-memberships-for-teams' ), 3 );
		}

		$user = get_user_by( 'email', $email );

		// sanity check - can't invite someone who is already a member
		if ( $user && $this->is_user_member( $user->ID ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'User is already a member of the team', 'woocommerce-memberships-for-teams' ), 4 );
		}

		$seat_count      = $this->get_seat_count();
		$used_seat_count = $this->get_used_seat_count();

		if ( $seat_count > 0 && $used_seat_count >= $seat_count ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'No more seats left', 'woocommerce-memberships-for-teams' ), 5 );
		}

		if ( ! $this->can_invite_user( $user, $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( $this->get_management_decline_reason() );
		}

		/**
		 * Fires before someone is invited to a team.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email invitation recipient email
		 * @param string $role role for the invited user
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		do_action( 'wc_memberships_for_teams_before_invite_to_team', $email, $role, $this );

		// TODO: for now, I've opted for consistency, so all users will get an invitation email, but we can also
		// choose to add users with no existing membership on the team plan to be added instantly {IT 2017-08-31}

		$invitation = wc_memberships_for_teams_create_invitation( array(
			'team_id' => $this,
			'email'   => $email,
			'role'    => $role,
		) );

		$invitation->send();

		/**
		 * Fires after someone is invited to a team.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email invitation recipient email
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		do_action( 'wc_memberships_for_teams_invite_to_team', $email, $invitation, $this );

		return $invitation;
	}


	/**
	 * Cancels a pending invitation for the given email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email recipient's email
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function cancel_invitation( $email ) {

		$invitation = $this->get_invitation( $email );

		// sanity check - don't try to cancel an invite if there isn't one
		if ( ! $invitation ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'No invitation found', 'woocommerce-memberships-for-teams' ) );
		}

		$invitation->cancel();
	}


	/**
	 * Returns an invitation given the recipient's email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email the recipient's email
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitation invitation instance
	 */
	public function get_invitation( $email ) {
		return wc_memberships_for_teams_get_invitation( $this, $email );
	}


	/**
	 * Checks whether the given email has been invited to the team or not.
	 *
	 * @param string $email the recipient's email
	 * @return bool true if has an invitation, false otherwise
	 */
	public function has_invitation( $email ) {
		return (bool) $this->get_invitation( $email );
	}


	/**
	 * Returns a list of invitations for this team.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args query arguments
	 * @param string $return (optional) what to return - set to 'query' to return the \WP_Query instance instead of a list of invitation instances
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitation[]|\WP_Query|false $invitations an array of invitations, associative array of query results or false on failure
	 */
	public function get_invitations( $args = array(), $return = null ) {
		return wc_memberships_for_teams_get_invitations( $this, $args, $return );
	}


	/**
	 * Returns the admin edit url for the team.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $remove_action whether to remove the default 'edit' action
	 * @return string url to the team edit screen
	 */
	public function get_edit_url( $remove_action = true ) {

		$url = get_edit_post_link( $this->id, false );

		if ( $remove_action ) {
			$url = remove_query_arg( 'edit' , $url );
		}

		return $url;
	}


	/**
	 * Regenerates the registration key for this team.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function generate_registration_key() {

		$this->registration_key = $this->post->post_password = Plugin::generate_token();

		wp_update_post( array(
			'ID'            => $this->id,
			'post_password' => $this->registration_key,
		) );

		return $this->registration_key;
	}


	/**
	 * Returns the registration key for this team.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_registration_key() {
		return $this->registration_key;
	}


	/**
	 * Returns the registration url for this team, or an invitation accept url if provided with an invitaton token.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token (optional) invitation token
	 * @return string|false
	 */
	public function get_registration_url( $token = null ) {

		$key = $token ? $token : $this->get_registration_key();

		if ( ! $key ) {
			return false;
		}

		$endpoint = get_option( 'woocommerce_myaccount_join_team_endpoint', 'join-team' );
		$url      = wc_get_account_endpoint_url( $endpoint );

		// Return an URL according to rewrite structure used:
		if ( get_option( 'permalink_structure' ) ) {

			// Using permalinks:
			// e.g. /my-account/join-team/{token}
			$url = trailingslashit( wc_get_account_endpoint_url( $endpoint ) ) . $key;

		} else {

			// Not using permalinks:
			// e.g. /?page_id=123&join_team={token}
			$url = add_query_arg( $endpoint, $key, $url );
		}

		return $url;
	}


	/**
	 * Returns the user team role meta key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_user_team_role_meta_key() {
		return $this->user_team_role_meta;
	}


	/**
	 * Returns the user team added date meta key.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_user_team_added_date_meta_key() {
		return $this->user_team_added_date_meta;
	}


	/**
	 * Returns user's existing user membership for the team's plan, if they have any.
	 *
	 * This will return both individual (standalone) memberships or memberships from another team.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_User_Membership|null user membership instance or null if not found
	 */
	public function get_existing_user_membership( $user_id ) {

		$args = array(
			'author'      => $user_id,
			'post_type'   => 'wc_user_membership',
			'post_parent' => $this->get_plan_id(),
			'post_status' => 'any',
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => '_team_id',
					'value'   => $this->id,
					'compare' => '!='
				),
				array(
					'key'     => '_team_id',
					'compare' => 'NOT EXISTS'
				),
			),
		);

		$user_memberships = get_posts( $args );
		$post             = ! empty( $user_memberships ) ? $user_memberships[0] : null;

		return $post ? wc_memberships_get_user_membership( $post ) : null;
	}


	/**
	 * Unschedules expiration events.
	 *
	 * @since 1.0.0
	 */
	public function unschedule_expiration_events() {

		$hook_args = array( 'team_id' => $this->id );

		// set a post meta to use as a lock to ensure all events are unscheduled before scheduling new ones
		if ( ! get_post_meta( $this->id, $this->locked_meta, true ) ) {
			add_post_meta( $this->id, $this->locked_meta, true, true );
		}

		// unschedule any previous expiry hooks
		if ( as_next_scheduled_action( 'wc_memberships_for_teams_team_membership_expiry', $hook_args, 'woocommerce-memberships-for-teams'  ) ) {
			as_unschedule_action( 'wc_memberships_for_teams_team_membership_expiry', $hook_args, 'woocommerce-memberships-for-teams' );
		}

		// unschedule any previous expiring soon hooks
		if ( as_next_scheduled_action( 'wc_memberships_for_teams_team_membership_expiring_soon', $hook_args, 'woocommerce-memberships-for-teams' ) ) {
			as_unschedule_action( 'wc_memberships_for_teams_team_membership_expiring_soon', $hook_args, 'woocommerce-memberships-for-teams' );
		}

		// unschedule any previous renewal reminder hooks
		if ( as_next_scheduled_action( 'wc_memberships_for_teams_team_membership_renewal_reminder', $hook_args, 'woocommerce-memberships-for-teams' ) ) {
			as_unschedule_action( 'wc_memberships_for_teams_team_membership_renewal_reminder', $hook_args, 'woocommerce-memberships-for-teams' );
		}

		// remove the lock
		delete_post_meta( $this->id, $this->locked_meta );
	}


	/**
	 * Sets expiration events for this team.
	 *
	 * Note: the renewal reminder is only set contextually when the membership is expired.
	 *
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Team::set_end_date()
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Teams_Handler::trigger_expiration_events()
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $end_timestamp team membership end date timestamp: when empty (unlimited membership), it will just clear any existing scheduled event
	 */
	public function schedule_expiration_events( $end_timestamp = null ) {

		$now = current_time( 'timestamp', true );

		// always unschedule events for the same team first
		$this->unschedule_expiration_events();

		// avoid race conditions by introducing a recursion if a lock is found
		if ( get_post_meta( $this->id, $this->locked_meta, true ) ) {
			$this->schedule_expiration_events( $end_timestamp );
			return;
		}

		// schedule team membership expiration hooks, provided there's an end date and it's after the beginning of today's date
		if ( is_numeric( $end_timestamp ) && (int) $end_timestamp > strtotime( 'today', $now ) ) {

			$hook_args = array( 'team_id' => $this->id );

			// Schedule the membership expiration event:
			as_schedule_single_action( $end_timestamp, 'wc_memberships_for_teams_team_membership_expiry', $hook_args, 'woocommerce-memberships-for-teams' );

			// Schedule the membership ending soon event:
			$days_before_expiry = $this->get_expiring_soon_time_before( $end_timestamp );

			if ( $end_timestamp - $days_before_expiry >= DAY_IN_SECONDS ) {

				if ( $days_before_expiry > $now ) {
					// if there's at least one day before the expiry date, use the email setting (days before)
					as_schedule_single_action( $days_before_expiry, 'wc_memberships_for_teams_team_membership_expiring_soon', $hook_args, 'woocommerce-memberships-for-teams' );
				} elseif ( $end_timestamp > $now && $median_time = absint( ( $now + $end_timestamp ) / 2 ) ) {
					// if it's less than one day, schedule as a median time between now and the effective end date (in the course of the last remaining day)
					as_schedule_single_action( $median_time, 'wc_memberships_for_teams_team_membership_expiring_soon', $hook_args, 'woocommerce-memberships-for-teams' );
				}
			}
		}
	}


	/**
	 * Returns the timestamp for days before expiry date.
	 *
	 * @see \WC_Memberships_User_Membership::get_expiring_soon_time_before()
	 *
	 * @since 1.0.0
	 *
	 * @param int $expiry_date timestamp when the membership expires
	 * @return int timestamp
	 */
	private function get_expiring_soon_time_before( $expiry_date ) {

		// the email that stores the setting
		$email = 'wc_memberships_for_teams_team_membership_ending_soon';

		/** @see \WC_Memberships_User_Membership_Ending_Soon_Email */
		$email_setting = get_option( "woocommerce_{$email}_settings" );

		if (    $email_setting
			 && isset( $email_setting['send_days_before'] )
			 && $days_before = absint( $email_setting['send_days_before'] ) ) {

			$time_before = $expiry_date - ( max( 1, $days_before ) * DAY_IN_SECONDS );

			// sanity check: the future can't be in the past :)
			return $time_before > current_time( 'timestamp', true ) ? $time_before : $expiry_date - DAY_IN_SECONDS;
		}

		// default value (3 days before)
		return $expiry_date - ( 3 * DAY_IN_SECONDS );
	}


	/**
	 * Checks whether the team membership can be renewed by the user.
	 *
	 * Note: does not check whether the user has capability to renew.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function can_be_renewed() {

		// check first if the status allows renewal
		$membership_plan = $this->plan instanceof \WC_Memberships_Membership_Plan ? $this->plan : $this->get_plan();
		$can_be_renewed  = true;

		if ( $membership_plan->is_access_length_type( 'fixed' ) ) {

			$fixed_end_date = $membership_plan->get_access_end_date( 'timestamp' );

			// fixed length memberships with an end date in the past
			// shouldn't be renewable (unless an admin changes the plan end date)
			if ( ! empty( $fixed_end_date ) && current_time( 'timestamp', true ) > $fixed_end_date ) {
				$can_be_renewed = false;
			}
		}

		$product = $this->get_product();

		// if the team product does not exist, is not purchasable anymore, or gives access to a different plan, the team can't be renewed
		if ( ! $product || ! $product->is_purchasable() || Product::get_membership_plan_id( $product ) !== $this->get_plan_id() ) {
			return false;
		}

		/**
		 * Filters whether a team membership can be renewed.
		 *
		 * This does not imply that it will be renewed but should meet the characteristics to be renewable by a user that has capability to renew.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_be_renewed whether can be renewed by a user
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team to renew membership for
		 */
		return (bool) apply_filters( 'wc_memberships_for_teams_team_membership_can_be_renewed', $can_be_renewed, $this );
	}


	/**
	 * Returns the renewal login token.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array of data
	 */
	public function get_renewal_login_token() {

		$token = get_post_meta( $this->id, $this->renewal_login_token_meta, true );

		return ! is_array( $token ) ? array() : $token;
	}


	/**
	 * Sets a renewal login token.
	 *
	 * @since 1.0.0
	 *
	 * @return array token data
	 */
	public function generate_renewal_login_token() {

		$user_token = array(
			'expires' => strtotime( '+30 days' ),
			'token'   => wp_generate_password( 32, false ),
		);

		update_post_meta( $this->id, $this->renewal_login_token_meta, $user_token );

		return $user_token;
	}


	/**
	 * Deletes the renewal login token.
	 *
	 * @since 1.0.0
	 */
	public function delete_renewal_login_token() {

		delete_post_meta( $this->id, $this->renewal_login_token_meta );
	}


	/**
	 * Returns the renew membership URL for frontend use.
	 *
	 * @since 1.0.0
	 *
	 * @return string renew URL (unescaped)
	 */
	public function get_renew_membership_url() {

		$user_token = $this->get_renewal_login_token();

		// See if we have an existing token we should be using first so we don't break URLs in previous emails.
		// Regenerate it if our token is expired anyway.
		if (      empty( $user_token )
			 || ! isset( $user_token['token'] )
			 ||   (int) $user_token['expires'] < time() ) {

			$user_token = $this->generate_renewal_login_token();
		}

		$renew_endpoint = wc_get_page_permalink( 'myaccount' );

		if ( false === strpos( $renew_endpoint, '?' ) ) {
			$renew_endpoint = trailingslashit( $renew_endpoint );
		}

		// use a user token rather than a nonce to validate the login request
		// given we don't want a 24 hr limit and a nonce isn't best for validating this anyway
		$renew_url = add_query_arg( array(
			'renew_team_membership' => $this->id,
			'user_token'            => $user_token['token'],
		), $renew_endpoint );

		/**
		 * Filters the renew team membership URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string $url URL
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the related team
		 */
		return (string) apply_filters( 'wc_memberships_get_renew_team_membership_url', $renew_url, $this );
	}


	/**
	 * Returns the team management status along with a possible decline message.
	 *
	 * @since 1.0.0
	 *
	 * @return array an associative array with 2 keys: `can_be_managed` and `message`
	 */
	public function get_management_status() {

		$status = array(
			'can_be_managed' => true,
			'messages'       => null,
		);

		if ( $this->is_order_refunded() ) {

			$status['can_be_managed'] = false;
			$status['message']        = array(
				'general'       => __( 'Team order has been refunded.', 'woocommerce-memberships-for-teams' ),
				'add_member'    => __( "Can't add more members because your team order has been refunded.", 'woocommerce-memberships-for-teams' ),
				'remove_member' => __( "Can't remove members because your team order has been refunded.", 'woocommerce-memberships-for-teams' ),
				'join_team'     => __( "Can't join team at the moment - please contact your team owner for more details.", 'woocommerce-memberships-for-teams' ),
			);
		}

		/**
		 * Filters the team management status.
		 *
		 * @since 1.0.0
		 *
		 * @param array an associative array with 2 keys: `can_be_managed` and `messages`
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the related team
		 */
		return apply_filters( 'wc_memberships_for_teams_team_management_status', $status, $this );
	}


	/**
	 * Checks whether the team can currently be managed or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function can_be_managed() {

		$status = $this->get_management_status();

		return isset( $status['can_be_managed'] ) ? (bool) $status['can_be_managed'] : false;
	}


	/**
	 * Checks whether a member can be added to the team.
	 *
	 * @since 1.1.3
	 *
	 * @param int|\WP_User $user_id user ID or object
	 * @param string $role member role (defaults to regular 'member')
	 * @return bool
	 */
	public function can_add_member( $user_id, $role = 'member' ) {

		/**
		 * Filters whether a user can be added as team member.
		 *
		 * @since 1.1.3
		 *
		 * @param bool $can_be_added true if team can be managed
		 * @param int $user_id ID of the user to add as a member
		 * @param Team $team the team object
		 * @param string $role invited member role
		 */
		return (bool) apply_filters( 'wc_memberships_for_teams_team_can_add_member', $this->can_be_managed(), $user_id, $this, $role );
	}


	/**
	 * Checks whether a member can be removed from the team.
	 *
	 * @since 1.1.3
	 *
	 * @param Team_Member $member team member
	 * @return bool
	 */
	public function can_remove_member( $member ) {

		/**
		 * Filters whether a member can be removed from a team.
		 *
		 * @since 1.1.3
		 *
		 * @param bool $can_be_removed true if the team can be managed
		 * @param Team_Member $member the member to be removed
		 * @param Team $team the team object
		 */
		return (bool) apply_filters( 'wc_memberships_for_teams_team_can_remove_member', $this->can_be_managed(), $member, $this );
	}


	/**
	 * Checks whether a user can be invited to join the team.
	 *
	 * @since 1.1.3
	 *
	 * @param \WP_User $user a user object
	 * @param string $role member role (defaults to regular 'member')
	 * @return bool
	 */
	public function can_invite_user( $user, $role = 'member' ) {

		/**
		 * Filters whether a member can be invited to a team.
		 *
		 * @since 1.1.3
		 *
		 * @param bool $can_be_invited true if the team can be managed
		 * @param \WP_User $user a user object
		 * @param Team $team the team object
		 * @param string $role invited member role
		 */
		return (bool) apply_filters( 'wc_memberships_for_teams_team_can_invite_user', $this->can_be_managed(), $user, $this, $role );
	}


	/**
	 * Determines whether the team can have seats added or not.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function can_add_seats() {

		$product = $this->get_product();

		// bail and skip the filter if there's no product associated with the team
		if ( ! $product || ! $product instanceof \WC_Product ) {
			return false;
		}

		$can_add_seats      = $product && $this->can_be_managed() && ! $this->is_membership_expired();
		$max_member_count   = Product::get_max_member_count( $product );
		$per_member_pricing = Product::has_per_member_pricing( $product );

		// if per-member pricing and we are at max seats, we can't add more
		if ( $per_member_pricing && 0 < $max_member_count && $max_member_count === $this->get_seat_count() ) {
			$can_add_seats = false;
		}

		// if per-team pricing and no max member, there are unlimited seats, so we can't add seats
		if ( ! $per_member_pricing && 1 > $max_member_count ) {
			$can_add_seats = false;
		}

		/**
		 * Filters whether seats can be added to this team or not.
		 *
		 * @since 1.1.0
		 *
		 * @param bool $can_add_seats whether seats can be added
		 * @param Team $this the Team object
		 */
		return apply_filters( 'wc_memberships_for_teams_team_can_add_seats', $can_add_seats, $this );
	}


	/**
	 * Determines whether the team can have seats removed or not.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function can_remove_seats() {

		/**
		 * Filters whether seats can be removed from this team or not.
		 *
		 * @since 1.1.0
		 *
		 * @param bool $can_remove_seats whether seats can be removed
		 * @param Team $this the Team object
		 */
		return apply_filters( 'wc_memberships_for_teams_team_can_remove_seats', false, $this );
	}


	/**
	 * Gets the seat change mode that should be used for this team.
	 *
	 * @since 1.1.0
	 *
	 * @return string seat change mode
	 */
	public function get_seat_change_mode() {

		$team_product = $this->get_product();

		if ( ! $team_product ) {

			$mode = 'none';

		} elseif ( $this->can_remove_seats() ) {

			$mode = 'update_seats';

		} elseif ( Product::has_per_member_pricing( $team_product ) ) {

			$mode = 'add_seats';

		} else {

			$mode = 'add_seat_blocks';
		}

		/**
		 * Filters the seat change mode for the current team.
		 *
		 * @since 1.1.0
		 *
		 * @param string $mode the seat change mode
		 * @param Team $this the Team object
		 */
		return apply_filters( 'wc_memberships_for_teams_team_seat_change_mode', $mode, $this );
	}


	/**
	 * Gets the new seat total for this team based on the seat change mode.
	 *
	 * @since 1.1.0
	 *
	 * @param int $change_amount change value
	 * @return int new seat total
	 */
	public function get_seat_change_total( $change_amount ) {

		$change_amount  = (int) $change_amount;
		$new_seat_total = $this->get_seat_count();

		switch( $this->get_seat_change_mode() ) {

			case 'update_seats':
				$new_seat_total = $change_amount;
			break;

			case 'add_seats':
				$new_seat_total += $change_amount;
			break;

			case 'add_seat_blocks':
				$new_seat_total += ( $change_amount * Product::get_max_member_count( $this->get_product() ) );
			break;
		}

		/**
		 * Filters the seat change total for the current team.
		 *
		 * @since 1.1.0
		 *
		 * @param int $new_seat_total the new seat total
		 * @param Team $this the Team object
		 * @param int $change_amount the change value passed in
		 */
		return apply_filters( 'wc_memberships_for_teams_team_seat_change_total', $new_seat_total, $this, $change_amount );
	}


	/**
	 * Returns the reason why team management is not permitted.
	 *
	 * @since 1.0.0
	 *
	 * @param string (optional) decline reason message type, defaults & falls back to general
	 * @return string
	 */
	public function get_management_decline_reason( $type = 'general' ) {

		$status = $this->get_management_status();

		if ( ! empty( $status['message'] ) ) {

			if ( is_string( $status['message'] ) ) {
				$message = $status['message'];
			} elseif ( ! empty( $status['message'][ $type ] ) ) {
				$message = $status['message'][ $type ];
			} elseif ( ! empty( $status['message'][ 'general' ] ) ) {
				$message = $status['message']['general'];
			}
		}

		return ! empty( $message ) ? $message : __( 'Team management is not permitted', 'woocommerce-memberships-for-teams' );
	}


	/**
	 * Validates team management status.
	 *
	 * TODO remove this deprecated method by version 2.0.0 or by March 2020, whichever comes first {FN 2019-03-13}
	 *
	 * @since 1.0.0
	 * @deprecated since 1.1.3
	 */
	public function validate_management_status() {
		_deprecated_function( __METHOD__, '1.1.3' );
	}


}
