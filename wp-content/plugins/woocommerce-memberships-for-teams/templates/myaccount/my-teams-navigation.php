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
 * Renders the tab sections on My Account page for a team.
 *
 * @version 1.0.0
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

$teams_area          = wc_memberships_for_teams()->get_frontend_instance()->get_teams_area_instance();
$team                = $teams_area->get_teams_area_team();
$teams_area_sections = $teams_area->get_teams_area_navigation_items( $team );

?>

<?php if ( ! empty( $teams_area_sections ) && is_array( $teams_area_sections ) ) : ?>

	<?php

	// reinstates WooCommerce core action
	do_action( 'woocommerce_before_account_navigation' );

	/**
	 * Fires before the teams area navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team being displayed
	 */
	do_action( 'wc_memberships_for_teams_teams_area_before_team_navigation', $team );

	?>

	<nav class="woocommerce-MyAccount-navigation wc-memberships-for-teams-teams-area-navigation">
		<ul>
			<?php foreach ( $teams_area_sections as $section_id => $section_data ) : ?>
				<li class="<?php echo wc_get_account_menu_item_classes( $section_id ) . ' ' . $section_data['class']; ?>">
					<a href="<?php echo esc_url( $section_data['url'] ); ?>"><?php echo esc_html( $section_data['label'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<?php

	/**
	 * Fires after the teams area navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team being displayed
	 */
	do_action( 'wc_memberships_for_teams_teams_area_after_team_navigation', $team );

	// reinstates WooCommerce core action
	do_action( 'woocommerce_after_account_navigation' );

	?>

<?php endif; ?>
