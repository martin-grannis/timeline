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
 * Invitation class. Represents an invitation to join a team.
 *
 * @since 1.0.0
 */
class Invitation {


	/** @var int invitation id */
	private $id;

	/** @var string invitation recipient email */
	private $email;

	/** @var int team id */
	private $team_id;

	/** @var string invitation token */
	private $token;

	/** @var int invitation sender (user) id */
	private $sender_id;

	/** @var string team member role for the invited user */
	private $member_role;

	/** @var string invitation (post) status */
	private $status;

	/** @var string invitation creation date */
	private $date;

	/** @var string invitation creation date in UTC */
	private $date_gmt;

	/** @var \WP_Post invitation post object */
	private $post;

	/** @var \SkyVerge\WooCommerce\Memberships\Teams\Team team object */
	private $team;


	/**
	 * Sets up the invitation instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_Post $id invitation id or post object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function __construct( $id ) {

		if ( is_numeric( $id ) ) {
			$this->post = get_post( $id );
		} elseif ( $id instanceof \WP_Post ) {
			$this->post = $id;
		}

		if ( ! $this->post || 'wc_team_invitation' !== $this->post->post_type ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid id or post', 'woocommerce-memberships-for-teams' ) );
		}

		$this->id          = $this->post->ID;
		$this->email       = $this->post->post_title;
		$this->team_id     = $this->post->post_parent;
		$this->token       = $this->post->post_password;
		$this->sender_id   = $this->post->post_author;
		$this->member_role = $this->post->post_mime_type; // mime type has been repurposed to store the role
		$this->date        = $this->post->post_date;
		$this->date_gmt    = $this->post->post_date_gmt;
		$this->status      = $this->post->post_status;
	}


	/**
	 * Returns the invitation id.
	 *
	 * @since 1.0.0
	 *
	 * @return int invitation ID
	 */
	public function get_id() {
		return $this->id;
	}


	/**
	 * Returns the invitation date in site's timezone.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string
	 */
	public function get_local_date( $format = 'mysql' ) {
		return wc_memberships_format_date( $this->date, $format );
	}


	/**
	 * Returns the invitation date in UTC.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format optional, defaults to 'mysql'
	 * @return string
	 */
	public function get_date( $format = 'mysql' ) {
		return wc_memberships_format_date( $this->date_gmt, $format );
	}


	/**
	 * Returns the email of the invitation recipient.
	 *
	 * @since 1.0.0
	 *
	 * @return string recipient's email
	 */
	public function get_email() {
		return $this->email;
	}


	/**
	 * Returns the invitation recipient user.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User|false user instance or false if no user account exists for the recipient's email
	 */
	public function get_user() {
		return get_user_by( 'email', $this->email );
	}


	/**
	 * Returns the name of the invited user, or email of recipient if not available.
	 *
	 * @since 1.0.0
	 *
	 * @return string user's name or recipient's email
	 */
	public function get_name() {

		if ( $user = $this->get_user() ) {
			$name = $user->display_name;
		} else {
			$name = $this->get_email();
		}

		return $name;
	}


	/**
	 * Returns the sender user instance.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_User|false user instance or false on failure
	 */
	public function get_sender() {
		return get_userdata( $this->get_sender_id() );
	}


	/**
	 * Returns the sender user id.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null sender user id or null if not set
	 */
	public function get_sender_id() {
		return $this->sender_id;
	}


	/**
	 * Returns the team id.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null team id or null if not set
	 */
	public function get_team_id() {
		return $this->team_id;
	}


	/**
	 * Returns the team.
	 *
	 * @since 1.0.0
	 *
	 * @return \SkyVerge\WooCommerce\Memberships\Teams\Team|false team instance or false on failure
	 */
	public function get_team() {

		if ( ! isset( $this->team ) ) {
			$this->team = wc_memberships_for_teams_get_team( $this->team_id );
		}

		return $this->team;
	}


	/**
	 * Returns the invitation team's plan.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_Membership_Plan|false membership plan instance or false on failure
	 */
	public function get_plan() {
		return $this->team ? $this->team->get_plan() : false;
	}


	/**
	 * Returns the role to be assigned to the invited user.
	 *
	 * TODO: there is a dicrepancy in method names between Invitation::get_member_role() and Team_Member::get_role()
	 * - my reasoning was that the invitation itself has no role, it's the roel of the would-be member, but I'd levae this opne for discussion {IT 2017-09-27}
	 *
	 * @since 1.0.0
	 *
	 * @param string $return (optional) set to 'label' to return the role label instead
	 * @return string role
	 */
	public function get_member_role( $return = null ) {

		$role = $this->member_role;

		if ( 'label' === $return ) {
			$roles = wc_memberships_for_teams_get_team_member_roles();
			$role  = ! empty( $roles[ $role ] ) ? $roles[ $role ] : $role;
		}

		return $role;
	}


