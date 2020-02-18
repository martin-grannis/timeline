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

defined( 'ABSPATH' ) or exit;

/**
 * Team invitation email.
 *
 * @type string $email_heading email heading
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Emails\Invitation $email email object
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation instance
 *
 * @version 1.1.2
 * @since 1.0.0
 */

$team = $invitation->get_team();
$plan = $invitation->get_plan();

echo '= ' . $email_heading . " =\n\n";

printf(
	/* translators: Placeholder: %s - membership plan name */
	esc_html__( 'This will give you %s access.', 'woocommerce-memberships-for-teams' ),
	$plan->get_name()
);

echo "\r\n\r\n";

if ( $invitation->get_user() ) {

	esc_html_e( 'Please use the link below to sign in and accept your invite.', 'woocommerce-memberships-for-teams' );

	if ( $existing_membership = $team->get_existing_user_membership( $user->ID ) ) {

		echo "\r\n\r\n";

		if ( $current_team = wc_memberships_for_teams()->get_teams_handler_instance()->get_user_membership_team( $existing_membership->get_id() ) ) {

			printf(
				/* translators: Placeholders: %1$s - current team name, %2$s - membership plan name, %3$s - new team name to join */
				esc_html__( 'You are a member of %1$s, which already gives you access to %2$s. Joining %3$s means you will leave your current team and your existing membership will be moved under new team management.', 'woocommerce-memberships-for-teams' ),
				$current_team->get_name(),
				$team->get_plan()->get_name(),
				$team->get_name()
			);

		} else {

			printf(
				/* translators: Placeholder: %s - membership plan name */
				esc_html__( 'Your existing %s membership will be moved under team management.', 'woocommerce-memberships-for-teams' ),
				$plan->get_name()
			);
		}
	}

} else {

	esc_html_e( 'Please use the confirmation link below to sign up for an account and accept your invite.', 'woocommerce-memberships-for-teams' );
}

echo "\r\n\r\n";

echo esc_url( $invitation->get_accept_url() ) . "\r\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
