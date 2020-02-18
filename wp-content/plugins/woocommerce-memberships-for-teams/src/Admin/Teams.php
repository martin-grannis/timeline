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
use SkyVerge\WooCommerce\Memberships\Teams\Plugin;

defined( 'ABSPATH' ) or exit;

/**
 * Admin Teams class
 *
 * @since 1.0.0
 */
class Teams {


	/** @var bool whether meta boxes wre already saved or not */
	private $saved_meta_boxes = false;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// teams admin screen columns
		add_filter( 'manage_edit-wc_memberships_team_columns',          array( $this, 'customize_columns' ) );
		add_filter( 'manage_edit-wc_memberships_team_sortable_columns', array( $this, 'customize_sortable_columns' ) );
		add_action( 'manage_wc_memberships_team_posts_custom_column',   array( $this, 'custom_column_content' ), 10, 2 );

		// filter row actions
		add_filter( 'post_row_actions', array( $this, 'customize_row_actions' ), 10, 2 );

		// filter post states displayed in teams list table
		add_filter( 'display_post_states', array( $this, 'customize_post_states' ), 10, 2 );

		// filter/sort by custom columns
		add_filter( 'request', array( $this, 'request_query' ) );

		add_action( 'edit_form_top',     array( $this, 'team_nonce' ) );
		add_action( 'save_post',         array( $this, 'save' ), 10, 2 );
		add_action( 'dbx_post_advanced', array( $this, 'load_team' ), 1 );
		add_action( 'dbx_post_sidebar',  array( $this, 'add_hidden_controls' ) );

