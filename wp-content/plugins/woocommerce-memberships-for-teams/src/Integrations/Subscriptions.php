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

namespace SkyVerge\WooCommerce\Memberships\Teams\Integrations;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;
use SkyVerge\WooCommerce\Memberships\Teams\Product;
use SkyVerge\WooCommerce\Memberships\Teams\Seat_Manager;
use SkyVerge\WooCommerce\Memberships\Teams\Team;
use SkyVerge\WooCommerce\Memberships\Teams\Team_Member;

defined( 'ABSPATH' ) or exit;

/**
 * Teams Subscriptions integration class.
 *
 * @since 1.0.0
 */
class Subscriptions {


	/**
	 * Sets up the Subscriptions integration class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// admin
		add_filter( 'wc_memberships_for_teams_team_billing_details', array( $this, 'add_subscription_details' ), 10, 2 );
		add_action( 'wc_memberships_for_teams_process_team_meta',    array( $this, 'update_team_subscription' ), 30, 2 );

		add_filter( 'wc_memberships_for_teams_membership_plan_list_team_product',        array( $this, 'remove_team_subscription_products' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_membership_plan_column_team_product_link', array( $this, 'adjust_subscription_team_product_link' ), 10, 3 );
		add_action( 'wc_memberships_for_teams_membership_plan_team_options',             array( $this, 'output_team_subscription_options' ) );

		// general
		add_action( 'wc_memberships_for_teams_create_team_from_order', array( $this, 'save_subscription_data' ), 10, 2 );
		add_action( 'wc_memberships_for_teams_add_team_member',        array( $this, 'adjust_team_member_user_membership_data' ), 10, 3 );
		add_action( 'woocommerce_checkout_subscription_created',       array( $this, 'update_team_subscription_on_resubscribe' ), 20, 2 );
		add_action( 'woocommerce_subscription_item_switched',          array( $this, 'update_team_subscription_on_switch' ), 10, 4 );

		// team management
		add_filter( 'wc_memberships_for_teams_team_can_add_member',    array( $this, 'maybe_allow_adding_new_member' ), 10, 3 );
		add_filter( 'wc_memberships_for_teams_team_can_remove_member', array( $this, 'maybe_allow_removing_existing_member' ), 10, 3 );
		add_filter( 'wc_memberships_for_teams_team_management_status', array( $this, 'adjust_team_management_status' ), 10, 2 );

		// seat changes
		add_filter( 'wc_memberships_for_teams_team_can_add_seats',               array( $this, 'maybe_disable_team_seat_addition' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_team_can_remove_seats',            array( $this, 'maybe_enable_team_seat_removal' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data',                            array( $this, 'add_subscription_data_to_seat_changes' ), 10, 3 );
		add_filter( 'woocommerce_subscriptions_can_item_be_switched',            array( $this, 'maybe_allow_team_subscription_to_be_switched' ), 10, 3 );
		add_filter( 'wc_memberships_for_teams_get_seat_change_product_quantity', array( $this, 'adjust_per_team_seat_change_product_quantity' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_should_prorate_seat_change',       array( $this, 'enable_seat_change_proration_for_subscriptions' ), 10, 3 );
		add_action( 'woocommerce_before_calculate_totals',                       array( $this, 'set_subscriptions_settings_for_seat_change' ), 10, 1 );
		add_filter( 'wc_memberships_for_teams_seat_change_notice_message',       array( $this, 'set_subscription_seat_change_notice_message' ), 10, 4 );
		add_action( 'woocommerce_subscriptions_switched_item',                   array( $this, 'disable_default_memberships_switch_handling' ), 5, 3 );
		add_filter( 'wcs_switch_proration_old_price_per_day',                    array( $this, 'maybe_correct_old_price_per_day' ), 10, 5 );

		// handle seat changes when there's a limitation set on the subscription product
		add_filter( 'woocommerce_subscriptions_product_limitation', [ $this, 'handle_subscription_product_limitation' ], 10, 2 );
		add_action( 'woocommerce_cart_emptied',                     [ $this, 'restore_subscription_product_limitation' ] );

		// this needs to hook after \WC_Subscriptions_Switcher::calculate_prorated_totals() which hooks at priority 99
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'correct_seat_change_sign_up_fees' ), 100, 1 );

		// frontend
		add_filter( 'woocommerce_order_again_cart_item_data', array( $this, 'remove_raw_cart_item_team_data'), 20, 2 );

		add_filter( 'wc_memberships_for_teams_teams_area_teams_actions',    array( $this, 'add_billing_action' ), 10, 2 );
		add_filter( 'wc_memberships_for_teams_teams_area_settings_actions', array( $this, 'add_billing_action' ), 10, 2 );

		add_filter( 'wc_memberships_for_teams_my_teams_column_names',      array( $this, 'add_next_bill_column' ) );
		add_filter( 'wc_memberships_for_teams_teams_area_my_team_details', array( $this, 'add_team_subscription_details' ), 10, 2 );

		add_action( 'wc_memberships_for_teams_my_teams_column_team-next-bill-on', array( $this, 'output_next_bill_date' ) );

		add_action( 'woocommerce_memberships_for_teams_join_team_form', array( $this, 'output_subscription_notice_and_options' ) );
		add_action( 'woocommerce_memberships_for_teams_joined_team', array( $this, 'maybe_cancel_existing_subscription' ), 10, 2 );

		// emails
		add_filter( 'woocommerce_email_enabled_wc_memberships_for_teams_team_membership_ending_soon', array( $this, 'skip_ending_soon_emails' ), 20, 2 );

		// init hooks that need to be executed early
		add_action( 'init', array( $this, 'init' ) );

		// add any admin notices
		add_action( 'admin_notices', array( $this, 'add_admin_notices' ) );
	}


	/**
	 * Adds any admin notices.
	 *
	 * @since 1.0.5-dev.2
	 */
	public function add_admin_notices() {

		$screen = get_current_screen();

		// viewing a Team post type (new, edit, or list table)
		if ( $screen && 'wc_memberships_team' === $screen->post_type ) {

			// viewing the Edit Team screen
			if ( 'post' === $screen->base && 'edit' === Framework\SV_WC_Helper::get_request( 'action' ) ) {

				// sanity check to ensure the object being edited is a valid team
				if ( $team = wc_memberships_for_teams_get_team( Framework\SV_WC_Helper::get_request( 'post' ) ) ) {

					$subscription_id  = (int) get_post_meta( $team->get_id(), '_subscription_id', true );
					$switched_team_id = (int) get_post_meta( $team->get_id(), '_subscription_switched_team_id', true );

					// display a notice if the subscription was switch and this team is no longer linked
					if ( ! $subscription_id && $switched_team_id && $switched_team_id !== $team->get_id() && $switched_team = wc_memberships_for_teams_get_team( $switched_team_id ) ) {

						$message = sprintf(
							/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
							__( 'Heads up! The owner of this team switched their subscription and it\'s now linked to a new team. %1$sClick here to edit the new team &raquo;%2$s', 'woocommerce-memberships-for-teams' ),
							'<a href="' . esc_url( $switched_team->get_edit_url() ) . '">', '</a>'
						);

						wc_memberships_for_teams()->get_admin_notice_handler()->add_admin_notice( $message, "switched_team_{$switched_team_id}", array(
							'notice_class' => 'notice-warning',
						) );
					}
				}
			}
		}
	}


