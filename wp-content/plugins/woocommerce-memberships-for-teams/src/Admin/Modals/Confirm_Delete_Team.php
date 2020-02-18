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

namespace SkyVerge\WooCommerce\Memberships\Teams\Admin\Modals;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Delete Team confirmation modal.
 *
 * @since 1.0.0
 */
class Confirm_Delete_Team extends \WC_Memberships_Modal {


	/**
	 * Constructs the modal.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->id           = 'wc-memberships-for-teams-modal-confirm-delete-team';
		$this->title        = __( 'Delete Team', 'woocommerce-memberships-for-teams' );
		$this->button_label = __( 'Delete Team', 'woocommerce-memberships-for-teams' );
	}


	/**
	 * Returns the modal body template.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	protected function get_template_body() {
		ob_start();

		?>

		<article>

			<form method="GET" action="{{{data.url}}}">

				<p>
					<?php esc_html_e( 'Are you sure you want to delete this team?', 'woocommerce-memberships-for-teams' ); ?>
					<?php esc_html_e( 'This will also delete any associated user memberships. You can opt to keep the user memberships by unlinking them from the team.', 'woocommerce-memberships-for-teams' ); ?>
				</p>

				<label>
					<input type="radio" value="0" name="keep_user_memberships" checked />
					<?php esc_html_e( 'Delete user memberships', 'woocommerce-memberships-for-teams' ); ?>
				</label>
				<br>
				<label>
					<input type="radio" value="1" name="keep_user_memberships" />
					<?php esc_html_e( 'Keep user memberships and make them standalone', 'woocommerce-memberships-for-teams' ); ?>
				</label>

			</form>

		</article>

		<?php

		return ob_get_clean();
	}


}
