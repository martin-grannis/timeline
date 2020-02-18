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

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Upgrade class. Handles version upgrades.
 *
 * @since 1.0.2
 *
 * @method Plugin get_plugin()
 */
class Upgrade extends Framework\Plugin\Lifecycle {


	/**
	 * Lifecycle constructor.
	 *
	 * @since 1.1.4
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {

		parent::__construct( $plugin );

		$this->upgrade_versions = [
			'1.0.2',
		];
	}


	/**
	 * Handles plugin activation.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 */
	public function activate() {

		$this->get_plugin()->add_rewrite_endpoints();
	}


	/**
	 * Handles plugin deactivation.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 */
	public function deactivate() {

		flush_rewrite_rules();
	}


	/**
	 * Runs updates.
	 *
	 * TODO remove this deprecated method by version 2.0.0 or by May 2020, whichever comes first {FN 2018-01-14}
	 *
	 * @since 1.0.2
	 * @deprecated since 1.1.2
	 *
	 * @param string $installed_version semver
	 */
	public static function run_update_scripts( $installed_version ) {

		_deprecated_function( 'SkyVerge\WooCommerce\Memberships\Teams\Upgrade::run_update_scripts()', '1.1.1' );
	}


	/**
	 * Runs plugin upgrade scripts.
	 *
	 * @since 1.1.2
	 *
	 * @param string $installed_version semver
	 */
	protected function upgrade( $installed_version ) {

		parent::upgrade( $installed_version );

		$this->get_plugin()->add_rewrite_endpoints();
	}


	/**
	 * Updates to v1.0.2
	 *
	 * @since 1.1.4
	 */
	protected function upgrade_to_1_0_2() {
		global $wpdb;

		// Before 1.0.1, team subscription items were missing the purchased team id, which caused a duplicate team
		// being created when the subscription renewed. The issue was fixed in 1.0.1, but no automatic way to fix
		// existing subscriptions was provided.
		// This update tries to resolve the issue for team subscriptions created before 1.0.1 as follows:
		// 1. Find all subscription items with a team name, but missing a team id
		// 2. Look up the team id from the parent order's order item, matching on the team name
		// 3. Add team id as subscription item meta
		$order_items = $wpdb->get_results( "
			SELECT oi1.order_item_id AS id, oim3.meta_value AS team_id
			FROM {$wpdb->posts} p
			JOIN {$wpdb->posts} p2 ON ( p2.ID = p.post_parent AND p2.post_type = 'shop_order' )
			JOIN {$wpdb->prefix}woocommerce_order_items oi1 ON p.ID = oi1.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_items oi2 ON p2.ID = oi2.order_id 
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim1 ON ( oim1.order_item_id = oi1.order_item_id AND oim1.meta_key = 'team_name' )
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim2 ON ( oim2.order_item_id = oi2.order_item_id AND oim2.meta_key = 'team_name' AND oim2.meta_value = oim1.meta_value )
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim3 ON ( oim3.order_item_id = oi2.order_item_id AND oim3.meta_key = '_wc_memberships_for_teams_team_id' )
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim4 ON ( oim4.order_item_id = oi1.order_item_id AND oim4.meta_key = '_wc_memberships_for_teams_team_id' )
			WHERE p.post_type = 'shop_subscription'
			AND oim4.order_item_id IS NULL
		" );

		if ( empty( $order_items ) ) {
			return;
		}

		foreach( $order_items as $order_item ) {
			try {
				wc_update_order_item_meta( $order_item->id, '_wc_memberships_for_teams_team_id', $order_item->team_id );
			} catch ( \Exception $e ) {}
		}
	}


}
