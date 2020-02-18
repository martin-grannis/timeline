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
 * Team Members class.
 *
 * @since 1.0.0
 */
class Team_Members {


	/** @var array memoization helper */
	private $team_members = array();


	/**
	 * Sets up team members.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'delete_user', array( $this, 'remove_deleted_user_from_team' ) );
	}


	/**
	 * Returns a team member.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id, post object or instance
	 * @param int|string|\WP_User $user_id user id, email or user instance
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member|false team member instance or false on failure
	 */
	public function get_team_member( $team_id, $user_id ) {

		if ( ! $team_id || ! $user_id ) {
			return false;
		}

		try {
			$team_member = new Team_Member( $team_id, $user_id );
		} catch( Framework\SV_WC_Plugin_Exception $e ) {
			return false;
		}

		/**
		 * Filters the found team member.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member $team_member the team member instance
		 * @param \WP_Post $post the team member post object
		 * @param int|\WP_Post|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id, post object or instance
		 */
		return apply_filters( 'wc_memberships_for_teams_team_member', $team_member, $user_id, $team_id );
	}


	/**
	 * Returns a list of team members given the input query.
	 *
	 * Can return either a plain list of team member objects or an associative array with query results and team member objects.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id or instance to get the team members for
	 * @param array $args {
	 *     (optional) an array of arguments to pass to \WP_User_Query - additionally, a few special arguments can be passed:
	 *
	 *     @type string|array $role a comma-separated list or array of team member roles, empty by default - specifying this will only fetch members with the given role
	 *     @type int $paged the page number for paging the results, corresponds to paged param for get_users()
	 *     @type int $number the number of team members to fetch per page, corresponds to the number param for get_users()
	 *     @type int $per_page alias of $number (yields to $number)
	 * }
	 * @param string $return (optional) what to return - set to 'query' to return an associative array of query results instead of a list of team member instances
	 * @param bool $force_refresh (optional) whether to force reloading the results even if a previous result has been memoized, defaults to false
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member[]|array|false $team_members an array of team members, associative array of query results or false on failure
	 */
	public function get_team_members( $team_id, $args = array(), $return = null, $force_refresh = false ) {

		if ( ! $team_id ) {
			return false;
		}

		$team = wc_memberships_for_teams_get_team( $team_id );

		if ( ! $team instanceof Team ) {
			return false;
		}

		if ( 'query' === $return ) {
			$args['count_total'] = true;
		}

		// parse roles - can be passed in as an array or comma-separated list, ie role => array( 'owner', 'manager' ), or role => 'owner,manager'
		$roles = ! empty( $args['role'] ) ? array_map( 'trim', ( is_array( $args['role'] ) ? $args['role'] : explode( ',', $args['role'] ) ) ) : null;

		if ( ! empty( $roles ) ) {

			$args['meta_query'] = array(
				array(
					'key'     => "_wc_memberships_for_teams_team_{$team->get_id()}_role",
					'value'   => $roles,
					'compare' => 'IN',
				)
			);

			unset( $args['role'] );
		}

		// set pagination args
		if ( empty( $args['number'] ) && ! empty( $args['per_page'] ) ) {
			$args['number'] = $args['per_page'];
		}

		unset( $args['per_page'] );

		// ensure only members for the provided team are returned
		$args['include'] = $team->get_member_ids();
		$args['include'][] = 999999999999999999999; // prevent an empty member id list from returning _all the users_

		// unique key for memoizing the results
		$query_key = http_build_query( $args ) . $team->get_id();

		if ( ! isset( $this->team_members[ $query_key ] ) || $force_refresh ) {

			$wp_user_query = new \WP_User_Query( $args );
			$team_members  = array();

			foreach ( $wp_user_query->get_results() as $user ) {
				$team_members[] = $this->get_team_member( $team->get_id(), $user );
			}

			$total    = (int) $wp_user_query->get_total();
			$per_page = (int) $wp_user_query->get( 'number' );

			$results = array(
				'team_members' => $team_members,
				'total'        => $total,
				'per_page'     => $per_page ? $per_page : -1, // normalize unlimited/all to -1
				'current_page' => (int) $wp_user_query->get( 'paged' ),
				'total_pages'  => (int) ( $total && $per_page ? ceil( $total / $per_page ) : 1 ),
			);

			$this->team_members[ $query_key ] = $results;
		}

		return 'query' === $return ? $this->team_members[ $query_key ] : $this->team_members[ $query_key ]['team_members'];
	}


	/**
	 * Returns a list of available team member roles.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] associative array of role ids and labels
	 */
	public function get_team_member_roles() {

		/**
		 * Filters the list of available team member roles.
		 *
		 * Note that this does not include owner by design.
		 *
		 * @since 1.0.0
		 *
		 * @param array $roles an associative array of role => label pairs
		 */
		return apply_filters( 'wc_memberships_for_teams_team_member_roles', array(
			'member'  => __( 'Member', 'woocommerce-memberships-for-teams' ),
			'manager' => __( 'Manager', 'woocommerce-memberships-for-teams' ),
		) );
	}


	/**
	 * Checks if a team member role is valid or not.
	 *
	 * @since 1.0.0
	 *
	 * @param string $role the role to check
	 * @return bool true if valid, false otherwise
	 */
	public function is_valid_team_member_role( $role ) {

		$roles = array_keys( $this->get_team_member_roles() );

		return in_array( $role, $roles, true );
	}


	/**
	 * Removes the user that is about to be deleted from any teams they are a member of.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id user id
	 */
	public function remove_deleted_user_from_team( $user_id ) {

		$teams = wc_memberships_for_teams_get_teams( $user_id );

		if ( ! empty( $teams ) ) {

			foreach ( $teams as $team ) {

				try {
					$team->remove_member( $user_id );
				} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
			}
		}
	}


	/**
	 * Returns an associative array ( id => link ) with the list of views available for a team members table.
	 *
	 * Note that the invitations view will actually load a different table instance.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object
	 * @param string $base_url base URL
	 * @return array
	 */
	public static function get_table_views( $team, $base_url ) {

		$views            = array();
		$show_invitations = ! empty( $_REQUEST['show_invitations'] );

		// add members list view link
		$members_label = sprintf(
			__( 'Members <span class="count">(%s)</span>', 'woocommerce-memberships-for-teams' ),
			number_format_i18n( $team->get_member_count() )
		);

		$members_classes = 'members';

		if ( ! $show_invitations ) {
			$members_classes .= ' current';
		}

		$views['members'] = self::get_view_link( $base_url, array( 'show_members' => 1 ), $members_label, $members_classes );


		// add invitations list view link
		$invitations_label = sprintf(
			__( 'Pending invitations <span class="count">(%s)</span>', 'woocommerce-memberships-for-teams' ),
			number_format_i18n( $team->get_invitation_count() )
		);

		$invitations_classes = 'invitations';

		if ( $show_invitations ) {
			$invitations_classes .= ' current';
		}

		$views['invitations'] = self::get_view_link( $base_url, array( 'show_invitations' => 1 ), $invitations_label, $invitations_classes );

		return $views;
	}


	/**
	 * Creates view links for admin & frontend list tables.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url base url
	 * @param array $args URL parameters for the link
	 * @param string $label link text
	 * @param string $class (optional) lass attribute, defaults to an empty string
	 * @return string the formatted link HTML
	 */
	public static function get_view_link( $url, $args, $label, $class = '' ) {

		$url = add_query_arg( $args, $url );

		$class_html = '';

		if ( ! empty( $class ) ) {
			 $class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);
		}

		return sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$label
		);
	}

}
