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
 * Invitations class.
 *
 * @since 1.0.0
 */
class Invitations {


	/** @var array memoization helper */
	private $invitations = array();


	/**
	 * Determines whether invitations should be skipped for registered users.
	 *
	 * If this is true, members are joined directly when a team manager submits an email address in the team management page.
	 * Even if this is true, yet an email does not match a registered user, invitations would still be sent then.
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Frontend::add_team_member()
	 *
	 * @since 1.1.2
	 *
	 * @param null|\SkyVerge\WooCommerce\Memberships\Teams\Team $team optional: pass a team to determine if invitations should be skipped for
	 * @param null|string|int|\WP_User optional: user being invited to join a team (by email, ID or object)
	 * @return bool
	 */
	public function should_skip_invitations( $team = null, $user = null ) {

		/**
		 * Filters whether invitations should be skipped.
		 *
		 * @since 1.1.2
		 *
		 * @param bool $skip_invitations whether invitations should be skipped
		 * @param null|\SkyVerge\WooCommerce\Memberships\Teams\Team optional argument to evaluate if invitations should be skipped for a particular team
		 * @param null|int|string|\WP_User optional being invited to join a team (by email, ID or object)
		 */
		return (bool) apply_filters( 'wc_memberships_for_teams_skip_invitations', false, $team, $user );
	}


