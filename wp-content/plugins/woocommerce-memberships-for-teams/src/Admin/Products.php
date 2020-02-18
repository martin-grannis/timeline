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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Admin Products class
 *
 * @since 1.0.0
 */
class Products {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// add team membership options to products
		add_filter( 'product_type_options',                             array( $this, 'add_team_membership_option' ) );
		add_action( 'woocommerce_product_options_pricing',              array( $this, 'add_team_membership_pricing_options' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_team_membership_options' ) );

		// add team membership options to variations
		add_action( 'woocommerce_variation_options_pricing',            array( $this, 'add_team_membership_variation_pricing_options' ), 10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes',    array( $this, 'add_team_membership_variation_options' ), 10, 3 );

		// process and save product team membership data
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_meta' ), 15 );

		// process and save variable product team membership data
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'process_product_meta_variable' ), 15 );
		add_action( 'woocommerce_ajax_save_product_variations',  array( $this, 'process_product_meta_variable' ), 15 );
	}


	/**
	 * Adds the team membership product type option to product data meta box.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $options array of product type options
	 * @return array $options
	 */
	public function add_team_membership_option( $options ) {

		$options['wc_memberships_for_teams_has_team_membership'] = array(
			'id'            => '_wc_memberships_for_teams_has_team_membership',
			'wrapper_class' => 'show_if_simple show_if_variable',
			'label'         => __( 'Team Membership', 'woocommerce-pdf-product-vouchers' ),
			'description'   => __( 'Team membership products give access to a team membership upon purchase.', 'woocommerce-memberships-for-teams' ),
			'default'       => 'no',
		);

		return $options;
	}


	/**
	 * Adds the team membership pricing options to the pricing options group.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_team_membership_pricing_options() {

		woocommerce_wp_select( array(
			'id'            => '_wc_memberships_for_teams_pricing',
			'label'         => __( 'Team Pricing', 'woocommerce-memberships-for-teams' ),
			'class'         => 'js-wc-memberships-for-teams-pricing',
			'wrapper_class' => 'js-wc-memberships-for-teams-show-if-has-team-membership hidden',
			'desc_tip'      => true,
			'options'       => array(
				'per_member' => __( 'Per Member', 'woocommerce-memberships-for-teams' ),
				'per_team'   => __( 'Per Team', 'woocommerce-memberships-for-teams' ),
			),
		) );

		woocommerce_wp_text_input( array(
			'id'                => '_wc_memberships_for_teams_min_member_count',
			'label'             => __( 'Minimum member count', 'woocommerce-memberships-for-teams' ),
			'class'             => 'js-wc-memberships-for-teams-min-member-count',
			'wrapper_class'     => 'hide_if_variable js-wc-memberships-for-teams-show-if-has-team-membership js-wc-memberships-for-teams-show-if-per-member-pricing hidden',
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => 1,
				'min'  => 0,
			),
		) );

		woocommerce_wp_text_input( array(
			'id'                => '_wc_memberships_for_teams_max_member_count',
			'label'             => __( 'Maximum member count', 'woocommerce-memberships-for-teams' ),
			'wrapper_class'     => 'hide_if_variable js-wc-memberships-for-teams-show-if-has-team-membership hidden',
			'description'       => __( 'Leave this blank to allow an unlimited number of seats', 'woocommerce-memberships-for-teams' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => 1,
				'min'  => 0,
			),
		) );

	}


	/**
	 * Adds the team membership product options on the product edit page for simple products.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_team_membership_options() {

		echo '<div class="options_group wc-memberships-for-teams-team-membership-options js-wc-memberships-for-teams-team-membership-options js-wc-memberships-for-teams-show-if-has-team-membership hidden">';

		woocommerce_wp_select( array(
			'id'            => '_wc_memberships_for_teams_plan',
			'label'         => __( 'Team members will have access to', 'woocommerce-memberships-for-teams' ),
			'desc_tip'      => true,
			// TODO: we should add a public method to Memberships for getting plan options {IT 2017-06-16}
			'options'       => wc_memberships()->get_plans_instance()->get_available_membership_plans( 'labels' ),
		) );

		// TODO: we should add a public method to Memberships for this text so we can avoid duplicating the translations in Teams {IT 2017-06-16}
		?><p><em><?php esc_html_e( 'Need to add or edit a plan?', 'woocommerce-memberships-for-teams' ); ?></em> <a target="_blank" href="<?php echo esc_url( admin_url( 'edit.php?post_type=wc_membership_plan' ) ); ?>"><?php esc_html_e( 'Manage Membership Plans', 'woocommerce-memberships-for-teams' ); ?></a></p><?php

		echo '</div>';
	}


	/**
	 * Adds the team membership pricing options to the variation pricing options group.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $loop
	 * @param array $variation_data
	 * @param \WP_Post $variation
	 */
	public function add_team_membership_variation_pricing_options( $loop, $variation_data, $variation ) {

		woocommerce_wp_text_input( array(
			'id'                => "_wc_memberships_for_teams_variable_min_member_count_{$loop}",
			'name'              => "_wc_memberships_for_teams_variable_min_member_count[{$loop}]",
			'label'             => __( 'Minimum member count', 'woocommerce-memberships-for-teams' ),
			'class'             => 'js-wc-memberships-for-teams-min-member-count short',
			'wrapper_class'     => 'js-wc-memberships-for-teams-show-if-has-team-membership js-wc-memberships-for-teams-show-if-per-member-pricing hidden form-row form-row-first',
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => 1,
				'min'  => 0,
			),
			'value' => isset( $variation_data['_wc_memberships_for_teams_min_member_count'][0] ) ? $variation_data['_wc_memberships_for_teams_min_member_count'][0] : null,
		) );

