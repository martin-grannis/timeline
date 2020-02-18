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
 * Membership plans handler class.
 *
 * @since 1.0.0
 */
class Membership_Plans {


	/**
	 * Checks whether a membership plan has any team products that grant access to it.
	 *
	 * @since 1.0.0
	 *
	 * @param int $plan_id membership plan id
	 * @return bool
	 */
	public function has_membership_plan_team_products( $plan_id ) {

		$products = $this->get_membership_plan_team_products( $plan_id );

		return ! empty( $products );
	}


	/**
	 * Returns team products that grant access to a membership plan.
	 *
	 * @since 1.0.0
	 *
	 * @param int $plan_id membership plan id
	 * @return \WC_Product[]
	 */
	public function get_membership_plan_team_products( $plan_id ) {

		$product_ids = get_posts( array(
			'post_type'   => array( 'product', 'product_variation' ),
			'post_status' => 'any',
			'fields'      => 'ids',
			'nopaging'    => true,
			'meta_query'  => array(
				array(
					'key'   => '_wc_memberships_for_teams_plan',
					'value' => $plan_id,
				),
			),
		) );

		$products = array();

		if ( ! empty( $product_ids ) ) {

			foreach ( $product_ids as $product_id ) {

				if ( $product = wc_get_product( $product_id ) ) {

					$team_product = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : $product;

					if ( $team_product && 'yes' === $team_product->get_meta( '_wc_memberships_for_teams_has_team_membership' ) ) {
						$products[] = $product;
					}
				}
			}
		}

		/**
		 * Filters the team products that grant access to a membership plan.
		 *
		 * @since 1.0.0
		 *
		 * @param \WC_Product[] $products the products
		 * @param int $plan_id membership plan id
		 */
		return apply_filters( 'wc_memberships_for_teams_membership_plan_team_products', $products, $plan_id );
	}


	/**
	 * Returns IDs of team products that grant access to a membership plan.
	 *
	 * @since 1.0.4
	 *
	 * @param int $plan_id a membership plan ID
	 * @return int[]
	 */
	public function get_membership_plan_team_product_ids( $plan_id ) {

		$product_ids   = array();
		$team_products = $this->get_membership_plan_team_products( $plan_id );

		foreach( $team_products as $team_product ) {
			$product_ids[] = $team_product->get_id();
		}

		return $product_ids;
	}


}
