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

/**
 * Admin class.
 *
 * @since 1.0.0
 */
class Admin {


	/** @var \SV_WP_Admin_Message_Handler instance */
	public $message_handler; // this is passed from \WC_Memberships_For_Teams and can't be protected

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Settings the settings class */
	private $settings;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Products the products admin handler */
	private $products;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Teams the teams admin handler */
	private $teams;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Team_Members the team members admin handler */
	private $team_members;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Invitations the invitations admin handler */
	private $invitations;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\User_Memberships the user memberships admin handler */
	private $user_memberships;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Admin\Membership_Plans the membership plans admin handler */
	private $membership_plans;

	/** @var \stdClass container of meta box classes instances */
	private $meta_boxes;


	/**
	 * Sets up the Admin class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_scripts_styles' ), 15 );

		// highlight WC > Memberships menu items when on Teams screens
		add_filter( 'parent_file',  array( $this, 'modify_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'modify_submenu_file' ) );

		// add teams admin tabs to Memberships screens
		add_filter( 'wc_memberships_admin_tabs', array( $this, 'add_admin_tabs' ) );

		// set current tab for Memberships admin pages
		add_filter( 'wc_memberships_admin_current_tab', array( $this, 'set_current_tab' ) );

		add_filter( 'wc_memberships_admin_screen_ids', array( $this, 'add_team_screen_ids' ) );

		add_action( 'current_screen', array( $this, 'load_meta_boxes' ) );

		add_filter( 'wc_memberships_modals', array( $this, 'load_modals' ), 10, 2 );

		// display admin messages
		add_action( 'admin_notices', array( $this, 'show_admin_messages' ) );

		// set the endpoint slug for Teams Area in My Account
		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4' ) ) {
			add_filter( 'woocommerce_settings_pages', array( $this, 'add_my_account_endpoints_options' ) );
		} else {
			add_filter( 'woocommerce_account_settings', array( $this, 'add_my_account_endpoints_options' ) );
		}

		$this->settings         = new Admin\Settings;
		$this->teams            = new Admin\Teams;
		$this->team_members     = new Admin\Team_Members;
		$this->invitations      = new Admin\Invitations;
		$this->products         = new Admin\Products;
		$this->user_memberships = new Admin\User_Memberships;
		$this->membership_plans = new Admin\Membership_Plans;
	}


	/**
	 * Returns the Message Handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \SV_WP_Admin_Message_Handler
	 */
	public function get_message_handler() {

		// note: this property is public since it needs to be passed from the main class
		return $this->message_handler;
	}


	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 */
	public function enqueue_scripts_styles() {

		$screen = get_current_screen();

		if ( $screen && in_array( $screen->id, $this->get_screen_ids( 'scripts' ), true ) ) {

			$this->enqueue_scripts( $screen );
			$this->enqueue_styles( $screen );
		}
	}


	/**
	 * Enqueues admin styles.
	 *
	 * @since 1.1.2
	 *
	 * @param \WP_Screen $screen current screen
	 */
	private function enqueue_styles( $screen ) {

		if ( 'wc_memberships_team' === $screen->id ) {

			wp_enqueue_style( 'wc-memberships-for-teams-team-admin', wc_memberships_for_teams()->get_plugin_url() . '/assets/css/admin/wc-memberships-for-teams-team-admin.min.css', array(), Plugin::VERSION );

		} elseif ( 'wc_user_membership' === $screen->id )  {

			wp_enqueue_style( 'wc-memberships-for-teams-user-memberships-admin', wc_memberships_for_teams()->get_plugin_url() . '/assets/css/admin/wc-memberships-for-teams-user-memberships-admin.min.css', array(), Plugin::VERSION );
		}
	}


