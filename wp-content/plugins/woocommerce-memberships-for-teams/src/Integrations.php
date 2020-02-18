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

use SkyVerge\WooCommerce\Memberships\Teams\Integrations\Subscriptions;
use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Teams integrations class.
 *
 * @since 1.0.0
 */
class Integrations {


	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Integrations\Subscriptions instance */
	protected $subscriptions;


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( wc_memberships_for_teams()->is_plugin_active( 'woocommerce-subscriptions.php' ) && class_exists( 'WC_Subscriptions' ) ) {
			$this->subscriptions = new Subscriptions();
		}
	}


	/**
	 * Returns the subscriptions integration class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Integrations\Subscriptions|null subscriptions instance if Subscriptions is active
	 */
	public function get_subscriptions_instance() {

		return $this->subscriptions;
	}


}
