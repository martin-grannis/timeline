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

namespace SkyVerge\WooCommerce\Memberships\Teams\Frontend;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;
use SkyVerge\WooCommerce\Memberships\Teams\Product;
use SkyVerge\WooCommerce\Memberships\Teams\Team;

defined( 'ABSPATH' ) or exit;

/**
 * The Teams Area lists the current user's Teams on the WooCommerce My Account page.
 *
 * We add an endpoint to WooCommerce My Account through a 'teams_area' query variable.
 * This translates as a slug that can be customer defined, just like other slugs managed by WooCommerce core for the My Account area.
 *
 * Unlike other My Account endpoints, the Teams Area expects more information coming from the URL (or via query strings if not using a permalink structure).
 *
 * @since 1.0.0
 */
class Teams_Area {


	/** @var string the endpoint / query var used by the teams area */
	private $query_var = 'teams';

	/** @var string the endpoint used by the teams area */
	private $endpoint;

	/** @var bool whether the installation is using pretty permalinks (true) or query strings (false) as URL rewrite structure */
	private $using_permalinks;

	/** @var bool whether tiptip has been enqueued or not */
	private $tiptip_enqueued = false;


	/**
	 * Sets up the Teams Area.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->using_permalinks = get_option( 'permalink_structure' );
		$this->endpoint         = $this->using_permalinks ? get_option( 'woocommerce_myaccount_teams_area_endpoint', 'teams' ) : $this->query_var;

		add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_var' ) );

		// add a menu item in My Account for the Teams endpoint
		add_filter( 'woocommerce_account_menu_items',                 array( $this, 'add_account_teams_area_menu_item' ), 999 );
		add_filter( 'woocommerce_account_menu_item_classes',          array( $this, 'adjust_account_teams_area_menu_item_classes' ), 999, 2 );
		add_filter( 'wc_get_template',                                array( $this, 'get_teams_area_navigation_template' ), 1, 2 );

		// renders the teams area content
		add_action( "woocommerce_account_{$this->endpoint}_endpoint", array( $this, 'output_teams_area' ) );

		// handles WordPress page title and content in Teams Area sections
		add_filter( 'the_title',                                      array( $this, 'adjust_account_page_title' ), 40 );
		add_filter( 'the_content',                                    array( $this, 'adjust_account_page_content' ), 40 );

		// filter the breadcrumbs in My Account area when viewing individual teams
		add_filter( 'woocommerce_get_breadcrumb',                     array( $this, 'adjust_account_page_breadcrumbs' ), 100 );

		// handle team area actions
		add_action( 'template_redirect', array( $this, 'handle_team_access' ), 5 );
		add_action( 'template_redirect', array( $this, 'handle_member_actions' ) );
		add_action( 'template_redirect', array( $this, 'handle_invitation_actions' ) );
	}


	/**
	 * Gets the localization array for the teams area team settings script.
	 *
	 * @since 1.1.0
	 *
	 * @return array
	 */
	public function get_team_settings_l10n() {

		$l10n = array();

		if ( $team = $this->get_teams_area_team() ) {

			$team_product = $team->get_product();
			$l10n         = array(
				'team' => array(
					'seat_change_mode' => $team->get_seat_change_mode(),
					'is_per_member'    => $team_product ? Product::has_per_member_pricing( $team_product ) : null,
					'max_seats'        => $team_product ? Product::get_max_member_count( $team_product ) : null,
					'min_seats'        => $team_product ? Product::get_min_member_count( $team_product ) : null,
					'current_seats'    => $team->get_seat_count(),
					'used_seats'       => $team->get_used_seat_count(),
				),
				'seat_change_message' => array(
					'template' => sprintf(
						/* translators: Placeholders: %1$s - <strong>, %2$s - </strong> */
						__( 'This action will %1$s{action} {count} {seat_n}%2$s, resulting in a total of %1$s{total_count} {total_seat_n}%2$s.', 'woocommerce-memberships-for-teams' ),
						'<strong>',
						'</strong>'
					),
					'actions' => array(
						'add'    => _x( 'add', 'Membership seat change action', 'woocommerce-memberships-for-teams' ),
						'remove' => _x( 'remove', 'Membership seat change action', 'woocommerce-memberships-for-teams' ),
					),
					'seat_n' => array(
						'singular' => __( 'seat', 'woocommerce-memberships-for-teams' ),
						'plural'   => __( 'seats', 'woocommerce-memberships-for-teams' ),
					),
				),
				'validation_errors' => array(
					'empty'                 => __( 'Please enter a valid seat value', 'woocommerce-memberships-for-teams' ),
					'no_change'             => __( 'Seat count unchanged', 'woocommerce-memberships-for-teams' ),
					'not_enough_free_seats' => __( 'This team does not have enough unoccupied seats to remove this many seats.', 'woocommerce-memberships-for-teams' ),
					'add_only'              => __( 'This team only allows adding seats, not removing', 'woocommerce-memberships-for-teams' ),
					                           /* translators: Placeholder: %1$d - number of seats */
					'below_min'             => $team_product ? sprintf( __( 'This team requires a minimum of %1$d seats.', 'woocommerce-memberships-for-teams' ), Product::get_min_member_count( $team_product ) ) : null,
					                           /* translators: Placeholder: %1$d - number of seats */
					'above_max'             => $team_product ? sprintf( _n( 'This team allows a maximum of %1$d seat.', 'This team allows a maximum of %1$d seats.', Product::get_max_member_count( $team_product ), 'woocommerce-memberships-for-teams' ), Product::get_max_member_count( $team_product ) ) : null,
				)
			);
		}

		return $l10n;
	}


	/**
	 * Adds the teams area query var to WooCommerce query vars.
	 *
	 * @see \WC_Query::get_query_vars()
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param string[] $query_vars array of query vars
	 * @return string[]
	 */
	public function add_query_var( $query_vars ) {

		if ( ! isset( $query_vars[ $this->query_var ] ) ) {
			$query_vars[ $this->query_var ] = $this->endpoint;
		}

		return $query_vars;
	}


