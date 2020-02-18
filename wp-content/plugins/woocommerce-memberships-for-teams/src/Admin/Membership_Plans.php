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
 * Admin Memberships Plan class
 *
 * @since 1.0.0
 */
class Membership_Plans {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// add teams information on plans edit screen rows
		add_action( 'manage_wc_membership_plan_posts_custom_column', array( $this, 'custom_column_content' ), 11, 2 );

		// handle membership plan edit screen meta boxes
		add_action( 'wc_membership_plan_options_membership_plan_data_general', array( $this, 'output_team_membership_options' ) );

		// handle membership plan saved from admin edit screen
		add_action( 'wc_memberships_save_meta_box', array( $this, 'force_purchase_access_method' ), 10, 4 );
	}


	/**
	 * Outputs team membership options.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output_team_membership_options() {
		global $post;

		$products = wc_memberships_for_teams()->get_membership_plans_instance()->get_membership_plan_team_products( $post->ID );

		if ( ! empty( $products ) ) :

			$items = array();

			foreach ( $products as $product ) :

				/**
				 * Filters whether a product should be listed among the team products.
				 *
				 * @since 1.0.4
				 *
				 * @param bool $list_product default true
				 * @param \WC_Product $product product instance
				 * @param int $plan_id related plan ID
				 */
				if ( (bool) apply_filters( 'wc_memberships_for_teams_membership_plan_list_team_product', true, $product, $post->ID ) ) :

					$product_name = sprintf( '%1$s (#%2$s)', $product->get_name(), $product->get_id() );

					$items[] = '<a href="' . get_edit_post_link( $product->get_id() ) . '">' . $product_name . '</a>';

				endif;

			endforeach;

			?>
			<div class="options_group">

				<?php if ( ! empty( $items ) ) : ?>

					<?php $product_links = wc_memberships_list_items( $items, strtolower( __( 'and', 'woocommerce-memberships-for-teams' ) ) ); ?>

					<p class="form-field plan-team-products-field">
						<label><?php esc_html_e( 'Team products', 'woocommerce-memberships-for-teams' ); ?></label>
						<span class="team-products"><?php echo $product_links; ?></span>
					</p>

				<?php endif; ?>

				<?php

				/**
				 * Fires after the team options for a membership plan have been rendered.
				 *
				 * @since 1.0.0
				 */
				do_action( 'wc_memberships_for_teams_membership_plan_team_options' );

				$team_access_note = '<br /><em><small>' . esc_html__( 'Default access method options are disabled because this plan has at least one team product associated.', 'woocommerce-memberships-for-teams' ) . '<small></em>';

				// given this is a team membership plan, disable other access methods options unless team products are removed and leave a note
				wc_enqueue_js( "
					jQuery( document ).ready( function( $ ) {

						$( 'input.js-access-method-type' ).each( function() {
							if ( 'purchase' !== $( this ).val() ) {
								$( this ).attr( 'disabled', 'disabled' );
							} else {
								$( this ).attr( 'checked', 'checked' );
							}
						} );

						$( '.plan-access-method-selectors' ).after( '" . $team_access_note . "' ); 
					} );
				" );

				?>
			</div>
			<?php

		endif;
	}


	/**
	 * Forces the access method to 'purchase' when saving a plan that has team products.
	 *
	 * Also warns an admin if the plan has a product that is both a team product and a product that grants individual access.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $posted posted data from $_POST
	 * @param string $meta_box_id The meta box id
	 * @param int $post_id WP_Post id
	 * @param \WP_Post $post WP_Post object
	 */
	public function force_purchase_access_method( $posted, $meta_box_id, $post_id, $post ) {

		if ( 'wc-memberships-membership-plan-data' === $meta_box_id ) {

			$membership_plan = new \WC_Memberships_Membership_Plan( $post );
			$team_products   = wc_memberships_for_teams()->get_membership_plans_instance()->get_membership_plan_team_product_ids( $post_id );

			if ( $membership_plan && ! empty( $team_products ) ) {

				$membership_plan->set_access_method( 'purchase' );

				$plan_products   = $membership_plan->get_product_ids();
				$shared_products = ! empty( $plan_products ) ? array_intersect( $plan_products, $team_products ) : array();

				// warn admin that there is one product that grants access to the membership plan but is also a team product
				if ( ! empty( $shared_products ) ) {

					wc_memberships_for_teams()->get_message_handler()->add_message(
						__( 'It looks like that this plan has at least one product that is both a team product and a product that can grant access to individual customers that complete a purchase. They will become team owners and plan members at the same time. Please make sure that this is intentional.', 'woocommerce-memberships-for-teams' )
					);
				}
			}
		}
	}


	/**
	 * Outputs custom column content.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function custom_column_content( $column, $post_id ) {
		global $post;

		// list additional products that give team access
		if ( 'access' === $column ) :

			$membership_plan = wc_memberships_get_membership_plan( $post );

			if ( $membership_plan && $membership_plan->is_access_method( 'purchase' ) ) :

				$products = wc_memberships_for_teams()->get_membership_plans_instance()->get_membership_plan_team_products( $post_id );

				if ( ! empty( $products ) ) :

					?>
					<ul class="access-from-list team-access-from-list">
						<?php

						foreach ( $products as $product ) :

							$product_link      = $this->get_edit_product_link( $product );
							$product_link_html = sprintf( '<li>%1$s%2$s</li>', $product_link, ' <small>(' . strtolower( __( 'Team', 'woocommerce-memberships-for-teams' ) ) . ')</small>' );

							/**
							 * Filters the team product link appearing on a membership plan column.
							 *
							 * @since 1.0.4
							 *
							 * @param string $product_link_html product link with additional data
							 * @param string $product_link product link (just the link)
							 * @param \WC_Product $product the product object
							 * @param \WC_Memberships_Membership_Plan the plan object
							 */
							echo (string) apply_filters( 'wc_memberships_for_teams_membership_plan_column_team_product_link', $product_link_html, $product_link, $product, $membership_plan );

						endforeach;

						?>
					</ul>
					<?php

				endif;

			endif;

		endif;
	}


	/**
	 * Outputs a link to edit a product in admin.
	 *
	 * @see \WC_Memberships_Admin_Membership_Plans::get_edit_product_link()
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product|\WC_Product_Variation $product a product or variation
	 * @return string
	 */
	private function get_edit_product_link( $product ) {

		$product_name = sprintf( '%1$s (#%2$s)', $product->get_name(), $product->get_id() );

		if ( $product->is_type( 'variation' ) ) {
			$product_link = get_edit_post_link( $product->get_parent_id() );
		} else {
			$product_link = get_edit_post_link( $product->get_id() );
		}

		$product_link = sprintf( '<a href="%1$s">%2$s</a>', $product_link, $product_name );

		return $product_link;
	}


}
