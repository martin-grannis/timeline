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
 * Team Membership Renewal Reminder Email class.
 *
 * TODO consider using \WC_Memberships_User_Membership_Email as the default abstract {FN 2019-01-16}
 *
 * @since 1.0.0
 */
class Membership_Renewal_Reminder extends Membership_Email {


	/**
	 * Sets up the invitation email class.
	 */
	public function __construct() {

		$this->id             = 'wc_memberships_for_teams_team_membership_renewal_reminder';
		$this->customer_email = true;

		$this->title          = __( 'Team membership renewal reminder', 'woocommerce-memberships-for-teams' );

		/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
		$description  =  sprintf( __( 'Team membership renewal reminder emails are sent to team owners inviting them to renew their membership. %1$sRenewal reminders are not sent for non-renewable team memberships, such as registration-based or admin-assign only memberships%2$s.', 'woocommerce-memberships-for-teams' ), '<strong>', '</strong>' );

		$this->description    = $description;
		$this->subject        = __( 'Renew {team_name} membership on {site_title}!', 'woocommerce-memberships-for-teams');
		$this->heading        = __( 'You can renew your {membership_plan} for {team_name}', 'woocommerce-memberships-for-teams');

		$this->template_html  = 'emails/team-membership-renewal-reminder.php';
		$this->template_plain = 'emails/plain/team-membership-renewal-reminder.php';

		parent::__construct();
	}


	/**
	 * Triggers the sending of this email.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team|int $team team instance or id
	 */
	public function trigger( $team ) {

		$this->object    = is_numeric( $team ) ? wc_memberships_for_teams_get_team( $team ) : $team;
		$owner           = $this->object ? $this->object->get_owner() : null;
		$this->recipient = $owner ? $owner->user_email : null;

		if (    ! $this->object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team
			 || ! $this->object->is_membership_expired() // only send if team membership is expired
			 || ! $this->object->can_be_renewed()
			 || ! $this->is_enabled()
			 || ! $this->get_recipient() ) {
			return;
		}

		$this->parse_merge_tags();

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}


}
