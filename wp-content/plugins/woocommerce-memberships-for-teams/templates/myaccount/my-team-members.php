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

/**
 * Renders the team members section on My Account page
 *
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
 * @type int $paged the current page number
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Frontend\Teams_Area $teams_area teams area handler instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

$seat_count       = $team->get_seat_count();
$remaining_seats  = $team->get_remaining_seat_count();
$show_invitations = ! empty( $_REQUEST['show_invitations'] );
$table_args       = array( 'team' => $team, 'paged' => $paged, 'teams_area' => $teams_area );

?>
<div class="woocommerce-account-my-teams">

	<?php

	/**
	 * Fires before the Team Members table in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_before_my_team_members', $team );

	?>

	<p>
		<?php if ( $seat_count > 0 ) : ?>
			<?php printf( _n( 'This team has <strong>%d seat remaining</strong>.', 'This team has <strong>%d seats remaining</strong>.', $remaining_seats, 'woocommerce-memberships-for-teams' ), $remaining_seats ); ?>
		<?php else : ?>
			<?php _e( 'This team has <strong>unlimited seats</strong>.', 'woocommerce-memberships-for-teams' ); ?>
		<?php endif; ?>
	</p>

	<p>
		<?php echo $teams_area->get_members_section_view_links(); ?>
	</p>

	<?php if ( $show_invitations ) : ?>
		<?php wc_get_template( 'myaccount/my-team-invitations-table.php', $table_args ); ?>
	<?php else : ?>
		<?php wc_get_template( 'myaccount/my-team-members-table.php', $table_args ); ?>
	<?php endif; ?>

	<?php

	/**
	 * Fires after the Team Members table in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_after_my_team_members', $team );

	?>

</div>
<?php

