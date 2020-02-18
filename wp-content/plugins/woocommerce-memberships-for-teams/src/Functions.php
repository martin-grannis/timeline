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
 * Returns the One True Instance of Memberships for Teams.
 *
 * @since 1.0.0
 *
 * @return \SkyVerge\WooCommerce\Memberships\Teams\Plugin plugin instance
 */
function wc_memberships_for_teams() {
	return \SkyVerge\WooCommerce\Memberships\Teams\Plugin::instance();
}

require_once( __DIR__ . '/Functions/Teams.php' );
require_once( __DIR__ . '/Functions/Team_Members.php' );
require_once( __DIR__ . '/Functions/Invitations.php' );
