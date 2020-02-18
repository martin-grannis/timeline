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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin\List_Tables;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Admin Teams class
 *
 * @since 1.0.0
 */
class Invitations extends \WP_List_Table {


	/** @var @var \SkyVerge\WooCommerce\Memberships\Teams\Team current team instance */
	protected $team;


	/**
	 * Constructs the list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		global $team;

		parent::__construct( array(
			'singular' => 'invitation',
			'plural'   => 'invitations',
			'ajax'     => false
		) );

		if ( isset( $args['team'] ) ) {
			$this->team = $args['team'];
		} else {
			$this->team = $team;
		}
	}


	/**
	 * Sets column titles.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array of column ids and labels
	 */
	public function get_columns() {

		$columns = array(
			'cb'    => '<input type="checkbox" />',
			'name'  => esc_html__( 'Name', 'woocommerce-memberships-for-teams' ),
			'email' => esc_html__( 'Email', 'woocommerce-memberships-for-teams' ),
			'role'  => esc_html__( 'Team Role', 'woocommerce-memberships-for-teams' ),
			'date'  => esc_html__( 'Invited On', 'woocommerce-memberships-for-teams' ),
		);

		return $columns;
	}


	/**
	 * Returns column content.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation invitation instance
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $invitation, $column_name ) {

		switch ( $column_name ) {

			case 'email':

				return $invitation->get_email();

			case 'role':

				return $invitation->get_member_role( 'label' );

			case 'date':

				$added_time = $invitation->get_date( 'timestamp' );

				if ( ! $added_time ) {
					return __( 'N/A', 'woocommerce-memberships-for-teams' );
				}

				$date_format = wc_date_format();
				$time_format = wc_time_format();

				$date = esc_html( date_i18n( $date_format, (int) $added_time ) );
				$time = esc_html( date_i18n( $time_format, (int) $added_time ) );

				return sprintf( '%1$s %2$s', $date, $time );

			default :

				return '';
		}
	}


	/**
	 * Handles the checkbox column output.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation invitation instance
	 */
	public function column_cb( $invitation ) {

		if ( current_user_can( 'manage_woocommerce' ) ) {

			$id = $invitation->get_id();

			?>
			<label class="screen-reader-text" for="cb-select-<?php echo sanitize_html_class( $id ); ?>"><?php esc_html_e( 'Select invitation', 'woocommerce-memberships-for-teams' ); ?></label>
			<input id="cb-select-<?php echo sanitize_html_class( $id ); ?>" type="checkbox" name="invitations[]" value="<?php echo esc_attr( $id ); ?>" />
			<div class="locked-indicator"></div>
			<?php
		}
	}


	/**
	 * Handles the member name column output.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation invitation instance
	 * @param string $classes
	 * @param string $data
	 * @param string $primary
	 */
	protected function _column_name( $invitation, $classes, $data, $primary ) {

		echo '<td class="' . $classes . ' member-name ', $data, '>';
		echo '<strong>';
		echo $invitation->get_name();
		echo '</strong>';
		echo $this->handle_row_actions( $invitation, 'name', $primary );
		echo '</td>';
	}