	/**
	 * Initializes early hooks.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function init() {

		add_filter( 'wc_memberships_membership_plan', array( $this, 'get_membership_plan' ), 2, 3 );
	}


	/**
	 * Filters a Membership Plan to return a subscription-tied Membership Plan.
	 *
	 * This method is a filter callback and should not be used directly.
	 * @see \wc_memberships_get_membership_plan() instead.
	 *
	 * TODO: Note that this method currently relies on the team object being passed to wc_memberships_get_membership_plan()
	 * as the 2nd argument instead of the user membership. It works for now, but is somewhat hacky and should be refactored
	 * later, perhaps by changing the 2nd arg in that method to a generic $context array which addons could adjust, ie
	 * `wc_memberships_get_membership_plan( $plan_id, array( 'team' => $team ) )`. {IT 2017-11-13}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Memberships_Membership_Plan $membership_plan the membership plan
	 * @param null|\WP_Post $membership_plan_post the membership plan post object
	 * @param null|\SkyVerge\WooCommerce\Memberships\Teams\Team $team the team object
	 * @return \WC_Memberships_Integration_Subscriptions_Membership_Plan|\WC_Memberships_Membership_Plan
	 */
	public function get_membership_plan( $membership_plan, $membership_plan_post = null, $team = null ) {

		// We can't filter directly $membership_plan:
		// it may have both regular products and subscription products that grant access;
		// instead, the team will tell the type of purchase.
		return $this->has_subscription_created_team( $team ) ? new \WC_Memberships_Integration_Subscriptions_Membership_Plan( $membership_plan->post ) : $membership_plan;
	}


	/**
	 * Checks if the product that created the team has a Subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance
	 * @return bool
	 */
	public function has_subscription_created_team( $team ) {

		$is_subscription_tied = false;

		if ( $team instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) {

			if ( $subscription_id = $this->get_team_subscription_id( $team ) ) {

				$is_subscription_tied = ! empty( $subscription_id ) && wcs_get_subscription( $subscription_id );

			} elseif ( $product = $team->get_product() ) {

				$is_subscription_tied = $this->is_subscription_product( $product );
			}
		}

		return $is_subscription_tied;
	}


	/**
	 * Adds subscription details to the edit team screen billing details section.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields an associative array of billing detail fields, in format label => field html
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_subscription_details( $fields, $team ) {

		if ( ! $team instanceof \SkyVerge\WooCommerce\Memberships\Teams\Team ) {
			return $fields;
		}

		$next_payment = '';
		$subscription = $this->get_team_subscription( $team );

		if ( $subscription ) {
			$next_payment = $subscription->get_time( 'next_payment' );
		}

		$edit_subscription_input = $this->get_edit_subscription_input( $team, $subscription );

		$fields[ __( 'Subscription:', 'woocommerce-memberships-for-teams' ) ] = $edit_subscription_input;
		$fields[ __( 'Next Bill On:', 'woocommerce-memberships-for-teams' ) ] = $next_payment ? date_i18n( wc_date_format(), $next_payment ) : esc_html__( 'N/A', 'woocommerce-memberships-for-teams' );

		$core_integration = $this->get_core_integration();

		// maybe replace the expiration date input
		if ( $subscription && $plan = $team->get_plan() ) {

			if ( $plan->is_access_length_type( 'subscription' ) && $core_integration->get_plans_instance()->grant_access_while_subscription_active( $plan->get_id() ) ) {

				$subscription_expires = $subscription instanceof \WC_Subscription ? $subscription->get_date_to_display( 'end' ) : '';

				wc_enqueue_js( '
					$( "#team-membership-end-date-section" ).find( ".wc-memberships-for-teams-date-input" ).hide();
					$( "#team-membership-end-date-section" ).append( "<span>' . esc_html( $subscription_expires ) . '</span>" );
				' );
			}
		}

		return $fields;
	}


	/**
	 * Returns the edit subscription input HTML.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param \WC_Subscription|null $subscription the subscription object
	 * @return string HTML
	 */
	private function get_edit_subscription_input( $team, $subscription = null ) {

		if ( $subscription && $subscription instanceof \WC_Subscription ) {
			$subscription_id   = Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
			$subscription_url  = get_edit_post_link( $subscription_id );
			$subscription_link = '<a href="' . esc_url( $subscription_url ) . '">' . esc_html( $subscription_id ) . '</a>';
			$selected          = array( $subscription_id => $this->get_core_integration()->get_formatted_subscription_id_holder_name( $subscription ) );
		} else {
			$selected          = array();
			$subscription_id   = '';
			$subscription_link = esc_html__( 'Team not linked to a Subscription', 'woocommerce-memberships-for-teams' );
		}

		/* translators: Placeholders: %1$s - link to a Subscription, %2$s - opening <a> HTML tag, %3%s - closing </a> HTML tag */
		$input = sprintf( __( '%1$s - %2$sEdit Link%3$s', 'woocommerce-memberships-for-teams' ),
			$subscription_link,
			'<a href="#" class="js-edit-subscription-link-toggle">',
			'</a>'
		);

		ob_start();

		?><br>
		<span class="wc-memberships-edit-subscription-link-field">
			<select
				class="sv-wc-enhanced-search"
				id="_subscription_id"
				name="_subscription_id"
				data-action="wc_memberships_edit_membership_subscription_link"
				data-nonce="<?php echo wp_create_nonce( 'edit-membership-subscription-link' ); ?>"
				data-placeholder="<?php esc_attr_e( 'Link to a Subscription or keep empty to leave unlinked', 'woocommerce-memberships-for-teams' ); ?>"
				data-allow_clear="true">
				<?php if ( $subscription instanceof \WC_Subscription ) : ?>
					<option value="<?php echo $subscription_id; ?>"><?php echo $subscription_id; ?></option>
				<?php endif; ?>
			</select>
		</span>
		<?php

		Framework\SV_WC_Helper::render_select2_ajax();

		$input .= ob_get_clean();

		// toggle editing of subscription id link
		wc_enqueue_js( '
			$( ".js-edit-subscription-link-toggle" ).on( "click", function( e ) { e.preventDefault(); $( ".wc-memberships-edit-subscription-link-field" ).toggle(); } ).click();
		' );

		return $input;
	}


	/**
	 * Checks whether a team is on a subscription or not.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return bool
	 */
	public function has_team_subscription( $team ) {
		return (bool) $this->get_team_subscription( $team );
	}


	/**
	 * Returns a Subscription for a team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return null|\WC_Subscription The Subscription object, null if not found
	 */
	public function get_team_subscription( $team ) {
		$subscription_id = $this->get_team_subscription_id( $team );

		return ! $subscription_id ? null : wcs_get_subscription( $subscription_id );
	}


	/**
	 * Returns a subscription ID for a team.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team team instance or id
	 * @return string|false
	 */
	public function get_team_subscription_id( $team ) {

		$team_id = is_object( $team ) ? $team->get_id() : $team;

		return get_post_meta( $team_id, '_subscription_id', true );
	}


	/**
	 * Updates the team subscription ID.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id post identifier
	 * @param \WP_Post $post the post object
	 */
	public function update_team_subscription( $post_id, \WP_Post $post ) {

		if ( $team = wc_memberships_for_teams_get_team( $post->ID ) ) {

			$new_subscription_id = ! empty( $_POST['_subscription_id'] ) ? (int) $_POST['_subscription_id'] : null;
			$old_subscription    = $this->get_team_subscription( $team );

			// always update the meta first in case the below membership looping fails
			update_post_meta( $post_id, '_subscription_id', $new_subscription_id );

			// if an ID is set, update the memberships with a new subscription link
			if ( $new_subscription_id && $new_subscription = wcs_get_subscription( $new_subscription_id ) ) {

				$this->update_team_user_memberships_subscription( $team, $new_subscription );

			// otherwise, remove the link from the memberships
			} elseif ( $old_subscription ) {

				$this->remove_team_user_memberships_subscription( $team, $old_subscription );
			}
		}
	}


	/**
	 * Adds a subscription attribute to the subscription team product listed among the access granting products.
	 *
	 * @internal
	 *
	 * @since 1.0.4
	 *
	 * @param string $html link HTML with additional information
	 * @param string $link link HTML (just the link)
	 * @param \WC_Product $product product object
	 * @return string HTML
	 */
	public function adjust_subscription_team_product_link( $html, $link, $product ) {

		if ( $this->is_subscription_product( $product ) ) {

			$attributes = array_map( 'strtolower', array(
				'(' . __( 'Subscription', 'woocommerce-memberships-for-teams' ) . ')',
				'(' . __( 'Team', 'woocommerce-memberships-for-teams' ) . ')',
			) );

			$html = sprintf( '<li>%1$s%2$s</li>', $link, ' <small>' . implode( ' ', $attributes ) . '</small>' );
		}

		return $html;
	}


	/**
	 * Toggles whether a product should be listed among the team products of a plan.
	 *
	 * Excludes subscription products, so these can be added separately in another list.
	 *
	 * @since 1.0.4
	 *
	 * @param bool $list_product whether to list the product among the team products of a membership plan
	 * @param \WC_Product $product a product that could be a subscription product
	 * @return bool
	 */
	public function remove_team_subscription_products( $list_product, $product ) {

		if ( $this->is_subscription_product( $product ) ) {
			$list_product = false;
		}

		return $list_product;
	}


	/**
	 * Outputs team membership subscription options.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function output_team_subscription_options() {
		global $post;

		$products = wc_memberships_for_teams()->get_membership_plans_instance()->get_membership_plan_team_products( $post->ID );

		if ( ! empty( $products ) ) :

			$items = array();

			foreach ( $products as $product ) :

				if ( $this->is_subscription_product( $product ) ) :

					$list_subscription_product = (bool) apply_filters( 'wc_memberships_for_teams_membership_plan_list_team_subscription_product', true, $product, $post->ID );

					if ( $list_subscription_product ) :

						$product_name = sprintf( '%1$s (#%2$s)', $product->get_name(), $product->get_id() );

						$items[] = '<a href="' . get_edit_post_link( $product->get_id() ) . '">' . $product_name . '</a>';

					endif;

				endif;

			endforeach;

			?>

			<?php if ( ! empty( $items ) ) : ?>

				<?php $product_links = wc_memberships_list_items( $items, __( 'and', 'woocommerce-memberships-for-teams' ) ); ?>

				<p class="form-field plan-team-subscriptions-field">
					<label><?php esc_html_e( 'Team subscriptions', 'woocommerce-memberships-for-teams' ); ?></label>
					<span class="team-subscriptions"><?php echo $product_links; ?></span>
				</p>

				<?php /* force display subscription length options */ ?>
				<style type="text/css">
					#membership-plan-data-general .plan-subscription-access-length-field { display: block !important }
				</style>

			<?php endif; ?>

			<?php

		endif;
	}