	/**
	 * Enqueues admin scripts.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Screen $screen current screen
	 */
	private function enqueue_scripts( $screen ) {

		$deps = array( 'jquery' );

		wp_register_script( 'wc-memberships-for-teams-membership-teams', wc_memberships_for_teams()->get_plugin_url() . '/assets/js/admin/wc-memberships-for-teams-membership-teams.min.js', $deps, Plugin::VERSION );

		if ( in_array( $screen->id, array( 'wc_memberships_team', 'edit-wc_memberships_team' ), true ) ) {

			if ( 'wc_memberships_team' === $screen->id ) {
				$deps[] = 'jquery-ui-datepicker';
			}

			$deps[] = 'wc-memberships-modals';
			$deps[] = 'wc-memberships-for-teams-membership-teams';
		}

		wp_enqueue_script( 'wc-memberships-for-teams-admin', wc_memberships_for_teams()->get_plugin_url() . '/assets/js/admin/wc-memberships-for-teams-admin.min.js', $deps, Plugin::VERSION );
		wp_localize_script( 'wc-memberships-for-teams-admin', 'wc_memberships_for_teams_admin', array(

			'ajax_url'                             => admin_url( 'admin-ajax.php' ),
			'post_url'                             => admin_url( 'post.php' ),
			'currency_symbol'                      => get_woocommerce_currency_symbol(),
			'bulk_edit_team_members_nonce'         => wp_create_nonce( 'team-bulk-edit-members' ),
			'add_team_member_nonce'                => wp_create_nonce( 'team-add-member' ),
			'get_existing_user_membership_nonce'   => wp_create_nonce( 'get-existing-user-membership-id' ),

			'i18n'                                 => array(

				'confirm_user_membership_move'          => __( "This user is already a member of the team's plan, either individually or having access from another team. Adding this user will move their existing user membership under this team's management. Do you want to continue?", 'woocommerce-memberships-for-teams' ),
				'confirm_user_membership_edit_override' => __( 'Editing a membership that is part of a team may cause unintended consequences. Please confirm to enable editing this membership.', 'woocommerce-memberships-for-teams' ),
				'confirm_change_team_owner'             => __( 'You are changing the team owner. Note that this does not affect team owner user membership, billing or subscriptions - these changes need to be made manually. Do you want to continue?', 'woocommerce-memberships-for-teams' ),
				'per_member_regular_price'              => __( 'Per-member price', 'woocommerce-memberships-for-teams' ),
				'per_member_sale_price'                 => __( 'Per-member sale price', 'woocommerce-memberships-for-teams' ),
				'per_team_regular_price'                => __( 'Per-team price', 'woocommerce-memberships-for-teams' ),
				'per_team_sale_price'                   => __( 'Per-team sale price', 'woocommerce-memberships-for-teams' ),
				'per_member_subscription_price'         => __( 'Per-member subscription price', 'woocommerce-memberships-for-teams' ),
				'per_member_subscription_sign_up_fee'   => __( 'Per-member sign-up fee', 'woocommerce-memberships-for-teams' ),
				'per_team_subscription_price'           => __( 'Per-team subscription price', 'woocommerce-memberships-for-teams' ),
				'per_team_subscription_sign_up_fee'     => __( 'Per-team sign-up fee', 'woocommerce-memberships-for-teams' ),

			),
		) );

		// additional scripts on other memberships screens
		if ( 'product' === $screen->id ) {

			wp_enqueue_script( 'wc-memberships-for-teams-admin-products', wc_memberships_for_teams()->get_plugin_url() . '/assets/js/admin/wc-memberships-for-teams-products-admin.min.js', array( 'wc-memberships-for-teams-admin' ), Plugin::VERSION );

		} elseif ( in_array( $screen->id, array( 'wc_user_membership', 'edit-wc_user_membership' ), true ) ) {

			wp_enqueue_script( 'wc-memberships-for-teams-user-memberships-admin', wc_memberships_for_teams()->get_plugin_url() . '/assets/js/admin/wc-memberships-for-teams-user-memberships-admin.min.js', array( 'wc-memberships-for-teams-admin' ), Plugin::VERSION );
		}
	}


	/**
	 * Gets Memberships for Teams admin screen ids.
	 *
	 * Note: this doesn't include all core Memberships screens, but may overlap some.
	 * @see \WC_Memberships_Admin::get_screen_ids() equivalent to get all screens used by Memberships and its add ons
	 *
	 * @since 1.1.2
	 *
	 * @param null|string $context optional context to grab only specific screen IDs
	 * @return array associative array of screen IDs by context
	 */
	public function get_screen_ids( $context = null ) {

		$meta_boxes_screens = $modals_screens = $scripts_screens = $tabs_screens = array(
			'wc_memberships_team',
			'edit-wc_memberships_team',
		);

		$scripts_screens[] = 'product';
		$scripts_screens[] = 'wc_user_membership';
		$scripts_screens[] = 'edit-wc_user_membership';

		/**
		 * Filters Memberships for Teams admin screen IDs.
		 *
		 * @since 1.1.2
		 *
		 * @param array $screen_ids associative array organized by context
		 */
		$screen_ids = (array) apply_filters( 'wc_memberships_for_teams_admin_screen_ids', array(
			'meta_boxes' => $meta_boxes_screens,
			'modals'     => $modals_screens,
			'scripts'    => array_merge( $meta_boxes_screens, $modals_screens, $tabs_screens, $scripts_screens ),
			'tabs'       => $tabs_screens,
		) );

		// return all screens or screens belonging to a particular group
		if ( null !== $context ) {
			$screen_ids = isset( $screen_ids[ $context ] ) ? $screen_ids[ $context ] : array();
		}

		return $screen_ids;
	}


	/**
	 * Highlights WooCommerce as the main menu item for team post type.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent_file
	 * @return string
	 */
	public function modify_parent_file( $parent_file ) {
		global $post_type;

		if ( 'wc_memberships_team' === $post_type ) {
			$parent_file  = 'woocommerce';
		}

		return $parent_file;
	}


	/**
	 * Highlights WooCommerce > Memberships as the selected submenu item for team post type.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $submenu_file
	 * @return string
	 */
	public function modify_submenu_file( $submenu_file ) {
		global $post_type;

		if ( 'wc_memberships_team' === $post_type ) {
			$submenu_file = 'edit.php?post_type=wc_user_membership';
		}

		return $submenu_file;
	}


