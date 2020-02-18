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
 * Capabilities class
 *
 * @since 1.0.0
 */
class Capabilities {


	/**
	 * Sets up the capabilities class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// adjust user capabilities
		add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 10, 3 ); // 1 step later than Memberships to prevent conflicts with cancel/renew caps
	}


	/**
	 * Checks if a user has a certain capability.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $all_caps all capabilities
	 * @param array $caps capabilities
	 * @param array $args capability arguments
	 * @return array all capabilities
	 */
	public function user_has_cap( $all_caps, $caps, $args ) {
		global $pagenow, $typenow;

		if ( ! empty( $caps ) ) {
			foreach ( $caps as $cap ) {

				switch ( $cap ) {

					// only owners can adjust seats on teams, renew team memberships, or manage team settings:
					case 'wc_memberships_for_teams_update_team_seats':
					case 'wc_memberships_for_teams_renew_team_membership':
					case 'wc_memberships_for_teams_manage_team_settings':

						$user_id = (int) $args[1];
						$team    = $this->get_team_from_args( $args );

						if ( $user_id && $team && $team->is_user_owner( $user_id ) ) {
							$all_caps[ $cap ] = true;
						}

					break;

					// owners and managers can manage team and its members
					case 'wc_memberships_for_teams_manage_team':
					case 'wc_memberships_for_teams_manage_team_members':

						$user_id = (int) $args[1];
						$team    = $this->get_team_from_args( $args );
						$member  = wc_memberships_for_teams_get_team_member( $team, $user_id );

						// users cannot manage their own roles in team
						if ( $user_id && $team && ( $team->is_user_owner( $user_id ) || ( $member && $member->has_role( 'manager' ) ) ) ) {
							$all_caps[ $cap ] = true;
						}

					break;

					// owners and managers can manage team members, but not themselves
					case 'wc_memberships_for_teams_manage_team_member':

						$user_id         = (int) $args[1];
						$team            = $this->get_team_from_args( $args );
						$member          = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$other_member_id = (int) $args[3];
						$other_member    = $other_member_id ? wc_memberships_for_teams_get_team_member( $team, $other_member_id ) : null;

						// only owners and managers can manage team members
						if ( $user_id && $other_member_id && $team && ( $team->is_user_owner( $user_id ) || ( $member && $member->has_role( 'manager' ) ) ) ) {

							// users cannot manage themselves, though
							if ( $user_id === $other_member_id ) {
								break;
							}

							// check if able to manage another manager
							if ( $other_member && $other_member->has_role( 'manager' ) && 'yes' !== get_option( 'wc_memberships_for_teams_managers_may_manage_managers', 'yes' ) ) {
								break;
							}

							// ...and managers cannot manage owners
							if ( $member && $member->has_role( 'manager' ) && $team->is_user_owner( $other_member_id ) ) {
								break;
							}

							$all_caps[ $cap ] = true;
						}

					break;

					// owners and managers can remove team members, but manager cannot remove themselves
					case 'wc_memberships_for_teams_remove_team_member':

						// short-circuit if member removal is disabled
						if ( 'no' === get_option( 'wc_memberships_for_teams_allow_removing_members', 'yes' ) ) {
							break;
						}

						$user_id         = (int) $args[1];
						$team            = $this->get_team_from_args( $args );
						$member          = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$other_member_id = (int) $args[3];
						$other_member    = $other_member_id ? wc_memberships_for_teams_get_team_member( $team, $other_member_id ) : null;
						$is_owner        = $team->is_user_owner( $user_id );

						// only owners and managers can remove team members
						if ( $user_id && $other_member_id && $team && ( $is_owner || ( $member && $member->has_role( 'manager' ) ) ) ) {

							// users cannot remove themselves, though, unless an owner and they don't need to take up a seat
							if ( $user_id === $other_member_id && ( ! $is_owner || ( $is_owner && 'yes' === get_option( 'wc_memberships_for_teams_owners_must_take_seat' ) ) ) ) {
								break;
							}

							// check if able to manage another manager
							if ( $other_member && $other_member->has_role( 'manager' ) && 'yes' !== get_option( 'wc_memberships_for_teams_managers_may_manage_managers', 'yes' ) ) {
								break;
							}

							// ...and managers cannot remove owners
							if ( $member && $member->has_role( 'manager' ) && $team->is_user_owner( $other_member_id ) ) {
								break;
							}

							$all_caps[ $cap ] = true;
						}

					break;

					// prevent deleting teams with active members
					case 'delete_published_memberships_team' :
					case 'delete_published_memberships_teams' :

						// this workaround (*hack*, *cough*) allows displaying the trash/delete link on teams list table even if the team has active members
						if ( 'edit.php' === $pagenow && 'wc_memberships_team' === $typenow && empty( $_POST ) && is_admin() ) {
							break;
						}

						$team = $this->get_team_from_args( $args );

						if ( $team->has_active_members() ) {
							$all_caps[ $cap ] = false;
						}

					break;

					// prevent cancelling or renewing team-based user memberships
					case 'wc_memberships_cancel_membership' :
					case 'wc_memberships_renew_membership' :

						if ( ! empty( $all_caps[ $cap ] ) ) {

							$user_id            = (int) $args[1];
							$user_membership_id = (int) $args[2];
							$user_membership    = wc_memberships_get_user_membership( $user_membership_id );
							$team_id            = wc_memberships_for_teams_get_user_membership_team_id( $user_membership_id );

							if ( $team_id && $user_membership ) {
								$all_caps[ $cap ] = false;
							}
						}

					break;
				}
			}
		}

		return $all_caps;
	}


	/**
	 * Returns the team instance from capability check arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args an array of arguments passed to 'user_has_cap'
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team|false
	 */
	private function get_team_from_args( $args ) {
		return ( $args[2] instanceof Team ) ? $args[2] : wc_memberships_for_teams_get_team( $args[2] );
	}

}