	/**
	 * Allows adding a new member to the team if they have an existing user membership.
	 *
	 * @internal
	 *
	 * @since 1.1.3
	 *
	 * @param bool $allow whether to allow or not
	 * @param int $user_id user identifier
	 * @param Team $team team object
	 * @return bool
	 */
	public function maybe_allow_adding_new_member( $allow, $user_id, $team ) {

		if ( ! $allow && $user_id ) {

			$existing_user_membership = $team->get_existing_user_membership( $user_id );

			if ( $existing_user_membership instanceof \WC_Memberships_Integration_Subscriptions_User_Membership && $existing_user_membership->get_subscription_id() > 0 ) {

				$member = wc_memberships_for_teams_get_team_member( $team, $existing_user_membership->get_user_id() );
				$allow  = $team->can_remove_member( $member ) || (bool) wc_memberships_for_teams_get_user_membership_team( $existing_user_membership->get_id() );
			}
		}

		return $allow;
	}


	/**
	 * Allows removal of team members from a subscription based team.
	 *
	 * @internal
	 *
	 * @since 1.1.3
	 *
	 * @param bool $allow whether to allow the team member removal
	 * @param Team_Member $member team member object
	 * @param Team $team team object
	 * @return bool
	 */
	public function maybe_allow_removing_existing_member( $allow, $member, $team ) {

		if ( ! $allow && $member ) {

			$existing_user_membership = $team->get_existing_user_membership( $member->get_id() );

			if ( $this->has_team_subscription( $team ) || ( $existing_user_membership instanceof \WC_Memberships_Integration_Subscriptions_User_Membership && $existing_user_membership->get_subscription_id() > 0 ) ) {
				$allow = true;
			}
		}

		return $allow;
	}


	/**
	 * Adjusts the team management status.
	 *
	 * Prevents managing the team if the related subscription is cancelled, expired or trashed.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array an associative array with 2 keys: `can_be_managed` and `decline_reason`
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the related team
	 * @return array
	 */
	public function adjust_team_management_status( $status, $team ) {

		if ( $status['can_be_managed'] && ( $subscription = $this->get_team_subscription( $team ) ) ) {

			$integration         = $this->get_core_integration();
			$subscription_status = $integration->get_subscription_status( $subscription );

			if ( in_array( $subscription_status, array( 'expired', 'trash', 'cancelled' ), true ) ) {

				$status['can_be_managed'] = false;
				$status['message']        = array(
					'general'       => __( 'Team subscription has been cancelled or expired.', 'woocommerce-memberships-for-teams' ),
					'add_member'    => __( "Can't add more members because your team subscription has been cancelled or expired.", 'woocommerce-memberships-for-teams' ),
					'remove_member' => __( "Can't remove members because your team subscription has been cancelled or expired.", 'woocommerce-memberships-for-teams' ),
					'join_team'     => __( "Can't join team at the moment - please contact your team owner for more details.", 'woocommerce-memberships-for-teams' ),
				);
			}
		}

		return $status;
	}


	/**
	 * Disables seat addition for sites that have an incompatible version of Subscriptions.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param bool $allow_addition whether to allow seats to be added
	 * @param Team $team the team in question
	 * @return bool
	 */
	public function maybe_disable_team_seat_addition( $allow_addition, $team ) {

		// only perform check on teams with a subscription
		if ( (bool) $this->get_team_subscription( $team ) ) {

			// disallow seat addition for subscriptions versions that don't support it
			$allow_addition = $this->subscriptions_version_can_seat_change();
		}

		return $allow_addition;
	}


	/**
	 * Enables seat removal for teams that are on a valid subscription.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param bool $allow_removal whether to allow seats to be removed
	 * @param Team $team the team in question
	 * @return bool
	 */
	public function maybe_enable_team_seat_removal( $allow_removal, $team ) {

		$has_subscription = (bool) $this->get_team_subscription( $team );

		if ( $has_subscription ) {

			$can_seat_change  = $this->subscriptions_version_can_seat_change();
			$product          = $team->get_product();
			$is_per_member    = $product ? Product::has_per_member_pricing( $product ) : null;

			$allow_removal = $can_seat_change && $is_per_member && $team->can_be_managed() && ! $team->is_membership_expired();
		}

		return $allow_removal;
	}


