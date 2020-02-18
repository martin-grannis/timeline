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

defined( 'ABSPATH' ) or exit;

/**
 * Renders the team members table on My Account page.
 *
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Frontend\Teams_Area $teams_area teams area handler instance
 *
 * @version 1.1.4
 * @since 1.0.0
 */

$seat_count      = $team->get_seat_count();
$remaining_seats = $team->get_remaining_seat_count();
$fields          = wc_memberships_for_teams()->get_frontend_instance()->get_add_team_member_form_fields();
$current_member  = wc_memberships_for_teams_get_team_member( $team, get_current_user_id() );

if ( $current_member && $current_member->has_role( 'manager' ) && 'yes' !== get_option( 'wc_memberships_for_teams_managers_may_manage_managers', 'yes' ) ) {
	unset( $fields['role']['options']['manager'] );
}

?>
<div class="woocommerce-account-my-teams">
	<?php

	/**
	 * Fires before the Add Member section in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_before_my_team_add_member', $team );

	?>
	<p>
		<?php

		if ( $seat_count > 0 ) {

			$seats_message = sprintf(
				/* translators: Placeholder: %d - seat count */
				_n( 'This team has <strong>%d seat remaining</strong>.', 'This team has <strong>%d seats remaining</strong>.', $remaining_seats, 'woocommerce-memberships-for-teams'  ),
				$remaining_seats
			);

		} else {

			$seats_message = sprintf(
				/* translators: Placeholders: %1$s - opening <strong> HTML tag, %2$s - closing </strong> HTML tag */
				esc_html__( 'This team has %1$sunlimited seats%2$s.', 'woocommerce-memberships-for-teams' ),
				'<strong>', '</strong>'
			);
		}

		if ( $team->is_user_owner( get_current_user_id() ) && ! $team->is_user_member( get_current_user_id() ) ) {

			$action_url = add_query_arg( array(
				'action' => 'add_owner_as_team_member',
			), wp_nonce_url( $teams_area->get_teams_area_url( $team, 'add-member' ), 'add-owner-as-team-member-' . $team->get_id() ) );

			$owner_message = sprintf(
				/* translators: Placeholders: %1$s - HTML opening tags, %2$s - HTML closing tags */
				esc_html__( 'You can %1$sadd yourself as a member%2$s, share your team registration link, or manually add new members below.', 'woocommerce-memberships-for-teams' ),
				'<a href="' . esc_url( $action_url ) . '"><strong>',
				'</strong></a>'
			);

		} else {

			$owner_message = esc_html__( 'You can share your team registration link or manually add new members below.', 'woocommerce-memberships-for-teams' );
		}

		// print messages together with a space between them
		printf( '%1$s %2$s', $seats_message, $owner_message );

		?>
	</p>

	<h3><?php esc_html_e( 'Registration Link', 'woocommerce-memberships-for-teams' ); ?></h3>

	<p><?php esc_html_e( 'This registration link will allow members to register themselves for your team. Please use caution when sharing this, as it allows any visitor to add themselves to your team.', 'woocommerce-memberships-for-teams' ); ?></p>

	<form id="registration-link-form" method="post">

		<?php wp_nonce_field( 'regenerate-team-registration-link-' . $team->get_id(), '_team_link_nonce' ); ?>

		<input
			type="hidden"
			name="regenerate_team_registration_link"
			value="<?php echo esc_attr( $team->get_id() ); ?>"
		/>

		<p class="form-row" id="registration-link_field">
			<input
				type="text"
				class="input-text"
				name="registration_link"
				id="registration-link"
				value="<?php echo esc_url( $team->get_registration_url() ); ?>"
			/>
			<?php if ( current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) ) : ?>
				<button class="woocommerce-button button regenerate-link" type="submit"><?php esc_html_e( 'Regenerate link', 'woocommerce-memberships-for-teams' ); ?></button>
			<?php endif; ?>
		</p>

	</form>

	<h3><?php esc_html_e( 'Add Member', 'woocommerce-memberships-for-teams' ); ?></h3>

	<?php if ( $seat_count > 0 && ! $remaining_seats ) : ?>

		<p><?php esc_html_e( "You can't add more members because your team has no more seats left.", 'woocommerce-memberships-for-teams' ); ?></p>

	<?php elseif ( ! $team->can_be_managed() ) : ?>

		<p><?php echo esc_html( $team->get_management_decline_reason( 'add_member' ) ); ?></p>

	<?php else : ?>

		<?php

		if ( wc_memberships_for_teams()->get_invitations_instance()->should_skip_invitations() ) {
			$additional_information = esc_html__( 'Your team member will be added automatically if they are registered, or receive an invitation via email.', 'woocommerce-memberships-for-teams' );
		} else {
			$additional_information = esc_html__( 'Your team member will receive an invitation via email.', 'woocommerce-memberships-for-teams' );
		}

		?>
		<p>
			<?php printf(
				/* translators: Placeholder: %s - additional information */
				esc_html__( 'Enter member details - %s', 'woocommerce-memberships-for-teams' ),
				lcfirst( $additional_information )
			); ?>
		</p>

		<form id="add-member-form" method="POST">

			<?php wp_nonce_field( 'add-team-member-' . $team->get_id(), '_team_add_member_nonce' ); ?>

			<input
				type="hidden"
				name="add_team_member"
				value="<?php echo esc_attr( $team->get_id() ); ?>"
			/>

			<div class="form-fields">
				<?php foreach ( $fields as $key => $field ) : ?>
					<?php $value = isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ? $_POST[ $key ] : null; ?>
					<?php woocommerce_form_field( $key, $field, $value ); ?>
				<?php endforeach; ?>
			</div>

			<input
				type="submit"
				class="woocommerce-button button"
				value="<?php esc_attr_e( 'Add member', 'woocommerce-memberships-for-teams' ); ?>"
			/>

		</form>

	<?php endif; ?>

	<?php

	/**
	 * Fires after the Add Member section in My Account page.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
	 */
	do_action( 'wc_memberships_for_teams_after_my_team_add_member', $team );

	?>
</div>