	/**
	 * Sets the role to be assigned to the invited user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $role the role to set
	 * @return string role
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function set_member_role( $role = 'member' ) {

		if ( ! wc_memberships_for_teams_is_valid_team_member_role( $role ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid role', 'woocommerce-memberships-for-teams' ) );
		}

		wp_update_post( array(
			'ID'             => $this->id,
			'post_mime_type' => $role,
		) );

		$this->member_role = $role;
		$this->post->post_mime_type = $role;

		return $role;
	}


	/**
	 * Returns the invitation token, generating it, if necessary.
	 *
	 * @since 1.0.0
	 *
	 * @return string the invitation token
	 */
	public function get_token() {

		if ( ! $this->token ) {
			$this->token = Plugin::generate_token();
		}

		return $this->token;
	}


	/**
	 * Returns the public, unique url for accepting the invitation.
	 *
	 * @since 1.0.0
	 *
	 * @return string the invitation accept url
	 */
	public function get_accept_url() {

		$token = 'i_' . $this->get_token();

		return $this->get_team()->get_registration_url( $token );
	}


	/**
	 * Sets the invitation status.
	 *
	 * @since 1.0.0
	 * @param string $new_status status to change the order to, no internal wcmti- prefix is required
	 */
	public function set_status( $new_status ) {

		// standardise status names
		$new_status = 0 === strpos( $new_status, 'wcmti-' ) ? substr( $new_status, 6 ) : $new_status;
		$old_status = $this->get_status();

		// get valid statuses
		$valid_statuses = wc_memberships_for_teams_get_invitation_statuses();

		// only update if they differ - and ensure post_status is a 'wcmti' status.
		if ( $new_status !== $old_status && array_key_exists( 'wcmti-' . $new_status, $valid_statuses ) ) {

			// TODO: consider adding a note to the invitation here {IT 2017-09-14}

			// update the invitation
			wp_update_post( array(
				'ID'          => $this->id,
				'post_status' => 'wcmti-' . $new_status,
			) );

			$this->status            = 'wcmti-' . $new_status;
			$this->post->post_status = 'wcmti-' . $new_status;
		}
	}


	/**
	 * Returns the invitation status.
	 *
	 * Note: trims the `wcmti-` internal prefix from the returned status.
	 *
	 * @since 1.0.0
	 *
	 * @return string status slug
	 */
	public function get_status() {
		return 0 === strpos( $this->status, 'wcmti-' ) ? substr( $this->status, 6 ) : $this->status;
	}


	/**
	 * Checks if the invitation has the given status.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $status single status or array of statuses
	 * @return bool
	 */
	public function has_status( $status ) {

		$has_status = ( ( is_array( $status ) && in_array( $this->get_status(), $status, true ) ) || $this->get_status() === $status );

		/**
		 * Filters whether an invitation has a status.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $has_status whether the invitation has a certain status
		 * @param \SkyVerge\WooCommerce\Memberships\Teams\Invitation $invitation the invitation instance
		 * @param array|string $status one (string) status or any statuses (array) to check
		 */
		return (bool) apply_filters( 'woocommerce_memberships_for_teams_invitation_has_status', $has_status, $this, $status );
	}


	/**
	 * Sends the invitation email.
	 *
	 * @since 1.0.0
	 */
	public function send() {

		wc_memberships_for_teams()->get_emails_instance()->send_invitation_email( $this );
	}


	/**
	 * Accepts the invitation on behalf of a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WP_user $user_id user id or instance
	 * @param bool $add_member whether to add the member to the team upon accepting (default true)
	 * @return false|Team_Member team member instance or false on failure or when not adding the member directly
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function accept( $user_id, $add_member = true ) {

		if ( ! $this->has_status( 'pending' ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Cannot accept this invitation - it may have been revoked or already accepted.', 'woocommerce-memberships-for-teams' ) );
		}

		$user = is_numeric( $user_id ) ? get_userdata( $user_id ) : $user_id;

		if ( ! $user instanceof \WP_User ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Invalid user', 'woocommerce-memberships-for-teams' ) );
		}

		$team_member = $add_member ? $this->get_team()->add_member( $user_id, $this->get_member_role() ) : false;

		$this->set_status( 'accepted' );

		// record date and user id for history
		update_post_meta( $this->id, '_accepted_date',    current_time( 'mysql', true ) );
		update_post_meta( $this->id, '_accepted_user_id', $user_id );

		return $team_member;
	}


	/**
	 * Cancels the invitation.
	 *
	 * @since 1.0.0
	 *
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function cancel() {

		if ( ! $this->has_status( 'pending' ) ) {
			throw new Framework\SV_WC_Plugin_Exception( __( 'Cannot cancel non-pending invitation', 'woocommerce-memberships-for-teams' ) );
		}

		$this->set_status( 'cancelled' );

		update_post_meta( $this->id, '_cancelled_date', current_time( 'mysql', true ) );
	}

}
