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
 * Admin Invitations class
 *
 * @since 1.0.0
 */
class Invitations {


	/**
	 * Sets up the admin invitations class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_action_team_invitations_bulk_resend',          array( $this, 'handle_invitation_bulk_actions' ) );
		add_action( 'admin_action_team_invitations_bulk_cancel',          array( $this, 'handle_invitation_bulk_actions' ) );
		add_action( 'admin_action_team_invitations_bulk_set_as_members',  array( $this, 'handle_invitation_bulk_actions' ) );
		add_action( 'admin_action_team_invitations_bulk_set_as_managers', array( $this, 'handle_invitation_bulk_actions' ) );

		add_action( 'admin_action_team_invitation_resend',         array( $this, 'handle_invitation_action' ) );
		add_action( 'admin_action_team_invitation_cancel',         array( $this, 'handle_invitation_action' ) );
		add_action( 'admin_action_team_invitation_set_as_member',  array( $this, 'handle_invitation_action' ) );
		add_action( 'admin_action_team_invitation_set_as_manager', array( $this, 'handle_invitation_action' ) );
	}


	/**
	 * Handles team invitations list table bulk actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @return string the redirect url
	 */
	public function handle_invitation_bulk_actions() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'team-bulk-edit-members' );

		$action         = str_replace( 'admin_action_team_invitations_', '', current_action() );
		$team           = wc_memberships_for_teams_get_team( $id );
		$invitation_ids = ! empty( $_REQUEST['invitations'] ) ? (array) $_REQUEST['invitations'] : array();

		if ( ! $team ) {
			return;
		}

		if ( empty( $invitation_ids ) ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		switch ( $action ) {
			case 'bulk_resend':

				$num = 0;

				foreach ( $invitation_ids as $id ) {

					try {
						$invitation = wc_memberships_for_teams_get_invitation( $id );
						$invitation->send();
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d invitation was re-sent.', '%d invitations were re-sent.', $num, 'woocommerce-memberships-for-teams' ), $num ) );
			break;

			case 'bulk_cancel':

				$num = 0;

				foreach ( $invitation_ids as $id ) {

					try {
						$invitation = wc_memberships_for_teams_get_invitation( $id );
						$invitation->cancel();
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d invitation was cancelled.', '%d invitations were cancelled.', $num, 'woocommerce-memberships-for-teams' ), $num ) );
			break;

			case 'bulk_set_as_members':

				$num = 0;

				foreach ( $invitation_ids as $id ) {

					try {
						$invitation = wc_memberships_for_teams_get_invitation( $id );
						$invitation->set_member_role( 'member' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set to be a member of the team.', '%d users were set to be members of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );
			break;

			case 'bulk_set_as_managers':

				$num = 0;

				foreach ( $invitation_ids as $id ) {

					try {
						$invitation = wc_memberships_for_teams_get_invitation( $id );
						$invitation->set_member_role( 'manager' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set to be a manager of the team.', '%d users were set to be managers of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );
			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


	/**
	 * Handles individual invitation actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_invitation_action() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id            = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';
		$team          = wc_memberships_for_teams_get_team( $id );
		$invitation_id = ! empty( $_REQUEST['invitation'] ) ? $_REQUEST['invitation'] : null;

		if ( ! $invitation_id || ! $team ) {
			return;
		}

		$action       = str_replace( 'admin_action_team_invitation_', '', current_action() );
		$nonce_action = 'team-' . str_replace( '_', '-', $action ) . '-' . $invitation_id;

		check_admin_referer( $nonce_action );

		$invitation = wc_memberships_for_teams_get_invitation( $invitation_id );

		if ( ! $invitation instanceof \SkyVerge\WooCommerce\Memberships\Teams\Invitation ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		$name = $invitation->get_name();

		switch ( $action ) {

			case 'resend':

				try {

					$invitation->send();

					/* translators: Placeholder: %s - email address */
					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( 'Invitation to %s re-sent.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( \Exception $e ) {

					/* translators:Placeholder: %s - error message */
					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot send invitation: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;

			case 'cancel':

				try {

					$invitation->cancel();

					/* translators: Placeholder: %s - email address */
					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( 'Invitation for %s was cancelled.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					/* translators: Placeholder: %s - error message */
					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot cancel invitation: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;

			case 'set_as_member':

				try {

					$invitation->set_member_role( 'member' );

					/* translators: Placeholder: %s - email address */
					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set to be a member of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					/* translators: Placeholder: %s - error message */
					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;

			case 'set_as_manager':

				try {

					$invitation->set_member_role( 'manager' );

					/* translators: Placeholder: %s - email address */
					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set to be a manager of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					/* translators: Placeholder: %s - error message */
					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


}
