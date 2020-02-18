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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin\Meta_Boxes;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Team Billing Details meta box class.
 *
 * @since 1.0.0
 */
class Team_Billing_Details {


	/**
	 * Constructs the meta box.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30 );

		add_action( 'wc_memberships_for_teams_process_team_meta', array( $this, 'save' ), 30, 2 );
	}


	/**
	 * Adds the meta box
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-memberships-for-teams-team-billing-details', __( 'Billing Details', 'woocommerce-memberships-for-teams' ), array( $this, 'output' ), 'wc_memberships_team', 'side' );
	}


	/**
	 * Outputs meta box contents.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output() {
		global $team;

		$order = $team->get_order();

		/**
		 * Fires before the billing details in edit team screen.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		do_action( 'wc_memberships_for_teams_before_team_billing_details', $team );

		if ( $order ) {

			/* translators: Placeholder: %s - order number */
			$order_ref       = '<a href="' . esc_url( get_edit_post_link( Framework\SV_WC_Order_Compatibility::get_prop( $order, 'id' ) ) ) . '">' . sprintf(  esc_html__( 'Order %s', 'woocommerce-memberships-for-teams' ), $order->get_order_number() ) . '</a>';
			$billing_fields  = array(
				__( 'Purchased in:', 'woocommerce-memberships-for-teams' ) => $order_ref,
				__( 'Order Date:', 'woocommerce-memberships-for-teams' )   => date_i18n( wc_date_format(), Framework\SV_WC_Order_Compatibility::get_date_created( $order )->getTimestamp() ),
				__( 'Order Total:', 'woocommerce-memberships-for-teams' )  => $order->get_formatted_order_total(),
			);

		} else {

			$billing_fields = array(
				__( 'No billing details:', 'woocommerce-memberships-for-teams' ) => esc_html__( 'This team was created manually.', 'woocommerce-memberships-for-teams' ),
			);
		}

		$billing_fields[ __( 'Product:', 'woocommerce-memberships-for-teams' ) ] = $this->get_edit_product_input( $team, $team->get_product() );

		/**
		 * Filters the team billing details fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array $billing_fields associative array of labels and data or inputs
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		$billing_fields = apply_filters( 'wc_memberships_for_teams_team_billing_details', $billing_fields, $team );

		foreach ( $billing_fields as $label => $field ) :

			?>
			<p class="billing-detail">
				<strong><?php echo esc_html( $label ); ?></strong>
				<?php echo $field; ?>
			</p>
			<?php

		endforeach;

		/**
		 * Fires after the billing details in edit team screen.
		 *
		 * @since 1.0.0
		 *
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		do_action( 'wc_memberships_for_teams_after_team_billing_details', $team );
	}


	/**
	 * Returns the edit product input HTML.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param \WC_Product|null $product the subscription object
	 * @return string HTML
	 */
	private function get_edit_product_input( $team, $product = null ) {

		if ( $product && $product instanceof \WC_Product ) {
			$product_id   = $product->get_id();
			$product_url  = get_edit_post_link( $product_id );
			$product_name = $product->get_formatted_name();
			$product_link = '<a href="' . esc_url( $product_url ) . '">' . esc_html( $product_id ) . '</a>';
		} else {
			$product_id = '';
			$product_name = '';
			$product_link = esc_html__( 'Team not linked to a product', 'woocommerce-memberships-for-teams' );
		}

		/* translators: Placeholders: %1$s - link to a product, %2$s - opening <a> HTML tag, %3%s - closing </a> HTML tag */
		$input = sprintf( __( '%1$s - %2$sEdit Link%3$s', 'woocommerce-memberships-for-teams' ),
			$product_link,
			'<a href="#" class="js-edit-product-link-toggle">',
			'</a>'
		);

		ob_start();

		?><br>
		<span class="wc-memberships-for-teams-edit-product-link-field">
			<select
				class="wc-product-search js-search-products"
				id="_product_id"
				name="_product_id"
				style="width:100%;"
				data-placeholder="<?php esc_attr_e( 'Select product&hellip;', 'woocommerce-memberships-for-teams' ); ?>"
				data-action="woocommerce_json_search_products_and_variations"
				data-exclude="wc_memberships_for_teams_non_team_products">
				<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo wp_kses_post( $product_name ); ?></option>
			</select>
		</span>
		<?php

		Framework\SV_WC_Helper::render_select2_ajax();

		$input .= ob_get_clean();

		// toggle editing of product id link
		wc_enqueue_js( '
			$( ".js-edit-product-link-toggle" ).on( "click", function( e ) { e.preventDefault(); $( ".wc-memberships-for-teams-edit-product-link-field" ).toggle(); } ).click();
		' );

		return $input;
	}


	/**
	 * Processes and saves meta box data.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id post identifier
	 * @param \WP_Post $post the post object
	 */
	public function save( $post_id, \WP_Post $post ) {
		global $wpdb;

		$team       = wc_memberships_for_teams_get_team( $post );
		$product_id = $_POST['_product_id'];

		if ( ! empty( $product_id ) ) {

			try {
				$team->set_product_id( $product_id );
			} catch ( Framework\SV_WC_Plugin_Exception $e ) {
				wc_memberships_for_teams()->get_message_handler()->add_error( $e->getMessage() );
			}

		} else {
			$team->delete_product_id();
		}

	}
}
