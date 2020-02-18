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

/**
 * Memberships Post Types class
 *
 * This class is responsible for registering the custom post types & taxonomy
 * required for Memberships.
 *
 * @since 1.0.0
 */
class Post_Types {


	/**
	 * Initialize and register the Teams post types
	 *
	 * @since 1.0.0
	 */
	public static function initialize() {

		self::init_post_types();
		self::init_user_roles();
		self::init_post_statuses();

		add_filter( 'post_updated_messages',      array( __CLASS__, 'updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( __CLASS__, 'bulk_updated_messages' ), 10, 2 );

		// maybe remove overzealous 3rd-party meta boxes
		add_action( 'add_meta_boxes', array( __CLASS__, 'maybe_remove_meta_boxes' ), 30 );
	}


	/**
	 * Initialize Teams user roles.
	 *
	 * @since 1.0.0
	 */
	private static function init_user_roles() {
		global $wp_roles;

		if ( class_exists( '\WP_Roles' ) && ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		// allow shop managers and admins to manage teams and user memberships
		if ( is_object( $wp_roles ) ) {

			$args                  = new \stdClass();
			$args->map_meta_cap    = true;
			$args->capability_type = 'memberships_team';
			$args->capabilities    = array();

			foreach ( get_post_type_capabilities( $args ) as $builtin => $mapped ) {

				$wp_roles->add_cap( 'shop_manager', $mapped );
				$wp_roles->add_cap( 'administrator', $mapped );
			}

			$wp_roles->add_cap( 'shop_manager',  'manage_woocommerce_memberships_teams' );
			$wp_roles->add_cap( 'administrator', 'manage_woocommerce_memberships_teams' );
		}
	}


	/**
	 * Registers Teams post types.
	 *
	 * @since 1.0.0
	 */
	private static function init_post_types() {

		register_post_type( 'wc_memberships_team',
			array(
				'labels' => array(
						'name'               => __( 'Teams', 'woocommerce-memberships-for-teams' ),
						'singular_name'      => __( 'Team', 'woocommerce-memberships-for-teams' ),
						'menu_name'          => _x( 'Memberships', 'Admin menu name', 'woocommerce-memberships-for-teams' ),
						'add_new'            => __( 'Add Team', 'woocommerce-memberships-for-teams' ),
						'add_new_item'       => __( 'Add New Team', 'woocommerce-memberships-for-teams' ),
						'edit'               => __( 'Edit', 'woocommerce-memberships-for-teams' ),
						'edit_item'          => __( 'Edit Team', 'woocommerce-memberships-for-teams' ),
						'new_item'           => __( 'New Team', 'woocommerce-memberships-for-teams' ),
						'view'               => __( 'View Teams', 'woocommerce-memberships-for-teams' ),
						'view_item'          => __( 'View Team', 'woocommerce-memberships-for-teams' ),
						'search_items'       => __( 'Search Teams', 'woocommerce-memberships-for-teams' ),
						'not_found'          => __( 'No Teams found', 'woocommerce-memberships-for-teams' ),
						'not_found_in_trash' => __( 'No Teams found in trash', 'woocommerce-memberships-for-teams' ),
					),
				'description'         => __( 'This is where you can add new Teams.', 'woocommerce-memberships-for-teams' ),
				'public'              => false,
				'show_ui'             => true,
				'capability_type'     => 'memberships_team',
				'map_meta_cap'        => true,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
			)
		);
	}


	/**
	 * Customizes updated messages for custom post types.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param array $messages original messages
	 * @return array $messages modified messages
	 */
	public static function updated_messages( $messages ) {

		$messages['wc_memberships_team'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Team saved.', 'woocommerce-memberships-for-teams' ),
			2  => __( 'Custom field updated.', 'woocommerce-memberships-for-teams' ),
			3  => __( 'Custom field deleted.', 'woocommerce-memberships-for-teams' ),
			4  => __( 'Team saved.', 'woocommerce-memberships-for-teams' ),
			5  => '', // Unused for teams
			6  => __( 'Team saved.', 'woocommerce-memberships-for-teams' ), // Original: Post published
			7  => __( 'Team saved.', 'woocommerce-memberships-for-teams' ),
			8  => '', // Unused for teams
			9  => '', // Unused for teams
			10 => __( 'Team draft updated.', 'woocommerce-memberships-for-teams' ), // Original: Post draft updated
		);

		return $messages;
	}


	/**
	 * Customizes bulk updated messages for custom post types.
	 *
	 * @internal
	 * @since 1.0.0
	 *
	 * @param array $messages original messages
	 * @param array $bulk_counts number of objects that were updated
	 * @return array $messages modified messages
	 */
	public static function bulk_updated_messages( $messages, $bulk_counts ) {

		$messages['wc_memberships_team'] = array(
			'updated'   => _n( '%s team updated.', '%s teams updated.', $bulk_counts['updated'], 'woocommerce-memberships-for-teams' ),
			'locked'    => _n( '%s team not updated, somebody is editing it.', '%s teams not updated, somebody is editing them.', $bulk_counts['locked'], 'woocommerce-memberships-for-teams' ),
			'deleted'   => _n( '%s team permanently deleted.', '%s teams permanently deleted.', $bulk_counts['deleted'], 'woocommerce-memberships-for-teams' ),
			'trashed'   => _n( '%s team moved to the Trash.', '%s teams moved to the Trash.', $bulk_counts['trashed'], 'woocommerce-memberships-for-teams' ),
			'untrashed' => _n( '%s team restored from the Trash.', '%s teams restored from the Trash.', $bulk_counts['untrashed'], 'woocommerce-memberships-for-teams' ),
		);

		return $messages;
	}


	/**
	 * Registers invitation post statuses.
	 *
	 * @since 1.0.0
	 */
	private static function init_post_statuses() {

		$statuses = wc_memberships_for_teams_get_invitation_statuses();

		foreach ( $statuses as $status => $args ) {

			$args = wp_parse_args( $args, array(
				'label'     => ucfirst( $status ),
				'public'    => false,
				'protected' => true,
			) );

			register_post_status( $status, $args );
		}
	}


	/**
	 * Removes third party meta boxes from our CPT screens unless they're on the whitelist.
	 *
	 * @internal
	 * @since 1.0.0
	 *
	 * @param string $post_type the post type
	 */
	public static function maybe_remove_meta_boxes( $post_type ) {

		if ( 'wc_memberships_team' !== $post_type ) {
			return;
		}

		// TODO: replace empty array below with whitelisted meta box ids {IT 2017-07-04}

		/**
		 * Filters the whitelisted meta boxes for Teams post types
		 *
		 * @since 1.0.0
		 *
		 * @param array $meta_box_ids allowed meta box includes
		 * @param string $post_type the post type
		 */
		$allowed_meta_box_ids = apply_filters( 'wc_memberships_for_teams_allowed_meta_box_ids', array_merge( array( 'submitdiv' ), array() ), $post_type );

		$screen = get_current_screen();

		foreach ( $GLOBALS['wp_meta_boxes'][ $screen->id ] as $context => $meta_boxes_by_context ) {

			foreach ( $meta_boxes_by_context as $subcontext => $meta_boxes_by_subcontext ) {

				foreach ( $meta_boxes_by_subcontext as $meta_box_id => $meta_box ) {

					if ( ! in_array( $meta_box_id, $allowed_meta_box_ids, true ) ) {
						remove_meta_box( $meta_box_id, $post_type, $context );
					}
				}
			}
		}
	}


}
