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
 * Teams handler class.
 *
 * TODO: perhaps naming this class just "Handler" would be enough? we're already on the
 * Teams namespace, so Teams/Teams_Handler vs Teams/Handler vs Teams/Teams... {IT 2017-07-08}
 *
 * @since 1.0.0
 */
class Teams_Handler {


	/** @var array memoization helper */
	private $teams = array();

	/** @var bool helper flag to let us know if a team member is being removed */
	private $team_member_is_being_removed = false;


	/**
	 * Sets up the teams handler.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// relationships - hooking to before_delete_post so that all metadata still exists
		add_action( 'before_delete_post', array( $this, 'maybe_delete_related_data' ) );

		// take note if a team member is being removed
		add_action( 'wc_memberships_for_teams_before_remove_team_member', array( $this, 'add_team_member_being_removed_flag' ) );
		add_action( 'wc_memberships_for_teams_after_remove_team_member',  array( $this, 'remove_team_member_being_removed_flag' ) );

		add_action( 'save_post', array( $this, 'save_team' ), 10, 3 );

		// queries
		add_filter( 'posts_clauses', array( $this, 'adjust_teams_query_posts_clauses' ), 10, 2 );

		// expiration events handling
		add_action( 'wc_memberships_for_teams_team_membership_expiry',           array( $this, 'trigger_expiration_events' ), 10, 1 );
		add_action( 'wc_memberships_for_teams_team_membership_expiring_soon',    array( $this, 'trigger_expiration_events' ), 10, 1 );
		add_action( 'wc_memberships_for_teams_team_membership_renewal_reminder', array( $this, 'trigger_expiration_events' ), 10, 1 );
	}


	/**
	 * Creates a team programmatically.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     optional - an array of team arguments
	 *
	 *     @type int $owner_id owner user id
	 *     @type int|\WC_Memberships_Plan $plan_id plan id or instance
	 *     @type int|\WC_Product $product_id product id or instance
	 *     @type int|\WC_Order $order_id order id or instance
	 *     @type string $name team name, defaults to 'Team'
	 *     @type int $seats the number of seats to add to the team - if not provided, will use the max member count from the product/variation
	 * }
	 * @param string $action either 'create' or 'renew' -- when in doubt, use 'create'
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team team instance
	 * @throws Framework\SV_WC_Plugin_Exception on validation errors or when wp_insert_post fails
	 */
	public function create_team( $args = array(), $action = 'create' ) {

		$name = __( 'Team', 'woocommerce-memberships-for-teams' );
		$args = wp_parse_args( $args, array(
			'team_id'    => 0,
			'owner_id'   => 0,
			'plan_id'    => 0,
			'product_id' => 0,
			'order_id'   => 0,
			'seats'      => null,
			'name'       => $name,
		) );

		if ( empty( $args['name'] ) ) {
			$args['name'] = $name;
		}

		$product = null;

		// normalize product id - allow passing in both an id and a product instance
		if ( $args['product_id'] instanceof \WC_Product ) {

			$product            = $args['product_id'];
			$args['product_id'] = $product->get_id();

		} elseif ( is_numeric( $args['product_id'] ) ) {

			$product = wc_get_product( $args['product_id'] );
		}

		// normalize order id - allow passing in both an id and an order instance
		if ( $args['order_id'] instanceof \WC_Order ) {
			$args['order_id'] = $args['order_id']->get_id();
		}

		// normalize plan id - allow passing in both an id and a plan instance
		if ( $args['plan_id'] instanceof \WC_Memberships_Plan ) {
			$args['plan_id'] = $args['plan_id']->get_id();
		}

		// if no plan id was passed, try to get it from the product
		if ( ! $args['plan_id'] && $product ) {
			$args['plan_id'] = Product::get_membership_plan_id( $product );
		}

		// an owner is required
		if ( empty( $args['owner_id'] ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Owner is required', 'woocommerce-memberships-for-teams' ) );
		}

		// a plan id is required
		if ( empty( $args['plan_id'] ) || ! ( $plan = wc_memberships_get_membership_plan( $args['plan_id'] ) ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid plan', 'woocommerce-memberships-for-teams' ) );
		}

		// if team id is provided, ensure it's valid
		if ( ! empty( $args['team_id'] ) && ! ( $team = $this->get_team( $args['team_id'] ) ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team', 'woocommerce-memberships-for-teams' ) );
		}

		$team_post_data = array(
			'post_title'     => $args['name'],
			'post_parent'    => (int) $args['plan_id'],
			'post_author'    => (int) $args['owner_id'],
			'post_type'      => 'wc_memberships_team',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
		);

		$updating = false;

		if ( (int) $args['team_id'] > 0 ) {
			$updating                 = true;
			$team_post_data['ID'] = (int) $args['team_id'];
		} else {
			$team_post_data['post_password'] = Plugin::generate_token(); // only generate reg. key for new teams
		}

		/**
		 * Filters team post object data, usually used when a product purchase creates a team or renews a team membership.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data new team post data
		 * @param array $args {
		 *     an array of team arguments
		 *
		 *     @type string $name the team name
		 *     @type int $plan_id the plan id the team has access to
		 *     @type int $owner_id the user id the team is assigned to
		 *     @type int $product_id the product id that creates the team (optional)
		 *     @type int $order_id the order id that contains the product that creates the team (optional)
		 * }
		 */
		$team_post_data = apply_filters( 'wc_memberships_for_teams_new_team_data', $team_post_data, $args );

		if ( $updating ) {
			$team_id = wp_update_post( $team_post_data, true );
		} else {
			$team_id = wp_insert_post( $team_post_data, true );
		}

		// bail out on error
		if ( is_wp_error( $team_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( $team_id->get_error_message() );
		}

		$team = $this->get_team( $team_id );

		// save product id that created the team
		if ( (int) $args['product_id'] > 0 ) {
			$team->set_product_id( $args['product_id'] );

			// get seats from product if not provided
			if ( ! is_numeric( $args['seats'] ) || $args['seats'] < 0 ) {
				$args['seats'] = Product::get_max_member_count( $product );
			}
		}

		// set seat count
		$team->set_seat_count( $args['seats'] );

		// save order id that created the team
		if ( (int) $args['order_id'] > 0 ) {
			$team->set_order_id( $args['order_id'] );
		}

		// get the plan object again, since the product and the order just set might influence the object filtering (e.g. Subscriptions)
		$plan = wc_memberships_get_membership_plan( (int) $args['plan_id'], $team );

		// Calculate team membership end date based on membership length: early renewals add to the existing membership length,
		// normal cases calculate membership length from "now" (UTC).
		$now        = current_time( 'timestamp', true );
		$since      = $now;
		$is_expired = $team->is_membership_expired();

		if ( 'renew' === $action && ! $is_expired ) {
			$end   = $team->get_membership_end_date( 'timestamp' );
			$since = ! empty( $end ) ? $end : $since;
		}

		// obtain the relative end date based on the membership plan
		$membership_end_date = $plan->get_expiration_date( $since, $args );

		// save/update the membership end date (adjusting individual user memberships as well)
		$team->set_membership_end_date( $membership_end_date, 'renew' === $action );

		// finally, renew any cancelled memberships, if enabled; note that this is handled separately from
		// reactivating expired user memberships in $team->set_membership_end_date(), as cancelled memberships should
		// not be extended by simply setting their end date
		if ( 'renew' === $action ) {

			foreach ( $team->get_user_memberships() as $user_membership ) {

				$end = $user_membership->get_end_date( 'timestamp' ); // this should be updated by now

				if ( $end > $now && $user_membership->has_status( 'cancelled' ) ) {

					/** This filter is documented in memberships/includes/class-wc-memberships-user-memberships.php */
					$renew_cancelled_membership = (bool) apply_filters( 'wc_memberships_renew_cancelled_membership', true, $user_membership, $args );

					if ( true === $renew_cancelled_membership ) {
						$user_membership->update_status( 'active' );
					}
				}
			}
		}

		/**
		 * Fires after a team has been created.
		 *
		 * This action hook is similar to `wc_memberships_for_teams_team_saved`
		 * but doesn't fire when teams are manually created from admin.
		 *
		 * @see \SkyVerge\WooCommerce\Memberships\Teams\Teams_Handler::save_user_membership()
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team that was just created
		 * @param bool $updating whether this is a post update or a newly created team
		 */
		do_action( 'wc_memberships_for_teams_team_created', $team, $updating );

		return $team;
	}


	/**
	 * Triggers `wc_memberships_for_teams_team_saved` when a team is created or updated.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id the post ID
	 * @param \WP_Post $post the post object
	 * @param bool $update whether we are updating or creating a new post
	 */
	public function save_team( $post_id, $post, $update ) {

		if ( 'wc_memberships_team' === get_post_type( $post ) && ( $team = $this->get_team( $post ) ) ) {

			/**
			 * Fires after a team post has been saved.
			 *
			 * This hook is similar to `wc_memberships_for_teams_team_created`,
			 * but will also fire when a team is manually created in admin,
			 * or upon an import or via command line interface, etc.
			 *
			 * @since 1.0.0
			 *
			 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the plan that was saved
			 * @param bool $updating whether this is a post update or a newly created team
			 */
			do_action( 'wc_memberships_for_teams_team_saved', $team, $update );
		}
	}


	/**
	 * Returns a team instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string|\WP_Post $post optional team id, registration key or post object, defaults to current global post object
	 * @return false|\SkyVerge\WooCommerce\Memberships\Teams\Team team instance or false if not found
	 */
	public function get_team( $post = null ) {

		if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {

			// get team post object from globals
			$post = $GLOBALS['post'];

		} elseif ( is_numeric( $post ) ) {

			// get team by id
			$post = get_post( $post );

		} elseif ( is_string( $post ) && ! empty( $post ) ) {

			// get team by registration key
			$args = array(
				'post_type'      => 'wc_memberships_team',
				'post_password'  => $post,
				'posts_per_page' => 1,
			);

			$teams = get_posts( $args );
			$post  = ! empty( $teams ) ? $teams[0] : null;

		} elseif ( $post instanceof Team ) {

			// get team by id (from the team instance that was passed in)
			$post = get_post( $post->get_id() );

		}

		try {
			$team = new Team( $post );
		} catch ( Framework\SV_WC_Plugin_Exception $e ) {
			return false;
		}

		/**
		 * Filters the found team.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 * @param \WP_Post $post the team post object
		 */
		return apply_filters( 'wc_memberships_for_teams_team', $team, $post );
	}


	/**
	 * Returns a list of teams for a user.
	 *
	 * Can return either a plain list of team objects or an associative array with query results and team objects.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id optional, defaults to current user
	 * @param array $args {
	 *     (optional) an array of arguments to pass to \WP_Query - additionally, a few special arguments can be passed:
	 *
	 *     @type string|array $status team status, defaults to 'any'
	 *     @type string|array $role a comma-separated list or array of team member roles, defaults to 'owner, manager' - specifying this will only fetch teams that the user has one of the given roles
	 *     @type int $paged the page number for paging the results (corresponds to paged param for get_posts())
	 *     @type int $per_page the number of teams to fetch per page (corresponds to the posts_per_page param for get_posts())
	 * }
	 * @param string $return (optional) what to return - set to 'query' to return an associative array of query results instead of a list of team instances
	 * @param bool $force_refresh (optional) whether to force reloading the results even if a previous result has been memoized, defaults to false
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team[]|array|false $teams an array of teams, associative array of query results or false on failure
	 */
	public function get_teams( $user_id = null, $args = array(), $return = null, $force_refresh = false ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$args = wp_parse_args( $args, array(
			'status'   => 'any',
			'role'     => 'owner, manager, member',
			'nopaging' => true,
		) );

		// add the wcm- prefix for the status if it's not "any"
		foreach ( (array) $args['status'] as $index => $status ) {

			if ( 'any' !== $status ) {
				$args['post_status'][ $index ] = 'wcm-' . $status;
			}
		}

		$args['post_type'] = 'wc_memberships_team';

		// set pagination args
		if ( ! isset( $args['posts_per_page'] ) && ! empty( $args['per_page'] ) ) {
			$args['posts_per_page'] = $args['per_page'];
		}

		if ( isset( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ) {
			$args['nopaging'] = false;
		}

		// parse roles - can be passed in as an array or comma-separated list, ie role => array( 'owner', 'manager', 'member' ), or role => 'owner,manager,member'
		$roles = array_map( 'trim', ( is_array( $args['role'] ) ? $args['role'] : explode( ',', $args['role'] ) ) );

		// simple case - if the only queried role is owner, we can simply look for a matching author
		if ( count( $roles ) === 1 && 'owner' === $roles[0] ) {
			$args['author'] = $user_id;
		} else {
			$args['suppress_filters']                  = false; // so that our posts_clauses filter is applied
			$args['_wc_memberships_for_teams_roles']   = $roles;
			$args['_wc_memberships_for_teams_user_id'] = $user_id;
		}

		unset( $args['status'], $args['role'] );

		$query_key = http_build_query( $args );

		if ( $force_refresh || ! isset( $this->teams[ $query_key ] ) ) {

			$wp_query = new \WP_Query( $args ); // the SQL clauses will be filtered by 'adjust_teams_query_posts_clauses()' below
			$teams    = array();

			foreach ( $wp_query->posts as $post ) {
				$teams[] = $this->get_team( $post );
			}

			$per_page = $wp_query->get( 'posts_per_page' );

			$results = array(
				'teams'        => $teams,
				'total'        => $wp_query->found_posts,
				'per_page'     => $per_page ? $per_page : -1, // normalize unlimited/all to -1
				'current_page' => $wp_query->get( 'paged' ),
				'total_pages'  => $wp_query->max_num_pages,
			);

			$this->teams[ $query_key ] = $results;
		}

		return 'query' === $return ? $this->teams[ $query_key ] : $this->teams[ $query_key ]['teams'];
	}


	/**
	 * Adjusts the SQL clauses when querying teams.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $pieces associative array of SQL clauses
	 * @param \WP_Query $wp_query the query object
	 * @return array
	 */
	public function adjust_teams_query_posts_clauses( $pieces, \WP_Query $wp_query ) {
		global $wpdb;

		// bail out if not the correct post type
		if ( empty( $wp_query->query['post_type'] ) || 'wc_memberships_team' !== $wp_query->query['post_type'] ) {
			return $pieces;
		}

		if ( ! empty( $wp_query->query_vars['_wc_memberships_for_teams_roles'] ) && ! empty( $wp_query->query_vars['_wc_memberships_for_teams_user_id'] ) ) {

			$roles   = $wp_query->query_vars['_wc_memberships_for_teams_roles'];
			$user_id = $wp_query->query_vars['_wc_memberships_for_teams_user_id'];

			// join with usermeta on user team role meta key
			$pieces['join'] .= $wpdb->prepare( "
				LEFT JOIN {$wpdb->postmeta} _teams_pm
				ON  {$wpdb->posts}.ID      = _teams_pm.post_id
				AND _teams_pm.meta_key   = '_member_id'
				AND _teams_pm.meta_value = %d
				LEFT JOIN {$wpdb->usermeta} _teams_um
				ON  _teams_um.user_id  = _teams_pm.meta_value
				AND _teams_um.meta_key = CONCAT( '_wc_memberships_for_teams_team_', {$wpdb->posts}.ID, '_role' )
			", $user_id );

			$pieces['where'] .= ' AND ( ';

			// match on the user team role meta
			if ( count( $roles ) > 1 ) {

				$_roles = $roles;

				if ( ( $key = array_search( 'owner', $_roles ) ) !== false ) {
					unset( $_roles[ $key ] );
				}

				$pieces['where'] .= $wpdb->prepare( "_teams_um.meta_value IN(" . implode( ', ', array_fill( 0, count( $_roles ), '%s' ) ) . ")", $_roles );
			} else {
				$pieces['where'] .= $wpdb->prepare( "_teams_um.meta_value = %s ", $roles[0] );
			}

			// match on the team post_author
			if ( in_array( 'owner', $roles, true ) ) {
				$pieces['where'] .= $wpdb->prepare( " OR {$wpdb->posts}.post_author = %d ", $user_id );
			}

			$pieces['where'] .= ' ) ';
		}

		return $pieces;
	}


	/**
	 * Deletes team related data when a post is being deleted.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post being deleted
	 */
	public function maybe_delete_related_data( $post_id ) {

		switch ( get_post_type( $post_id ) ) {

			case 'wc_memberships_team':
				$this->handle_team_deletion( $post_id );
			break;

			case 'wc_membership_plan':
				$this->handle_membership_plan_deletion( $post_id );
			break;

			case 'wc_user_membership':
				$this->handle_user_membership_deletion( $post_id );
			break;
		}
	}


	/**
	 * Handles team deletion.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $team_id post object ID of the user membership being deleted
	 */
	private function handle_team_deletion( $team_id ) {

		if ( $team = $this->get_team( $team_id ) ) {

			// remove members from the team before it's deleted
			$keep_user_membership = ! empty( $_REQUEST['keep_user_memberships'] );

			// delete related user memberships
			foreach ( $team->get_member_ids() as $user_id ) {
				try {
					$team->remove_member( $user_id, $keep_user_membership );
				} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
			}
		}
	}


	/**
	 * Handles membership plan deletion.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $plan_id ID of the membership plan being deleted
	 */
	private function handle_membership_plan_deletion( $plan_id ) {
		global $wpdb;

		// delete any teams that are on a plan that's being deleted
		$team_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID FROM $wpdb->posts
			WHERE post_parent = %d
		", $plan_id ) );

		if ( ! empty( $team_ids ) ) {
			foreach ( $team_ids as $team_id ) {
				wp_delete_post( $team_id, true );
			}
		}
	}


	/**
	 * Handles user membership deletion.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_membership_id ID of the use membership being deleted
	 */
	private function handle_user_membership_deletion( $user_membership_id ) {

		// Currently there is a 1-to-1 relation between team member and user membership, so if a
		// user membership is deleted, the member should be removed from the team as well - this may well
		// change in the future if/when multiple plans per team are supported.
		// Note: only push forward if the team-based user membership is being deleted outside of removing a member from a team.
		if ( ! $this->team_member_is_being_removed && $team_id = $this->get_user_membership_team_id( $user_membership_id ) ) {

			$team            = $this->get_team( $team_id );
			$user_membership = wc_memberships_get_user_membership( $user_membership_id );
			$user_id         = $user_membership ? $user_membership->get_user_id() : null;

			if ( $team && $user_id ) {

				// we can skip deleting the user memberships, as it's being deleted anyway
				// TODO: this will likely need reconsidering if/when adding support for multiple plans per team {IT 2019-09-25}
				try{
					$team->remove_member( $user_id, true );
				} catch( Framework\SV_WC_Plugin_Exception $e ) {}
			}
		}
	}


	/**
	 * Adds a flag to let us know that a team member is being removed.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_team_member_being_removed_flag() {
		$this->team_member_is_being_removed = true;
	}


	/**
	 * Removes the flag to let us know that a team member is being removed.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function remove_team_member_being_removed_flag() {
		$this->team_member_is_being_removed = false;
	}


	/**
	 * Returns team ID for the given user membership, if any.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Memberships_User_Membership $user_membership_id a user membership
	 * @return int|null team id or null if no link found
	 */
	public function get_user_membership_team_id( $user_membership_id ) {

		$user_membership_id = $user_membership_id instanceof \WC_Memberships_User_Membership ? $user_membership_id->get_id() : $user_membership_id;

		if ( ! is_numeric( $user_membership_id ) ) {
			return null;
		}

		// TODO: the meta key here is hardcoded, as opposed to in the Team class, it's take from $user_membership_team_id_meta property -
		// perhaps that property should be made public & static, so it can be accessed from outside {IT 2017-08-25}
		$team_id = get_post_meta( $user_membership_id, '_team_id', true );

		return $team_id ? (int) $team_id : null;
	}


	/**
	 * Returns the team for the given user membership, if any.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Memberships_User_Membership $user_membership_id a user membership
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team|false team instance or false if not found
	 */
	public function get_user_membership_team( $user_membership_id ) {

		$team_id = $this->get_user_membership_team_id( $user_membership_id );

		return $team_id ? $this->get_team( $team_id ) : false;
	}


	/**
	 * Triggers team membership expiration events.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args expiration event args
	 * @param string $force_event event to trigger, only when calling the method directly and not as hook callback
	 */
	public function trigger_expiration_events( $args, $force_event = '' ) {

		$team_id        = isset( $args['team_id'] ) ? (int) $args['team_id'] : $args;
		$current_filter = ! empty( $force_event ) ? $force_event : current_filter();

		if ( ! is_numeric( $team_id ) || empty( $current_filter ) ) {
			return;
		}

		// you may fire when ready
		if ( $emails_instance = wc_memberships_for_teams()->get_emails_instance() ) {

			if ( 'wc_memberships_for_teams_team_membership_expiring_soon' === $current_filter ) {

				$emails_instance->send_membership_ending_soon_email( $team_id );

			} elseif ( 'wc_memberships_for_teams_team_membership_expiry' === $current_filter ) {

				$emails_instance->send_membership_ended_email( $team_id );

			} elseif ( 'wc_memberships_for_teams_team_membership_renewal_reminder' === $current_filter ) {

				$emails_instance->send_membership_renewal_reminder_email( $team_id );
			}
		}
	}


}
