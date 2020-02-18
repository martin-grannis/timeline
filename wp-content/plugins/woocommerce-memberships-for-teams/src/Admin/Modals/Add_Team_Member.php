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
 * Add Team Member modal class.
 *
 * @since 1.0.0
 */
class Add_Team_Member extends \WC_Memberships_Member_Modal {


	/**
	 * Constructs the modal.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct();

		$this->id           = 'wc-memberships-for-teams-modal-add-team-member';
		$this->title        = __( 'Add Team Member', 'woocommerce-memberships-for-teams' );
		$this->button_label = __( 'Add Team Member', 'woocommerce-memberships-for-teams' );
	}


	/**
	 * Returns the modal description.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_description() {
		return __( 'Search for an existing user or create a new one to add as a member to the team.', 'woocommerce-memberships-for-teams' );
	}
}
