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

namespace SkyVerge\WooCommerce\Memberships\Teams;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

defined( 'ABSPATH' ) or exit;

class Utilities {


	/**
	 * Sets up utilities.
	 *
	 * @since 1.1.2
	 */
	public function __construct() {

		// add team data to CSV exports
		add_filter( 'wc_memberships_csv_export_user_memberships_headers',          array( $this, 'add_user_memberships_csv_export_team_headers' ) );
		add_filter( 'wc_memberships_csv_export_user_memberships_team_id_column',   array( $this, 'export_user_memberships_csv_team_data' ), 11, 3 );
		add_filter( 'wc_memberships_csv_export_user_memberships_team_slug_column', array( $this, 'export_user_memberships_csv_team_data' ), 11, 3 );
		add_filter( 'wc_memberships_csv_export_user_memberships_team_name_column', array( $this, 'export_user_memberships_csv_team_data' ), 11, 3 );
		add_filter( 'wc_memberships_csv_export_user_memberships_team_role_column', array( $this, 'export_user_memberships_csv_team_data' ), 11, 3 );
	}


	/**
	 * Adds CSV export headers for teams.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 *
	 * @param array $headers associative array
	 * @return array
	 */
	public function add_user_memberships_csv_export_team_headers( $headers ) {

		$teams_headers = array(
			'team_id'   => 'team_id',
			'team_slug' => 'team_slug',
			'team_name' => 'team_name',
			'team_role' => 'team_role',
		);

		if ( isset( $headers['order_id'] ) ) {
			$headers = Framework\SV_WC_Helper::array_insert_after( $headers, 'order_id', $teams_headers );
		} else {
			$headers = array_merge( $headers, $teams_headers );
		}

		return $headers;
	}


	/**
	 * Exports team data in user memberships CSV exports.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 *
	 * @param string|int $value default value
	 * @param string $column_name the processed column name
	 * @param \WC_Memberships_User_Membership $user_membership
	 * @return string
	 */
	public function export_user_memberships_csv_team_data( $value, $column_name, $user_membership ) {

		if ( $team = wc_memberships_for_teams_get_user_membership_team( $user_membership ) ) {

			switch ( $column_name ) {

				case 'team_id' :
					$value = $team->get_id();
				break;

				case 'team_slug' :
					$value = $team->get_slug();
				break;

				case 'team_name' :
					$value = $team->get_name();
				break;

				case 'team_role' :
					if ( $member = wc_memberships_for_teams_get_team_member( $team->get_id(), $user_membership->get_user_id() ) ) {
						$value = $member->get_role();
					}
				break;
			}
		}

		return (string) $value;
	}


}
