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
 * Emails handler.
 *
 * This class handles all email-related functionality in Memberships for Teams.
 *
 * TODO consider avoiding code duplication by using \WC_Memberships_Emails core emails handler {FN 2019-01-16}
 *
 * @since 1.0.0
 */
class Emails {


	/**
	 * Sets up team emails.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// add emails
		add_filter( 'woocommerce_email_classes', array( $this, 'get_email_classes' ) );

		// prevent individual user membership expiry emails from being sent
		$emails = array(
			'WC_Memberships_User_Membership_Ending_Soon_Email',
			'WC_Memberships_User_Membership_Ended_Email',
			'WC_Memberships_User_Membership_Renewal_Reminder_Email',
		);

		foreach ( $emails as $email ) {
			add_filter( "woocommerce_email_enabled_{$email}", array( $this, 'disable_team_user_membership_email' ), 10, 2 );
		}
	}


	/**
	 * Adds custom team emails to WC emails.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $emails optional, associative array of email objects
	 * @return \WC_Email[] associative array with email objects as values
	 */
	public function get_email_classes( $emails = array() ) {

		// applies when this method is called directly and not as WooCommerce hook callback
		if ( empty( $emails ) && ! class_exists( '\WC_Email' ) ) {
			WC()->mailer();
		}

		$emails['wc_memberships_for_teams_team_invitation']                  = new Emails\Invitation;
		$emails['wc_memberships_for_teams_team_membership_renewal_reminder'] = new Emails\Membership_Renewal_Reminder;
		$emails['wc_memberships_for_teams_team_membership_ending_soon']      = new Emails\Membership_Ending_Soon;
		$emails['wc_memberships_for_teams_team_membership_ended']            = new Emails\Membership_Ended;

		return $emails;
	}


	/**
	 * Sends a team email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email the type of team email to send
	 * @param mixed $args the param to pass to the email to be sent
	 */
	public function send_email( $email, $args ) {

		// ensure the email class is capitalized
		$emails = $this->get_email_classes();

		if ( ! isset( $emails[ $email ] ) || ! method_exists( $emails[ $email ], 'trigger' ) ) {
			return;
		}

		$emails[ $email ]->trigger( $args );
	}


	/**
	 * Sends an invitation email.
	 *
	 * @see Emails\Invitation
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation|int $invitation invitation instance or id
	 */
	public function send_invitation_email( $invitation ) {

		$this->send_email( 'wc_memberships_for_teams_team_invitation', $invitation );
	}


	/**
	 * Sends a team membership renewal reminder email.
	 *
	 * @see Emails\Membership_Renewal_Reminder
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team|int $team teams instance or id
	 */
	public function send_membership_renewal_reminder_email( $team ) {
		$this->send_email( 'wc_memberships_for_teams_team_membership_renewal_reminder', $team );
	}


	/**
	 * Sends a team membership expiring soon email.
	 *
	 * @see Emails\Membership_Ending_Soon
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team|int $team teams instance or id
	 */
	public function send_membership_ending_soon_email( $team ) {
		$this->send_email( 'wc_memberships_for_teams_team_membership_ending_soon', $team );
	}


	/**
	 * Sends a team membership ended email.
	 *
	 * @see Emails\Membership_Ended
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team|int $team teams instance or id
	 */
	public function send_membership_ended_email( $team ) {
		$this->send_email( 'wc_memberships_for_teams_team_membership_ended', $team );
	}


	/**
	 * Disables user membership emails for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_enabled whether the email is enabled or not
	 * @param \WC_Memberships_User_Membership|int $user_membership the user membership instance or id
	 * @return bool
	 */
	public function disable_team_user_membership_email( $is_enabled, $user_membership ) {

		if ( $is_enabled ) {

			if ( is_numeric( $user_membership ) ) {
				$user_membership = wc_memberships_get_user_membership( $user_membership );
			}

			// if linked to a team, skip
			if ( $user_membership && $team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership->get_id() ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}


}
