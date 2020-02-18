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

namespace SkyVerge\WooCommerce\Memberships\Teams\Frontend;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;
use SkyVerge\WooCommerce\Memberships\Teams\Product;

defined( 'ABSPATH' ) or exit;

/**
 * Checkout handler.
 *
 * @since 1.0.0
 */
class Checkout {


	/**
	 * Setup checkout handler.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// force registration during checkout process
		add_filter( 'wc_memberships_force_checkout_registration', array( $this, 'force_registration' ), 10, 2 );
	}


	/**
	 * Adjusts whether registration should be forced on checkout or not.
	 *
	 * This will happen if all of the following are true:
	 *
	 * 1. user is not logged in
	 * 2. an item in the cart contains a product that creates a team
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force whether to force checkout registration or not
	 * @param \WC_Memberships_Membership_Plan[] $membership_plans an array of all the available membership plans, provided for context
	 *
	 * @return bool
	 */
	public function force_registration( $force, $membership_plans ) {

		// skip if already forced
		if ( $force ) {
			return $force;
		}

		if ( is_user_logged_in() ) {
			return false;
		}

		$force = false;

		// loop over items to see if any of them create a team
		foreach ( WC()->cart->get_cart() as $key => $item ) {

			// this covers both parents & variations
			if ( $plan_id = Product::get_membership_plan_id( $item['data'] ) ) {

				foreach ( $membership_plans as $plan ) {
					if ( (int) $plan->get_id() === $plan_id ) {

						$force = true;

						break 2;
					}
				}
			}
		}

		return $force;
	}


}
