<?php
/**
 * Plugin Name: Teams for WooCommerce Memberships
 * Plugin URI: https://woocommerce.com/products/teams-woocommerce-memberships/
 * Description: Expands WooCommerce Memberships to sell memberships to teams, families, companies, or other groups!
 * Author: SkyVerge
 * Author URI: https://www.woocommerce.com/
 * Version: 1.1.4
 * Text Domain: woocommerce-memberships-for-teams
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2017-2019 SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   SkyVerge\WooCommerce\Memberships\Teams
 * @author    SkyVerge
 * @copyright Copyright (c) 2017-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * Woo: 2893267:f0b7ed22ec012e2e159ec30f5af5c1d1
 * WC requires at least: 3.0.4
 * WC tested up to: 3.6.2
 */

defined( 'ABSPATH' ) or exit;

// ensure required functions are loaded
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// queue plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'f0b7ed22ec012e2e159ec30f5af5c1d1', '2893267' );

// Required Action Scheduler library: this ensures the latest version is loaded regardless of what Memberships, Subscriptions or WooCommerce have loaded
// TODO: when WooCommerce 3.5 is the minimum required version we can stop bundling Action Scheduler as it's now part of core WooCommerce {FN 2018-10-09}
require_once( plugin_dir_path( __FILE__ ) . 'vendor/prospress/action-scheduler/action-scheduler.php' );


/**
 * WooCommerce Memberships for Teams loader.
 *
 * @since 1.0.0
 */
class WC_Memberships_For_Teams_Loader {


	/** minimum PHP version required by this plugin */
	const MIN_PHP_VERSION = '5.4.0';

	/** minimum WordPress version required by this plugin */
	const MIN_WP_VERSION = '4.6';

	/** minimum WooCommerce version required by this plugin */
	const MIN_WC_VERSION = '3.0.4';

	/** minimum Memberships version required by this plugin */
	const MIN_MEMBERSHIPS_VERSION = '1.9.4';

	/** SkyVerge plugin framework version used by this plugin */
	const FRAMEWORK_VERSION = '5.4.0';

	/** the plugin namespace */
	const PLUGIN_NAMESPACE = 'SkyVerge\WooCommerce\Memberships\Teams';

	/** the plugin name, for displaying notices */
	const PLUGIN_NAME = 'Teams for WooCommerce Memberships';

	/** @var \WC_Memberships_For_Teams_Loader single instance of the plugin loader */
	protected static $instance;

	/** @var array the admin notices to add */
	private $notices = array();