	/**
	 * Checks if we are on the teams area endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_teams_area() {
		global $wp_query;

		if ( $wp_query ) {
			if ( $this->using_permalinks ) {
				$is_endpoint_url = array_key_exists( $this->endpoint, $wp_query->query_vars ) || ! empty( $wp_query->query_vars[ $this->endpoint ] );
			} else {
				$is_endpoint_url = isset( $_GET[ $this->query_var ] );
			}
		}

		return ! empty( $is_endpoint_url );
	}


	/**
	 * Checks if we are currently viewing a teams area section.
	 *
	 * @since 1.0.0
	 *
	 * @param null|array|string $section optional: check against a specific section, an array of sections or any valid section (null)
	 * @return bool
	 */
	public function is_teams_area_section( $section = null ) {

		$is_section = false;

		if ( $this->is_teams_area() ) {

			$the_section = $this->get_teams_area_section();

			if ( null !== $section ) {
				// check for specified section(s)
				$is_section = is_array( $section ) ? in_array( $the_section, $section, true  ) : $section === $the_section;
			} else {
				// check for more generic sections list
				$is_section = array_key_exists( $the_section, $this->get_teams_area_sections() );
			}
		}

		return $is_section;
	}


	/**
	 * Returns the teams area current section to display.
	 *
	 * @since 1.0.0
	 *
	 * @return string section name
	 */
	public function get_teams_area_section() {

		$query_vars = $this->get_teams_area_query_vars();

		return ! empty( $query_vars[1] ) ? $query_vars[1] : '';
	}


	/**
	 * Returns the teams area current page.
	 *
	 * @since 1.0.0
	 *
	 * @return int page ID
	 */
	public function get_teams_area_section_page() {

		$query_vars = $this->get_teams_area_query_vars();

		return ! empty( $query_vars[2] ) ? max( 1, (int) $query_vars[2] ) : 1;
	}


	/**
	 * Returns the teams area current team ID.
	 *
	 * @since 1.0.0
	 *
	 * @return int team ID
	 */
	public function get_teams_area_team_id() {

		$query_vars = $this->get_teams_area_query_vars();

		return isset( $query_vars[0] ) && is_numeric( $query_vars[0] ) ? $query_vars[0] : 0;
	}


	/**
	 * Returns the teams area current team instance.
	 *
	 * @since 1.0.0
	 *
	 * @return false|\SkyVerge\WooCommerce\Memberships\Teams\Team instance
	 */
	public function get_teams_area_team() {
		return wc_memberships_for_teams_get_team( $this->get_teams_area_team_id() );
	}


	/**
	 * Returns the teams area query vars.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] array of teams area query vars
	 */
	private function get_teams_area_query_vars() {
		global $wp;

		$query_vars = array();

		if ( ! get_option( 'permalink_structure' ) ) {
			if ( isset( $_GET[ $this->endpoint ] ) && is_numeric( $_GET[ $this->endpoint ] ) ) {
				$query_vars[] = (int) $_GET[ $this->endpoint ];
			}
			if ( isset( $_GET['teams_area_section'] ) ) {
				$query_vars[] = $_GET['teams_area_section'];
			}
			if ( isset( $_GET['teams_area_section_page'] ) && is_numeric( $_GET['teams_area_section_page'] ) ) {
				$query_vars[] = $_GET['teams_area_section_page'];
			}
		} else {
			$query_vars = ! empty( $wp->query_vars[ $this->endpoint ] ) ? explode( '/',  $wp->query_vars[ $this->endpoint ] ) : $query_vars;
		}

		return $query_vars;
	}


	/**
	 * Returns the teams area sections.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array
	 */
	public function get_teams_area_sections() {

		/**
		 * Filters the available sections for the teams area.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sections associative array with teams area id and label of each section
		 */
		return apply_filters( 'wc_memberships_team_teams_area_sections', array(
			'members'    => __( 'Members', 'woocommerce-memberships-for-teams' ),
			'add-member' => __( 'Add Member', 'woocommerce-memberships-for-teams' ),
			'settings'   => __( 'Team Settings', 'woocommerce-memberships-for-teams' ),
		) );
	}