		woocommerce_wp_text_input( array(
			'id'                => "_wc_memberships_for_teams_variable_max_member_count_{$loop}",
			'name'              => "_wc_memberships_for_teams_variable_max_member_count[{$loop}]",
			'label'             => __( 'Maximum member count', 'woocommerce-memberships-for-teams' ),
			'class'             => 'js-wc-memberships-for-teams-max-member-count short',
			'wrapper_class'     => 'js-wc-memberships-for-teams-show-if-has-team-membership hidden form-row form-row-last',
			'description'       => __( 'Leave this blank to allow an unlimited number of seats', 'woocommerce-memberships-for-teams' ),
			'desc_tip'          => true,
			'type'              => 'number',
			'custom_attributes' => array(
				'step' => 1,
				'min'  => 0,
			),
			'value' => isset( $variation_data['_wc_memberships_for_teams_max_member_count'][0] ) ? $variation_data['_wc_memberships_for_teams_max_member_count'][0] : null,
		) );

	}


	/**
	 * Adds the team membership product variation options on the product edit page for variable products.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $loop
	 * @param array $variation_data
	 * @param \WP_Post $variation
	 */
	public function add_team_membership_variation_options( $loop, $variation_data, $variation) {

		echo '<div class="wc-memberships-for-teams-variation-team-membership-options js-wc-memberships-for-teams-variation-team-membership-options js-wc-memberships-for-teams-show-if-has-team-membership hidden">';

		$options = array( 'parent' => __( 'Same plan as parent', 'woocommerce-memberships-for-teams' ) ) + wc_memberships()->get_plans_instance()->get_available_membership_plans( 'labels' );

		woocommerce_wp_select( array(
			'id'            => "_wc_memberships_for_teams_variable_plan_{$loop}",
			'name'          => "_wc_memberships_for_teams_variable_plan[{$loop}]",
			'label'         => __( 'Team members will have access to', 'woocommerce-memberships-for-teams' ),
			'desc_tip'      => true,
			'options'       => $options,
			'wrapper_class' => 'form-row form-row-full',
			'value'         => isset( $variation_data['_wc_memberships_for_teams_plan'][0] ) ? $variation_data['_wc_memberships_for_teams_plan'][0] : null,
		) );

		woocommerce_wp_hidden_input( array(
			'id'    => "_wc_memberships_for_teams_variable_has_team_membership[{$loop}]",
			'class' => 'js-wc-memberships-for-teams-variable-has-team-membership',
			'value' => get_post_meta( $variation->id, '_wc_memberships_for_teams_has_team_membership', true ),
		) );

		// TODO: we should add a public method to Memberships for this text so we can avoid duplicating the translations in Teams {IT 2017-06-16}
		?><p><em><?php esc_html_e( 'Need to add or edit a plan?', 'woocommerce-memberships-for-teams' ); ?></em> <a target="_blank" href="<?php echo esc_url( admin_url( 'edit.php?post_type=wc_membership_plan' ) ); ?>"><?php esc_html_e( 'Manage Membership Plans', 'woocommerce-memberships-for-teams' ); ?></a></p><?php

		echo '</div>';
	}


	/**
	 * Processes and saves product team membership data.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id the product id
	 */
	public function process_product_meta( $post_id ) {

		$has_team_membership = isset( $_POST['_wc_memberships_for_teams_has_team_membership'] ) ? 'yes' : 'no';

		update_post_meta( $post_id, '_wc_memberships_for_teams_has_team_membership', $has_team_membership );

		// update team membership details
		if ( 'yes' === $has_team_membership ) {

			$pricing          = ! empty( $_POST['_wc_memberships_for_teams_pricing'] )          ? sanitize_text_field( $_POST['_wc_memberships_for_teams_pricing'] ) : 'per_member';
			$min_member_count = ! empty( $_POST['_wc_memberships_for_teams_min_member_count'] ) ? (int) $_POST['_wc_memberships_for_teams_min_member_count'] : '';
			$max_member_count = ! empty( $_POST['_wc_memberships_for_teams_max_member_count'] ) ? (int) $_POST['_wc_memberships_for_teams_max_member_count'] : '';
			$plan_id          = ! empty( $_POST['_wc_memberships_for_teams_plan'] )             ? (int) $_POST['_wc_memberships_for_teams_plan'] : 0;

			update_post_meta( $post_id, '_wc_memberships_for_teams_pricing', $pricing );
			update_post_meta( $post_id, '_wc_memberships_for_teams_min_member_count', $min_member_count );
			update_post_meta( $post_id, '_wc_memberships_for_teams_max_member_count', $max_member_count );
			update_post_meta( $post_id, '_wc_memberships_for_teams_plan', $plan_id );

			// ensure the plan's access method is 'purchase'
			$membership_plan = $plan_id > 0 ? wc_memberships_get_membership_plan( $plan_id ) : null;

			if ( $membership_plan ) {
				$membership_plan->set_access_method( 'purchase' );
			}
		}

	}


	/**
	 * Processes and saves variable product team membership data.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id the product id
	 */
	public function process_product_meta_variable( $post_id ) {

		if ( isset( $_POST['variable_post_id'] ) ) {

			$variable_post_id    = $_POST['variable_post_id'];
			$has_team_membership = isset( $_POST['_wc_memberships_for_teams_variable_has_team_membership'] ) ? $_POST['_wc_memberships_for_teams_variable_has_team_membership'] : array();
			$min_member_count    = isset( $_POST['_wc_memberships_for_teams_variable_min_member_count'] ) ? $_POST['_wc_memberships_for_teams_variable_min_member_count'] : array();
			$max_member_count    = isset( $_POST['_wc_memberships_for_teams_variable_max_member_count'] ) ? $_POST['_wc_memberships_for_teams_variable_max_member_count'] : array();
			$membership_plan     = isset( $_POST['_wc_memberships_for_teams_variable_plan'] ) ? $_POST['_wc_memberships_for_teams_variable_plan'] : array();
			$max_loop            = max( array_keys( $_POST['variable_post_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i++ ) {

				// ensure the variation post id is set, and that the parent product has team membership enabled
				if ( ! isset( $variable_post_id[ $i ] ) || empty( $has_team_membership[ $i ] ) || 'yes' !== $has_team_membership[ $i ] ) {
					continue;
				}

				$variation_id = (int) $variable_post_id[ $i ];

				$plan = ( ! $membership_plan[ $i ] || 'parent' === $membership_plan[ $i ] ) ? null : (int) $membership_plan[ $i ];

				update_post_meta( $variation_id, '_wc_memberships_for_teams_min_member_count', (int) $min_member_count[ $i ] );
				update_post_meta( $variation_id, '_wc_memberships_for_teams_max_member_count', (int) $max_member_count[ $i ] );
				update_post_meta( $variation_id, '_wc_memberships_for_teams_plan', $plan );
			}
		}
	}

}