	/**
	 * Creates a new invitation.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     an array of invitation arguments
	 *
	 *     @type int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id or instance
	 *     @type string $email email address to send the invitation to
	 *     @type int $sender_id (optional) sender user id, defaults to current user id
	 *     @type string $role (optional) the role to assign to the invited user, defaults to 'member'
	 * }
	 * @return false|Invitation instance
	 * @throws Framework\SV_WC_Plugin_Exception on validation errors or when wp_insert_post fails
	 */
	public function create_invitation( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'sender_id' => get_current_user_id(),
			'team_id'   => 0,
			'email'     => 0,
			'role'      => 'member',
		) );

		$team = wc_memberships_for_teams_get_team( $args['team_id'] );

		if ( ! $team instanceof Team ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team', 'woocommerce-memberships-for-teams' ) );
		}

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid email', 'woocommerce-memberships-for-teams' ) );
		}

		$new_invitation_post_data = array(
			'post_title'     => $args['email'],
			'post_parent'    => $team->get_id(),
			'post_author'    => $args['sender_id'],
			'post_type'      => 'wc_team_invitation',
			'post_status'    => 'wcmti-pending',
			'post_password'  => Plugin::generate_token(),
			'post_mime_type' => $args['role'],
			'comment_status' => 'closed',
		);

		/**
		 * Filters new invitation post data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data new invitation post data
		 * @param array $args array of Invitation arguments {
		 *     @type string $email email of the invitation recipient
		 *     @type int $team_id the team id
		 *     @type int $sender_id the sender user id
		 *     @type string $role the role to assign the invited user to
		 * }
		 */
		$new_invitation_post_data = apply_filters( 'wc_memberships_for_teams_new_invitation_post_data', $new_invitation_post_data, $args );

		$invitation_id = wp_insert_post( $new_invitation_post_data );

		if ( is_wp_error( $invitation_id ) ) {
			throw new Framework\SV_WC_Plugin_Exception( $invitation_id->get_error_message() );
		}

		$invitation = $this->get_invitation( $invitation_id );

		/**
		 * Fires after an invitation has been created.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation that was just created
		 */
		do_action( 'wc_memberships_for_teams_invitation_created', $invitation );

		return $invitation;
	}


	/**
	 * Returns an invitation.
	 *
	 * Supports getting invitation by token, id, post object, invitation instance or a combination of the team (or it's id) and the recipient's email.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int|\WP_Post|\SkyVerge\WooCommerce\Memberships\Teams\Invitation|\SkyVerge\WooCommerce\Memberships\Teams\Team $id invitation token, id or instance, or team id or instance
	 * @param string $email (optional) invitation recipient email, required if $id is a team id or instance
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Inviitation|false invitation instance or false on failure
	 */
	public function get_invitation( $id, $email = null ) {

		$post = null;

		if ( $email ) {

			// get invitation by team id + recipient email
			$team = wc_memberships_for_teams_get_team( $id );

			if ( ! $team instanceof Team || ! is_email( $email ) ) {
				return false;
			}

			$args = array(
				'title'          => $email,
				'post_status'    => array_keys( $this->get_invitation_statuses() ),
				'post_type'      => 'wc_team_invitation',
				'post_parent'    => $team->get_id(),
				'posts_per_page' => 1,
			);

			$invitations = get_posts( $args );
			$post        = ! empty( $invitations ) ? $invitations[0] : null;

		} elseif ( is_numeric( $id ) ) {

			// get invitation by id
			$post = get_post( $id );

		} elseif ( is_string( $id ) && ! empty( $id ) ) {

			// get invitation by token
			$args = array(
				'post_type'      => 'wc_team_invitation',
				'post_status'    => array_keys( $this->get_invitation_statuses() ),
				'post_password'  => $id,
				'posts_per_page' => 1,
			);

			$invitations = get_posts( $args );
			$post        = ! empty( $invitations ) ? $invitations[0] : null;

		} elseif ( $id instanceof Invitation ) {

			// get invitation by id (from the invitation instance that was passed in)
			$post = get_post( $id->get_id() );

		} elseif ( $id instanceof \WP_Post ) {

			$post = $id;
		}

		try {
			$invitation = new Invitation( $post );
		} catch ( Framework\SV_WC_Plugin_Exception $e ) {
			return false;
		}

		/**
		 * Filters the found invitation.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation instance
		 * @param \WP_Post $post the invitation post object
		 */
		return apply_filters( 'wc_memberships_for_teams_invitation', $invitation, $post );
	}


	/**
	 * Returns a list of invitations given the input query.
	 *
	 * Can return either a plain list of invitation objects or an associative array with query results and invitation objects.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id to get the invitations for
	 * @param array $args {
	 *     (optional) an array of arguments to pass to \WP_Query - additionally, a few special arguments can be passed:
	 *
	 *     @type string|array $status invitation status, defaults to 'pending', can be used instead of $post_status
	 *     @type string|array $role a comma-separated list or array of team member roles, empty by default - specifying this will only fetch invitations that grant the one of thge specified roles for the user
	 *     @type int $paged the page number for paging the results, corresponds to paged param for get_posts()
	 *     @type int $per_page the number of invitations to fetch per page, corresponds to the posts_per_page param for get_posts()
	 * }
	 * @param string $return (optional) what to return - set to 'query' to return the \WP_Query instance instead of a list of invitation instances
	 * @param bool $force_refresh (optional) whether to force reloading the results even if a previous result has been memoized, defaults to false
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitation[]|\WP_Query|false $invitations an array of invitations, associative array of query results or false on failure
	 */
	public function get_invitations( $team_id, $args = array(), $return = null, $force_refresh = false ) {

		$team = $team_id && ! $team_id instanceof Team ? wc_memberships_for_teams_get_team( $team_id ) : $team_id;

		if ( ! $team instanceof Team ) {
			return false;
		}

		$args = wp_parse_args( $args, array(
			'status'   => 'pending',
			'nopaging' => true,
		) );

		// handle invitation statuses
		$default_statuses = array_keys( $this->get_invitation_statuses() );

		if ( 'any' === $args['status'] ) {
			$args['status'] = $default_statuses;
		}

		$args['post_status'] = array();

		if ( ! empty( $args['status'] ) ) {

			// enforces a 'wcmti-' prefix if missing
			foreach ( (array) $args['status'] as $status ) {

				$status = Framework\SV_WC_Helper::str_starts_with( $status, 'wcmti-' ) ? $status : 'wcmti-' . $status;

				if ( in_array( $status, $default_statuses, true ) ) {
					$args['post_status'][] = $status;
				}
			}
		}

		// ensure correct post type
		$args['post_type'] = 'wc_team_invitation';

		// set pagination args
		if ( isset( $args['paged'] ) ) {

			$args['nopaging'] = false;

			if ( ! isset( $args['posts_per_page'] ) ) {
				$args['posts_per_page'] = ! empty( $args['per_page'] ) ? (int) $args['per_page'] : 20; // default to 20 per page
			}

		}

		// parse roles - can be passed in as an array or comma-separated list, ie role => array( 'owner', 'manager' ), or role => 'owner,manager'
		$roles = ! empty( $args['role'] ) ? array_map( 'trim', ( is_array( $args['role'] ) ? $args['role'] : explode( ',', $args['role'] ) ) ) : null;

		// we have repurposed the post_mime_type to store the role of the invited user
		if ( ! empty( $roles ) ) {

			$args['post_mime_type'] = $roles;

			unset( $args['role'] );
		}

		// scope query to the team
		$args['post_parent'] = $team->get_id();

		// unique key for memoizing the results
		$query_key = http_build_query( $args ) . $team->get_id();

		if ( $force_refresh || ! isset( $this->invitations[ $query_key ] ) ) {

			$wp_query    = new \WP_Query( $args );
			$invitations = array();

			foreach ( $wp_query->posts as $post ) {
				$invitations[] = $this->get_invitation( $post );
			}

			$results = array(
				'invitations'  => $invitations,
				'total'        => (int) $wp_query->found_posts,
				'per_page'     => (int) $wp_query->get( 'posts_per_page' ),
				'current_page' => (int) $wp_query->get( 'paged' ),
				'total_pages'  => (int) $wp_query->max_num_pages,
			);

			$this->invitations[ $query_key ] = $results;
		}

		return 'query' === $return ? $this->invitations[ $query_key ] : $this->invitations[ $query_key ]['invitations'];
	}


	/**
	 * Returns all invitation statuses.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array of statuses and their arguments
	 */
	public function get_invitation_statuses() {

		// TODO: is this a sane prefix? wcmti stands for "woocommerce memberships team invitation" {IT 2017-09-14}

		$statuses = array(

			'wcmti-pending'       => array(
				'label'       => _x( 'Pending', 'Invitation Status', 'woocommerce-memberships-for-teams' ),
				/* translators: Pending Invitation(s) */
				'label_count' => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'woocommerce-memberships-for-teams' ),
			),

			'wcmti-accepted'       => array(
				'label'       => _x( 'Accepted', 'Invitation Status', 'woocommerce-memberships-for-teams' ),
				/* translators: Accepted Invitation(s) */
				'label_count' => _n_noop( 'Accepted <span class="count">(%s)</span>', 'Accepted <span class="count">(%s)</span>', 'woocommerce-memberships-for-teams' ),
			),

			'wcmti-cancelled'       => array(
				'label'       => _x( 'Cancelled', 'Invitation Status', 'woocommerce-memberships-for-teams' ),
				/* translators: Cancelled Invitation(s) */
				'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'woocommerce-memberships-for-teams' ),
			),

		);

		/**
		 * Filters invitation statuses.
		 *
		 * @since 1.0.0
		 *
		 * @param array $statuses associative array of statuses and their arguments
		 */
		return apply_filters( 'wc_memberships_for_teams_invitation_statuses', $statuses );
	}


}
