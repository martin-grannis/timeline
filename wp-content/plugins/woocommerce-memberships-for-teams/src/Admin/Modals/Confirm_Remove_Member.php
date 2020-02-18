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
 * Remove Member confirmation modal.
 *
 * @since 1.0.0
 */
class Confirm_Remove_Member extends \WC_Memberships_Modal {


	/**
	 * Constructs the modal.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->id = 'wc-memberships-for-teams-modal-confirm-remove-member';
	}


	/**
	 * Returns the modal header template.
	 *
	 * By default, this will display the modal's title and a close button.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	protected function get_template_header() {

		ob_start();

		?>
		<header class="wc-backbone-modal-header">
			<h1>
			<# if ( data.bulk_action ) { #>
			<?php esc_html_e( 'Remove Members', 'woocommerce-memberships-for-teams' ); ?>
			<# } else { #>
			<?php esc_html_e( 'Remove Member', 'woocommerce-memberships-for-teams' ); ?>
			<# } #>
			</h1>
			<button class="modal-close modal-close-link dashicons dashicons-no-alt">
				<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-memberships-for-teams' ); ?></span>
			</button>
		</header>
		<?php

		return ob_get_clean();
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
					<# if ( data.bulk_action ) { #>
					<?php esc_html_e( 'Are you sure you want to remove these members?', 'woocommerce-memberships-for-teams' ); ?>
					<# } else { #>
					<?php esc_html_e( 'Are you sure you want to remove this member?', 'woocommerce-memberships-for-teams' ); ?>
					<# } #>
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


	/**
	 * Returns the modal footer template.
	 *
	 * By default, this will be the area for the modal action buttons.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML
	 */
	protected function get_template_footer() {

		ob_start();

		?>
		<footer>
			<div class="inner">
				<button id="btn-ok" class="button button-large <?php echo sanitize_html_class( $this->button_class ); ?>">
					<# if ( data.bulk_action ) { #>
					<?php esc_html_e( 'Remove Members', 'woocommerce-memberships-for-teams' ); ?>
					<# } else { #>
					<?php esc_html_e( 'Remove Member', 'woocommerce-memberships-for-teams' ); ?>
					<# } #>
				</button>
			</div>
		</footer>
		<?php

		return ob_get_clean();
	}


}