	/**
	 * Generates and displays row action links.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation invitation instance being acted upon
	 * @param string $column_name current column name
	 * @param string $primary primary column name
	 * @return string row actions output for members
	 */
	protected function handle_row_actions( $invitation, $column_name, $primary ) {

		if ( $primary !== $column_name || ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}

		$actions = array();
		$role    = $invitation->get_member_role();
		$name    = $invitation->get_name();

		if ( $invitation->has_status( 'pending' ) ) {

			if ( 'member' === $role ) {
				$actions['set_as_manager'] = sprintf(
					'<a href="%s" class="set-as-manager" aria-label="%s">%s</a>',
					add_query_arg( array(
						'action'     => 'team_invitation_set_as_manager',
						'invitation' => $invitation->get_id(),
						'_wpnonce'   => wp_create_nonce( 'team-set-as-manager-' . $invitation->get_id() ),
					), $this->team->get_edit_url() ),
					/* translators: Placeholder: %s - user's name */
					esc_attr( sprintf( __( 'Set &#8220;%s&#8221; as a manager of this team', 'woocommerce-memberships-for-teams' ), $name ) ),
					__( 'Set as manager', 'woocommerce-memberships-for-teams' )
				);
			}
			elseif ( 'manager' === $role ) {
				$actions['set_as_member'] = sprintf(
					'<a href="%s" class="set-as-member" aria-label="%s">%s</a>',
					add_query_arg( array(
						'action'     => 'team_invitation_set_as_member',
						'invitation' => $invitation->get_id(),
						'_wpnonce'   => wp_create_nonce( 'team-set-as-member-' . $invitation->get_id() ),
					), $this->team->get_edit_url() ),
					/* translators: Placeholder: %s - user's name */
					esc_attr( sprintf( __( 'Set &#8220;%s&#8221; as a member of this team', 'woocommerce-memberships-for-teams' ), $name ) ),
					__( 'Set as member', 'woocommerce-memberships-for-teams' )
				);
			}

			$actions['resend'] = sprintf(
				'<a href="%s" class="resend" aria-label="%s">%s</a>',
					add_query_arg( array(
						'action'     => 'team_invitation_resend',
						'invitation' => $invitation->get_id(),
						'_wpnonce'   => wp_create_nonce( 'team-resend-' . $invitation->get_id() ),
					), $this->team->get_edit_url() ),
				/* translators: Placeholder: %s - user's name */
				esc_attr( sprintf( __( 'Resend invitation to &#8220;%s&#8221;', 'woocommerce-memberships-for-teams' ), $name ) ),
				__( 'Resend', 'woocommerce-memberships-for-teams' )
			);

			$actions['cancel'] = sprintf(
				'<a href="%s" class="cancel" aria-label="%s">%s</a>',
					add_query_arg( array(
						'action'     => 'team_invitation_cancel',
						'invitation' => $invitation->get_id(),
						'_wpnonce'   => wp_create_nonce( 'team-cancel-' . $invitation->get_id() ),
					), $this->team->get_edit_url() ),
				/* translators: Placeholder: %s - user's name */
				esc_attr( sprintf( __( 'Cancel invitation for &#8220;%s&#8221; to join this team', 'woocommerce-memberships-for-teams' ), $name ) ),
				__( 'Cancel', 'woocommerce-memberships-for-teams' )
			);

		}

		/**
		 * Filters the invitation row actions.
		 *
		 * @since 1.0.0
		 *
		 * @param array $actions associative array of actions and HTML link elements
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation invitation instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
		 */
		$actions = apply_filters( 'wc_memberships_for_teams_team_invitation_row_actions', $actions, $invitation, $this->team );

		return $this->row_actions( $actions );
	}


	/**
	 * Generates the table navigation above or below the table.
	 *
	 * This is a duplicate of WP_List_Table::display_tablenav, with the nonce field removed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php endif; ?>

			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
		<?php
	}


	/**
	 * Generates the list table filters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which either top or bottom
	 */
	protected function extra_tablenav( $which ) {
		?>
			<div class="alignleft actions">
			<?php
				if ( 'top' === $which ) {
					ob_start();

					$this->roles_dropdown();

					/**
					 * Fires before the Filter button on the team invitations list table.
					 *
					 * @since 1.0.0
					 *
					 * @param string $which the location of the extra table nav markup: 'top' or 'bottom'
					 */
					do_action( 'wc_memberships_for_teams_restrict_manage_team_invitations', $which );

					$output = ob_get_clean();

					if ( ! empty( $output ) ) {
						echo $output;
						submit_button( __( 'Filter', 'woocommerce-memberships-for-teams' ), '', 'filter_action', false, array( 'id' => 'team-member-query-submit' ) );
					}
				}
			?>
			</div>

		<?php
		/**
		 * Fires immediately following the closing "actions" div in the tablenav for the team invitations list table.
		 *
		 * @since 1.0.0
		 *
		 * @param string $which the location of the extra table nav markup: 'top' or 'bottom'
		 */
		do_action( 'wc_memberships_for_teams_team_invitations_extra_tablenav', $which );
	}