	/**
	 * Returns a teams area URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team object or id
	 * @param string $teams_area_section (optional) which section of the teams area to point to
	 * @param int|string $paged optional, for paged sections
	 * @param array $args optional, query args to add to the url
	 * @return string unescaped URL
	 */
	public function get_teams_area_url( $team, $teams_area_section = '', $paged = '', $args = array() ) {

		$my_account_page_id = wc_get_page_id( 'myaccount' );
		$team_id            = is_object( $team ) ? $team->get_id() : (int) $team;

		// bail out if something is wrong
		if ( ! $my_account_page_id > 0 || ! $team_id || 0 === $team_id ) {
			return '';
		}

		// if unspecified, will get the first tab
		if ( empty( $teams_area_section ) ) {

			$team = is_int( $team ) ? wc_memberships_for_teams_get_team( $team_id ) : $team;

			if ( ! $team ) {
				return '';
			}

			// get the first tab, unless an empty team, in which case send to the add member section
			$teams_area_section = count( $team->get_member_ids() ) > 0 ? key( (array) $this->get_teams_area_sections() ) : 'add-member';
		}

		if ( ! empty( $paged ) ) {
			$paged = max( absint( $paged ), 1 );
		}

		$url = wc_get_account_endpoint_url( $this->endpoint );

		// Return an URL according to rewrite structure used:
		if ( get_option( 'permalink_structure' ) ) {

			// Using permalinks:
			// e.g. /my-account/teams/123/members/2
			$url = trailingslashit( $url ) . $team_id . '/' . $teams_area_section . '/' . $paged;

		} else {

			// Not using permalinks:
			// e.g. /?page_id=123&teams=456&teams_area_section=members&teams_area_section_page=2
			$url = add_query_arg( array(
				$this->endpoint           => $team_id,
				'teams_area_section'      => $teams_area_section,
				'teams_area_section_page' => $paged,
			), $url );
		}

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


	/**
	 * Returns the Memberships endpoint title for the Teams Area current view.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Team|null $team optional team to form a breadcrumb when viewing an individual team
	 * @return string unescaped label
	 */
	private function get_teams_area_teams_endpoint_title( $team = null ) {

		$endpoint_title = __( 'Teams', 'woocommerce-memberships-for-teams' );

		// perhaps display the current team name
		if ( $team instanceof Team ) {
			if ( is_rtl() ) {
				$endpoint_title  = $team->get_name() . ' &laquo; ' . $endpoint_title;
			} else {
				$endpoint_title .= ' &raquo; ' . $team->get_name();
			}
		}

		/**
		 * Filters the "Teams" teams area title in My Account page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $endpoint_title the endpoint title
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team|null $team the current team or null if viewing the teams list
		 */
		return (string) apply_filters( 'wc_memberships_for_teams_my_account_teams_title', $endpoint_title, $team );
	}


	/**
	 * Returns the Teams Area endpoint URL.
	 *
	 * Normally this would consist of the simple endpoint itself. However, if the user has a single team only, we redirect to that automatically.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_teams_area_teams_endpoint_url() {

		$teams = wc_memberships_for_teams_get_teams();

		if ( 1 === count( $teams ) && ( $team = $teams[0] ) ) {

			$url = $this->get_teams_area_url( $team );

		} else {

			$url = wc_get_account_endpoint_url( $this->endpoint );
		}

		return $url;
	}


	/**
	 * Returns the Teams Area endpoint path to be used in the menu items.
	 *
	 * Normally this would consist of the simple endpoint itself. However, if the user has a single team only, we redirect to that automatically.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_teams_area_teams_endpoint_path() {

		$teams = wc_memberships_for_teams_get_teams();

		if ( 1 === count( $teams ) && ( $team = $teams[0] ) ) {

			$my_account_url = wc_get_page_permalink( 'myaccount' );
			$path           = str_replace( $my_account_url, '', $this->get_teams_area_url( $team ) );

		} else {
			$path = $this->endpoint;
		}

		return untrailingslashit( $path );
	}



	/**
	 * Adds a My Account menu item for the Teams Area.
	 *
	 * @since 1.0.0
	 *
	 * @internal
	 *
	 * @param array $items associative array of custom endpoints and endpoint labels
	 * @return array
	 */
	public function add_account_teams_area_menu_item( $items ) {

		if ( $this->is_teams_area_section() ) {

			// if we are viewing a team, then wipe out the My Account items to make room for the teams area sections
			$items = array();

		} else {

			// display new endpoint if there is at least 1 team the current user can manage
			$teams = wc_memberships_for_teams_get_teams( get_current_user_id(), array(
				'per_page' => 1,
				'role'     => array( 'owner', 'manager' ),
			) );

			if ( ! empty( $teams ) ) {

				$endpoint_title = esc_html( $this->get_teams_area_teams_endpoint_title() );
				$endpoint_path  = $this->get_teams_area_teams_endpoint_path();

				if ( array_key_exists( 'orders', $items ) ) {
					$items = Framework\SV_WC_Helper::array_insert_after( $items, 'orders', array( $endpoint_path => $endpoint_title ) );
				} else {
					$items[ $endpoint_path ] = $endpoint_title;
				}
			}
		}

		return $items;
	}


	/**
	 * Adjusts the CSS classes of the teams area endpoints.
	 *
	 * @see wc_get_account_menu_item_classes()
	 * @see \WC_Memberships_Members_Area::adjust_account_members_area_menu_item_classes()
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $classes array of CSS classes
	 * @param string $endpoint the current endpoint
	 * @return string[] array of CSS classes (to be passed later in `sanitize_html_class()` by WC)
	 */
	public function adjust_account_teams_area_menu_item_classes( $classes, $endpoint ) {

		if ( ! $this->is_teams_area_section() ) {

			$teams_area_endpoint = $this->get_teams_area_teams_endpoint_path();

			if ( $endpoint === $teams_area_endpoint ) {

				$class_prefix       = 'woocommerce-MyAccount-navigation-link--';
				$members_area_class = $class_prefix . $teams_area_endpoint;
				$new_classes        = array();

				foreach ( $classes as $class ) {

					if ( $class === $members_area_class ) {

						$current_section = $this->get_teams_area_section();

						if ( ! empty( $current_section ) ) {
							$new_classes[] = $class_prefix . $current_section;
						} else {
							$new_classes[] = $class_prefix . 'teams';
						}

					} else {

						$new_classes[] = $class;
					}
				}

				$classes = $new_classes;
			}
		}

		return $classes;
	}


	/**
	 * Checks whether any products that create a team exist.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true, if such products exist, false otherwise
	 */
	private function team_products_exist() {
		return count( get_posts( array(
			'post_type'      => 'product',
			'meta_key'       => '_wc_memberships_for_teams_has_team_membership',
			'meta_value'     => 'yes',
			'posts_per_page' => 1,
		) ) ) > 0;
	}


	/**
	 * Returns the menu items for the currently viewed team in teams area.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team instance
	 * @return array associative array
	 */
	public function get_teams_area_navigation_items( $team ) {

		$teams               = wc_memberships_for_teams_get_teams();
		$teams_count         = count( $teams );
		$teams_area_sections = $this->get_teams_area_sections();
		$menu_items          = array( 'back-to-teams' => array(
			'url'   => 1 === $teams_count ? wc_get_account_endpoint_url( 'dashboard' ) : wc_get_account_endpoint_url( $this->endpoint ),
			/* translators: Placeholder: %s - "Back to Teams" or "Back to Dashboard" label to return back to the teams list or the My Account dashboard */
			'label' => sprintf( __( 'Back to %s', 'woocommerce-memberships-for-teams' ), 1 === $teams_count ? __( 'Dashboard', 'woocommerce-memberships-for-teams' ) : $this->get_teams_area_teams_endpoint_title() ),
			'class' => '',
		) );

		foreach ( $teams_area_sections as $section_id => $section_name ) {

			if ( 'settings' === $section_id && ! current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) ) {
				continue;
			}

			/**
			 * Filters the teams area section name title.
			 *
			 * @since 1.0.0
			 *
			 * @param string $section_name the section name (e.g. "Members", "Add Member", "Settings"...)
			 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the current team displayed
			 */
			$section_name = apply_filters( "wc_memberships_for_teams_teams_area_{$section_id}_title", $section_name, $team );

			$menu_items[ $section_id ] = array(
				'url'   => $this->get_teams_area_url( $team, $section_id ),
				'label' => $section_name,
				'class' => $this->is_teams_area_section( $section_id ) ? ' is-active' : '',
			);
		}

		/**
		 * Filters the teams area menu items for the current team.
		 *
		 * @since 1.0.0
		 *
		 * @param array $menu_items associative array of URLs and labels
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the menu items are for
		 */
		return (array) apply_filters( 'wc_memberships_for_teams_teams_area_navigation_items', $menu_items, $team );
	}


