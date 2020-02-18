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
 * Admin User Memberships class
 *
 * @since 1.0.0
 */
class User_Memberships {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// extend user membership columns in admin edit screen
		add_filter( 'manage_edit-wc_user_membership_columns',        array( $this, 'handle_edit_screen_columns' ), 20 );
		add_action( 'manage_wc_user_membership_posts_custom_column', array( $this, 'handle_edit_screen_column_content' ), 20, 2 );

		// add user membership team details
		add_filter( 'wc_memberships_user_membership_billing_details', array( $this, 'replace_user_membership_billing_details' ), 11, 2 );

		// disable user membership fields and transfer action when part of a team
		add_action( 'wc_memberships_after_user_membership_details', array( $this, 'disable_user_membership_fields' ) );
		add_filter( 'wc_memberships_user_membership_actions',       array( $this, 'remove_transfer_action' ), 10, 2 );
		add_filter( 'post_row_actions',                             array( $this, 'remove_row_actions' ), 20, 2 );

		// add teams sorting / filtering abilities to user memberships
		add_action( 'restrict_manage_posts', array( $this, 'output_user_memberships_team_filters' ), 11 );
		add_filter( 'request',               array( $this, 'request_query' ) );
	}


	/**
	 * Customizes the user membership admin screen columns.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 *
	 * @param array $columns
	 * @return array
	 */
	public function handle_edit_screen_columns( $columns ) {

		$team_column = array( 'team' => __( 'Team', 'woocommerce-memberships-for-teams' ) );

		if ( isset( $columns['plan'] ) ) {
			$columns = Framework\SV_WC_Helper::array_insert_after( $columns, 'plan', $team_column );
		} else {
			$columns = array_merge( $columns, $team_column );
		}

		return $columns;
	}


	/**
	 * Handles content for the teams columns in user memberships edit screen.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 *
	 * @param string $column column name
	 * @param int $user_membership_id the corresponding post ID
	 */
	public function handle_edit_screen_column_content( $column, $user_membership_id ) {
		global $post;

		if ( 'team' === $column ) {

			$team = wc_memberships_for_teams_get_user_membership_team( $user_membership_id );

			if ( $team ) {

				$member = wc_memberships_for_teams_get_team_member( $team->get_id(), $post ? $post->post_author : 0 );

				echo '<a href="' . get_edit_post_link( $team->get_id() ) .' ">' . $team->get_formatted_name() . '</a>';
				echo $member ? '<br /><em>' . $member->get_role( 'label' ) . '</em>' : '';

			} else {

				echo '&ndash;';
			}
		}
	}


	/**
	 * Replaces the user membership billing details with team details for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $billing_fields associative array of labels and data or inputs
	 * @param \WC_Memberships_User_Membership $user_membership user membership instance
	 * @return array
	 */
	public function replace_user_membership_billing_details( $billing_fields, $user_membership ) {

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership->id );

		if ( $team_id ) {

			$team   = wc_memberships_for_teams_get_team( $team_id );
			$member = wc_memberships_for_teams_get_team_member( $team, $user_membership->get_user() );

			$added_time = $member->get_local_added_date( 'timestamp' );

			if ( ! $added_time ) {
				return __( 'N/A', 'woocommerce-memberships-for-teams' );
			}

			$date_format = wc_date_format();
			$time_format = wc_time_format();

			$date = esc_html( date_i18n( $date_format, (int) $added_time ) );
			$time = esc_html( date_i18n( $time_format, (int) $added_time ) );

			$added = sprintf( '%1$s %2$s', $date, $time );
			$role  = $member->get_role( 'label' );

			$billing_fields = array(
				__( 'Granted from team:', 'woocommerce-memberships-for-teams' ) => '<a href="' . get_edit_post_link( $team_id ) . '">' . esc_html( $team->get_formatted_name() ) . '</a>',
				__( 'Member added:', 'woocommerce-memberships-for-teams' )      => esc_html( $added ),
				__( 'Team role:', 'woocommerce-memberships-for-teams' )         => esc_html( $role ),
			);
		}

		return $billing_fields;
	}


	/**
	 * Disables editing user membership details for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_User_Membership $user_membership user membership instance
	 */
	public function disable_user_membership_fields( $user_membership  ) {

		if ( $team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership->get_id() ) ) :

			?>
			<div id="wc-memberships-for-teams-user-membership-editing-locked">
				<input
					type="hidden"
					name="_team_membership_allow_edit"
					id="wc-memberships-for-teams-allow-edit-user-membership"
					value=""
				/>
				<?php

				$editing_actions = array( sprintf(
					/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
					'<br />' . esc_html__( '%1$sEdit team details%2$s instead', 'woocommerce-memberships-for-teams' ),
					'<a href="' . get_edit_post_link( $team_id ) . '">', '</a>'
				) );

				/**
				 * Filters whether editing of a team user membership is allowed.
				 *
				 * Confirmation will be required by the admin user.
				 *
				 * @since 1.1.2
				 *
				 * @param bool $allow_edit default true for non-subscription linked memberships
				 * @param \WC_Memberships_User_Membership|\WC_Memberships_Integration_Subscriptions_User_Membership $user_membership membership object
				 */
				if ( true === apply_filters( 'wc_memberships_for_teams_allow_editing_user_membership', ! $user_membership instanceof \WC_Memberships_Integration_Subscriptions_User_Membership, $user_membership ) ) :

					$editing_actions[] = strtolower( sprintf(
						/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s closing </a> HTML link tag */
						esc_html__( '%1$sEnable editing%2$s for this user membership.', 'woocommerce-memberships-for-teams' ),
						'<a id="wc-memberships-for-teams-user-membership-edit-override" href="#">', '</a>'
					) );

				endif;

				?>
				<p class="form-field">
					<?php printf(
						/* translators: Placeholders: %s - text with possible actions for the user */
						esc_html__( 'Editing has been disabled because this membership belongs to a team. %s', 'woocommerce-memberships-for-teams' ),
						wc_memberships_list_items( $editing_actions )
					); ?>
				</p>
			</div>
			<?php

			// alter the deletion alert text
			wc_enqueue_js( " 
				wc_memberships_admin.i18n.delete_membership_confirm += ' " . esc_html__( 'This will remove the member from the team.', 'woocommerce-memberships-for-teams' ) . "'; 
			" );

		endif;
	}


	/**
	 * Removes transfer membership action for team-based memberships.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $actions array of membership admin actions
	 * @param int $user_membership_id the post id of the wc_user_membership post
	 * @return string[]
	 */
	public function remove_transfer_action( $actions, $user_membership_id ) {

		$team_id = wc_memberships_for_teams_get_user_membership_team_id( $user_membership_id );

		if ( (int) $team_id > 0 ) {
			unset( $actions['transfer-action'] );
		}

		return $actions;
	}


	/**
	 * Removes edit screen row actions from team-based user memberships.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 *
	 * @param array $actions associative array of row actions
	 * @param \WP_Post $post related membership post object
	 * @return array
	 */
	public function remove_row_actions( $actions, $post ) {

		if ( 'wc_user_membership' === $post->post_type ) {

			$team_id = wc_memberships_for_teams_get_user_membership_team_id( $post->ID );

			if ( (int) $team_id > 0 ) {
				unset( $actions['pause'], $actions['cancel'] );
			}
		}

		return $actions;
	}


	/**
	 * Outputs team filters for the user memberships list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The post type slug.
	 */
	public function output_user_memberships_team_filters( $post_type ) {

		if ( 'wc_user_membership' === $post_type ) :

			$team_id   = 0;
			$team_name = '';

			if ( ! empty( $_GET['_team_id'] ) ) {

				$team_id = absint( $_GET['_team_id'] );

				if ( $team = wc_memberships_for_teams_get_team( $team_id ) ) {

					$team_name = esc_html( $team->get_formatted_name() );
				}
			}

			?>
			<span class="wc-memberships-for-teams-filter-by-team-id-wrapper" style="display:inline-block;width:200px;">
				<select
					name="_team_id"
					class="sv-wc-enhanced-search"
					id="wc-memberships-for-teams-filter-by-team-id"
					style="min-width:200px;"
					data-action="wc_memberships_for_teams_json_search_teams"
					data-nonce="<?php echo wp_create_nonce( 'search-teams' ); ?>"
					data-placeholder="<?php esc_attr_e( 'Search for a team&hellip;', 'woocommerce-memberships-for-teams' ); ?>"
					data-allow_clear="true">
					<?php if ( $team_id > 0 ) : ?>
						<option value="<?php echo esc_attr( $team_id ); ?>" selected><?php echo esc_html( $team_name ); ?></option>
					<?php endif; ?>
				</select>
			</span>

			<?php Framework\SV_WC_Helper::render_select2_ajax(); ?>

			<label style="margin: 0 4px;">
				<input
					type="checkbox"
					name="_excl_teams"
					id="wc-memberships-for-teams-filter-by-no-team"
					value="yes"
					<?php checked( isset( $_GET['_excl_teams'] ) && 'yes' === $_GET['_excl_teams'] ); ?>
				/><?php esc_html_e( 'Exclude team members', 'woocommerce-memberships-for-teams' ); ?>
			</label>
			<?php

		endif;
	}


	/**
	 * Handles custom filters and sorting for the user memberships screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars query vars for \WP_Query
	 * @return array modified query vars
	 */
	public function request_query( $vars ) {
		global $typenow;

		if ( 'wc_user_membership' === $typenow ) {

			$team_id  = isset( $_GET['_team_id'] )    && is_numeric( $_GET['_team_id'] ) ? absint( $_GET['_team_id'] ) : 0;
			$no_teams = isset( $_GET['_excl_teams'] ) && 'yes' === $_GET['_excl_teams'];

			if ( ( $team_id > 0 || $no_teams ) && ! isset( $vars['meta_query'] ) ) {
				$vars['meta_query'] = array();
			}

			if ( $no_teams ) {
				$vars['meta_query'][] = array(
					'key'     => '_team_id',
					'compare' => 'NOT EXISTS',
				);
			} elseif ( $team_id > 0 ) {
				$vars['meta_query'][] = array(
					'key'   => '_team_id',
					'value' => $team_id,
				);
			}
		}

		return $vars;
	}


	/**
	 * Disables updating user membership for team-based memberships.
	 *
	 * TODO remove this deprecated method by version 2.0.0 or by May 2020, whichever comes first {FN 2019-01-15}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated since 1.1.1
	 */
	public function maybe_disable_updating_user_membership() {

		_deprecated_function( 'SkyVerge\WooCommerce\Memberships\Teams\Admin\User_Memberships::maybe_disable_updating_user_membership::()', '1.1.1' );
	}


}
