<?php
/**
 * B3 REST API Extensions
 *
 * A WordPress plugin that extends the WP API in order to support B3 and your
 * projects.
 *
 * @package    B3
 * @subpackage B3/API
 * @author     The B3 Team <b3@beebeebee.be>
 * @license    GPL-2.0+
 * @link       http://beebeebee.be
 *
 * @wordpress-plugin
 * Plugin Name:       B3 REST API Extensions
 * Version:           0.2.0-alpha
 * Description:       This plugin extends the WP-API in order to support B3 and
 *                    your projects.
 * Author:            The B3 Team
 * Author URI:        http://beebeebee.be
 * Plugin URI:        http://beebeebee.be
 * Text Domain:       b3-rest-api
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/B3ST/B3-REST-API
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once dirname( __FILE__ ) . '/loader.php';

class B3_JSON_REST_API {

	const VERSION = '0.2.0-alpha';

	/**
	 * Unique identifier for the B3 REST API plugin.
	 * @var string
	 */
	protected $plugin_slug = 'b3-rest-api';

	/**
	 * WP API server.
	 * @var WP_JSON_Server
	 */
	protected $server;

	/**
	 * B3 API router.
	 * @var B3_Router
	 */
	protected $router;

	/**
	 * Retrieve this plugin slug.
	 *
	 * @return string Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * [get_server description]
	 * @return [type] [description]
	 */
	public function get_server() {
		return $this->server;
	}

	public function load() {
		add_action( 'init', array( $this, 'init' ), 99 );
		add_action( 'init', array( $this, 'setup_i18n' ), 99 );
		add_action( 'wp_json_server_before_serve', array( $this, 'setup_server' ), 99, 1 );
	}

	/**
	 * Loads the plugin classes.
	 *
	 * Called by the `init` action.
	 */
	public function init() {
		B3_Loader::ready();
	}

	/**
	 * Setup internationalization support.
	 *
	 * Called by the `init` action.
	 *
	 * @return [type] [description]
	 */
	public function setup_i18n() {
		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Initialize the server.
	 *
	 * Called by the `wp_json_server_before_serve` action.
	 */
	public function setup_server( WP_JSON_Server $server ) {
		$server = new B3_Server( $server );
		$router = new B3_Router( $server );

		// Create controller registry:
		$server->controllers( new B3_Controller_Registry( $server ) );

		// Load configuration file:
		$router->add_conf( dirname( __FILE__ ) . '/conf/routes' );

		// Register post processing hooks:
		$posts_controller = $server->controllers()->get( 'B3_Posts_Controller' );
		add_filter( 'json_prepare_post', array( $posts_controller, 'json_prepare_post' ), 99, 3 );

		$this->server = $server;
	}

}

/**
 * Loads the plugin.
 */
function start_b3_json_rest_api() {
	$plugin = new B3_JSON_REST_API;
	add_action( 'plugins_loaded', array( $plugin, 'load' ) );
	return $plugin;
}

start_b3_json_rest_api();
