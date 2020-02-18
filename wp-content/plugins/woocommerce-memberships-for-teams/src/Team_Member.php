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
 * Team member class. Represents a single member in a team. Since a single user may be part of multiple teams,
 * when constructing the class instance, a team must be provided for context.
 *
 * In the future, this class might be abstracted and find use in the core Memberships extension.
 *
 * @since 1.0.0
 */
class Team_Member {


	/** @var int member (user) ID */
	private $id;

	/** @var int team id */
	private $team_id;

	/** @var \WP_User user object */
	private $user;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Team team object */
	private $team;

	/** @var string team role meta key */
	protected $team_role_meta;

	/** @var string team added date meta key */
	protected $team_added_date_meta;


	/**
	 * Sets up the team member instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id, post object or instance
	 * @param int|string|\WP_User $user_id user id, email or user instance
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function __construct( $team_id, $user_id ) {

		// load the team
		$this->team = wc_memberships_for_teams_get_team( $team_id );

		if ( ! ( $this->team instanceof Team ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid team', 'woocommerce-memberships-for-teams' ) );
		}

		// load the user
		if ( is_numeric( $user_id ) ) {
			$this->user = get_userdata( $user_id );
		} elseif ( $user_id instanceof \WP_User ) {
			$this->user = $user_id;
		} elseif ( is_string( $user_id ) && is_email( $user_id ) ) {
			$this->user = get_user_by( 'email', $user_id );
		}

		if ( ! ( $this->user instanceof \WP_User ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid user', 'woocommerce-memberships-for-teams' ) );
		}

		// ensure that the user is actually a member of the team
		if ( ! $this->team->is_user_member( $this->user->ID ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'User is not a member of this team', 'woocommerce-memberships-for-teams' ) );
		}

		// load in user data...
		$this->id      = (int) $this->user->ID;
		$this->team_id = (int) $this->team->get_id();

		// set meta keys
		$this->team_role_meta       = $this->team->get_user_team_role_meta_key();
		$this->team_added_date_meta = $this->team->get_user_team_added_date_meta_key();
	}


	/**
	 * Returns the member (user) ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int member ID
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns user object.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User
	 */
	public function get_user() {
		return $this->user;
	}


	/**
	 * Returns the team ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int team ID
	 */
	public function get_team_id() {
		return $this->team_id;
	}


	/**
	 * Returns the team object.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team|false team instance or false if not found
	 */
	public function get_team() {

		// get the team if not already set
		if ( ! $this->team ) {
			$this->team = wc_memberships_for_teams_get_team( $this->get_team_id() );
		}

		return $this->team;
	}


	/**
	 * Checks whether the member is the owner of the team or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_team_owner() {

		return $this->team->is_user_owner( $this->id );
	}


	/**
	 * Checks whether the team member has the specified role in team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $role the role to check for
	 * @return bool true if has the role, false otherwise
	 */
	public function has_role( $role ) {
		return $role === $this->get_role();
	}


	/**
	 * Returns the member's role in the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $return (optional) set to 'label' to return the role label instead
	 * @return string role
	 */
	public function get_role( $return = null ) {

		// short-circuit for owners
		if ( $this->is_team_owner() ) {
			return 'label' === $return ? __( 'Owner', 'woocommerce-memberships-for-teams' ) : 'owner';
		}

		$role = get_user_meta( $this->get_id(), $this->team_role_meta, true );

		if ( 'label' === $return ) {
			$roles = wc_memberships_for_teams_get_team_member_roles();
			$role  = ! empty( $roles[ $role ] ) ? $roles[ $role ] : $role;
		}

		return $role;
	}


	/**
	 * Sets the member's role in the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $role user's role in team, either `member` or `manager`, defaults to `member`
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function set_role( $role = 'member' ) {

		// if a falsy value was provided for role, default to 'member'
		if ( ! $role ) {
			$role = 'member';
		}

		if ( ! wc_memberships_for_teams_is_valid_team_member_role( $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid role', 'woocommerce-memberships-for-teams' ) );
		}

		// do not allow changing owner's role
		if ( $this->is_team_owner() ) {
			throw new Framework\SV_WC_Plugin_Exception( __( "Changing owner's role is not allowed", 'woocommerce-memberships-for-teams' ) );
		}

		update_user_meta( $this->get_id(), $this->team_role_meta, $role );
	}


	/**
	 * Returns the member's name for display purposes.
	 *
	 * Tries to return the display name, falls back to the email.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->user->display_name ? $this->user->display_name : $this->user->user_email;
	}


	/**
	 * Returns the member's email.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->user->user_email;
	}


	/**
	 * Returns the UTC date when member was added to the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string|int|null date in specified format
	 */
	public function get_added_date( $format = 'mysql' ) {

		$added_date = get_user_meta( $this->id, $this->team_added_date_meta, true );

		return $added_date ? wc_memberships_format_date( $added_date, $format ) : null;
	}


	/**
	 * Returns the local date when member was added to the team.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string|int|null localized date in specified format
	 */
	public function get_local_added_date( $format = 'mysql' ) {

		$date = $this->get_added_date( 'timestamp' );

		return ! empty( $date ) ? wc_memberships_adjust_date_by_timezone( $date, $format ) : null;
	}


	/**
	 * Returns a list of all the team-based user memberships for the team member.
	 *
	 * @since 1.0.0
	 *
	 * @param string $return (optional) either 'user_memberships' or 'ids', defaults to 'user_memberships'
	 * @return \WC_Memberships_User_Membership[]|array() an array of user memberships or ids (may be empry)
	 */
	public function get_user_memberships( $return = 'user_memberships' ) {

		$args = array(
			'author'      => $this->get_id(),
			'post_type'   => 'wc_user_membership',
			'post_status' => 'any',
			'nopaging'    => true,
			'meta_key'    => '_team_id',
			'meta_value'  => $this->get_team_id(),
		);

		if ( 'ids' === $return ) {
			$args['fields'] = 'ids';
		}

		$results = get_posts( $args );

		return 'ids' === $return ? $results : array_filter( array_map( 'wc_memberships_get_user_membership', $results ) );
	}


	/**
	 * Returns the first team-based user membership for the team member.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_User_Membership|null the user membership instance or null if none found
	 */
	public function get_user_membership() {

		$user_memberships = $this->get_user_memberships();

		return ! empty( $user_memberships ) ? $user_memberships[0] : null;
	}


	/**
	 * Returns the id of the first team-based user membership for the team member.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null user membership id or null if none
	 */
	public function get_user_membership_id() {

		$user_membership_ids = $this->get_user_memberships( 'ids' );

		return ! empty( $user_membership_ids ) ? (int) $user_membership_ids[0] : null;
	}


}
