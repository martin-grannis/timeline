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
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Invitation instance
 * @throws Framework\SV_WC_Plugin_Exception on validation errors or when wp_insert_post fails
 */
function wc_memberships_for_teams_create_invitation( $args = array() ) {

	return wc_memberships_for_teams()->get_invitations_instance()->create_invitation( $args );
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
 * @return false|\SkyVerge\WooCommerce\Memberships\Teams\Invitation invitation instance or false on failure
 */
function wc_memberships_for_teams_get_invitation( $id, $email = null ) {
	return wc_memberships_for_teams()->get_invitations_instance()->get_invitation( $id, $email );
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
function wc_memberships_for_teams_get_invitations( $team_id, $args = array(), $return = null, $force_refresh = false ) {
	return wc_memberships_for_teams()->get_invitations_instance()->get_invitations( $team_id, $args, $return, $force_refresh );
}


/**
 * Returns all invitation statuses.
 *
 * @since 1.0.0
 *
 * @return array associative array of statuses and their arguments
 */
function wc_memberships_for_teams_get_invitation_statuses() {
	return wc_memberships_for_teams()->get_invitations_instance()->get_invitation_statuses();
}
