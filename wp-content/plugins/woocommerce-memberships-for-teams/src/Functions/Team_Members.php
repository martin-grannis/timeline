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

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Returns a team member.
 *
 * @since 1.0.0
 *
 * @param int|\WP_Post|\SkyVerge\WooCommerce\Memberships\Teams\Team $team_id team id, post object or instance
 * @param int|string|\WP_User $user_id user id, email or user instance
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member|false team member instance or false on failure
 */
function wc_memberships_for_teams_get_team_member( $team_id, $user_id ) {
	return wc_memberships_for_teams()->get_team_members_instance()->get_team_member( $team_id, $user_id );
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
 *     @type int $per_page the number of team members to fetch per page, corresponds to the number param for get_users()
 * }
 * @param string $return (optional) what to return - set to 'query' to return the \WP_User_Query instance instead of a list of team member instances
 * @param bool $force_refresh (optional) whether to force reloading the results even if a previous result has been memoized, defaults to false
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team_Member[]|\array|false $team_members an array of team members, associative array of query results or false on failure
 */
function wc_memberships_for_teams_get_team_members( $team_id, $args = array(), $return = null, $force_refresh = false ) {
	return wc_memberships_for_teams()->get_team_members_instance()->get_team_members( $team_id, $args, $return, $force_refresh );
}



/**
 * Returns a list of available team member roles.
 *
 * @since 1.0.0
 *
 * @return string[] associative array of role ids and labels
 */
function wc_memberships_for_teams_get_team_member_roles() {
	return wc_memberships_for_teams()->get_team_members_instance()->get_team_member_roles();
}


/**
 * Checks if a team member role is valid or not.
 *
 * @since 1.0.0
 *
 * @param string $role the role to check
 * @return bool true if valid, false otherwise
 */
function wc_memberships_for_teams_is_valid_team_member_role( $role ) {
	return wc_memberships_for_teams()->get_team_members_instance()->is_valid_team_member_role( $role );
}
