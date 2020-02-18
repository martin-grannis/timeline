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
 * Team Members meta box class.
 *
 * @since 1.0.0
 */
class Team_Members {


	/**
	 * Constructs the meta box.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 30 );
	}

	/**
	 * Adds the meta box
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		add_meta_box( 'wc-memberships-for-teams-team-members', __( 'Team Members', 'woocommerce-memberships-for-teams' ), array( $this, 'output' ), 'wc_memberships_team', 'normal' );
	}


	/**
	 * Outputs meta box contents.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output() {
		global $team, $post;

		$show_invitations = ! empty( $_REQUEST['show_invitations'] );
		$list_table_class = $show_invitations ? 'Invitations' : 'Team_Members';
		$list_table_class = '\\SkyVerge\\WooCommerce\\Memberships\\Teams\\Admin\\List_Tables\\' . $list_table_class;
		/* @type \WP_List_Table $list_table */
		$list_table       = new $list_table_class( array( 'team' => $team ) );
		$search_label     = $show_invitations ? __( 'Search Invitations', 'woocommerce-memberships-for-teams' ) : __( 'Search Members', 'woocommerce-memberships-for-teams' );

		$list_table->prepare_items();
		?>

		<?php if ( ! $team->can_be_managed() ) : ?>
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Team management is disabled:', 'woocommerce-memberships-for-teams' ); ?></strong>
				<?php echo esc_html( $team->get_management_decline_reason() ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( 'auto-draft' !== $post->post_status ) : ?>
			<a href="#" class="button js-add-team-member"><?php esc_html_e( 'Add Team Member', 'woocommerce-memberships-for-teams' ); ?></a>
			<br>
		<?php endif; ?>

		<?php $list_table->views(); ?>

		<div id="members-filter">

		<?php $list_table->search_box( $search_label, 'member' ); ?>

		<?php $list_table->display(); ?>

		</div>
		<?php
	}

}