	/**
	 * Adds Teams tab to memberships admin screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_admin_tabs( $tabs ) {

		$tabs = Framework\SV_WC_Helper::array_insert_after( $tabs, 'members', array(
			'teams' => array(
				'title' => __( 'Teams', 'woocommerce-memberships-for-teams' ),
				'url'   => admin_url( 'edit.php?post_type=wc_memberships_team' ),
			)
		) );

		return $tabs;
	}


	/**
	 * Sets the current Memberships tab.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_tab current tab slug
	 * @return string
	 */
	public function set_current_tab( $current_tab ) {
		global $typenow;

		if ( 'wc_memberships_team' === $typenow ) {
			$current_tab = 'teams';
		}

		return $current_tab;
	}


	/**
	 * Adds Teams screens to Memberships core screens.
	 *
	 * @see \WC_Memberships_Admin::get_screen_ids()
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $screens associative array of context => screen ids
	 * @return array
	 */
	public function add_team_screen_ids( $screens ) {

		$team_screens = $this->get_screen_ids();

		foreach ( $team_screens as $screen => $ids ) {
			if ( 'product' !== $screen && ! empty( $ids ) ) {
				$screens[ $screen ] = isset( $screens[ $screen ] ) ? array_merge( $screens[ $screen ], $ids ) : $ids;
			}
		}

		return $screens;
	}


	/**
	 * Loads meta boxes.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function load_meta_boxes() {
		global $pagenow;

		$screen = get_current_screen();

		// bail out if not on a new post / edit post screen or not on a Teams modals screen
		if ( ! $screen || ! in_array( $pagenow, array( 'post-new.php', 'post.php' ), true ) || ! in_array( $screen->id, $this->get_screen_ids( 'modals' ), true ) ) {
			return;
		}

		$meta_box_classes = array();
		$this->meta_boxes = new \stdClass();

		// load voucher meta boxes
		if ( 'wc_memberships_team' === $screen->id ) {
			$meta_box_classes[] = 'Team_Details';
			$meta_box_classes[] = 'Team_Billing_Details';
			$meta_box_classes[] = 'Team_Members';
		}

		// load and instantiate
		foreach ( $meta_box_classes as $class_name ) {

			$instance_name = strtolower( $class_name );
			$class         = __NAMESPACE__ . '\\Admin\\Meta_Boxes\\' . $class_name;

			$this->meta_boxes->$instance_name = new $class();
		}
	}


	/**
	 * Loads modals.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	* @param \WC_Memberships_Modal[] $modals an associative array of modals names and instances
	* @param \WP_Screen $screen the current screen*
	* @return \WC_Memberships_Modal[] an associative array of modals names and instances
	 */
	public function load_modals( $modals, $screen ) {

		if ( $screen && in_array( $screen->id, $this->get_screen_ids( 'modals' ), true ) ) {

			if ( 'wc_memberships_team' === $screen->id ) {
				$modals['wc_memberships_for_teams_add_team_member']             = new Admin\Modals\Add_Team_Member;
				$modals['wc-memberships-for-teams-modal-confirm-remove-member'] = new Admin\Modals\Confirm_Remove_Member;
			}

			if ( 'wc_memberships_team' === $screen->id || 'edit-wc_memberships_team' === $screen->id ) {
				$modals['wc_memberships_for_teams_confirm_delete_team'] = new Admin\Modals\Confirm_Delete_Team;
			}
		}

		return $modals;
	}


	/**
	 * Displays admin messages.
	 *
	 * @since 1.0.0
	 */
	public function show_admin_messages() {

		wc_memberships_for_teams()->get_message_handler()->show_messages();
	}


	/**
	 * Adds custom slugs for endpoints in My Account page.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings
	 * @return array $settings
	 */
	public function add_my_account_endpoints_options( $settings ) {

		$new_settings = array();

		foreach ( $settings as $setting ) {

			$new_settings[] = $setting;

			if ( isset( $setting['id'] ) && 'woocommerce_logout_endpoint' === $setting['id'] ) {

				$new_settings[] = array(
						'title'    => __( 'My Teams', 'woocommerce-memberships-for-teams' ),
						'desc'     => __( 'Endpoint for the "My Account &rarr; My Teams" page', 'woocommerce-memberships-for-teams' ),
						'id'       => 'woocommerce_myaccount_teams_area_endpoint',
						'type'     => 'text',
						'default'  => 'teams',
						'desc_tip' => true,
				);

				$new_settings[] = array(
						'title'    => __( 'Join Team', 'woocommerce-memberships-for-teams' ),
						'desc'     => __( 'Endpoint for the "My Account &rarr; Join Team" page', 'woocommerce-memberships-for-teams' ),
						'id'       => 'woocommerce_myaccount_join_team_endpoint',
						'type'     => 'text',
						'default'  => 'join-team',
						'desc_tip' => true,
				);
			}
		}

		return $new_settings;
	}


}
