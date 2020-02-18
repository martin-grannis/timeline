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

namespace SkyVerge\WooCommerce\Memberships\Teams\Emails;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Team Invitation Email class.
 *
 * TODO consider using \WC_Memberships_User_Membership_Email as the default abstract {FN 2019-01-16}
 *
 * @since 1.0.0
 */
class Invitation extends \WC_Email {


	/**
	 * Sets up the invitation email class.
	 */
	public function __construct() {

		$this->id             = 'wc_memberships_for_teams_team_invitation';
		$this->customer_email = true;

		$this->title          = __( 'Team invitation', 'woocommerce-memberships-for-teams' );
		$this->description    = __( 'Invitation emails are sent when a new member is invited to a team.', 'woocommerce-memberships-for-teams' );

		$this->subject        = __( "You've been invited to join {team_name}", 'woocommerce-memberships-for-teams');
		$this->heading        = __( '{sender_name} has invited you to join the {team_name} team on {site_title}.', 'woocommerce-memberships-for-teams');

		$this->template_html  = 'emails/team-invitation.php';
		$this->template_plain = 'emails/plain/team-invitation.php';

		parent::__construct();
	}


	/**
	 * Triggers the sending of this email.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation|int $invitation invitation instance or id
	 */
	public function trigger( $invitation ) {

		$this->object    = is_numeric( $invitation ) ? wc_memberships_for_teams_get_invitation( $invitation ) : $invitation;
		$this->recipient = $this->object ? $invitation->get_email() : null;

		if (    ! $this->object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Invitation
			 || ! $this->is_enabled()
			 || ! $this->get_recipient() ) {
			return;
		}

		$this->parse_merge_tags();

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


	/**
	 * Returns the email HTML content.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	public function get_content_html() {

		ob_start();

		wc_get_template( $this->template_html, array(
			'invitation'      => $this->object,
			'email_heading'   => $this->get_heading(),
			'email'           => $this,
			'sent_to_admin'   => false,
			'plain_text'      => false
		) );

		return ob_get_clean();
	}


	/**
	 * Returns email plain text content.
	 *
	 * @since 1.0.0
	 *
	 * @return string plain text
	 */
	public function get_content_plain() {

		ob_start();

		wc_get_template( $this->template_plain, array(
			'invitation'      => $this->object,
			'email_heading'   => $this->get_heading(),
			'sent_to_admin'   => false,
			'plain_text'      => true
		) );

		return ob_get_clean();
	}


	/**
	 * Parses the email's body merge tags.
	 *
	 * @since 1.0.0
	 */
	protected function parse_merge_tags() {

		if ( ! $this->object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Invitation ) {
			return;
		}

		$invitation = $this->object;

		// get sender data
		$sender            = $invitation->get_sender();
		$sender_name       = ! empty( $sender->display_name ) ? $sender->display_name : '';
		$sender_first_name = ! empty( $sender->first_name )   ? $sender->first_name   : $sender_name;
		$sender_last_name  = ! empty( $sender->last_name )    ? $sender->last_name    : '';
		$sender_full_name  = $sender_first_name && $sender_last_name ? $sender_first_name . ' ' . $sender->last_name : $sender_name;

		// get recipient data
		$recipient            = $invitation->get_user();
		$recipient_email      = $this->get_recipient();
		$recipient_name       = ! empty( $recipient->display_name ) ? $recipient->display_name : '';
		$recipient_first_name = ! empty( $recipient->first_name )   ? $recipient->first_name   : $recipient_name;
		$recipient_last_name  = ! empty( $recipient->last_name )    ? $recipient->last_name    : '';
		$recipient_full_name  = $recipient_first_name && $recipient_last_name ? $recipient_first_name . ' ' . $recipient->last_name : $recipient_name;

		// membership expiry date
		$expiration_date_timestamp = $invitation->get_local_date( 'timestamp' );

		$team = $invitation->get_team();
		$plan = $invitation->get_plan();

		$this->find['team_name']                  = '{team_name}';
		$this->find['sender_name']                = '{sender_name}';
		$this->find['sender_first_name']          = '{sender_first_name}';
		$this->find['sender_last_name']           = '{sender_last_name}';
		$this->find['sender_full_name']           = '{sender_full_name}';
		$this->find['recipient_email']            = '{recipient_email}';
		$this->find['recipient_name']             = '{recipient_name}';
		$this->find['recipient_first_name']       = '{recipient_first_name}';
		$this->find['recipient_last_name']        = '{recipient_last_name}';
		$this->find['recipient_full_name']        = '{recipient_full_name}';
		$this->find['membership_plan']            = '{membership_plan}';
		$this->find['membership_expiration_date'] = '{membership_expiration_date}';

		$this->replace['team_name']                  = $team ? $team->get_name() : '';
		$this->replace['sender_name']                = $sender_name;
		$this->replace['sender_first_name']          = $sender_first_name;
		$this->replace['sender_last_name']           = $sender_last_name;
		$this->replace['sender_full_name']           = $sender_full_name;
		$this->replace['recipient_email']            = $recipient_email;
		$this->replace['recipient_name']             = $recipient_name;
		$this->replace['recipient_first_name']       = $recipient_first_name;
		$this->replace['recipient_last_name']        = $recipient_last_name;
		$this->replace['recipient_full_name']        = $recipient_full_name;
		$this->replace['membership_plan']            = $plan ? $plan->get_name() : '';
		$this->replace['membership_expiration_date'] = date_i18n( wc_date_format(), $expiration_date_timestamp );
	}


	/**
	 * Adjusts the email settings form fields.
	 *
	 * Extends and overrides parent method.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		// set the default fields from parent
		parent::init_form_fields();

		$form_fields = $this->form_fields;

		if ( isset( $form_fields['enabled'] ) ) {

			// set email enabled by default
			$form_fields['enabled']['default'] = 'yes';
		}

		if ( isset( $form_fields['subject'] ) ) {

			// adds a subject merge tag hint in field description
			$form_fields['subject']['desc_tip']    = $form_fields['subject']['description'];
			$form_fields['subject']['description'] = sprintf( __( '%s inserts your site name.', 'woocommerce-memberships-for-teams' ), '<strong><code>{site_name}</code></strong>' );
		}

		if ( isset( $form_fields['heading'] ) ) {

			// adds a heading merge tag hint in field description
			$form_fields['heading']['desc_tip']    = $form_fields['heading']['description'];
			$form_fields['heading']['description'] = sprintf( __( '%s inserts the membership plan name.', 'woocommerce-memberships-for-teams' ), '<strong><code>{membership_plan}</code></strong>' );
		}

		// email body is set on a membership plan basis in plan settings
		if ( isset( $form_fields['body'] ) ) {
			unset( $form_fields ['body'] );
		}

		// TODO: consider adding a recipient name fallback if not provided, ie "Hey John!" => "Hey there!" {IT 2017-08-31}

		// set the updated fields
		$this->form_fields = $form_fields;
	}


}