	/**
	 * Initializes the loader.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

		add_action( 'admin_init',    array( $this, 'check_environment' ) );
		add_action( 'admin_init',    array( $this, 'add_plugin_notices' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );

		// if environment checks pass, initialize the plugin
		if ( $this->is_environment_compatible() ) {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
		}
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot clone instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, sprintf( 'You cannot unserialize instances of %s.', get_class( $this ) ), '1.0.0' );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {

		if ( ! $this->is_plugins_compatible() ) {
			return;
		}

		$this->load_framework();

		// autoload plugin and vendor files
		$loader = require_once( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' );

		// register plugin namespace with autoloader
		$loader->addPsr4( self::PLUGIN_NAMESPACE . '\\', __DIR__ . '/src' );

		// include the functions file to make wc_memberships_for_teams() available
		require_once( plugin_dir_path( __FILE__ ) . 'src/Functions.php' );

		// fire it up!
		wc_memberships_for_teams();
	}


	/**
	 * Loads the base framework classes.
	 *
	 * @since 1.1.2
	 */
	private function load_framework() {

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\' . $this->get_framework_version_namespace() . '\\SV_WC_Plugin' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'vendor/skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php' );
		}

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\' . $this->get_framework_version_namespace() . '\\SV_WP_Async_Request' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'vendor/skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-async-request.php' );
		}

		if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginFramework\\' . $this->get_framework_version_namespace() . '\\SV_WP_Background_Job_Handler' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'vendor/skyverge/wc-plugin-framework/woocommerce/utilities/class-sv-wp-background-job-handler.php' );
		}
	}


	/**
	 * Gets the framework version in namespace form.
	 *
	 * @since 1.1.2
	 *
	 * @return string
	 */
	private function get_framework_version_namespace() {

		return 'v' . str_replace( '.', '_', $this->get_framework_version() );
	}


	/**
	 * Gets the framework version used by this plugin.
	 *
	 * @since 1.1.2
	 *
	 * @return string
	 */
	private function get_framework_version() {

		return self::FRAMEWORK_VERSION;
	}


	/**
	 * Checks the server environment and other factors and deactivates plugins as necessary.
	 *
	 * Based on http://wptavern.com/how-to-prevent-wordpress-plugins-from-activating-on-sites-with-incompatible-hosting-environments
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {

		if ( ! $this->is_environment_compatible() ) {

			$this->deactivate_plugin();

			wp_die( sprintf( '%1$s could not be activated: %2$s', self::PLUGIN_NAME, $this->get_environment_message() ) );
		}
	}


	/**
	 * Checks the environment on loading WordPress, just in case the environment changes after activation.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function check_environment() {

		if ( ! $this->is_environment_compatible() && is_plugin_active( plugin_basename( __FILE__ ) ) ) {

			$this->deactivate_plugin();

			$this->add_admin_notice( 'bad_environment', 'error', $this->get_environment_message() );
		}
	}


	/**
	 * Deactivates the plugin.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 */
	public function deactivate_plugin() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}


	/**
	 * Adds notices for out-of-date WordPress and/or WooCommerce/Memberships versions.
	 *
	 * @internal
	 *
	 * @since 1.1.2
	 */
	public function add_plugin_notices() {

		if ( ! $this->is_wp_compatible() ) {

			$this->add_admin_notice( 'update_wordpress', 'error', sprintf(
				'%s requires WordPress version %s or higher. Please %supdate WordPress &raquo;%s',
				'<strong>' . self::PLUGIN_NAME . '</strong>',
				self::MIN_WP_VERSION,
				'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">', '</a>'
			) );
		}

		if ( ! $this->is_wc_compatible() ) {

			$this->add_admin_notice( 'update_woocommerce', 'error', sprintf(
				'%s requires WooCommerce version %s or higher. Please %supdate WooCommerce &raquo;%s',
				'<strong>' . self::PLUGIN_NAME . '</strong>',
				self::MIN_WC_VERSION,
				'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">', '</a>'
			) );
		}

		if ( ! $this->is_memberships_compatible() ) {

			$this->add_admin_notice( 'update_woocommerce_memberships', 'error', sprintf(
				'%s requires WooCommerce Memberships version %s or higher. Please %supdate WooCommerce Memberships &raquo;%s',
				'<strong>' . self::PLUGIN_NAME . '</strong>',
				self::MIN_MEMBERSHIPS_VERSION,
				'<a href="' . esc_url( admin_url( 'update-core.php' ) ) . '">', '</a>'
			) );
		}
	}


	/**
	 * Adds an admin notice to be displayed when there are activation issues.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug notice slug
	 * @param string $class notice CSS class
	 * @param string $message notice message body
	 */
	public function add_admin_notice( $slug, $class, $message ) {

		$this->notices[ $slug ] = array(
			'class'   => $class,
			'message' => $message
		);
	}


	/**
	 * Displays any activation admin notices.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function admin_notices() {

		foreach ( $this->notices as $notice_key => $notice ) :

			?>
			<div class="<?php echo esc_attr( $notice['class'] ); ?>">
				<p><?php echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) ); ?></p>
			</div>
			<?php

		endforeach;
	}


	/**
	 * Checks if a plugin is active.
	 *
	 * @see SV_WC_Plugin::is_plugin_active() framework method but we can't use that yet, so the code here partially duplicates it
	 *
	 * @since 1.0.7-dev.1
	 *
	 * @param string $plugin plugin filename with full path
	 * @return bool
	 */
	private static function is_plugin_active( $plugin ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( $plugin, $active_plugins, false ) || array_key_exists( $plugin, $active_plugins );
	}


	/**
	 * Determines if WooCommerce core is active.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private static function is_woocommerce_active() {

		return self::is_plugin_active( 'woocommerce/woocommerce.php' );
	}


	/**
	 * Determines if WooCommerce Memberships is active.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private static function is_memberships_active() {

		return get_option( 'wc_memberships_is_active', false ) && self::is_plugin_active( 'woocommerce-memberships/woocommerce-memberships.php' );
	}


	/**
	 * Gets WordPress version.
	 *
	 * @since 1.1.2
	 *
	 * @return string semver
	 */
	private static function get_wordpress_version() {

		return get_bloginfo( 'version' );
	}


	/**
	 * Gets the installed WooCommerce version number.
	 *
	 * @since 1.1.2
	 *
	 * @return string semver
	 */
	private static function get_woocommerce_version() {

		if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
			$wc_version = WC_VERSION;
		} else {
			$wc_version = get_option( 'woocommerce_version', '0' );
		}

		return $wc_version;
	}


	/**
	 * Gets the installed WooCommerce Memberships version number.
	 *
	 * Unfortunately we can't use `wc_memberships()->get_version()` as it's too early to access that.
	 *
	 * @since 1.1.2
	 *
	 * @return string semver
	 */
	private static function get_memberships_version() {

		return get_option( 'wc_memberships_version', '0' );
	}


	/**
	 * Checks whether the current installed version of WordPress meets the requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private function is_wp_compatible() {

		return (bool) version_compare( self::get_wordpress_version(), self::MIN_WP_VERSION, '>=' );
	}


	/**
	 * Checks whether the current installed version of WooCommerce meets the requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private function is_wc_compatible() {

		return self::is_woocommerce_active() && (bool) version_compare( self::get_woocommerce_version(), self::MIN_WC_VERSION, '>=' );
	}


	/**
	 * Checks whether the current installed version of Memberships meets the requirements.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private function is_memberships_compatible() {

		return self::is_memberships_active() && (bool) version_compare( self::get_memberships_version(), self::MIN_MEMBERSHIPS_VERSION, '>=' );
	}


	/**
	 * Determines if the required plugins are compatible.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private function is_plugins_compatible() {

		return $this->is_wp_compatible() && $this->is_wc_compatible() && $this->is_memberships_compatible();
	}


	/**
	 * Determines if the server environment is compatible with Memberships.
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	private function is_environment_compatible() {

		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}


	/**
	 * Returns the message for display when the environment is incompatible with this plugin.
	 *
	 * @since 1.1.2
	 *
	 * @return string
	 */
	private function get_environment_message() {

		return sprintf( 'The minimum PHP version required for this plugin is %1$s. You are running %2$s.', self::MIN_PHP_VERSION, PHP_VERSION );
	}


	/**
	 * Returns the main WooCommerce Memberships for Teams loader instance.
	 *
	 * Ensures only one instance is/can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return \WC_Memberships_For_Teams_Loader
	 */
	public static function instance() {

		if ( null === self::$instance ) {

			self::$instance = new self();
		}

		return self::$instance;
	}


}

// fire it up!
WC_Memberships_For_Teams_Loader::instance();
