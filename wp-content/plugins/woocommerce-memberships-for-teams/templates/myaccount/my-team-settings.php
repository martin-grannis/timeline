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
 * Renders the team settings page on My Account page
 *
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Frontend\Teams_Area $teams_area teams area handler instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

?>

<div class="woocommerce-account-my-teams">

	<?php

	/**
	 * Fires before the Team settings page in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_before_my_team_settings', $team );

	?>

	<h3><?php esc_html_e( 'Team Name', 'woocommerce-memberships-for-teams' ); ?></h3>

	<form id="team-name-form" method="post">

		<?php wp_nonce_field( 'update-team-name-' . $team->get_id(), '_team_settings_nonce' ); ?>

		<input type="hidden" name="update_team_name" value="<?php echo esc_attr( $team->get_id() ); ?>" />

		<?php $value = isset( $_POST[ 'team_name' ] ) && ! empty( $_POST[ 'team_name' ] ) ? $_POST[ 'team_name' ] : $team->get_name(); ?>

		<p class="form-row" id="team-name_field">
			<input type="text" class="input-text" name="team_name" id="team-name" value="<?php echo esc_attr( $value ); ?>">

			<button type="submit"><?php esc_html_e( 'Update name', 'woocommerce-memberships-for-teams' ); ?></button>
		</p>

	</form>

	<?php if ( ! empty( $team_details ) && is_array( $team_details ) ) : ?>

		<h3><?php esc_html_e( 'Team Details', 'woocommerce-memberships-for-teams' ); ?></h3>

		<table class="shop_table shop_table_responsive my_account_orders my_account_teams my_team_details">
			<tbody>
				<?php foreach ( $team_details as $setting_id => $data ) : ?>
					<tr class="<?php echo sanitize_html_class( $data['class'] ); ?>">
						<td><?php echo esc_html( $data['label'] ); ?></td>
						<td><?php echo $data['content']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $team->can_add_seats() ) : ?>

			<form id="team-seat-update-form" method="post">
				<?php wp_nonce_field( 'update-team-seats-' . $team->get_id(), '_team_seats_nonce' ); ?>
				<input type="hidden" name="update_team_seats" value="<?php echo esc_attr( $team->get_id() ); ?>" />
				<input type="hidden" name="seat_change_mode" value="<?php echo esc_attr( $team->get_seat_change_mode() ); ?>" />
				<p id="team-seat-form-instructions"><?php echo wp_kses( $team_seat_details['instructions'], array( 'strong' => array() ) ); ?></p>
				<input type="number"
				       class="input-text"
				       name="team_seats"
				       id="team-seats-field"
				       min="<?php echo esc_attr( $team_seat_details['field_min'] ); ?>"
				       max="<?php echo esc_attr( $team_seat_details['field_max'] ); ?>"
				       value="<?php echo esc_attr( $team_seat_details['field_value'] ); ?>"
				/>
				<button type="submit" id="submit-seats-form"><?php esc_html_e( 'Submit', 'woocommerce-memberships-for-teams' ); ?></button>
				<p id="seat-change-message"></p>
			</form>

		<?php endif; ?>

	<?php endif; ?>

	<?php

	/**
	 * Fires after the Team settings page in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_after_my_team_settings', $team );

	?>

</div>