	/**
	 * Overrides the account navigation with teams area menu items.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $located the located template
	 * @param string $template_name the template name
	 * @return string template to load
	 */
	public function get_teams_area_navigation_template( $located, $template_name ) {

		if ( 'myaccount/navigation.php' === $template_name && $this->is_teams_area_section() ) {
			$located = wc_locate_template( 'myaccount/my-teams-navigation.php' );
		}

		return $located;
	}


	/**
	 * Loads the Teams Area templates.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $section the teams area section to display
	 * @param array $args array of arguments {
	 *      @type \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object (required)
	 *      @type int $user_id member ID (required)
	 *      @type int $paged optional pagination (optional)
	 * }
	 */
	public function get_template( $section, $args ) {

		// bail out: no args, no party
		if ( empty( $args['team'] ) && empty( $args['user_id'] ) && ( ! $args['team'] instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) ) {
			return;
		}

		// TODO: should we prefetch members here, to apply sorting and paging to it?
		$paged   = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;

		// get any sorting args
		$sorting = $this->get_teams_area_sorting_args();

		// allow custom sections if wc_memberships_team_teams_area_sections is filtered
		$located = wc_locate_template( "myaccount/my-team-{$section}.php" );

		// add teams area instance to args
		$args['teams_area'] = $this;

		if ( 'settings' === $section ) {
			$args['team_details']      = $this->get_teams_area_team_details( $args['team'] );
			$args['team_seat_details'] = $this->get_teams_area_seat_details( $args['team'] );

			if ( empty( $args['team_seat_details'] ) ) {
				unset( $args['team_seat_details'] );
			}
		}

		if ( is_readable( $located ) ) {
			wc_get_template( "myaccount/my-team-{$section}.php", $args );
		}
	}


	/**
	 * Returns query sorting arguments for teams area content.
	 *
	 * @since 1.0.0
	 *
	 * @return array associative array compatible with `get_posts` and `WP_Query` sorting arguments
	 */
	public function get_teams_area_sorting_args() {

		$args = array();

		if ( isset( $_GET['sort_by'] ) && in_array( $_GET['sort_by'], array( 'title', 'type' ), true ) ) {

			$args['orderby'] = $_GET['sort_by'];

			if ( isset( $_GET['sort_order'] ) && in_array( strtoupper( $_GET['sort_order'] ), array( 'ASC', 'DESC' ), true ) ) {
				$args['order'] = strtoupper( $_GET['sort_order'] );
			} else {
				$args['order'] = 'ASC';
			}
		}

		return $args;
	}


	/**
	 * Renders the teams area content.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output_teams_area() {

		$the_content = '';

		if ( $this->is_teams_area() ) {

			$team = $this->get_teams_area_team();

			// check if team exists and current user can manage the team
			if ( $team && current_user_can( 'wc_memberships_for_teams_manage_team', $team ) ) {

				// sections for this team
				$sections = (array) $this->get_teams_area_sections();

				// Teams Area should have at least one section enabled
				if ( ! empty( $sections ) ) {

					$my_account_page = get_post( wc_get_page_id( 'myaccount' ) );

					$html_before = isset( $content_pieces[0] ) ? $content_pieces[0] : '';
					$html_after  = isset( $content_pieces[1] ) ? $content_pieces[1] : '';

					// get the section to display, or use the first designated section as fallback:
					$section = $this->get_teams_area_section();
					$section = ! empty( $section ) && array_key_exists( $section, $sections ) ? $section : $sections[0];

					// get a paged request for the given section:
					$paged = $this->get_teams_area_section_page();

					// make sure managers don't see the team setting page
					if ( 'settings' === $section && ! current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) ) {

						$the_content = '';

					} else {

						ob_start();

						echo $html_before;

						?>
						<div
							class="my-team-section <?php echo sanitize_html_class( $section ); ?>"
							id="wc-memberships-for-teams-teams-area-section"
							data-section="<?php echo esc_attr( $section ); ?>"
							data-page="<?php echo esc_attr( $paged ); ?>">
							<?php $this->get_template( $section, array(
								'team'       => $team,
								'paged'      => $paged,
								'teams_area' => $this,
							) ); ?>
						</div>
						<?php

						echo $html_after;

						// grab everything that was output above while processing any shortcode in between
						$the_content = do_shortcode( ob_get_clean() );
					}

				}

			} else {

				// TODO: consider adding paging to teams table as well {IT 2017-09-13}
				wc_get_template( 'myaccount/my-teams.php', array(
					'teams'      => wc_memberships_for_teams_get_teams(),
					'teams_area' => $this,
				) );
			}
		}

		echo $the_content;
	}


	/**
	 * Sets the My Account page title when viewing the Teams Area endpoint.
	 *
	 * If we are in "Teams" it will display "Teams" as the page title.
	 * If we are viewing an individual team section, it will display "Teams > {Team Name}" as the title.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $title the page title
	 * @return string
	 */
	public function adjust_account_page_title( $title ) {

		if ( $this->is_teams_area() && ( ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) ) {

			$title = esc_html( $this->get_teams_area_teams_endpoint_title( $this->is_teams_area_section() ? $this->get_teams_area_team() : null ) );

			// remember: the removal priority must match the priority when the filter was added in constructor
			remove_filter( 'the_title', array( $this, 'adjust_account_page_title' ), 40 );
		}

		return $title;
	}


