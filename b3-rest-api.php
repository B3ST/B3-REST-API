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

require_once dirname( __FILE__ ) . '/resources/B3_API.php';

class B3_JSON_REST_API {

	const VERSION = '0.2.0-alpha';

	/**
	 * Unique identifier for the B3 REST API plugin.
	 * @var string
	 */
	protected $plugin_slug = 'b3-rest-api';

	/**
	 * Plugin instance.
	 * @var B3_JSON_REST_API
	 */
	protected static $instance = null;

	/**
	 * WP API server.
	 * @var WP_JSON_ResponseHandler
	 */
	protected $server;

	/**
	 * Resources provided by this extension.
	 * @var array
	 */
	protected $resources = array();

	/**
	 * Plugin constructor.
	 *
	 * @fixme JIBBERS CRABST WHY AM I CALLING ADD_ACTION() IN A CONSTRUCTOR
	 */
	public function __construct() {

		$this->resources = array(
			'B3_Comment'  => null,
			'B3_Menu'     => null,
			'B3_Post'     => null,
			'B3_Settings' => null,
			'B3_Sidebar'  => null,
		);

		add_action( 'init', array( $this, 'init' ), 99 );
	}

	/**
	 * Retrieve or create an instance of the plugin to be used by
	 * WordPress.
	 *
	 * It is NOT my intention for this to be a singleton, which is
	 * why the constructor is exposed and additonal instances may be
	 * created (for testing, etc.)
	 *
	 * @return B3_JSON_REST_API Plugin instance.
	 */
	public static function get_instance() {

		if ( null === static::$instance ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * Retrieve this plugin slug.
	 *
	 * @return string Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Initializes the plugin.
	 *
	 * Called by the `init` action.
	 */
	public function init() {
		B3_Loader::ready();

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		// Setup internationalization support:
		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

		add_action( 'wp_json_server_before_serve', array( $this, 'init_router' ), 99, 0 );

		add_action( 'wp_json_server_before_serve', array( $this, 'default_filters' ), 10, 1 );
	}

	/**
	 * Initialize the router.
	 */
	public function init_router() {
		$router = new B3_Router( $this );
		$router->init();

		$posts_controller = $router->get_controller( 'B3_Posts_Controller' );

		add_filter( 'json_prepare_post', array( $posts_controller, 'json_prepare_post' ), 99, 3 );
	}

	/**
	 * Hooks B3 extensions to the WP API.
	 *
	 * Called by the `wp_json_server_before_serve` action.
	 *
	 * @param  WP_JSON_Server $server WP API response handler.
	 *
	 * @todo Get rid of this method when we're done moving to the new controllers.
	 */
	public function default_filters( WP_JSON_Server $server ) {
		$this->server = $server;
		foreach ( $this->resources as $class => $resource ) {
			include_once dirname( __FILE__ ) . '/resources/' . $class . '.php';
			$this->resources[ $class ] = $resource = new $class( $server );
			add_filter( 'json_endpoints', array( $resource, 'register_routes' ), 10, 1 );
		}
	}

	/**
	 * Generates a REST API error.
	 *
	 * @param  string   $code    Error code.
	 * @param  string   $message Error message.
	 * @param  int      $status  HTTP status code (default: 500).
	 * @return WP_Error          Error object.
	 */
	public static function error( $code, $message, $status = 500 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

}

add_action( 'plugins_loaded', array( 'B3_JSON_REST_API', 'get_instance' ) );