		add_action( 'add_meta_boxes', array( $this, 'customize_meta_boxes' ), 30 );

		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );
	}


	/**
	 * Customizes teams list table columns.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns
	 * @return array
	 */
	public function customize_columns( $columns ) {

		// remove title and date columns
		unset( $columns['title'],  $columns['date'] );

		$columns['title']   = __( 'Name', 'woocommerce-memberships-for-teams' );         // team name column
		$columns['owner']   = __( 'Owner', 'woocommerce-memberships-for-teams' );        // owner name & email
		$columns['plan']    = __( 'Plan', 'woocommerce-memberships-for-teams' );         // associated membership plan
		$columns['created'] = __( 'Created On', 'woocommerce-memberships-for-teams' );   // team creation date
		$columns['members'] = __( 'Members', 'woocommerce-memberships-for-teams' );    // member count

		return $columns;
	}


	/**
	 * Customizes teams list table sortable columns.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns
	 * @return array
	 */
	public function customize_sortable_columns( $columns ) {

		$columns['created'] = array( 'date', true );
		$columns['members'] = array( 'used_seats', true );

		return $columns;
	}


	/**
	 * Customizes teams list table row actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions
	 * @param \WP_Post $post
	 * @return array
	 */
	public function customize_row_actions( $actions, \WP_Post $post ) {

		if ( 'wc_memberships_team' === $post->post_type ) {

			// remove quick edit, permanently delete actions
			unset( $actions['inline hide-if-no-js'], $actions['delete'] );

			$team = wc_memberships_for_teams_get_team( $post );

			if ( $post && isset( $actions['trash'] ) && $team->has_active_members() ) {

				$tip = '';

				if ( 'trash' === $post->post_status ) {
					$tip = esc_attr__( 'This team cannot be restored because it has active members.', 'woocommerce-memberships-for-teams' );
				} elseif ( EMPTY_TRASH_DAYS ) {
					$tip = esc_attr__( 'This team cannot be moved to trash because it has active members.', 'woocommerce-memberships-for-teams' );
				}

				if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
					$tip = esc_attr__( 'This team cannot be permanently deleted because it has active members.', 'woocommerce-memberships-for-teams' );
				}

				$actions['trash'] = '<span title="' . $tip . '" style="cursor: help;">' . strip_tags( $actions['trash'] ) . '</span>';
			}
		}

		return $actions;
	}



	/**
	 * Customizes post states displayed in teams list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_states
	 * @param \WP_Post $post
	 * @return array
	 */
	public function customize_post_states( $post_states, \WP_Post $post ) {

		if ( 'wc_memberships_team' === $post->post_type ) {

			// remove password protected post state
			unset( $post_states['protected'] );
		}

		return $post_states;
	}


	/**
	 * Outputs custom column content for teams list table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function custom_column_content( $column, $post_id ) {

		$team        = wc_memberships_for_teams_get_team( $post_id );
		$owner       = get_userdata( $team->get_owner_id() );
		$date_format = wc_date_format();
		$time_format = wc_time_format();

		switch ( $column ) {

			case 'owner':
				printf( '<strong><a href="%s">%s</a></strong><br>%s', get_edit_user_link( $owner->ID ), $owner->display_name, $owner->user_email );
			break;

			case 'plan':

				// It shouldn't normally ever happen that the plan can't be found,
				// but prevents fatal errors on borked installations where the
				// associated plan disappeared.
				if ( $plan = $team->get_plan() ) {
					echo '<a href="' . esc_url( get_edit_post_link( $plan->get_id() ) ) . '">' . $plan->get_name() . '</a>';
				} else {
					echo '-';
				}

			break;

			case 'created':

				$created_time = $team->get_local_date( 'timestamp' );

				$date = esc_html( date_i18n( $date_format, (int) $created_time ) );
				$time = esc_html( date_i18n( $time_format, (int) $created_time ) );

				printf( '%1$s %2$s', $date, $time );

			break;

			case 'members':
				$used_seat_count = $team->get_used_seat_count();

				if ( $seat_count = $team->get_seat_count() ) {
					/* translators: Placeholders: %1$d - a number, %2$d - a number */
					printf( esc_html__( '%1$d of %2$s seats', 'woocommerce-memberships-for-teams' ), $used_seat_count, $seat_count );
				} else {
					/* translators: Placeholder: %d - a number */
					printf( esc_html__( '%d of unlimited seats', 'woocommerce-memberships-for-teams' ), $used_seat_count );
				}
			break;
		}
	}


	/**
	 * Handles custom filters and sorting for the teams screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args query vars for \WP_Query
	 * @return array modified query vars
	 */
	public function request_query( $query_args ) {
		global $typenow;

		if ( 'wc_memberships_team' === $typenow ) {

			// custom sorting
			if ( isset( $query_args['orderby'] ) && 'used_seats' === $query_args['orderby'] ) {

				$query_args = array_merge( $query_args, array(
					'orderby'    => 'meta_value',
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key'     => '_used_seats',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_used_seats',
							'compare' => 'EXISTS',
						),
					)
				) );
			}

			// additional free input searches
			if ( ! empty( $query_args['s'] ) ) {

				$team_query_args = array(
					'post_type'   => 'wc_memberships_team',
					'post_status' => 'any',
					'fields'      => 'ids',
					'nopaging'    => true,
				);

				// a number could be an ID for the team, but also an ID for user membership, plan order, subscription or customer
				if ( is_numeric( $query_args['s'] ) ) {

					if ( $found_team = wc_memberships_for_teams_get_team( (int) $query_args['s'] ) ) {

						$found_team_ids_by_s[] = $found_team->get_id();

					} else {

						$user_membership_id = $membership_plan_id = $order_id = $subscription_id = $member_id = (int) $query_args['s'];
						$meta_query_args    = $team_query_args;

						$meta_query_args['meta_query'] = array(
							'relation' => 'OR',
							array(
								'key'     => '_member_id',
								'value'   => $member_id,
								'compare' => 'LIKE',
							),
							array(
								'key'     => '_order_id',
								'value'   => $order_id,
								'type'    => 'numeric',
							),
							array(
								'key'     => '_subscription_id',
								'value'   => $subscription_id,
								'type'    => 'numeric',
							),
						);

						if ( $user_membership = wc_memberships_get_user_membership( $user_membership_id ) ) {

							$meta_query_args['meta_query'][] = array(
								'key'     => '_member_id',
								'value'   => $user_membership->get_user_id(),
								'compare' => 'LIKE',
							);
						}

						$found_team_ids_by_s = get_posts( $meta_query_args );

						if ( $plan = wc_memberships_get_membership_plan( $membership_plan_id ) ) {

							$plan_query_args = $team_query_args;

							$plan_query_args['post_parent'] = $plan->get_id();

							$found_team_ids_by_s = array_unique( array_merge( $found_team_ids_by_s, get_posts( $plan_query_args ) ) );
						}
					}

				// allow search by member email
				} elseif ( is_email( $query_args['s'] ) ) {

					$user = get_user_by( 'email', $query_args['s'] );

					if ( $user instanceof \WP_User ) {
						$found_team_ids_by_s = $this->get_teams_ids_by_user( $user, $team_query_args );
					}

				// if it's a string, it may be a user name or a plan slug
				} elseif ( is_string( $query_args['s'] ) ) {

					if ( $plan = wc_memberships_get_membership_plan( $query_args['s'] ) ) {

						$query_args['post_parent'] = $plan->get_id();

						// we remove the search string otherwise it will disrupt results
						unset( $query_args['s'], $_REQUEST['s'] );

					} elseif ( $user = get_user_by( 'login', $query_args['s'] ) ) {

						$found_team_ids_by_s = $this->get_teams_ids_by_user( $user, $team_query_args );

						// we remove the search string as it identified a user already
						unset( $query_args['s'], $_REQUEST['s'] );
					}
				}

				if ( ! empty( $found_team_ids_by_s ) ) {

					$found_team_ids = get_posts( array_merge( $query_args, array( 'fields' => 'ids' ) ) );

					$query_args['post__in'] = array_unique( array_merge( $found_team_ids, $found_team_ids_by_s ) );

					// since we have meta results already, we remove the query string to avoid disrupting the results
					unset( $query_args['s'], $_REQUEST['s'] );
				}
			}
		}

		return $query_args;
	}


	/**
	 * Returns team IDs by owner user or members within a team, searching from a user object.
	 *
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Admin\Teams::request_query()
	 *
	 * @since 1.0.4
	 *
	 * @param \WP_User $user user object
	 * @param array $query_args associative array
	 * @return int[] array of post IDs
	 */
	private function get_teams_ids_by_user( \WP_User $user, array $query_args ) {

		$user_memberships = wc_memberships_get_user_memberships( $user );

		// maybe $user it's one of the team members
		if ( ! empty( $user_memberships ) ) {

			$meta_query_args = $query_args;

			$meta_query_args['meta_query'] = array( 'relation' => 'OR' );

			foreach ( $user_memberships as $user_membership ) {

				$meta_query_args['meta_query'][] = array(
					'key'     => '_member_id',
					'value'   => $user_membership->get_user_id(),
					'compare' => 'LIKE',
				);
			}

			$found_ids = get_posts( $meta_query_args );

		// maybe $user it's the team owner
		} else {

			$query_args['post_author'] = $user->ID;

			$found_ids = get_posts( $query_args );
		}

		return $found_ids;
	}


	/**
	 * Outputs the team nonce field in team edit screen.
	 *
	 * @since 1.0.0
	 * @param \WP_Post $post the post object
	 */
	public function team_nonce( \WP_Post $post ) {

		if ( ! is_object( $post ) || 'wc_memberships_team' !== $post->post_type ) {
			return;
		}

		wp_nonce_field( 'wc_memberships_team_save_data', 'wc_memberships_team_meta_nonce' );
	}


	/**
	 * Processes and saves team data.
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

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_boxes ) {
			return;
		}

		// don't save meta boxes for revisions or autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// check the nonce
		if ( empty( $_POST['wc_memberships_team_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wc_memberships_team_meta_nonce'], 'wc_memberships_team_save_data' ) ) {
			return;
		}

		// check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// we need this save event to run once to avoid potential endless loops
		$this->saved_meta_boxes = true;

		/**
		 * Fires when a team is saved/updated from admin.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id post identifier
		 * @param \WP_Post $post the post object
		 */
		do_action( 'wc_memberships_for_teams_process_team_meta', $post_id, $post );
	}


	/**
	 * Loads the team before rendering any content on the edit screen.
	 *
	 * Provides a single, globally available instance of the current team being edited.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function load_team() {
		global $post, $team, $typenow, $pagenow;

		if ( 'wc_memberships_team' !== $typenow ) {
			return;
		}

		// load the team instance and make it available globally
		$team = wc_memberships_for_teams_get_team( $post );

		// set data for a new auto-draft team
		if ( 'post-new.php' === $pagenow ) {

			$plans = wc_memberships()->get_plans_instance()->get_available_membership_plans();

			// TODO: possibly handle this in a more graceful manner, especially if/when adding multiple plan support {IT 2017-09-18}
			if ( empty( $plans ) ) {

				wc_memberships_for_teams()->get_admin_instance()->get_message_handler()->add_error( sprintf( __( 'Cannot add team: no membership plans available. %1$sClick here%2$s to add a membership plan.', 'woocommerce-memberships-for-teams' ), '<a href="' . esc_url( admin_url( 'post-new.php?post_type=wc_membership_plan' ) ) . '">', '</a>' ) );
				wp_redirect( wp_get_referer() ); exit;
			}

			// generate registration key
			$post->post_password = Plugin::generate_token();

			// set plan id to the first available plan, to avoid fatals when adding members to draft teams
			$plan_ids          = array_keys( $plans );
			$post->post_parent = $plan_ids[0];

			wp_update_post( array(
				'ID'            => $post->ID,
				'post_password' => $post->post_password,
				'post_parent'   => $post->post_parent,
			) );
		}
	}


	/**
	 * Adds hidden controls at the end the edit team screen.
	 *
	 * The extra action field is required so that the team members list table bulk action field does not override
	 * team's own action.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_hidden_controls() {
		?><input type="hidden" name="action" value="editpost"><?php
	}


	/**
	 * Returns the sendback (redirect) URL for team members bulk actions.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id team post id
	 * @return string
	 */
	private function get_members_bulk_action_sendback_url( $post_id ) {

		$sendback = wp_get_referer();

		if ( ! $sendback ) {
			$sendback = admin_url( 'post.php' );
			$sendback = add_query_arg( 'post', $post_id, $sendback );
			$sendback = add_query_arg( 'action', 'edit', $sendback );
		}

		return $sendback;
	}


	/**
	 * Handles team members list table bulk actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_member_bulk_actions() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'team-bulk-edit-members' );

		$action = str_replace( 'admin_action_', '', current_action() );
		$team   = wc_memberships_for_teams_get_team( $id );
		$users  = ! empty( $_REQUEST['users'] ) ? (array) $_REQUEST['users'] : array();

		if ( empty( $users ) ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		switch ( $action ) {

			case 'bulk_remove_members':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$team->remove_member( $user_id );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				/* translators: Placeholder: %d - a number */
				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d member was removed from the team.', '%d members were removed from the team', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;

			case 'bulk_set_as_members':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$member->set_role( 'member' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				/* translators: Placeholder: %d - a number */
				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set as a member of the team.', '%d users were set as members of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;

			case 'bulk_set_as_managers':

				$num = 0;

				foreach ( $users as $user_id ) {

					try {
						$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
						$member->set_role( 'manager' );
						$num++;
					} catch ( Framework\SV_WC_Plugin_Exception $e ) {}
				}

				/* translators: Placeholder: %d - a number */
				wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( _n( '%d user was set as a manager of the team.', '%d users were set as managers of the team.', $num, 'woocommerce-memberships-for-teams' ), $num ) );

			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


	/**
	 * Handles individual member actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_member_action() {

		if ( empty( $_REQUEST['post'] ) ) {
			return;
		}

		// get the post
		$id      = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';
		$team    = wc_memberships_for_teams_get_team( $id );
		$user_id = ! empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;

		if ( ! $user_id || ! $team ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		$action       = str_replace( 'admin_action_', '', current_action() );
		$nonce_action = 'team-' . str_replace( '_', '-', $action ) . '-' . $user_id;

		check_admin_referer( $nonce_action );

		if ( 'add_member' === $action ) {
			$user   = is_numeric( $user_id ) ? get_userdata( $user_id ) : get_user_by( $user_id, 'email' );
			$name   = $user->display_name;
			$member = null;
		} else {
			$member = wc_memberships_for_teams_get_team_member( $team, $user_id );
			$name   = $member ? $member->get_name() : '';
		}

		switch ( $action ) {

			case 'add_member':

				try {

					$team->add_member( $user_id );

					/* translators: Placeholder: %s - user's name */
					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was added to team as a member.', 'woocommerce-memberships-for-teams' ), $name ) );

				} catch ( \Exception $e ) {

					/* translators: Placeholder: %s - error message */
					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot add member: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
				}

			break;

			case 'remove_member':

				try {

					$team->remove_member( $user_id );

					/* translators: Placeholder: %s - user's name */
					$message = is_numeric( $user_id ) ? __( '%s was removed from the team.', 'woocommerce-memberships-for-teams' ) : __( 'Invitation for %s was cancelled.', 'woocommerce-memberships-for-teams' );

					wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( $message, $name ) );

				} catch ( Framework\SV_WC_Plugin_Exception $e ) {

					/* translators: Placeholders: %1$s - user name, %2$s - error message */
					$message = is_numeric( $user_id ) ? __( 'Cannot remove %1$s: %2$s', 'woocommerce-memberships-for-teams' ) : __( 'Cannot cancel invitation for %s: %2$s', 'woocommerce-memberships-for-teams' );

					wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( $message, $e->getMessage() ) );
				}

			break;

			case 'set_as_member':

				if ( $member ) {

					try {

						$member->set_role( 'member' );

						/* translators: Placeholder: %s - user's name */
						wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set as a member of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

					} catch ( Framework\SV_WC_Plugin_Exception $e ) {

						/* translators: Placeholder: %s - error message */
						wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
					}
				}

			break;

			case 'set_as_manager':

				if ( $member ) {

					try {

						$member->set_role( 'manager' );

						/* translators: Placeholder: %s - user's name */
						wc_memberships_for_teams()->get_message_handler()->add_message( sprintf( __( '%s was set as a manager of the team.', 'woocommerce-memberships-for-teams' ), $name ) );

					} catch ( Framework\SV_WC_Plugin_Exception $e ) {

						/* translators: Placeholder: %s - error message */
						wc_memberships_for_teams()->get_message_handler()->add_error( sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() ) );
					}
				}

			break;
		}

		wp_redirect( wp_get_referer() );
		exit;
	}


	/**
	 * Customizes meta boxes on the team edit screen
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function customize_meta_boxes() {

		// remove the built-in submit box div
		remove_meta_box( 'submitdiv', 'wc_memberships_team', 'side' );
	}


	/**
	 * Disables the auto-save functionality for Teams.
	 *
	 * This prevents the JS confirmation from appearing when saving a team or navigating away from the edit team screen.
	 * Since autosave only tracks the team name (post title) changes, it is of little us for us anyway.
	 *
	 * @internal
	 *
	 * @since 1.0.1
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && 'wc_memberships_team' === get_post_type( $post->ID ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}
}
