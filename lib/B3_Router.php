<?php

class B3_Router {

	/**
	 * Plugin instance.
	 * @var object
	 */
	protected $plugin;

	/**
	 * API namespace.
	 * @var string
	 */
	protected $namespace = 'b3';

	/**
	 * Routes configuration.
	 * @var string
	 */
	protected $conf = '';

	/**
	 * Controller instances.
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * [__construct description]
	 * @param object $plugin    []
	 * @param string $file      [description]
	 * @param string $namespace [description]
	 */
	public function __construct( $plugin, $conf = '', $namespace = '' ) {
		$conf = ! empty( $conf ) && file_exists( $conf ) ? $conf : dirname( __FILE__ ) . '/../conf/routes';
		$this->plugin    = $plugin;
		$this->conf      = realpath( $conf );
		$this->namespace = ! empty( $namespace ) ? $namespace : $this->namespace;
	}

	/**
	 * Read routes file and generate routes.
	 */
	public function init() {
		$routes  = file( $this->conf );
		$methods = implode( '|', array( 'GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS' ) );

		foreach ( $routes as $route ) {
			$matches = array();
			$match   = preg_match( "/^\s*($methods)\s+([^\s]+)\s+([^\s]+)/", $route, $matches );

			if ( ! $match ) {
				continue;
			}

			$method   = $matches[1];
			$pattern  = $matches[2];
			$callback = $this->parse_callback( $matches[3] );

			if ( empty( $callback ) ) {
				continue;
			}

			$this->add_route( $method, $pattern, $callback );
		}
	}

	/**
	 * [callback description]
	 * @param  [type]   $callback [description]
	 * @return callable           [description]
	 */
	protected function parse_callback( $callback ) {

		if ( strpos( $callback, '->' ) ) {
			list( $class, $method ) = explode( '->', $callback );
			$instance = $this->get_controller( $class );

			return method_exists( $instance, $method ) ? array( $instance, $method ) : false;
		}

		if ( strpos( $callback, '::' ) ) {
			list( $class, $method ) = explode( '::', $callback );
			return method_exists( $class, $method ) ? array( $class, $method ) : false;
		}

		return function_exists( $callback ) ? $callback : false;
	}

	/**
	 * [get_controller description]
	 * @param  [type] $class [description]
	 * @return [type]        [description]
	 */
	protected function get_controller( $class ) {
		if ( ! class_exists( $class ) ) {
			return false;
		}

		if ( empty( $this->controllers[ $class ] ) ) {
			$this->controllers[ $class ] = new $class;
		}

		return $this->controllers[ $class ];
	}

	/**
	 * Add a route.
	 *
	 * @param [type] $method   [description]
	 * @param [type] $route    [description]
	 * @param [type] $callback [description]
	 * @param array  $args     [description]
	 */
	protected function add_route( $method, $route, $callback, $args = array() ) {
		$args = array(
			'methods'  => $method,
			'callback' => $callback,
			'args'     => $args,
		);

		register_json_route( $this->namespace, $route, $args );
	}

}
