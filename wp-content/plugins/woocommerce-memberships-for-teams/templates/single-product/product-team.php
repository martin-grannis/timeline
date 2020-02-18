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

/**
 * Frontend product page team fields template.
 *
 * @type \WC_Product $product product instance
 * @type int $product_id the product ID
 * @type string[] $fields array of team user input fields
 *
 * @version 1.0.0
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

?>

<div class="wc-memberships-for-teams-team-fields-wrapper" id="team-fields-wrapper-<?php echo esc_attr( $product_id ); ?>">

	<div class="team-fields">

		<?php
		if ( ! empty( $fields ) ) :
			foreach ( $fields as $key => $field ) :

				$value = isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ? stripslashes( $_POST[ $key ] ) : null;

				woocommerce_form_field( $key, $field, $value );
			endforeach;
		endif;
		?>

		<?php if ( ! $product->is_sold_individually() && \SkyVerge\WooCommerce\Memberships\Teams\Product::has_per_member_pricing( $product ) ) : ?>
			<p class="team-seat-count-label"><strong><?php esc_html_e( 'Number of Seats:', 'woocommerce-memberships-for-teams' ); ?></strong></p>
		<?php endif; ?>
	</div>

</div>
