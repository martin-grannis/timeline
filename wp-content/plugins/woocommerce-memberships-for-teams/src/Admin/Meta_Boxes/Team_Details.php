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
 * Team Details meta box class.
 *
 * @since 1.0.0
 */
class Team_Details {


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
		add_meta_box( 'wc-memberships-for-teams-team-details', __( 'Team Details', 'woocommerce-memberships-for-teams' ), array( $this, 'output' ), 'wc_memberships_team', 'side', 'high' );
	}


	/**
	 * Outputs meta box contents.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output() {
		global $team, $post, $action;

		$seat_count       = $team->get_seat_count();
		$selected_plan_id = $team->get_plan_id();
		$user_id          = $team->get_owner_id();

		$allowed = array( 'input' => array( 'type' => array(), 'class' => array(), 'placeholder' => array(), 'name' => array(), 'id' => array(), 'maxlength' => array(), 'size' => array(), 'value' => array(), 'patten' => array() ), 'div' => array( 'class' => array() ), 'span' => array(), 'br' => array() );

		?>

		<input type="hidden" name="post_status" value="publish" />

		<?php

		/**
		 * Fires at the beginning of the team details meta box.
		 *
		 * @since 1.0.0
		 *
		 * @param int $team_id Team (post) ID
		 */
		do_action( 'woocommerce_memberships_for_teams_team_details_meta_box_start', $post->ID );
		?>

		<div id="team-owner-section" class="section">
			<!--email_off--> <!-- Disable CloudFlare email obfuscation -->
			<label for="post_author"><?php esc_html_e( 'Owner:', 'woocommerce-memberships-for-teams' ); ?>
			<?php
				if ( $user_id ) {
					printf( '<a href="%s">%s</a>',
						esc_url( get_edit_user_link( $user_id ) ),
						esc_html__( 'View user &rarr;', 'woocommerce-memberships-for-teams' )
					);
				}
			?>
			</label><br>
			<?php
			$user_string = '';
			if ( $user_id ) {
				$user        = $team->get_owner();
				/* translators: 1: user display name 2: user ID 3: user email */
				$user_string = sprintf(
					esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
					$user->display_name,
					absint( $user_id ),
					$user->user_email
				);
			}

			?>
			<input type="hidden" id="original_post_author" value="<?php echo esc_attr( $user_id ); ?>" />
			<select class="wc-customer-search wide" id="post_author" name="post_author" data-placeholder="<?php esc_attr_e( 'No-one', 'woocommerce-memberships-for-teams' ); ?>" data-allow_clear="false">
				<option value="<?php echo esc_attr( $user_id ); ?>"><?php echo htmlspecialchars( $user_string ); ?></option>
			</select>
			<!--/email_off-->
		</div>

		<div id="team-created-date-section" class="section">
			<label for="created_date"><?php esc_html_e( 'Created:', 'woocommerce-memberships-for-teams' ); ?></label><br>
			<?php echo $this->date_input( $team->get_local_date( 'timestamp' ), array( 'name_attr' => 'created_date' ) ); ?>
		</div>

		<div id="team-membership-end-date-section" class="section">
			<label for="membership_end_date"><?php esc_html_e( 'Team memberships begin to expire:', 'woocommerce-memberships-for-teams' ); ?></label><br>
			<?php echo $this->date_input( $team->get_local_membership_end_date( 'timestamp' ), array( 'name_attr' => 'membership_end_date' ) ); ?>
		</div>

		<div id="team-seat-section" class="section">
			<label for="seat_count"><?php esc_html_e( 'Seat count:', 'woocommerce-memberships-for-teams' ); ?></label><br>
			<input type="number" class="wide" min="0" step="1" name="seat_count" id="seat_count" value="<?php echo esc_attr( $seat_count ); ?>" placeholder="<?php esc_attr_e( 'Leave blank for unlimited', 'woocommerce-memberships-for-teams' ); ?>" />
		</div>

		<div id="team-plan-section" class="section">
			<label for="team_membership_plan"><?php esc_html_e( 'Members can access:', 'woocommerce-memberships-for-teams' ); ?></label><br>
			<select id="team_membership_plan" class="wide" name="membership_plan_id" <?php if ( $team->has_active_members() ) : ?>disabled title="<?php esc_attr_e( 'Plan cannot be changed because this team has active members.', 'woocommerce-memberships-for-teams' ); ?>"<?php endif; ?>>
				<?php foreach ( wc_memberships()->get_plans_instance()->get_available_membership_plans( 'labels' ) as $plan_id => $name ) : ?>
					<option value="<?php echo esc_attr( $plan_id ); ?>" <?php selected( $plan_id, $selected_plan_id ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php

		/**
		 * Fires at the end of the team details meta box.
		 *
		 * @since 1.0.0
		 *
		 * @param int $team_id Team (post) ID
		 */
		do_action( 'woocommerce_memberships_for_teams_team_details_meta_box_end', $post->ID );

		?>

		<div id="team-actions">

			<?php do_action( 'post_submitbox_start' ); ?>

			<div id="delete-action">
				<?php
				if ( current_user_can( 'delete_post', $post->ID ) ) {
					if ( ! EMPTY_TRASH_DAYS ) {
						$delete_text = __( 'Delete Permanently', 'woocommerce-memberships-for-teams' );
					} else {
						$delete_text = __( 'Move to Trash', 'woocommerce-memberships-for-teams' );
					}
					?>
				<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo esc_html( $delete_text ); ?></a><?php
				} ?>
			</div>

			<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update' ) ?>" />
		</div>
		<?php
	}


	 /**
	 * Renders a date/time input field.
	 *
	 * Borrowed with thanks from WooCommerce Subscriptions.
	 *
	 * @since 1.0.0
	 *
	 * @param int (optional) timestamp for a certain date, if empty, will leave inputs empty
	 * @param array $args {
	 *    an array of arguments
	 *
	 *    @type string $id_attr the date to display in the selector in MySQL format ('Y-m-d H:i:s')
	 *    @type string $date the date to display in the selector in MySQL format ('Y-m-d H:i:s')
	 *    @type int $tab_index' (optional) the tab index for the element, defaults to 0
	 *    @type bool $include_time (optional) whether to include a specific time for the selector, defaults to true
	 *    @type bool $include_year (optional) whether to include a the year field, defaults to true
	 *    @type bool $include_buttons (optional) whether to include submit buttons on the selector, defaults to true
	 * }
	 * @return string HTML input
	 */
	private function date_input( $timestamp = null, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'name_attr'    => '',
			'include_time' => true,
		) );

		$date = $timestamp ? date_i18n( 'Y-m-d', $timestamp ) : '';

		/* translators: date placeholder for input, javascript format */
		$date_input = '<input type="text" class="date-picker" placeholder="' . esc_attr__( 'YYYY-MM-DD', 'woocommerce-memberships-for-teams' ) . '" name="' . esc_attr( $args['name_attr'] ) . '" id="' . esc_attr( $args['name_attr'] ) . '" maxlength="10" value="' . esc_attr( $date ) . '" pattern="([0-9]{4})-(0[1-9]|1[012])-(##|0[1-9#]|1[0-9]|2[0-9]|3[01])"/>';

		if ( true === $args['include_time'] ) {
			$hours        = $timestamp ? date_i18n( 'H', $timestamp ) : '';
			/* translators: hour placeholder for time input, javascript format */
			$hour_input   = '<input type="text" class="hour" placeholder="' . esc_attr__( 'HH', 'woocommerce-memberships-for-teams' ) . '" name="' . esc_attr( $args['name_attr'] ) . '_hour" id="' . esc_attr( $args['name_attr'] ) . '_hour" value="' . esc_attr( $hours ) . '" maxlength="2" size="2" pattern="([01]?[0-9]{1}|2[0-3]{1})" />';
			$minutes      = $timestamp ? date_i18n( 'i', $timestamp ) : '';
			/* translators: minute placeholder for time input, javascript format */
			$minute_input = '<input type="text" class="minute" placeholder="' . esc_attr__( 'MM', 'woocommerce-memberships-for-teams' ) . '" name="' . esc_attr( $args['name_attr'] ) . '_minute" id="' . esc_attr( $args['name_attr'] ) . '_minute" value="' . esc_attr( $minutes ) . '" maxlength="2" size="2" pattern="[0-5]{1}[0-9]{1}" />';
			$date_input   = sprintf( '%s@%s:%s', $date_input, $hour_input, $minute_input );
		}

		$date_input = '<div class="wc-memberships-for-teams-date-input">' . $date_input . '</div>';

		return $date_input;
	}


	/**
	 * Parses and combines a posted date + time into a timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date_field date field key
	 * @return int|false timestamp or false orn failure
	 */
	private function parse_posted_date( $date_field ) {

		$date   = $_POST[ $date_field ];
		$hour   = str_pad( (int) $_POST[ $date_field . '_hour' ], 2, '0', STR_PAD_LEFT );
		$minute = str_pad( (int) $_POST[ $date_field . '_minute'  ], 2, '0', STR_PAD_LEFT );
		$second = '00';

		$mysql_date = sprintf( '%s %s:%s:%s', $date, $hour, $minute, $second );

		return strtotime( $mysql_date );
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

		$timezone = wc_timezone_string();

		// update date
		if ( empty( $_POST['created_date'] ) ) {
			$date = current_time( 'timestamp' );
		} else {
			$date = $this->parse_posted_date( 'created_date' );
		}

		$post->post_date     = date_i18n( 'Y-m-d H:i:s', $date );
		$post->post_date_gmt = get_gmt_from_date( $post->post_date );

		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->posts
			SET post_date = %s, post_date_gmt = %s
			WHERE ID = %s
		", $post->post_date, $post->post_date_gmt, $post_id ) );

		$team = wc_memberships_for_teams_get_team( $post->ID );

		// update end date if changed
		if ( ! empty( $_POST['membership_end_date'] ) && $end_date = $this->parse_posted_date( 'membership_end_date' ) ) {
			$end_date = wc_memberships_adjust_date_by_timezone( $end_date, 'timestamp', $timezone );
		} else {
			$end_date = '';
		}

		if ( $end_date != $team->get_membership_end_date( 'timestamp' ) ) {
			$team->set_membership_end_date( $end_date );
		}

		// update seat count
		if ( isset( $_POST['seat_count'] ) ) {

			$used_seats = $team->get_used_seat_count();
			$seat_count = ! empty( $_POST['seat_count'] ) ? absint( $_POST['seat_count'] ) : '';

			if ( $seat_count && $used_seats > $seat_count ) {
				/* translators: Placeholders: %1$d - a number, %2$d - a number */
				wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Could not update seat count - the value provided (%1$d) is lower than the number of seats used (%2$d).', 'woocommerce-memberships-for-teams' ), $seat_count, $used_seats ) );
			} else {
				update_post_meta( $post->ID, '_seat_count', $seat_count );
			}
		}

		// update plan id (unless there are active members on this team)
		if ( ! empty( $_POST['membership_plan_id'] ) && ! $team->has_active_members() ) {

			$plan_id = (int) $_POST['membership_plan_id'];

			try {
				$team->set_plan_id( $plan_id );
			} catch ( Framework\SV_WC_Plugin_Exception $e ) {
				/* translators: Placeholder: %s - error message */
				wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Could not update team plan: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
			}
		}
	}


}
