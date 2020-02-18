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
 * Team Membership Abstract Email class.
 *
 * TODO consider using \WC_Memberships_User_Membership_Email as the default abstract {FN 2019-01-16}
 *
 * @since 1.0.0
 */
abstract class Membership_Email extends \WC_Email {


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
			'team'          => $this->object,
			'email_heading' => $this->get_heading(),
			'email'         => $this,
			'sent_to_admin' => false,
			'plain_text'    => false
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
			'team'          => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true
		) );

		return ob_get_clean();
	}


	/**
	 * Parses the email's body merge tags.
	 *
	 * @since 1.0.0
	 */
	protected function parse_merge_tags() {

		if ( ! $this->object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) {
			return;
		}

		$team = $this->object;

		// get owner data
		$owner            = $team->get_owner();
		$owner_email      = $this->get_recipient();
		$owner_name       = ! empty( $owner->display_name ) ? $owner->display_name : '';
		$owner_first_name = ! empty( $owner->first_name )   ? $owner->first_name   : $owner_name;
		$owner_last_name  = ! empty( $owner->last_name )    ? $owner->last_name    : '';
		$owner_full_name  = $owner_first_name && $owner_last_name ? $owner_first_name . ' ' . $owner->last_name : $owner_name;

		// membership expiry date
		$expiration_date_timestamp = $team->get_local_membership_end_date( 'timestamp' );

		$plan = $team->get_plan();

		$this->find['team_name']                  = '{team_name}';
		$this->find['owner_email']                = '{owner_email}';
		$this->find['owner_name']                 = '{owner_name}';
		$this->find['owner_first_name']           = '{owner_first_name}';
		$this->find['owner_last_name']            = '{owner_last_name}';
		$this->find['owner_full_name']            = '{owner_full_name}';
		$this->find['membership_plan']            = '{membership_plan}';
		$this->find['membership_expiration_date'] = '{membership_expiration_date}';

		$this->replace['team_name']                  = $team ? $team->get_name() : '';
		$this->replace['owner_email']                = $owner_email;
		$this->replace['owner_name']                 = $owner_name;
		$this->replace['owner_first_name']           = $owner_first_name;
		$this->replace['owner_last_name']            = $owner_last_name;
		$this->replace['owner_full_name']            = $owner_full_name;
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
			$form_fields['heading']['description'] = sprintf( __( '%1$s inserts the team name, %2$s inserts the membership plan name.', 'woocommerce-memberships-for-teams' ), '<strong><code>{team_name}</code></strong>', '<strong><code>{membership_plan}</code></strong>' );
		}

		// email body is set on a membership plan basis in plan settings
		if ( isset( $form_fields['body'] ) ) {
			unset( $form_fields ['body'] );
		}

		// set the updated fields
		$this->form_fields = $form_fields;
	}


}
