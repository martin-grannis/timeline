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
 * Team membership renewal reminder email.
 *
 * @type string $email_heading email heading
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Emails\Membership_Renewal_Reminder $email email object
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
 *
 * @version 1.1.2
 * @since 1.0.0
 */

$owner = $team->get_owner();
$plan  = $team->get_plan();

$site_title          = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
$membership_end_date = date_i18n( wc_date_format(), $team->get_local_membership_end_date( 'timestamp' ) );

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php printf(
		/* translators: Placeholder: %s - email recipient's name */
		esc_html__( 'Hey %s,', 'woocommerce-memberships-for-teams' ),
		$owner->display_name
	); ?>
</p>

<p>
	<?php printf(
		/* translators: Placeholders: %1$s - site title, %2$s - membership end date */
		esc_html__( 'Your team membership access at %1$s expired on %2$s!', 'woocommerce-memberships-for-teams' ),
		$site_title,
		$membership_end_date
	); ?>
</p>

<p>
	<?php printf(
		/* translators: Placeholder: %s - membership plan name */
		esc_html__( 'If you would like to continue having access to %s, please renew your membership.', 'woocommerce-memberships-for-teams' ),
		$plan->get_name()
	); ?>
</p>

<p><a href="<?php echo esc_url( $team->get_renew_membership_url() ); ?>"><?php esc_html_e( 'Click here to log in and renew your team membership now', 'woocommerce-memberships-for-teams' ); ?></a>.</p>

<?php

do_action( 'woocommerce_email_footer', $email );
