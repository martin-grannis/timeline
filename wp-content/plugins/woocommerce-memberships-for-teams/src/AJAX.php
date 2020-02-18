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
 * AJAX class
 *
 * @since 1.0.0
 */
class AJAX {


	/**
	 * Sets up the AJAX class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// only return team products when appropriate
		add_filter( 'woocommerce_json_search_found_products', array( $this, 'filter_json_search_found_products' ) );

		add_action( 'wp_ajax_wc_memberships_for_teams_json_search_teams', array( $this, 'json_search_teams' ) );

		add_action( 'wp_ajax_wc_memberships_for_teams_get_existing_user_membership_id', array( $this, 'get_existing_user_membership_id' ) );
	}


	/**
	 * Removes non-team products from json search results.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products
	 * @return array $products
	 */
	public function filter_json_search_found_products( $products ) {

		// remove non-voucher products
		if ( isset( $_GET['exclude'] ) && 'wc_memberships_for_teams_non_team_products' === $_GET['exclude'] ) {
			foreach( $products as $id => $title ) {

				$product = wc_get_product( $id );

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				$parent_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $id;
				$has_team  = 'yes' === get_post_meta( $parent_id, '_wc_memberships_for_teams_has_team_membership', true );
				$team_plan = $has_team ? get_post_meta( $id, '_wc_memberships_for_teams_plan', true ) : null;

				if ( $has_team && ! $team_plan && $product->is_type( 'variation' ) ) {
					$team_plan = get_post_meta( $parent_id, '_wc_memberships_for_teams_plan', true );
				}

				if ( ! $has_team || ! $team_plan ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}


	/**
	 * Returns a list of teams based on the search criteria.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function json_search_teams() {

		check_ajax_referer( 'search-teams', 'security' );

		$search_term = (string) wc_clean( Framework\SV_WC_Helper::get_request( 'term' ) );
		$results     = array();

		if ( ! empty( $search_term ) ) {

			$team_posts = get_posts( array(
				'post_type' => 'wc_memberships_team',
				'status'    => 'any',
				'fields'    => 'ids',
				'nopaging'  => true,
				's'         => $search_term,
			) );

			foreach ( $team_posts as $team_id ) {

				if ( $team = wc_memberships_for_teams_get_team( $team_id ) ) {

					$results[ $team_id ] = esc_html( $team->get_formatted_name() );
				}
			}
		}

		wp_send_json( $results );
	}


	/**
	 * Returns user's existing user membership  id for the team's plan, if they have any.
	 *
	 * This will return both individual (standalone) memberships or memberships from another team.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function get_existing_user_membership_id() {

		check_ajax_referer( 'get-existing-user-membership-id', 'security' );

		$user_id            = (int) $_GET['user_id'];
		$team_id            = (int) $_GET['team_id'];
		$user_membership_id = null;

		if ( $user_id > 0 && $team_id > 0 ) {

			$team = wc_memberships_for_teams_get_team( $team_id );

			if ( $team ) {
				$user_membership = $team->get_existing_user_membership( $user_id );

				if ( $user_membership ) {
					$user_membership_id = $user_membership->get_id();
				}
			}
		}

		wp_send_json_success( $user_membership_id );
	}


}
