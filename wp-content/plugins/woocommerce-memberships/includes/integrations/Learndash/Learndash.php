<?php
/**
 * WooCommerce Memberships
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to https://docs.woocommerce.com/document/woocommerce-memberships/ for more information.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Memberships\Integrations;

defined( 'ABSPATH' ) or exit;

/**
 * Integration class for LearnDash plugin.
 *
 * @since 1.12.3
 */
class Learndash {


	/**
	 * Constructor.
	 *
	 * @since 1.12.3
	 */
	public function __construct() {

		add_filter( 'learndash_content', array( $this, 'learndash_restricted_content' ), 1, 2 );
	}


	/**
	 * Filters the LearnDash course content for members when restricted.
	 *
	 * Helps us hide the course overview and status, too.
	 *
	 * @since 1.12.3
	 *
	 * @param string $content the learner content
	 * @param \WP_Post $post the post object
	 * @return string updated content
	 */
	public function learndash_restricted_content( $content, $post ) {

		$user_id = get_current_user_id();
		$post_id = $post instanceof \WP_Post ? $post->ID : get_the_ID(); // this filter runs in the loop

		// bail if we don't have a post ID or the post is public
		if ( ! $post_id || ! wc_memberships_is_post_content_restricted( $post_id ) ) {
			return $content;
		}

		if ( ! wc_memberships_user_can( $user_id, 'view', array( 'post' => $post_id ) ) ) {

			$message_type = 'restricted';

			if ( ! current_user_can( 'wc_memberships_view_restricted_post_content', $post_id ) ) {
				$message_type = 'restricted';
			} elseif ( ! current_user_can( 'wc_memberships_view_delayed_post_content', $post_id ) ) {
				$message_type = 'delayed';
			}

			$message_code = "content_{$message_type}";
			$args         = array(
				'access_time' => wc_memberships()->get_capabilities_instance()->get_user_access_start_time_for_post( $user_id, $post_id ),
				'code'        => $message_code,
				'context'     => 'notice', // use this to avoid recursion issues with excerpts from applying the_content filter
				'post'        => $post,
				'post_id'     => $post_id,
			);

			$message = \WC_Memberships_User_Messages::get_message_html( $message_code, $args );

			ob_start();

			?>
			<div class="woocommerce">
				<div class="woocommerce-info wc-memberships-restriction-message wc-memberships-message wc-memberships-content-restricted-message">
					<?php echo wp_kses_post( $message ); ?>
				</div>
			</div>
			<?php

			$content = ob_get_clean();
		}

		return $content;
	}


}
