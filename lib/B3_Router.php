<?php

class B3_Router {

	/**
	 * Plugin instance.
	 * @var WP_API_Server
	 */
	protected $server;

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
	 * [__construct description]
	 * @param WP_JSON_Server $server    WP-API server instance.
	 * @param string 		 $file      [description]
	 * @param string 		 $namespace [description]
	 */
	public function __construct( WP_JSON_Server $server, $conf = '' ) {
		$this->server = $server;
		$this->conf   = realpath( $conf );
	}

	/**
	 * Read routes file and generate routes.
	 */
	public function init() {
		if ( empty( $this->conf ) || ! file_exists( $this->conf ) ) {
			return;
		}

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

			$instance      = $this->server->controllers->register( $class );
			$is_controller = method_exists( $instance, $method ) && $instance instanceOf WP_JSON_Controller;

			return $is_controller ? array( $instance, $method ) : null;
		}

		if ( strpos( $callback, '::' ) ) {
			list( $class, $method ) = explode( '::', $callback );

			return method_exists( $class, $method ) ? array( $class, $method ) : null;
		}

		return function_exists( $callback ) ? $callback : null;
	}

	/**
	 * Add a route.
	 *
	 * @param [type] $method   [description]
	 * @param [type] $pattern  [description]
	 * @param [type] $callback [description]
	 * @param array  $args     [description]
	 */
	protected function add_route( $method, $pattern, $callback, $args = array() ) {
		$args = array(
			'methods'  => $method,
			'callback' => $callback,
			'args'     => $args,
		);

		register_json_route( $this->namespace, $pattern, $args );
	}

}