	/**
	 * Adjusts the HTML content of the My Account page and wraps it with a Teams Area div.
	 *
	 * @internal
	 * @see Teams_Area::output_members_area()
	 *
	 * @since 1.0.0
	 *
	 * @param string $content post content HTML
	 * @return string the same HTML content wrapped in a new div to identify the teams area container
	 */
	public function adjust_account_page_content( $content ) {

		if ( $this->is_teams_area_section() && ( ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) ) {

			$member_id = get_current_user_id();
			$team_id   = $this->get_teams_area_team_id();

			ob_start();

			?>
			<div
				class="my-team member-<?php echo esc_attr( $member_id ); ?> team-<?php echo esc_attr( $team_id ); ?>"
				id="wc-memberships-for-teams-teams-area"
				data-member="<?php echo esc_attr( $member_id ); ?>"
				data-membership="<?php echo esc_attr( $team_id ); ?>">
				<?php echo $content; ?>
			</div>
			<?php

			$content = ob_get_clean();

			// remember: the removal priority must match the priority when the filter was added in constructor
			remove_filter( 'the_content', array( $this, 'adjust_account_page_content' ), 40 );
		}

		return $content;
	}


	/**
	 * Adjusts WooCommerce My Account area breadcrumbs.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $crumbs WooCommerce My Account breadcrumbs
	 * @return array
	 */
	public function adjust_account_page_breadcrumbs( $crumbs ) {
		global $wp;

		// sanity check to see if we're at the right endpoint:
		if (    isset( $wp->query_vars[ $this->endpoint ] )
			 && is_account_page()
			 && ( count( $crumbs ) > 0 ) ) {

			// add the top-level "Teams" endpoint link, if we're on the teams area to begin with
			$crumbs[] = array( esc_html( $this->get_teams_area_teams_endpoint_title() ), wc_get_endpoint_url( $this->endpoint ) );

			// get membership data
			$current_user_id = get_current_user_id();
			$team            = $this->get_teams_area_team();

			// check if team exists and the current logged in user is the owner of the team
			if ( $current_user_id && $team && $team->is_user_owner( $current_user_id ) ) {

				// add a link to the current team content being viewed
				$crumbs[] = array( $team->get_name(), $this->get_teams_area_url( $team ) );

				if ( $this->is_teams_area_section() ) {

					$teams_area_sections = $this->get_teams_area_sections();

					if ( ( $current_section = $this->get_teams_area_section() ) && array_key_exists( $current_section, $teams_area_sections ) ) {

						// add a link to the current section of the teams area
						$crumbs[] = array( $teams_area_sections[ $current_section ], $this->get_teams_area_url( $team, $current_section ) );
					}
				}
			}
		}

		return $crumbs;
	}


	/**
	 * Returns a an array of team details for usage in teams area.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the details are for
	 * @return array
	 */
	private function get_teams_area_team_details( $team ) {

		$details = array(
			'membership-plan' => array(
				'label'   => _x( 'Plan', 'Membership plan', 'woocommerce-memberships-for-teams' ),
				'content' => $team->get_plan() ? $team->get_plan()->get_name() : '',
				'class'   => 'my-team-detail-membership-plan',
			),
			'seat-count' => array(
				'label'   => _x( 'Seats', 'Partitive (how many seats?)', 'woocommerce-memberships-for-teams' ),
				'content' => ( $seat_count = $team->get_seat_count() ) ? esc_html( $seat_count ) : esc_html__( 'Unlimited', 'woocommerce-memberships-for-teams' ),
				'class'   => 'my-team-detail-team-seat-count',
			),
			'member-count' => array(
				'label'   => _x( 'Members', 'Partitive (how many members?)', 'woocommerce-memberships-for-teams' ),
				'content' => $team->get_used_seat_count() . ( ( $invitation_count = $team->get_invitation_count() ) ? ' ' . sprintf( esc_html__( '(including %d pending invitations)', 'woocommerce-memberships-for-teams' ), $invitation_count ) : '' ),
				'class'   => 'my-team-detail-team-member-count',
			),
			'created-date' => array(
				'label'   => __( 'Created On', 'woocommerce-memberships-for-teams' ),
				'content' => is_numeric( $team->get_local_date( 'timestamp' ) ) ? date_i18n( wc_date_format(), $team->get_local_date( 'timestamp' ) ) : esc_html__( 'N/A', 'woocommerce-memberships-for-teams' ),
				'class'   => 'my-team-detail-team-created-date',
			),
			'actions' => array(
				'label'   => __( 'Actions', 'woocommerce-memberships-for-teams' ),
				'content' => $this->get_action_links( 'settings', $team ),
				'class'   => 'my-team-detail-team-actions',
			),
		);

		/**
		 * Filters the teams area team details.
		 *
		 * @since 1.0.0
		 *
		 * @param array $details associative array of settings labels and HTML content for each row
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the details are for
		 */
		return apply_filters( 'wc_memberships_for_teams_teams_area_my_team_details', $details, $team );
	}


	/**
	 * Returns an array of seat details for use in the team seat change form.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team
	 * @return array $team_seat_details
	 */
	private function get_teams_area_seat_details( Team $team ) {

		$product           = $team->get_product();

		if ( ! $product || ! $product instanceof \WC_Product ) {
			return array();
		}

		$max_seats         = Product::get_max_member_count( $product );
		$team_seat_details = array();

		switch( $team->get_seat_change_mode() ) {

			case 'update_seats':

				$team_seat_details = array(
					'instructions' => __( 'Enter the new seat count to reduce seats or to go to checkout to purchase additional seats.', 'woocommerce-memberships-for-teams' ),
					'field_value'  => $team->get_seat_count(),
					'field_max'    => 0 < $max_seats ? $max_seats : '',
					'field_min'    => max( Product::get_min_member_count( $product ), 1 ),
				);

			break;

			case 'add_seats':

				$team_seat_details = array(
					'instructions' => __( 'Enter the number of seats you would like to add to this team.', 'woocommerce-memberships-for-teams' ),
					'field_value'  => 1,
					'field_min'    => 1,
					'field_max'    => 0 < $max_seats ? $max_seats - $team->get_seat_count() : '',
				);

			break;

			case 'add_seat_blocks':

				$instructions = sprintf(
					/* translators: Placeholder: %1$d - number of seats */
					__( 'This team is sold in blocks of <strong>%1$d seats</strong>. How many blocks would you like to add?', 'woocommerce-memberships-for-teams' ),
					Product::get_max_member_count( $team->get_product() )
				);

				$team_seat_details = array(
					'instructions' => $instructions,
					'field_value' => 1,
					'field_min'   => 1,
					'field_max'   => '',
				);

			break;
		}

		return $team_seat_details;
	}


