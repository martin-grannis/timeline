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
 * Renders the join team page.
 *
 * @version 1.0.0
 * @since 1.0.0
 */

defined( 'ABSPATH' ) or exit;

global $token, $team, $invitation, $is_invitation_page;

$current_user                 = get_user_by( 'id', get_current_user_id() );
$current_user_matches_invitee = $current_user && $invitation && $invitation->get_email() === $current_user->user_email;
$logout_url                   = $team ? wp_logout_url( $invitation ? $invitation->get_accept_url() : $team->get_registration_url() ) : null;
$existing_user_membership     = $team && $current_user ? $team->get_existing_user_membership( $current_user->ID ) : null;

// TODO: possibly fine-tune error messages below, ie in case the invitation is valid, but the team no longer exists {IT 2017-09-15}
?>

<div class="woocommerce-account-join-team">

<?php if ( ! $team ) : ?>

	<p class="woocommerce-error"><?php esc_html_e( 'Looks like the link you followed is no longer valid. If you were invited to join a team contact the team owner and ask them for a new invitation.', 'woocommerce-memberships-for-teams' ); ?></p>

<?php else : ?>

	<?php if ( $current_user ) : ?>

		<p>
			<?php printf(
				/* translators: Placeholders: %1$s user display name, %2$s logout url */
				__( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'woocommerce' ),
				'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
				esc_url( $logout_url )
			); ?>
		</p>

	<?php else : ?>

		<p><?php esc_html_e( 'Hey there!', 'woocommerce-memberships-for-teams' ); ?></p>

	<?php endif; ?>

	<p>
		<?php if ( $invitation && $sender = $invitation->get_sender() ) : ?>
			<?php /* translators: Placeholders: %1$s - inviter name, %2$s - team name */ ?>
			<?php printf( esc_html__( 'You\'ve been invited by %1$s to join %2$s.', 'woocommerce-memberships-for-teams' ), $sender->display_name, $team->get_name() ); ?>
		<?php else : ?>
			<?php /* translators: Placeholders: %s - team name */ ?>
			<?php printf( esc_html__( "You've been invited to join %s.", 'woocommerce-memberships-for-teams' ), $team->get_name() ); ?>
		<?php endif; ?>

		<?php if ( $team->get_membership_end_date() ) : ?>
			<?php

			// ensure correct end date is displayed
			$end_date = $team->get_local_membership_end_date( 'timestamp' );

			if ( $existing_user_membership && $existing_user_membership->get_end_date( 'timestamp' ) > $end_date ) {
				$end_date = $existing_user_membership->get_end_date( 'timestamp' );
			}

			?>
			<?php /* translators: Placeholders: %1$s - membership plan name, %2$s - date */ ?>
			<?php printf( esc_html__( 'This will give you %1$s access until %2$s.', 'woocommerce-memberships-for-teams' ), $team->get_plan()->get_name(), date_i18n( wc_date_format(), $end_date ) ); ?>
		<?php else : ?>
			<?php /* translators: Placeholders: %s - membership plan name */ ?>
			<?php printf( esc_html__( 'This will give you %s access.', 'woocommerce-memberships-for-teams' ), $team->get_plan()->get_name() ); ?>
		<?php endif; ?>

		<?php if ( $invitation ) : ?>
			<?php if ( $current_user && $current_user->user_email === $invitation->get_email() ) : ?>
				<?php esc_html_e( 'Click the button below to join this team.', 'woocommerce-memberships-for-teams' ); ?>
			<?php elseif ( ! $current_user && $invitation->get_user() ) : ?>
				<?php esc_html_e( 'Please log in with your account to join this team.', 'woocommerce-memberships-for-teams' ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</p>

	<?php if ( $invitation && $current_user && ! $current_user_matches_invitee ) : ?>
		<div class="woocommerce-error">
			<p><?php printf( esc_html__( 'We sent this invitation to %1$s, but it looks like you are logged in as %2$s', 'woocommerce-memberships-for-teams' ), $invitation->get_email(), $current_user->user_email ); ?></p>
			<p><?php printf( esc_html__( 'If you want to accept this invitation with your current account, click the button below, otherwise log out and sign up or log in as %s', 'woocommerce-memberships-for-teams' ), $invitation->get_email() ); ?></p>
		</div>
	<?php elseif ( ! $current_user && ( ! $invitation || ! $invitation->get_user() ) ) : ?>
		<p><?php esc_html_e( 'Please create an account or log in with an existing account to join this team.', 'woocommerce-memberships-for-teams' ); ?></p>
	<?php endif; ?>

	<?php // notify about user membership reassignment ?>
	<?php if ( ( $current_user && ! $invitation || $current_user && $current_user_matches_invitee ) && $existing_user_membership ) : ?>
		<?php if ( $current_team = wc_memberships_for_teams()->get_teams_handler_instance()->get_user_membership_team( $existing_user_membership->get_id() ) ) : ?>
			<?php /* translators: Placeholders: %1$s - current team name, %2$s - membership plan name, %3$s - new team name */ ?>
			<p class="woocommerce-info"><?php printf( esc_html__( 'You are a member of %1$s, which already gives you access to %2$s. Joining %3$s means you will leave your current team and your existing membership will be moved under new team management.', 'woocommerce-memberships-for-teams' ), $current_team->get_name(), $team->get_plan()->get_name(), $team->get_name() ); ?></p>
		<?php else : ?>
			<?php /* translators: Placeholders: %s - membership plan name */ ?>
			<p class="woocommerce-info"><?php printf( esc_html__( 'Your existing %s membership will be moved under team management.', 'woocommerce-memberships-for-teams' ), $team->get_plan()->get_name() ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( $current_user ) : ?>

		<?php if ( $team->is_user_member( $current_user->ID ) ) : ?>

			<?php /* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */ ?>
			<p class="woocommerce-info"><?php printf( esc_html__( 'You are already a member of this team. %1$sClick here to view your account%2$s.', 'woocommerce-memberships-for-teams' ), '<a href="' . wc_get_page_permalink( 'myaccount' ) . '">', '</a>' ); ?></p>

		<?php else : ?>

			<form id="join-team-form" method="post">

				<?php wp_nonce_field( 'join-team-' . $team->get_id() ); ?>

				<input type="hidden" name="join_team" value="<?php echo esc_attr( $token ); ?>" />

				<?php

				/**
				 * Fires at the beginning of the join team form.
				 *
				 * @since 1.0.2
				 *
				 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
				 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation|false $invitation team invitation instance or false if no invitation
				 */
				do_action( 'woocommerce_memberships_for_teams_join_team_form', $team, $invitation );

				?>

				<?php if ( $invitation && ! $current_user_matches_invitee ) : ?>
					<button class="button" type="submit"><?php /* translators: Placeholders: %s - email */ printf( esc_html__( 'Join Team as %s', 'woocommerce-memberships-for-teams' ), $current_user->user_email ); ?></button>
					<?php /* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */ printf( __( 'Or %1$sLog Out%2$s to create a new account', 'woocommerce-memberships-for-teams' ), '<a href="' . $logout_url . '">', '</a>' ); ?>
				<?php else : ?>
					<button class="button" type="submit"><?php esc_html_e( 'Join Team', 'woocommerce-memberships-for-teams' ); ?></button>
				<?php endif; ?>

			</form>

		<?php endif; ?>

	<?php elseif ( $invitation && $invitation->get_user() ) : ?>

		<?php woocommerce_login_form(); ?>

	<?php else : ?>

		<?php wc_get_template( 'myaccount/form-join-team-register.php', array( 'invitation' => $invitation, 'token' => $token ) ); ?>

	<?php endif; ?>

<?php endif; ?>

</div>
