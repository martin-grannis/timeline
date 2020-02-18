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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Admin Team Members class
 *
 * @since 1.0.0
 */
class Team_Members {


	/**
	 * Sets up the admin team members class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_action_team_bulk_remove_members',  array( $this, 'handle_member_bulk_actions' ) );
		add_action( 'admin_action_team_bulk_set_as_members',  array( $this, 'handle_member_bulk_actions' ) );
		add_action( 'admin_action_team_bulk_set_as_managers', array( $this, 'handle_member_bulk_actions' ) );

		add_action( 'admin_action_team_add_member',     array( $this, 'handle_member_action' ) );
		add_action( 'admin_action_team_remove_member',  array( $this, 'handle_member_action' ) );
		add_action( 'admin_action_team_set_as_member',  array( $this, 'handle_member_action' ) );
		add_action( 'admin_action_team_set_as_manager', array( $this, 'handle_member_action' ) );
	}


	/**
	 * Handles team members list table bulk actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @return string the redirect url
	 */
	public function handle_member_bulk_actions() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'team-bulk-edit-members' );

		$action = str_replace( 'admin_action_team_', '', current_action() );
		$team   = wc_memberships_for_teams_get_team( $id );
		$users  = ! empty( $_REQUEST['users'] ) ? (array) $_REQUEST['users'] : array();

		if ( ! $team ) {
			return;
		}

		if ( empty( $users ) ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		switch ( $action ) {

			case 'bulk_remove_members':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$team->remove_member( $user_id, ! empty( $_REQUEST['keep_user_memberships'] ) );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d member was removed from the team.', '%d members were removed from the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;

			case 'bulk_set_as_members':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$member->set_role( 'member' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set as a member of the team.', '%d users were set as members of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;

			case 'bulk_set_as_managers':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$member->set_role( 'manager' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set as a manager of the team.', '%d users were set as managers of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


	/**
	 * Handles individual member actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_member_action() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id      = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';
		$team    = wc_memberships_for_teams_get_team( $id );
		$user_id = ! empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;

		if ( ! $user_id || ! $team ) {
			return;
		}

		$action       = str_replace( 'admin_action_team_', '', current_action() );
		$nonce_action = 'team-' . str_replace( '_', '-', $action ) . ( 'add_member' !== $action ? '-' . $user_id : '' );

		check_admin_referer( $nonce_action );

		if ( 'add_member' === $action ) {
			$user   = is_numeric( $user_id ) ? get_userdata( $user_id ) : get_user_by( $user_id, 'email' );
			$name   = $user->display_name;
			$member = null;
		} else {
			$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
			$name   = $member ? $member->get_name() : '';
		}

		// the following gettext messages are documented in Admin/Teams.php

		switch ( $action ) {

			case 'add_member':

				try {

					$team->add_member( $user_id );

					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was added to team as a member.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( \Exception $e ) {

					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot add member: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;

			case 'remove_member':

				try {

					$team->remove_member( $user_id, ! empty( $_REQUEST['keep_user_memberships'] ) );

					$message = is_numeric( $user_id ) ? __( '%s was removed from the team.', 'woocommerce-memberships-for-teams' ) : __( 'Invitation for %s was cancelled.', 'woocommerce-memberships-for-teams' );

					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( $message, $name ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					$message = is_numeric( $user_id ) ? __( 'Cannot remove member: %s', 'woocommerce-memberships-for-teams' ) : __( 'Cannot cancel invittation for %s.', 'woocommerce-memberships-for-teams' );

					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( $message, $e->getMessage() ) );
				}

			break;

			case 'set_as_member':

				if ( $member ) {

					try {

						$member->set_role( 'member' );

						wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set as a member of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

					} catch ( Framework\SV_WC_Plugin_Exception $e ) {

						wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
					}
				}

			break;

			case 'set_as_manager':

				if ( $member ) {

					try {

						$member->set_role( 'manager' );

						wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set as a manager of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

					} catch ( Framework\SV_WC_Plugin_Exception $e ) {

						wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
					}
				}

			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


}