	/**
	 * Returns the Teams Area action links HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section teams area section to display actions for
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the teams area is for
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member|object $object an object to pass to a filter hook (optional)
	 * @return string action links HTML
	 */
	public function get_action_links( $section, $team, $object = null ) {

		$default_actions = array();
		$object_id       = 0;

		if ( $object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team_Member ) {
			$object_id = $object->get_id();
		} elseif ( $object instanceof \SkyVerge\WooCommerce\Memberships\Teams\Invitation ) {
			$object_id = $object->get_id();
		} elseif ( $object instanceof \WP_User || isset( $object->ID ) ) {
			$object_id = $object->ID;
		}

		$user_id = get_current_user_id();

		switch ( $section ) {

			case 'teams' :
			case 'settings' :

				// renew: Show only for teams with expired memberships that can be renewed
				if ( ( $team->is_membership_expired() || $team->is_order_refunded() ) && current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) ) {

					$default_actions['renew'] = array(
						'url'  => $team->get_renew_membership_url(),
						'name' => __( 'Renew', 'woocommerce-memberships-for-teams' ),
					);
				}

				if ( 'settings' !== $section && current_user_can( 'wc_memberships_for_teams_manage_team', $team ) ) {

					$default_actions['view'] = array(
						'url'  => $this->get_teams_area_url( $team ),
						'name' => __( 'View', 'woocommerce-memberships-for-teams' ),
					);
				}

				if ( 'settings' === $section && $team->is_user_owner( $user_id ) && $team->can_add_seats() ) {

					$default_actions['update_seats'] = array(
						'url'  => '#',
						'name' => $team->can_remove_seats() ? __( 'Change Seats', 'woocommerce-memberships-for-teams' ) : __( 'Add Seats', 'woocommerce-memberships-for-teams' ),
					);
				}

			break;

			case 'members':

				if ( ! empty( $object_id ) ) {

					$member = $object;

					if ( $member && current_user_can( 'wc_memberships_for_teams_remove_team_member', $team, $object_id ) ) {

						$default_actions['remove_member'] = array(
							'name' => __( 'Remove', 'woocommerce-memberships-for-teams' ),
						);

						if ( $team->can_be_managed() ) {

							$user_membership = $member->get_user_membership();

							// can't remove expired or cancelled members
							if ( $user_membership && $user_membership->has_status( array( 'cancelled', 'expired' ) ) ) {

								$default_actions['remove_member']['tip'] = __( "Can't remove this member because their membership is expired or has been cancelled." );

							} else {

								$default_actions['remove_member']['url'] = add_query_arg( array(
									'action'   => 'remove_member',
									'user'     => $member->get_id(),
									'_wpnonce' => wp_create_nonce( 'team-remove-member-' . $member->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) );
							}

						} else {

							$default_actions['remove_member']['tip'] = $team->get_management_decline_reason( 'remove_member' );
						}

					}

					if ( current_user_can( 'wc_memberships_for_teams_manage_team_member', $team, $object_id ) ) {

						$role = $member->get_role();

						if ( 'member' === $role && 'yes' === get_option( 'wc_memberships_for_teams_managers_may_manage_managers', 'yes' ) ) {
							$default_actions['set_as_manager'] = array(
								'url' => add_query_arg( array(
									'action'   => 'set_as_manager',
									'user'     => $member->get_id(),
									'_wpnonce' => wp_create_nonce( 'team-set-as-manager-' . $member->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) ),
								'name' => __( 'Set as manager', 'woocommerce-memberships-for-teams' ),
							);
						} elseif ( 'manager' === $role ) {
							$default_actions['set_as_member'] = array(
								'url' => add_query_arg( array(
									'action'   => 'set_as_member',
									'user'     => $member->get_id(),
									'_wpnonce' => wp_create_nonce( 'team-set-as-member-' . $member->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) ),
								'name' => __( 'Set as member', 'woocommerce-memberships-for-teams' ),
							);
						}
					}

				}

			break;

			case 'invitations':

				if ( ! empty( $object_id ) ) {

					$invitation = $object;

					if ( current_user_can( 'wc_memberships_for_teams_manage_team_members', $team ) ) {

						if ( $team->can_be_managed() ) {

							$default_actions['resend'] = array(
								'url' => add_query_arg( array(
									'action'     => 'invitation_resend',
									'invitation' => $invitation->get_id(),
									'_wpnonce'   => wp_create_nonce( 'team-invitation-resend-' . $invitation->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) ),
								'name' => __( 'Resend', 'woocommerce-memberships-for-teams' ),
							);

						}

						$role = $invitation->get_member_role();

						if ( 'member' === $role ) {
							$default_actions['set_as_manager'] = array(
								'url' => add_query_arg( array(
									'action'     => 'invitation_set_as_manager',
									'invitation' => $invitation->get_id(),
									'_wpnonce'   => wp_create_nonce( 'team-invitation-set-as-manager-' . $invitation->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) ),
								'name' => __( 'Set as manager', 'woocommerce-memberships-for-teams' ),
							);
						} elseif ( 'manager' === $role ) {
							$default_actions['set_as_member'] = array(
								'url' => add_query_arg( array(
									'action'     => 'invitation_set_as_member',
									'invitation' => $invitation->get_id(),
									'_wpnonce'   => wp_create_nonce( 'team-invitation-set-as-member-' . $invitation->get_id() ),
								), $this->get_teams_area_url( $team->get_id() ) ),
								'name' => __( 'Set as member', 'woocommerce-memberships-for-teams' ),
							);
						}

						$default_actions['cancel'] = array(
							'url' => add_query_arg( array(
								'action'     => 'invitation_cancel',
								'invitation' => $invitation->get_id(),
								'_wpnonce'   => wp_create_nonce( 'team-invitation-cancel-' . $invitation->get_id() ),
							), $this->get_teams_area_url( $team->get_id() ) ),
							'name' => __( 'Cancel', 'woocommerce-memberships-for-teams' ),
						);
					}

				}

			break;

		}

		/**
		 * Filters team actions on My Account and Teams Area pages.
		 *
		 * @since 1.0.0
		 *
		 * @param array $default_actions associative array of actions
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team current team instance
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member|\SkyVerge\WooCommerce\Memberships\Teams\Invitation|object $object current object where the action is run (optional)
		 */
		$actions = apply_filters( "wc_memberships_for_teams_teams_area_{$section}_actions", $default_actions, $team, $object );

		$links = '';

		if ( ! empty( $actions ) ) {
			foreach ( $actions as $key => $action ) {

				$tag  = ! empty( $action['url'] ) ? 'a' : 'span disabled';
				$href = ! empty( $action['url'] ) ? ' href="' . esc_url( $action['url'] ) . '"' : '';
				$tip  = ! empty( $action['tip'] ) ? ' title="' . esc_attr( $action['tip'] ) . '"' : '';

				$classes = array( 'button', 'wc-memberships-for-teams-team-area-action', sanitize_html_class( $key ) );

				if ( empty( $action['url'] ) ) {
					$classes[] = 'disabled';
				}

				if ( $tip ) {

					$classes[] = 'tip';

					// ensure jquery tiptip has been enqueued, on-demand
					if ( ! $this->tiptip_enqueued ) {

						wp_enqueue_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );

						wc_enqueue_js( '
							$( ".wc-memberships-for-teams-team-area-action.button.tip" ).tipTip();
						' );

						$this->tiptip_enqueued = true;
					}

				}

				$link = '<' . $tag . $href . $tip . ' class="' . implode( ' ', $classes ) . '">' . esc_html( $action['name'] ) . '</' . $tag . '> ';

				$links .= $link;
			}
		}

		return $links;
	}


	/**
	 * Returns Teams Area pagination links.
	 *
	 * @since 1.0.0
	 *
	 * @param false|int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the links are related to
	 * @param string $section Teams Area section
	 * @param int $total_pages total number of pages
	 * @param int $current_page the current page number
	 * @param array $args optional, query args to add to the links
	 * @return string HTML or empty output if query is not paged
	 */
	public function get_pagination_links( $team, $section, $total_pages, $current_page, $args = array() ) {

		$links = '';

		if ( $total_pages > 1 ) {

			if ( is_numeric( $team ) ) {
				$team = wc_memberships_for_teams_get_team( $team );
			}

			if ( $team ) {

				$links .= '<span class="wc-memberships-for-teams-teams-area-pagination">';

				// page navigation entities
				$first         = '<span class="first">&#x25C4;</span>';
				$first_tooltip = __( 'First', 'woocommerce-memberships-for-teams' );
				$prev          = '<span class="prev">&#x25C2;</span>';
				$prev_tooltip  = __( 'Previous', 'woocommerce-memberships-for-teams' );
				$current       = ' &nbsp; <span class="current">' . $current_page . '</span> &nbsp; ';
				$next          = '<span class="next">&#x25B8;</span>';
				$next_tooltip  = __( 'Next', 'woocommerce-memberships-for-teams' );
				$last          = '<span class="last">&#x25BA;</span>';
				$last_tooltip  = __( 'Last', 'woocommerce-memberships-for-teams' );

				if ( 1 === $current_page ) {
					// first page, show next
					$links .= $current;
					$links .= ' <a title="' . esc_html( $next_tooltip )   . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, 2, $args ) )                . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-next">' . $next . '</a> ';
					$links .= ' <a title="' . esc_html( $last_tooltip )   . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, $total_pages, $args ) )             . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-last">' . $last . '</a> ';
				} elseif ( $total_pages === $current_page ) {
					// last page, show prev
					$links .= ' <a title="' . esc_html( $first_tooltip ) . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, 1, $args ) )                 . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-first">' . $first . '</a> ';
					$links .= ' <a title="' . esc_html( $prev_tooltip )  . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, $current_page - 1, $args ) ) . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-prev">' . $prev . '</a> ';
					$links .= $current;
				} else {
					// in the middle of pages, show both
					$links .= ' <a title="' . esc_html( $first_tooltip ) . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, 1, $args ) )                 . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-first">' . $first . '</a> ';
					$links .= ' <a title="' . esc_html( $prev_tooltip )  . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, $current_page - 1, $args ) ) . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-prev">' . $prev . '</a> ';
					$links .= $current;
					$links .= ' <a title="' . esc_html( $next_tooltip )  . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, $current_page + 1, $args ) ) . '" class="wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-next">' . $next . '</a> ';
					$links .= ' <a title="' . esc_html( $last_tooltip )  . '" href="' . esc_url( $this->get_teams_area_url( $team, $section, $total_pages, $args ) )              . '" class="wc-memberships-for-teams-teams-area-page-linkwc-memberships-for-teams-teams-area-page-link wc-memberships-for-teams-teams-area-pagination-last">' . $last . '</a> ';
				}

				$links .= '</span>';
			}
		}

		/**
		 * Filters the teams area pagination links.
		 *
		 * @since 1.0.0
		 *
		 * @param string $links HTML
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team the links are related to
		 * @param string $section the current section displayed
		 * @param int $total_pages total number of pages
		 * @param int $current_page the current page number
		 */
		return (string) apply_filters( 'wc_memberships_for_teams_teams_area_pagination_links', $links, $team, $section, $total_pages, $current_page );
	}


	/**
	 * Returns a list of HTML view links for the Teams Area members section.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_members_section_view_links() {

		$team  = $this->get_teams_area_team();
		$url   = $this->get_teams_area_url( $team, 'members' );
		$views = \SkyVerge\WooCommerce\Memberships\Teams\Team_Members::get_table_views( $team, $url );

		if ( ! $views ) {
			return;
		}

		ob_start();

		echo "<ul class='views'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo "</ul>";

		return ob_get_clean();
	}


	/**
	 * Handles teams area access.
	 *
	 * Prevents non-managers from accessing a team endpoint.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_team_access() {

		if ( $this->is_teams_area() ) {

			$team = $this->get_teams_area_team();

			if ( $team ) {

				// check if the current user can manage the team altogether
				if ( ! current_user_can( 'wc_memberships_for_teams_manage_team', $team ) ) {

					// redirect to dashboard with a notice
					wc_add_notice( __( 'You cannot manage this team.', 'woocommerce-memberships-for-teams' ), 'error' );
					wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
					exit;
				}

				// check if the current user can manage the team settings, specifically
				if ( 'settings' === $this->get_teams_area_section() && ! current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) ) {
					wc_add_notice( __( 'You cannot manage settings for this team.', 'woocommerce-memberships-for-teams' ), 'error' );
				}

			} elseif ( $this->get_teams_area_team_id() ) {

				// redirect to teams area with a notice
				wc_add_notice( __( 'No such team.', 'woocommerce-memberships-for-teams' ), 'error' );
				wp_safe_redirect( wc_get_account_endpoint_url( $this->endpoint ) );
				exit;
			}
		}
	}


	/**
	 * Handles teams area member actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_member_actions() {

		if ( ! $this->is_teams_area() || empty( $_GET['action'] ) || empty( $_GET['user'] ) ) {
			return;
		}

		$user_id      = $_GET['user'];
		$action       = $_GET['action'];
		$nonce_action = 'team-' . str_replace( '_', '-', $action ) . '-' . $user_id;
		$team         = $this->get_teams_area_team();
		$member       = wc_memberships_for_teams_get_team_member( $team, $user_id );
		$notice_type  = 'notice';

		if ( ! $team ) {

			$notice_message = __( 'Invalid team.', 'woocommerce-memberships-for-teams' );
			$notice_type    = 'error';

		} elseif ( ! $member ) {

			$notice_message = __( 'Invalid member.', 'woocommerce-memberships-for-teams' );
			$notice_type    = 'error';

		} else {

			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {

				switch ( $action ) {

					case 'set_as_member':

						try {

							$member->set_role( 'member' );

							$notice_message = sprintf( __( '%s was set as a member of the team.', 'woocommerce-memberships-for-teams' ), $member->get_name() );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;

					case 'set_as_manager':

						try {

							$member->set_role( 'manager' );

							$notice_message = sprintf( __( '%s was set as a manager of the team.', 'woocommerce-memberships-for-teams' ), $member->get_name() );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;

					case 'remove_member':

						try {

							$team->remove_member( $user_id );

							$notice_message = sprintf( __( '%s was removed from the team.', 'woocommerce-memberships-for-teams' ), $member->get_name() );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot remove member: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;
				}

			} else {

				/* translators: Placeholder: %s - team name */
				$notice_message = __( 'Cannot perform action. Please try again.', 'woocommerce-memberships-for-teams' );
				$notice_type    = 'error';
			}
		}

		if ( isset( $notice_message, $notice_type ) ) {

			wc_add_notice( $notice_message, $notice_type );

			if ( 'notice' === $notice_type ) {

				wp_safe_redirect( $this->get_teams_area_url( $team, 'members' ) );
				exit;
			}
		}
	}


	/**
	 * Handles teams area invitation actions.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function handle_invitation_actions() {

		if ( ! $this->is_teams_area() || empty( $_GET['action'] ) || empty( $_GET['invitation'] ) ) {
			return;
		}

		$invitation_id = $_GET['invitation'];
		$action        = $_GET['action'];
		$nonce_action  = 'team-' . str_replace( '_', '-', $action ) . '-' . $invitation_id;
		$team          = $this->get_teams_area_team();
		$invitation    = wc_memberships_for_teams_get_invitation( $invitation_id );
		$notice_type   = 'notice';

		if ( ! $team ) {

			$notice_message = __( 'Invalid team.', 'woocommerce-memberships-for-teams' );
			$notice_type    = 'error';

		}
		elseif ( ! $invitation || $invitation->get_team_id() !== $team->get_id() ) {

			$notice_message = __( 'Invalid invitation.', 'woocommerce-memberships-for-teams' );
			$notice_type    = 'error';

		} else {

			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $nonce_action ) ) {

				$name = $invitation->get_name();

				switch ( $action ) {

					case 'invitation_resend':

						try {

							$invitation->send();

							$notice_message = sprintf( __( 'Invitation to %s re-sent.', 'woocommerce-memberships-for-teams' ), $name );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot send invitation: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;

					case 'invitation_set_as_member':

						try {

							$invitation->set_member_role( 'member' );

							$notice_message = sprintf( __( '%s was set to be a member of the team.', 'woocommerce-memberships-for-teams' ), $name );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;

					case 'invitation_set_as_manager':

						try {

							$invitation->set_member_role( 'manager' );

							$notice_message = sprintf( __( '%s was set to be a manager of the team.', 'woocommerce-memberships-for-teams' ), $name );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot set role in team: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;

					case 'invitation_cancel':

						try {

							$invitation->cancel();

							$notice_message = sprintf( __( 'Invitation for %s was cancelled.', 'woocommerce-memberships-for-teams' ), $name );

						} catch ( Framework\SV_WC_Plugin_Exception $e ) {

							$notice_message = sprintf( __( 'Cannot cancel invitation: %s', 'woocommerce-memberships-for-teams' ), $e->getMessage() );
							$notice_type    = 'error';
						}

					break;
				}

			} else {

				/* translators: Placeholder: %s - team name */
				$notice_message = __( 'Cannot perform action. Please try again.', 'woocommerce-memberships-for-teams' );
				$notice_type    = 'error';
			}
		}


		if ( isset( $notice_message, $notice_type ) ) {
			wc_add_notice( $notice_message, $notice_type );

			if ( 'notice' === $notice_type ) {
				wp_safe_redirect( $this->get_teams_area_url( $team, 'members', null, array( 'show_invitations' => 1 ) ) );
				exit;
			}
		}
	}
}