	/**
	 * Outputs the member roles filter dropdown.
	 *
	 * @since 1.0.0
	 */
	private function roles_dropdown() {

		$roles         = wc_memberships_for_teams_get_team_member_roles();
		$selected_role = isset( $_GET['team_member_role'] ) ? $_GET['team_member_role'] : '';

		?>
		<label for="filter-by-team-member-role" class="screen-reader-text"><?php esc_html_e( 'Filter by team member role', 'woocmmerce-memberships-for-teams'); ?></label>
		<select name="team_member_role" id="filter-by-team-member-role">
			<option value=""><?php echo esc_html_e( 'All team roles', 'woocommeerce-memberships-for-teams' ); ?></option>
			<?php foreach ( $roles as $role => $label ) : ?>
				<option value="<?php echo esc_attr( $role ); ?>" <?php selected( $selected_role, $role ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}


	/**
	 * Prepares team members for display.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		$team_id = $this->team->get_id();

		// set column headers manually, see https://codex.wordpress.org/Class_Reference/WP_List_Table#Extended_Properties
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$search    = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ): '';
		$post_type = 'wc_team_invitation';
		$per_page  = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );

		/** This filter is documented in wp-admin/includes/post.php */
		$per_page = apply_filters( 'edit_posts_per_page', $per_page, $post_type );
		$paged    = $this->get_pagenum();

		$args = array(
			'paged'    => $paged,
			'per_page' => $per_page,
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$args['order'] = $_REQUEST['order'];
		}

		// handle role filter
		if ( ! empty( $_REQUEST['team_member_role'] ) ) {
			$args['role'] = $_REQUEST['team_member_role'];
		}

		/**
		 * Filters the query arguments used to retrieve invitations in the team invitations list table.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args arguments
		 */
		$args = apply_filters( 'wc_memberships_for_teams_invitations_list_table_query_args', $args );

		$results = $this->team->get_invitations( $args, 'query' );

		$this->items = $results['invitations'];

		$this->set_pagination_args( array(
			'total_items' => $results['total'],
			'per_page'    => $per_page,
		) );
	}


	/**
	 * Outputs the HTML to display when there are no invitations for the team.
	 *
	 * @see \WP_List_Table::no_items()
	 * @since 1.0.0
	 */
	public function no_items() {
		global $post;

		$message = ( 'auto-draft' === $post->post_status ) ? __( 'Please save the team first to add members.', 'woocommerce-memberships-for-teams' ) : __( 'No invitations found.', 'woocommerce-memberships-for-teams' );

		echo esc_html( $message );
	}


	/**
	 * Returns an associative array ( id => link ) with the list of views available on this table.
	 *
	 * Note that the invitations view will actually load a different list table instance.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_views() {

		$url   = $this->get_base_url();
		$views = \SkyVerge\WooCommerce\Memberships\Teams\Team_Members::get_table_views( $this->team, $url );

		return $views;
	}


	/**
	 * Returns the base url for the team edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @return string url
	 */
	protected function get_base_url() {

		return add_query_arg( array(
			'post'   => $this->team->get_id(),
			'action' => 'edit',
		), admin_url( 'post.php' ) );
	}


	/**
	 * Returns the list of bulk actions available on this table.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array ( option_name => option_title )
	 */
	protected function get_bulk_actions() {

		return array(
			'team_invitations_bulk_resend'          => esc_html__( 'Resend', 'woocommerce-memberships-for-teams' ),
			'team_invitations_bulk_set_as_members'  => esc_html__( 'Set as member', 'woocommerce-memberships-for-teams' ),
			'team_invitations_bulk_set_as_managers' => esc_html__( 'Set as manager', 'woocommerce-memberships-for-teams' ),
			'team_invitations_bulk_cancel'          => esc_html__( 'Cancel', 'woocommerce-memberships-for-teams' ),
		);
	}


}