	/**
	 * Checks if the currently-installed version of Subscriptions is compatible
	 * with seat changes on teams.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	protected function subscriptions_version_can_seat_change() {

		$can_seat_change = false;

		if ( class_exists( 'WC_Subscriptions' ) && ! empty( \WC_Subscriptions::$version ) ) {

			$subscriptions_version = \WC_Subscriptions::$version;
			$can_seat_change       = version_compare( $subscriptions_version, '2.4.2', '>=' );
		}

		return $can_seat_change;
	}


	/**
	 * Adds subscription data for seat changes involving team membership subscriptions.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param array $cart_item_data the cart item data
	 * @param int $product_id the product id
	 * @param int $variation_id the variation id
	 * @return array
	 * @throws \Exception
	 */
	public function add_subscription_data_to_seat_changes( $cart_item_data, $product_id, $variation_id ) {

		$seat_change  = isset( $cart_item_data['team_meta_data']['_wc_memberships_for_teams_team_seat_change'] ) ? $cart_item_data['team_meta_data']['_wc_memberships_for_teams_team_seat_change'] : null;
		$team         = $this->get_subscription_team_from_cart_item_data( $cart_item_data );
		$subscription = $team ? $this->get_team_subscription( $team ) : null;

		if ( $seat_change && $team && $subscription ) {

			$next_payment_timestamp = $subscription->get_time( 'next_payment' );

			// if there are no payments left, calculate based on the end date of the subscription
			$next_payment_timestamp = $next_payment_timestamp ? $next_payment_timestamp : $subscription->get_time( 'end' );

			$cart_item_data['subscription_switch'] = array(
				'subscription_id'         => $subscription->get_id(),
				'item_id'                 => $this->get_subscription_item_id( $team ),
				'next_payment_timestamp'  => $next_payment_timestamp,
				'upgraded_or_downgraded'  => '',
			);
		}

		return $cart_item_data;
	}


	/**
	 * Maybe allows a subscription item to be switched.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param bool $can_be_switched whether the item can be switched
	 * @param \WC_Order_Item|array $item Order Item object or array representing an order item
	 * @param \WC_Subscription $subscription subscription object
	 * @return bool
	 */
	public function maybe_allow_team_subscription_to_be_switched( $can_be_switched, $item, $subscription ) {

		if ( is_array( $item ) ) {

			$item_team_id = isset( $item['_wc_memberships_for_teams_team_id'] ) ? (int) $item['_wc_memberships_for_teams_team_id'] : null;

		} else {

			$item_team_id = (int) $item->get_meta( '_wc_memberships_for_teams_team_id', true );
		}

		$teams = $this->get_teams_from_subscription( $subscription );

		foreach( $teams as $team_id => $team ) {

			if ( $item_team_id === $team_id ) {

				$can_be_switched = current_user_can( 'wc_memberships_for_teams_update_team_seats', $team_id );
				break;
			}
		}

		return $can_be_switched;
	}


	/**
	 * Adjusts the seat change product quantity for per-team subscription memberships.
	 *
	 * Per-team membership seat changes have a change value based on the number of
	 * blocks of seats to add to the team. We need to convert that to an overall
	 * total quantity of seat blocks so that subscriptions can prorate it correctly.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param int $change_value the seat change value passed from the seat change form
	 * @param Team $team the team object
	 * @return int cart quantity
	 * @throws \Exception
	 */
	public function adjust_per_team_seat_change_product_quantity( $change_value, $team ) {

		$quantity = (int) $change_value;

		if ( $team && $team instanceof Team ) {

			$product      = $team->get_product();
			$per_team     = ! Product::has_per_member_pricing( $product );
			$subscription = $this->get_team_subscription( $team );

			if ( $subscription && $per_team && $change_value ) {

				$order_item = wcs_get_order_item( $this->get_subscription_item_id( $team ), $subscription );
				$quantity  += (int) $order_item->get_quantity();
			}
		}

		return $quantity;
	}


	/**
	 * Turns on proration for seat changes on teams tied to subscription length.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param bool $enable_proration whether to enable proration for seat changes
	 * @param Team $team the team object
	 * @param int $change_value the change value, based on the team's seat change mode
	 * @return bool
	 */
	public function enable_seat_change_proration_for_subscriptions( $enable_proration, $team, $change_value ) {

		if ( $team && $team instanceof Team && ( $plan = $team->get_plan() ) && 'subscription' === $plan->get_access_length_type() ) {
			$enable_proration = true;
		}

		return $enable_proration;
	}


