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
 * Product class. Provides utility and helper methods for team products.
 *
 * @since 1.0.0
 */
class Product {


	/**
	 * Returns the parent product id for a variation or regular id in all other cases.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return int product id
	 */
	public static function get_parent_id( \WC_Product $product ) {

		return (int) ( $parent_id = Framework\SV_WC_Product_Compatibility::get_prop( $product, 'parent_id' ) ) ? $parent_id : Framework\SV_WC_Product_Compatibility::get_prop( $product, 'id' );
	}


	/**
	 * Checks whether a product is a team product.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product to check
	 * @return bool true if has team membership, false otherwise
	 */
	public static function has_team_membership( \WC_Product $product ) {
		return 'yes' === get_post_meta( self::get_parent_id( $product ), '_wc_memberships_for_teams_has_team_membership', true );
	}


	/**
	 * Checks whether a product is set up with per member pricing.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product to check
	 * @return bool true if has per member pricing, false otherwise
	 */
	public static function has_per_member_pricing( \WC_Product $product ) {
		return 'per_member' === get_post_meta( self::get_parent_id( $product ), '_wc_memberships_for_teams_pricing', true );
	}


	/**
	 * Returns the min member count for the given team product.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return int|null min member count or null if not set
	 */
	public static function get_min_member_count( \WC_Product $product ) {

		$min_member_count = get_post_meta( $product->get_id(), '_wc_memberships_for_teams_min_member_count', true );

		return $min_member_count ? (int) $min_member_count : null;
	}


	/**
	 * Returns the max member count for the given team product.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return int|null max member count or null if not set
	 */
	public static function get_max_member_count( \WC_Product $product ) {

		$max_member_count = get_post_meta( $product->get_id(), '_wc_memberships_for_teams_max_member_count', true );

		return $max_member_count ? (int) $max_member_count : null;
	}


	/**
	 * Returns the membership plan id for the team product.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return int|null plan id or null if not set
	 */
	public static function get_membership_plan_id( \WC_Product $product ) {

		$plan_id = get_post_meta( $product->get_id(), '_wc_memberships_for_teams_plan', true );

		if ( ! $plan_id && $product->is_type( 'variation' ) ) {
			$plan_id = get_post_meta( Framework\SV_WC_Product_Compatibility::get_prop( $product, 'parent_id' ), '_wc_memberships_for_teams_plan', true );
		}

		return $plan_id ? (int) $plan_id : null;
	}


	/**
	 * Returns user input fields for a team product.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product to check
	 * @return array|false associative array of user input fields or false if not a team product.
	 */
	public static function get_team_user_input_fields( \WC_Product $product ) {

		if ( ! self::has_team_membership( $product) ) {
			return false;
		}

		$fields = array(
			'team_name' => array(
				'label' => __( 'Team Name', 'woocommerce-memberships-for-teams' ),
				'required' => true,
			),
		);

		if ( 'yes' !== get_option( 'wc_memberships_for_teams_owners_must_take_seat' ) ) {
			$fields['team_owner_takes_seat'] = array(
				'type'        => 'checkbox',
				'label'       => __( 'Take up a seat', 'woocommerce-memberships-for-teams' ),
				'description' => __( 'Use a seat to add me as a team member', 'woocommerce-memberships-for-teams' ),
			);
		}

		/**
		 * Filters user input fields for a team product.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields associative array of user input fields
		 * @param \WC_Product $product the product
		 */
		return apply_filters( 'wc_memberships_for_teams_product_team_user_input_fields', $fields, $product );
	}


	/**
	 * Checks whether a product has any required team user input fields.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product to check
	 * @return bool true if has required user input fields, false otherwise
	 */
	public static function has_required_team_user_input_fields( \WC_Product $product ) {

		$fields = self::get_team_user_input_fields( $product );

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {

				if ( ! empty( $field['required'] ) ) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Returns the label for a team user input field.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product
	 * @param string $name field name (key)
	 * @return string|null field label or null if not defined for this product
	 */
	public static function get_team_user_input_field_label( $product, $name ) {

		$label  = null;
		$fields = self::get_team_user_input_fields( $product );

		if ( isset( $fields[ $name ], $fields[ $name ]['label'] ) ) {
			$label = $fields[ $name ]['label'];
		}

		return $label;
	}

}
