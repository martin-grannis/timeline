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
 * Admin Settings class
 *
 * @since 1.0.0
 */
class Settings {


	/**
	 * Sets up the settings class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'woocommerce_get_sections_memberships', array( $this, 'add_teams_section'  ) );
		add_filter( 'woocommerce_get_settings_memberships', array( $this, 'add_teams_settings' ), 10, 2 );
	}


	/**
	 * Adds Teams section to Memberships settings screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $sections associative array of sections
	 * @return array
	 */
	public function add_teams_section( $sections ) {

		$sections['teams'] = __( 'Teams', 'woocommerce-memberships-for-teams' );

		return $sections;
	}


	/**
	 * Adds Teams settings to Memberships settings screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings array of the plugin settings
	 * @param string $current_section the current section being output
	 * @return array
	 */
	public function add_teams_settings( $settings, $current_section ) {

		if ( 'teams' === $current_section ) {

			/**
			 * Filters Memberships for Teams settings.
			 *
			 * @since 1.0.0
			 *
			 * @param array $settings array of teams settings
			 */
			$settings = (array) apply_filters( 'wc_memberships_for_teams_settings', array(

				array(
					'name'     => __( 'Teams', 'woocommerce-memberships-for-teams' ),
					'type'     => 'title',
				),

				array(
					'type'     => 'checkbox',
					'id'       => 'wc_memberships_for_teams_allow_removing_members',
					'name'     => __( 'Allow removing members', 'woocommerce-memberships-for-teams' ),
					'desc'     => __( 'If enabled, team owners and managers can remove members from their team.', 'woocommerce-memberships-for-teams' ),
					'default'  => 'yes',
				),

				array(
					'type'     => 'checkbox',
					'id'       => 'wc_memberships_for_teams_owners_must_take_seat',
					'name'     => __( 'Owners must be members', 'woocommerce-memberships-for-teams' ),
					'desc'     => __( 'If enabled, team owners must take up a seat in their team.', 'woocommerce-memberships-for-teams' ),
					'default'  => 'no',
				),

				array(
					'type'     => 'checkbox',
					'id'       => 'wc_memberships_for_teams_managers_may_manage_managers',
					'name'     => __( 'Allow managers to add or remove other managers', 'woocommerce-memberships-for-teams' ),
					'desc'     => __( 'If enabled, team managers can add/remove other managers. Otherwise, only a team owner may add or remove managers.', 'woocommerce-memberships-for-teams' ),
					'default'  => 'yes',
				),

				array(
					'type'     => 'sectionend',
				),

			) );
		}

		return $settings;

	}
}