	/**
	 * Sets the correct settings for calculating prorated payments on team seat changes.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Cart $cart the cart object
	 */
	public function set_subscriptions_settings_for_seat_change( $cart ) {

		if ( \WC_Subscriptions_Switcher::cart_contains_switches() ) {

			// loop over the items for safety, though we should only have one cart
			// item, since we clear the cart out before adding the seat change item.
			foreach( $cart->get_cart() as $cart_item_key => $cart_item ) {

				if ( ! isset( $cart_item['subscription_switch']['subscription_id'],
					$cart_item['team_meta_data']['_wc_memberships_for_teams_team_seat_change'],
					$cart_item['team_meta_data']['_wc_memberships_for_teams_team_id'] ) ) {
					continue;
				}

				$team         = wc_memberships_for_teams_get_team( $cart_item['team_meta_data']['_wc_memberships_for_teams_team_id'] );
				$seat_change  = $cart_item['team_meta_data']['_wc_memberships_for_teams_team_seat_change'];
				$subscription = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );

				if ( $team && $team instanceof Team && $subscription && $subscription instanceof \WC_Subscription ) {

					// filter the setting to prorate subscription price during switching
					add_filter( 'option_woocommerce_subscriptions_apportion_recurring_price', function( $value, $option_name ) use ( $team, $seat_change ) {

						return Seat_Manager::should_prorate_seat_change( $team, $seat_change ) ? 'yes-upgrade' : $value;
					}, 10, 2 );

					// disable sign up fees on all switches -- we have to handle this on our own
					add_filter( 'option_woocommerce_subscriptions_apportion_sign_up_fee', function( $value, $option_name ) { return 'no'; }, 10, 2 );
				}
			}
		}
	}


	/**
	 * Adds subscriptions-specific information to the seat change message.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param string $seat_change_message seat change notice message
	 * @param \WC_Order $order the order object
	 * @param \WC_Order_Item $order_item the order item object
	 * @param Team $team the team object
	 * @return string
	 */
	public function set_subscription_seat_change_notice_message( $seat_change_message, $order, $order_item, $team ) {

		if ( $subscription = $this->get_team_subscription( $team ) ) {

			$next_payment_timestamp    = $subscription->get_time( 'next_payment' );
			$formatted_recurring_total = $subscription->get_formatted_order_total();
			$subscription_status_text  = 0 === $next_payment_timestamp ? __( 'subscription will end', 'woocommerce-memberships-for-teams' ) : __( 'next payment is', 'woocommerce-memberships-for-teams' );
			$date_timestamp            = 0 === $next_payment_timestamp ? $subscription->get_time( 'end' ) : $next_payment_timestamp;

			$seat_change_message .= sprintf(
				/* translators: Placeholders: %1$s - new recurring total, %2$s - subscription status text, %3$s - next payment date */
				__( ' Your new recurring total is %1$s, and your %2$s on %3$s.', 'woocommerce-memberships-for-teams' ),
				$formatted_recurring_total,
				$subscription_status_text,
				date( 'F j, Y', $date_timestamp )
			);
		}

		return $seat_change_message;
	}


	/**
	 * Disables the default subscription switching handling of Memberships if we
	 * are performing a seat change.
	 *
	 * Default Memberships behavior is to cancel the membership if a
	 * subscription switch is detected.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Subscription $subscription the subscription object
	 * @param array|\WC_Order_Item_Product $new_order_item the new order item (switching to)
	 * @param array $old_order_item the old order item (switching from)
	 */
	public function disable_default_memberships_switch_handling( $subscription, $new_order_item, $old_order_item ) {

		$seat_change_count = $new_order_item->get_meta( '_wc_memberships_for_teams_team_seat_change', true );

		if ( $seat_change_count && 0 < $seat_change_count ) {

			$memberships_subscriptions_integration = wc_memberships()->get_integrations_instance()->get_subscriptions_instance();

			remove_action( 'woocommerce_subscriptions_switched_item', array( $memberships_subscriptions_integration, 'handle_subscription_switches' ), 10 );
		}
	}


	/**
	 * Maybe corrects the old price per day calculated during a seat change.
	 *
	 * When multiple subscription switches take place within the same billing
	 * period, Subscriptions always pulls the previous pricing from the
	 * renewal or purchase rather than the latest switch, to guard against
	 * 'not yet paid amounts'. We need to get the last full price taking the
	 * most recent switching into account, so we override that value here if this
	 * isn't the first subscription to take place during this billing cycle.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param float $old_price_per_day price per day from most recent order or renewal
	 * @param \WC_Subscription $subscription
	 * @param array $cart_item subscription cart item array
	 * @param string $old_recurring_total recurring total from the most recent order or renewal
	 * @param int $days_in_old_cycle number of days in the current billing cycle
	 * @return float
	 */
	public function maybe_correct_old_price_per_day( $old_price_per_day, $subscription, $cart_item, $old_recurring_total, $days_in_old_cycle ) {

		$last_order_id = $subscription->get_last_order( 'ids', 'any' );
		$last_switch   = $subscription->get_last_order( 'all', 'switch' );

		if ( $last_switch instanceof \WC_Order && $last_order_id === $last_switch->get_id() ) {

			$product_id = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : null;
			$product_id = isset( $cart_item['variation_id'] ) && ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $product_id;

			foreach ( $last_switch->get_items() as $last_order_item ) {

				if ( wcs_get_canonical_product_id( $last_order_item ) === $product_id ) {

					$old_recurring_total = $subscription->get_total( 'edit' );
					break;
				}
			}

			$old_price_per_day = $days_in_old_cycle > 0 ? $old_recurring_total / $days_in_old_cycle : $old_recurring_total;
		}

		return $old_price_per_day;
	}


	/**
	 * Disables a subscription limitation if the product is tied to team access.
	 *
	 * Before doing so, it checks that the current user is the owner of the team, assuming the intention is to change seats.
	 *
	 * @internal
	 *
	 * @since 1.1.4
	 *
	 * @param string $limitation whether the limitation is in place (default 'no')
	 * @param \WC_Product $product the subscription product
	 * @return string yes or no
	 */
	public function handle_subscription_product_limitation( $limitation, $product ) {

		if ( 'no' !== $limitation && $this->ignore_subscription_product_limitation( $product ) ) {

			$current_user = get_current_user_id();

			// get only teams that the current user is owner of
			if ( $current_user > 0 && ( $teams = wc_memberships_for_teams_get_teams( $current_user, [ 'role' => 'owner' ] ) ) ) {

				foreach ( $teams as $team ) {

					// the product with a limitation is the same as the one linked to team access
					if ( (int) $product->get_id() === (int) $team->get_product_id() ) {

						$limitation = 'no';
						break;
					}
				}
			}
		}

		return $limitation;
	}


	/**
	 * Determines whether a subscription product limitation should be ignored to allow a team owner to update seats.
	 *
	 * Helper method, do not open to public.
	 *
	 * @since 1.1.4
	 *
	 * @param \WC_Product $product subscription product, possibly related to team access
	 * @return bool
	 */
	private function ignore_subscription_product_limitation( $product ) {

		$ignore = false;

		if ( $product && WC()->session && \WC_Subscriptions_Product::is_subscription( $product ) && Product::has_team_membership( $product ) ) {

			// the request comes likely from the team area, to update seats
			if ( ! empty( $_REQUEST['seat_change_mode'] ) && 'none' !== $_REQUEST['seat_change_mode'] ) {
				$ignore     = true;
				WC()->session->set( 'ignore_subscription_product_limitation', $product->get_id() );
			// the request from the team area has already applied, we look for its trace in cart contents
			} else {
				$product_id = (int) WC()->session->get( 'ignore_subscription_product_limitation' );
				$ignore     = $product_id === $product->get_id();
			}
		}

		return $ignore;
	}


	/**
	 * Ensures to remove a flag to ignore subscription product limitations.
	 *
	 * @internal
	 *
	 * @since 1.1.4
	 */
	public function restore_subscription_product_limitation() {

		if ( WC()->session->get( 'ignore_subscription_product_limitation' ) ) {
			WC()->session->set( 'ignore_subscription_product_limitation', null );
		}
	}


	/**
	 * Adjusts the sign up fee for a subscription for a seat change, if needed.
	 *
	 * The \WC_Subscriptions_Switcher class takes care of most of the proration calculations needed,
	 * but does not offer a solution to a few scenarios which we need to account for here:
	 *
	 *   - Fixed-length/date plans should not prorate new seats that are added - we still want to use the
	 *     Subscriptions Switcher class to adjust the ongoing subscription billing, but we should override
	 *     the fees to correctly apply the full price of the additional seats here.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param \WC_Cart $cart the WooCommerce cart instance
	 */
	public function correct_seat_change_sign_up_fees( \WC_Cart $cart ) {

		if ( ! \WC_Subscriptions_Switcher::cart_contains_switches() ) {
			return;
		}

		foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {

			if ( ! isset( $cart_item['subscription_switch']['subscription_id'],
				$cart_item['team_meta_data']['_wc_memberships_for_teams_team_seat_change'],
				$cart_item['team_meta_data']['_wc_memberships_for_teams_team_id'] ) ) {
				continue;
			}

			$team = wc_memberships_for_teams_get_team( $cart_item['team_meta_data']['_wc_memberships_for_teams_team_id'] );

			if ( $team && $team instanceof Team && ( $plan = $team->get_plan() ) && in_array( $plan->get_access_length_type(), array( 'fixed', 'specific' ), true ) ) {

				$product_id = wcs_get_canonical_product_id( $cart_item );

				// look the product up rather than use the cart or subscription data, in case fees or prices have changed
				$product             = wc_get_product( $product_id );
				$product_price       = \WC_Subscriptions_Product::get_price( $product );
				$product_sign_up_fee = \WC_Subscriptions_Product::get_sign_up_fee( $product );

				$subscription      = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );
				$existing_item     = wcs_get_order_item( $cart_item['subscription_switch']['item_id'], $subscription );
				$existing_quantity = $existing_item['qty'];
				$new_quantity      = $cart_item['quantity'];
				$new_item_count    = $new_quantity - $existing_quantity;

				// no need to apply fees if we aren't adding any items
				if ( 1 > $new_item_count ) {
					continue;
				}

				// total amount to charge for new items added in this seat change
				$new_item_price = $new_item_count * $product_price;

				// total fees to add for new items added in this seat change
				$new_item_fees  = $new_item_count * $product_sign_up_fee;

				// all charges, item cost and fees, are represented as a single fee using
				// subscription switcher -- get that total here
				$fee_total = $new_item_price + $new_item_fees;

				// subscriptions will multiply this fee value by the new total
				// quantity for this item rather than the quantity of new additions
				// made in this seat change, and there doesn't seem to be a way
				// to change/disable this behavior, so we divide by the same value
				// in anticipation of the upcoming unnecessary multiplication
				$fee = (float) $fee_total / $new_quantity;

				wcs_set_objects_property( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'subscription_sign_up_fee', $fee, 'set_prop_only' );
			}
		}
	}


	/**
	 * Returns the most recent order item ID that created this team subscription.
	 *
	 * @since 1.1.0
	 *
	 * @param Team $team the team object
	 * @return int|null
	 * @throws \Exception
	 */
	public function get_subscription_item_id( $team ) {

		$item_id = null;

		if ( $team && $subscription = $this->get_team_subscription( $team ) ) {

			foreach( $subscription->get_items() as $line_item_id => $line_item ) {

				if ( $team->get_id() === (int) wc_get_order_item_meta( $line_item_id, '_wc_memberships_for_teams_team_id', true ) ) {
					$item_id = $line_item_id;
					break;
				}
			}
		}

		return $item_id;
	}


	/**
	 * Returns a team from cart item data if it is valid and has a valid subscription.
	 *
	 * @since 1.1.0
	 *
	 * @param array $cart_item_data cart item data
	 * @return Team|null team object if a valid team with a subscription is found, null otherwise
	 */
	private function get_subscription_team_from_cart_item_data( $cart_item_data ) {

		$team_id = isset( $cart_item_data['team_meta_data']['_wc_memberships_for_teams_team_id'] ) ? $cart_item_data['team_meta_data']['_wc_memberships_for_teams_team_id'] : null;

		if ( $team_id && ( $team = wc_memberships_for_teams_get_team( $team_id ) ) && $team instanceof Team && $this->has_team_subscription( $team ) ) {
			return $team;
		}

		return null;
	}


	/**
	 * Saves related subscription data when a team is created via a purchase.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param array $args
	 * @throws \Exception
	 */
	public function save_subscription_data( $team, $args ) {

		$product = wc_get_product( $args['product_id'] );

		// handle access from Subscriptions
		if ( $product && $this->is_subscription_product( $product ) ) {

			$subscription = wc_memberships_get_order_subscription( $args['order_id'], $product->get_id() );

			if ( $subscription ) {

				$previous_subscription_id = (int) $this->get_team_subscription_id( $team );
				$subscription_id          = (int) Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );

				update_post_meta( $team->get_id(), '_subscription_id', $subscription_id );

				// store team id on the subscription item
				if ( $team_uid = wc_get_order_item_meta( $args['item_id'], '_wc_memberships_for_teams_team_uid', true ) ) {

					foreach ( $subscription->get_items() as $item ) {

						if ( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_uid', true ) === $team_uid ) {
							wc_update_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id', $team->get_id() );
						}
					}
				}

				// finally, if this was a re-purchase of a cancelled subscription, make sure each user membership is
				// updated with the new subscription id and is re-activated
				if ( $previous_subscription_id && $previous_subscription_id !== $subscription_id ) {

					$this->update_team_user_memberships_subscription( $team, $subscription, $team->get_order(), $product );
				}
			}
		}
	}


	/**
	 * Updates related subscription data on resubscribe.
	 *
	 * A resubscribe order replaces a cancelled subscription with a new one.
	 *
	 * @internal
	 *
	 * @since 1.0.4
	 *
	 * @param \WC_Subscription $new_subscription the new subscription object
	 * @param \WC_Order $resubscribe_order the order that created a new subscription
	 */
	public function update_team_subscription_on_resubscribe( $new_subscription, $resubscribe_order ) {

		$new_order_id        = Framework\SV_WC_Order_Compatibility::get_prop( $resubscribe_order, 'id' );
		$new_subscription_id = Framework\SV_WC_Order_Compatibility::get_prop( $new_subscription, 'id' );
		$old_subscription_id = $new_subscription_id > 0 ? get_post_meta( $new_subscription_id, '_subscription_resubscribe', true ) : 0;
		$old_subscription    = $old_subscription_id > 0 ? wcs_get_subscription( $old_subscription_id ) : null;

		if ( $old_subscription && in_array( $old_subscription->get_status(), array( 'cancelled', 'pending-cancel' ), false ) ) {

			$existing_teams = $this->get_teams_from_subscription( $old_subscription_id );

			if ( ! empty( $existing_teams ) ) {

				foreach ( $existing_teams as $existing_team ) {

					// update the team's subscription link and the order link
					update_post_meta( $existing_team->get_id(), '_subscription_id', $new_subscription_id );
					update_post_meta( $existing_team->get_id(), '_order_id', $new_order_id );

					// also reactivate any cancelled memberships within the team's seats
					$this->update_team_user_memberships_subscription( $existing_team, $new_subscription, $resubscribe_order, $existing_team->get_product() );
				}
			}
		}
	}


	/**
	 * Updates a team's subscription data when the subscription is switched.
	 *
	 * This method updates a switched subscription's new line item with the new
	 * team ID that was generated during the switch, and marks the _old_ team
	 * as having been switched so we can alert the user.
	 *
	 * Also removes the _old_ team's link to the subscription.
	 *
	 * TODO: Eventually we want to properly support subscription switching, and
	 *       this handling shouldn't be needed when that happens. For now, this
	 *       helps avoid confusion a bit when a customer switches and a second
	 *       team is created {CW 2018-09-05}
	 *
	 * @internal
	 *
	 * @since 1.0.5
	 *
	 * @param \WC_Order $order order object
	 * @param \WC_Subscription $subscription subscription object
	 * @param int|string $new_line_item_id line item ID for the subscription being switched to
	 * @param int|string $old_line_item_id line item ID for the subscription being switched from
	 * @throws \Exception
	 */
	public function update_team_subscription_on_switch( $order, $subscription, $new_line_item_id, $old_line_item_id ) {

		$new_team = null;

		// the switched-to subscription line item should have a UID for the new team
		if ( $new_team_uid = wc_get_order_item_meta( $new_line_item_id, '_wc_memberships_for_teams_team_uid', true ) ) {

			foreach ( $order->get_items() as $item ) {

				// find the matching line item on the switch order
				if ( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_uid', true ) === $new_team_uid ) {

					// set the new team ID on the subscription item from the matching order item
					if ( $new_team = wc_memberships_for_teams_get_team( wc_get_order_item_meta( $item->get_id(), '_wc_memberships_for_teams_team_id' ) ) ) {
						wc_update_order_item_meta( $new_line_item_id, '_wc_memberships_for_teams_team_id', $new_team->get_id() );
					}
				}
			}
		}

		$new_team_id = $new_team ? $new_team->get_id() : null;
		$old_team_id = (int) wc_get_order_item_meta( $old_line_item_id, '_wc_memberships_for_teams_team_id' );

		if ( $new_team_id !== $old_team_id && $old_team = wc_memberships_for_teams_get_team( $old_team_id ) ) {

			// unlink the old team from the subscription being switched
			delete_post_meta( $old_team->get_id(), '_subscription_id' );

			// store the new team ID generated from the switch
			if ( $new_team ) {
				update_post_meta( $old_team->get_id(), '_subscription_switched_team_id', $new_team->get_id() );
			}
		}
	}


	/**
	 * Updates the user memberships in a team with a new subscription link.
	 *
	 * @since 1.0.4
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object
	 * @param \WC_Subscription $subscription subscription object
	 * @param \WC_Order|null $order order object
	 * @param \WC_Product|null $product subscription product object
	 */
	private function update_team_user_memberships_subscription( $team, $subscription, $order = null, $product = null ) {

		if ( $subscription instanceof \WC_Subscription ) {

			foreach ( $team->get_user_memberships() as $user_membership ) {

				// set the membership's subscription ID
				$subscription_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );
				$subscription_membership->set_subscription_id( Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' ) );

				$order_id = $order instanceof \WC_Order ? Framework\SV_WC_Order_Compatibility::get_prop( $order, 'id' ) : null;

				// if associated with an order
				if ( $order_id ) {

					$note = '';

					$subscription_membership->set_order_id( $order_id );

					if ( $product ) {

						$subscription_membership->set_product_id( $product->get_id() );

						/* translators: Placeholders: %1$s - subscription product name, %2%s - order number */
						$note = sprintf( __( 'Membership re-activated due to subscription re-purchase (%1$s, Order %2$s).', 'woocommerce-memberships-for-teams' ),
							$product->get_title(),
							'<a href="' . esc_url( admin_url( 'post.php?post=' . $order_id  . '&action=edit' ) ) .'" >' . esc_html( $order_id ) . '</a>'
						);
					}

					if ( $subscription_membership->has_status( array( 'pending', 'cancelled' ) ) ) {

						$subscription_membership->update_status( 'active', $note );
					}
				}
			}
		}
	}


	/**
	 * Removes a subscription link from the user memberships in a team.
	 *
	 * @since 1.0.5
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team team object
	 * @param \WC_Subscription $subscription subscription object
	 */
	private function remove_team_user_memberships_subscription( $team, $subscription ) {

		if ( $subscription instanceof \WC_Subscription && $core_integration = $this->get_core_integration() ) {

			foreach ( $team->get_user_memberships() as $user_membership ) {
				$core_integration->unlink_membership( $user_membership, $subscription );
			}
		}
	}


	/**
	 * Sets the related subscription data when a user membership is created for a team member.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team_Member $team_member the team member instance
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @param \WC_Memberships_User_Membership $user_membership the user membership instance
	 */
	public function adjust_team_member_user_membership_data( $team_member, $team, $user_membership ) {

		$subscription = $this->get_team_subscription( $team_member->get_team_id() );

		// handle subscription data when adding a new member to a subscription based team
		if ( $subscription ) {

			$subscription_id = Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
			$user_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $user_membership->post );

			$user_membership->set_subscription_id( $subscription_id );
			$user_membership->set_start_date( $team_member->get_team()->get_date() );

			// the following code is copy-paste from WC_Memberships_Integration_Subscriptions_Lifecycle::update_subscription_memberships() and could perhaps be abstracted in core {IT 2017-09-20}
			$integration = $this->get_core_integration();

			// if statuses do not match, update
			if ( ! $integration->has_subscription_same_status( $subscription, $user_membership ) ) {

				$subscription_status = $integration->get_subscription_status( $subscription );

				// special handling for paused memberships which might be put on free trial
				if ( 'active' === $subscription_status && 'paused' === $user_membership->get_status() ) {

					// get trial end timestamp
					$trial_end = $integration->get_subscription_event_time( $subscription, 'trial_end' );

					// if there is no trial end date or the trial end date is past and the Subscription is active, activate the membership...
					if ( ! $trial_end || current_time( 'timestamp', true ) >= $trial_end ) {
						$user_membership->activate_membership( __( 'Membership activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships-for-teams' ) );
					// ...otherwise, put the membership on free trial
					} else {
						$user_membership->update_status( 'free_trial', __( 'Membership free trial activated because WooCommerce Subscriptions was activated.', 'woocommerce-memberships-for-teams' ) );
						$user_membership->set_free_trial_end_date( date( 'Y-m-d H:i:s', $trial_end ) );
					}

				// all other membership statuses: simply update the status
				} else {

					$integration->update_related_membership_status( $subscription, $user_membership, $subscription_status );
				}
			}

			$plan = $team->get_plan();

			if ( $plan && $plan->is_access_length_type( 'subscription' ) && $integration->get_plans_instance()->grant_access_while_subscription_active( $plan->get_id() ) ) {

				$end_date = $integration->get_subscription_event_date( $subscription, 'end' );

			} else {

				$end_date = $team->get_membership_end_date( 'timestamp' );
			}

			// end date has changed
			if ( strtotime( $end_date ) !== $user_membership->get_end_date( 'timestamp' ) ) {
				$user_membership->set_end_date( $end_date );
			}

		// If the team the user is being added to has no subscription, check if the member being added has instead an existing membership tied to a subscription from another team:
		// this may be a niche occurrence when an admin wants to move a member of an expired subscription-tied team to a new manually-created team.
		} elseif ( $user_membership instanceof \WC_Memberships_Integration_Subscriptions_User_Membership && $user_membership->has_subscription() ) {

			// unlink the membership
			$this->get_core_integration()->unlink_membership( $user_membership, $user_membership->get_subscription_id() );

			// move to active status
			if ( $user_membership->is_expired() || $user_membership->is_cancelled() ) {
				$user_membership->update_status( 'active' );
			}
		}
	}


	/**
	 * Removes any user-supplied team field data from Subscription item's custom line item meta.
	 *
	 * Teams takes care of copying over the user-input itself, so this avoids the same meta from being
	 * added and displayed twice.
	 *
	 * @internal
	 *
	 * @see \SkyVerge\WooCommerce\Memberships\Teams\Cart::add_order_again_cart_item_team_data()
	 *
	 * @since 1.0.2
	 *
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param \WC_Order_Item_Product $item the order item to order again
	 * @return array associative array of name/value pairs of cart item data to set in the session
	 */
	public function remove_raw_cart_item_team_data( $cart_item_data, $item ) {

		$cart_item_key = isset( $cart_item_data['subscription_resubscribe'] ) ? 'subscription_resubscribe' : 'subscription_renewal';

		if ( ! empty( $cart_item_data[ $cart_item_key ] ) ) {

			$product = $item->get_product();

			// remove any user-input fields, so that they're not being added/displayed twice
			$fields = Product::get_team_user_input_fields( $product );

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $field ) {
					unset( $cart_item_data[ $cart_item_key ]['custom_line_item_meta'][ $key ] );
				}
			}
		}

		return $cart_item_data;
	}


	/**
	 * Adds subscription billing link to team actions in Teams Area.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions list of actions
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_billing_action( $actions, $team ) {

		if ( current_user_can( 'wc_memberships_for_teams_manage_team_settings', $team ) && $subscription = $this->get_team_subscription( $team ) ) {

			$actions = array_merge( array( 'billing' => array(
				'url'  => $subscription->get_view_order_url(),
				'name' => __( 'Billing', 'woocommerce-memberships-for-teams' ),
			) ), $actions );

			unset( $actions['renew'], $actions['cancel'] );
		}

		return $actions;
	}


	/**
	 * Adds next bill date row to a subscription-tied team in Team Status table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns list of table columns and their names
	 * @return array
	 */
	public function add_next_bill_column( $columns ) {

		return Framework\SV_WC_Helper::array_insert_after( $columns, 'team-created-date', array( 'team-next-bill-on' => __( 'Next Bill On', 'woocommerce-memberships-for-teams' ) ) );
	}


	/**
	 * Adds next bill date row to a subscription-tied team in Team Status table.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $team_details associative array of team details
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return array
	 */
	public function add_team_subscription_details( $team_details, $team ) {

		if ( $subscription = $this->get_team_subscription( $team ) ) {

			$team_details = Framework\SV_WC_Helper::array_insert_after(
				$team_details,
				'created-date',
				array( 'next-bill-date' => array(
					'label'   => __( 'Next Bill On', 'woocommerce-memberships-for-teams' ),
					'content' => $this->get_formatted_next_bill_date( $team ),
					'class'   => 'my-team-detail-team-next-bill-date',
				) )
			);
		}

		return $team_details;
	}


	/**
	 * Outputs the next bill date for a subscription-tied team.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 */
	public function output_next_bill_date( $team ) {
		echo $this->get_formatted_next_bill_date( $team );
	}


	/**
	 * Returns the formatted next bill date for a subscription-tied team.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return string
	 */
	public function get_formatted_next_bill_date( $team ) {

		if ( $subscription = $this->get_team_subscription( $team ) ) {
			$next_payment = $subscription->get_time( 'next_payment', 'site' );
		}

		if ( ! empty( $next_payment ) ) {
			$date = date_i18n( wc_date_format(), $next_payment );
		} else {
			$date = esc_html__( 'N/A', 'woocommerce-memberships-for-teams' );
		}

		return $date;
	}


	/**
	 * Outputs existing subscription cancellation notice and options on join team page.
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 */
	public function output_subscription_notice_and_options( $team ) {

		$user_id      = get_current_user_id();
		$subscription = $user_id ? $this->get_user_existing_subscription( $user_id, $team ) : null;

		// check if the user ID matches to understand if the member owns the subscription too or they won't be able to cancel it: ?>
		<?php if ( $subscription && $user_id === $subscription->get_user_id() && $subscription->has_status( 'active' ) ) : ?>

			<p class="woocommerce-info"><?php printf( esc_html__( 'You have an active subscription (%s) tied to your current membership. Would you like this subscription to be cancelled when joining the team?.', 'woocommerce-memberships-for-teams' ), '<a href="' . esc_url( $subscription->get_view_order_url() ) . '">' . sprintf( esc_html_x( '#%s', 'hash before order number', 'woocommerce-memberships-for-teams' ), esc_html( $subscription->get_order_number() ) ) . '</a>' ); ?></p>

			<?php woocommerce_form_field( 'cancel_existing_subscription', array(
				'label' => __( 'Cancel my existing subscription', 'woocommerce-memberships-for-teams' ),
				'type'  => 'checkbox'
			) ) ;?>

			<input
				type="hidden"
				name="existing_subscription_id"
				value="<?php echo esc_attr( $subscription->get_id() ); ?>"
			/>

		<?php endif; ?>

		<?php
	}


	/**
	 * Gets user's existing subscription for the given team's membership plan, if any.
	 *
	 * @since 1.0.2
	 *
	 * @param int $user_id the user id to get the subscription for
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @return false|null|\WC_Subscription
	 */
	private function get_user_existing_subscription( $user_id, $team ) {

		$existing_user_membership = $team->get_existing_user_membership( $user_id );

		if ( ! $existing_user_membership ) {
			return null;
		}

		$subscription_user_membership = new \WC_Memberships_Integration_Subscriptions_User_Membership( $existing_user_membership->post );

		return $subscription_user_membership->get_subscription();
	}


	/**
	 * Cancels an existing subscription for a membership plan after user joins a team for the same plan.
	 *
	 * @internal
	 *
	 * @since 1.0.2
	 *
	 * @param int $user_id id of the the user that joined the team
	 * @param \SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance
	 * @throws \Exception
	 */
	public function maybe_cancel_existing_subscription( $user_id, $team) {

		if ( $user_id && ! empty( $_POST['cancel_existing_subscription'] ) && ! empty( $_POST['existing_subscription_id'] ) ) {

			$subscription_id = (int) $_POST['existing_subscription_id'];
			$subscription    = wcs_get_subscription( $subscription_id );

			if ( $subscription && $user_id === $subscription->get_user_id() ) {

				/* translators: Placeholders: %s - team name */
				$subscription->update_status( 'cancelled', sprintf( esc_html__( 'Subscription cancelled because user joined team (%s).', 'woocommerce-memberships-for-teams' ), $team->get_name()) );

				$message = sprintf( esc_html__( 'Your existing subscription (%s) has been cancelled.', 'woocommerce-memberships-for-teams' ), '<a href="' . esc_url( $subscription->get_view_order_url() ) . '">' . sprintf( esc_html_x( '#%s', 'hash before order number', 'woocommerce-memberships-for-teams' ), esc_html( $subscription->get_order_number() ) ) . '</a>' );

				wc_add_notice( $message, 'notice' );
			}
		}
	}


	/**
	 * Disables Membership Ending Soon emails for teams tied to a subscription.
	 *
	 * Currently, a subscription cannot be renewed before its expiration date.
	 *
	 * TODO however this could change in the future if Subscriptions introduces early renewals {FN 2017-04-04}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param bool $is_enabled whether the email is enabled in the first place
	 * @param int|\SkyVerge\WooCommerce\Memberships\Teams\Team $team the team instance which could be tied to a subscription
	 * @return bool
	 */
	public function skip_ending_soon_emails( $is_enabled, $team ) {

		if ( $is_enabled ) {

			if ( is_numeric( $team ) ) {
				$team = wc_memberships_for_teams_get_team( $team );
			}

			// if it's linked to a subscription, skip
			if ( $team && $subscription_id = $this->get_team_subscription_id( $team ) ) {
				$is_enabled = false;
			}
		}

		return $is_enabled;
	}


	/**
	 * Returns Teams from a Subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Subscription $subscription Subscription post object or ID
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team[] array of team objects or empty array, if none found
	 */
	public function get_teams_from_subscription( $subscription ) {

		$teams = array();

		if ( is_numeric( $subscription ) ) {
			$subscription_id = (int) $subscription;
		} elseif ( is_object( $subscription ) ) {
			$subscription_id = (int) Framework\SV_WC_Order_Compatibility::get_prop( $subscription, 'id' );
		}

		if ( ! empty( $subscription_id ) ) {

			$team_posts = get_posts( array(
				'post_type'        => 'wc_memberships_team',
				'post_status'      => 'any',
				'nopaging'         => true,
				'suppress_filters' => 1,
				'meta_query'       => array(
					array(
						'key'   => '_subscription_id',
						'value' => $subscription_id,
						'type' => 'numeric',
					),
				) )
			);

			foreach ( $team_posts as $team_post ) {

				$team = wc_memberships_for_teams_get_team( $team_post );

				if ( $team ) {
					$teams[ $team->get_id() ] = $team;
				}
			}
		}

		return $teams;
	}


	/**
	 * Checks if a product is a subscription product or not
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product instance
	 * @return bool
	 */
	public function is_subscription_product( $product ) {

		$is_subscription = false;

		// by using Subscriptions method we can account for custom subscription product types
		if ( is_callable( '\WC_Subscriptions_Product::is_subscription' ) ) {
			$is_subscription = \WC_Subscriptions_Product::is_subscription( $product );
		}

		return $is_subscription || $product->is_type( array( 'subscription', 'variable-subscription', 'subscription_variation' ) );
	}


	/**
	 * Returns the core Subscriptions integration class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_Integration_Subscriptions instance
	 */
	private function get_core_integration() {
		return wc_memberships()->get_integrations_instance()->get_subscriptions_instance();
	}


	/**
	 * Separates regular team products from subscription-based team products in edit plan screen.
	 *
	 * TODO remove this method by version 2.0.0 or by December 2019 {FN 2018-06-28}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated since 1.0.4
	 *
	 * @param \WC_Product[] $products array of team products
	 * @param int $plan_id membership plan id
	 * @return \WC_Product[]
	 */
	public function adjust_membership_plan_team_products( $products, $plan_id ) {

		_deprecated_function( 'SkyVerge\WooCommerce\Memberships\Teams\Integrations\Subscriptions::adjust_membership_plan_team_products()', '1.10.4' );

		return $products;
	}


}
