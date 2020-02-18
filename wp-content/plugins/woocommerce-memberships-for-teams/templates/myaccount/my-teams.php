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
 * Renders a section on My Account page to list customer teams.
 *
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Team[] $teams array of team objects
 * @type \SkyVerge\WooCommerce\Memberships\Teams\Frontend\Teams_Area $teams_area teams area handler instance
 *
 * @version 1.0.0
 * @since 1.0.0
 */
global $post;

?>
<div class="woocommerce-account-my-teams">

	<?php

	/**
	 * Fires before the Teams table in My Account page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'wc_memberships_for_teams_before_my_teams' );

	?>

	<?php if ( ! empty( $teams ) ) : ?>

		<table class="shop_table shop_table_responsive my_account_orders my_account_teams">

			<thead>
				<tr>
					<?php

					/**
					 * Filters the Teams table columns in My Account page.
					 *
					 * @since 1.0.0
					 *
					 * @param array $my_teams_columns associative array of column ids and names
					 * @param int $user_id the member ID
					 */
					$my_teams_columns = apply_filters( 'wc_memberships_for_teams_my_teams_column_names', array(
						'team-name'         => _x( 'Name', 'Team name', 'woocommerce-memberships-for-teams' ),
						'team-created-date' => __( 'Created On', 'woocommerce-memberships-for-teams' ),
						'team-member-count' => _x( 'Members', 'Partitive (how many members?)', 'woocommerce-memberships-for-teams' ),
						'team-actions'      => '&nbsp;',
					), get_current_user_id() );

					?>
					<?php foreach ( $my_teams_columns as $column_id => $column_name ) : ?>
						<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<tbody>
				<?php foreach ( $teams as $team ) : // TODO: PAGING ?>

					<tr class="team">
						<?php foreach ( $my_teams_columns as $column_id => $column_name ) : ?>

							<?php if ( 'team-name' === $column_id ) : ?>

								<td class="team-name" data-title="<?php echo esc_attr( $column_name ); ?>">
									<a href="<?php echo esc_url( $teams_area->get_teams_area_url( $team ) ); ?>"><?php echo esc_html( $team->get_name() ); ?></a>
								</td>

							<?php elseif ( 'team-created-date' === $column_id ) : ?>

								<td class="team-created-date" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php $created_time = $team->get_local_date( 'timestamp' ); ?>
									<?php if ( ! empty( $created_time ) && is_numeric( $created_time ) ) : ?>
										<time datetime="<?php echo date( 'Y-m-d', $created_time ); ?>" title="<?php echo esc_attr( date_i18n( wc_date_format(), $created_time ) ); ?>"><?php echo date_i18n( wc_date_format(), $created_time ); ?></time>
									<?php else : ?>
										<?php esc_html_e( 'N/A', 'woocommerce-memberships-for-teams' ); ?>
									<?php endif; ?>
								</td>

							<?php elseif ( 'team-member-count' === $column_id ) : ?>

								<td class="team-member-count" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php

									$used_seat_count = $team->get_used_seat_count();

									if ( $seat_count = $team->get_seat_count() ) {
										printf( esc_html__( '%1$d of %2$s seats', 'woocommerce-memberships-for-teams' ), $used_seat_count, $seat_count );
									} else {
										printf( esc_html__( '%d of unlimited seats', 'woocommerce-memberships-for-teams' ), $used_seat_count );
									}

									?>
								</td>

							<?php elseif ( 'team-seat-count' === $column_id ) : ?>

								<td class="team-seat-count" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php $seat_count = $team->get_seat_count(); ?>
									<?php if ( ! empty( $seat_count ) && is_numeric( $seat_count ) ) : ?>
										<?php echo esc_html( $seat_count ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Unlimited', 'woocommerce-memberships-for-teams' ); ?>
									<?php endif; ?>
								</td>

							<?php elseif ( 'team-actions' === $column_id ) :

								?>
								<td class="team-actions order-actions" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php echo $teams_area->get_action_links( 'teams', $team ); ?>
								</td>

							<?php else : ?>

								<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
									<?php

									/**
									 * Fires when populating a Teams Area table column.
									 *
									 * @since 1.0.0
									 *
									 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the current team
									 */
									do_action( "wc_memberships_for_teams_my_teams_column_{$column_id}", $team );

									?>
								</td>

							<?php endif; ?>

						<?php endforeach; ?>
					</tr>

				<?php endforeach; ?>
			</tbody>
		</table>

	<?php else : ?>

		<p>
			<?php

			/**
			 * Filters the text for non owners in My Account area.
			 *
			 * @since 1.0.0
			 *
			 * @param string $no_teams_text the text displayed to users without team ownerships
			 * @param int $user_id the current user
			 */
			echo (string) apply_filters( 'wc_memberships_for_teams_my_teams_no_teams_text', __( "Looks like you don't have any teams yet!", 'woocommerce-memberships-for-teams' ), get_current_user_id() );

			?>
		</p>

	<?php endif; ?>


	<?php

	/**
	 * Fires after the Teams table in My Account page.
	 *
	 * @since 1.0.0
	 */
	do_action( 'wc_memberships_for_teams_after_my_teams' );

	?>

</div>
<?php
