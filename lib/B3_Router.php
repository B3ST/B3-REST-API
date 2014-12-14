<?php

class B3_Router {

	/**
	 * Plugin instance.
	 * @var WP_API_Server
	 */
	protected $server;

	/**
	 * [__construct description]
	 * @param WP_JSON_Server $server    WP-API server instance.
	 * @param string 		 $file      [description]
	 * @param string 		 $namespace [description]
	 */
	public function __construct( WP_JSON_Server $server ) {
		$this->server = $server;
	}

	/**
	 * Read routes file and generate routes.
	 *
	 * @param string $filename  Configuration file path.
	 * @param string $namespace Routes namespace.
	 */
	public function add_conf( $filename, $namespace = 'b3' ) {

		if ( empty( $filename ) ) {
			return;
		}

		$filename = realpath( $filename );

		if ( ! file_exists( $filename ) ) {
			return;
		}

		$this->parse_conf( $filename, $namespace );
	}

	/**
	 * Parse route configuration file.
	 *
	 * @param  string $file      Route configuration file name.
	 * @param  string $namespace Routes namespace.
	 */
	protected function parse_conf( $filename, $namespace ) {
		$routes  = file( $filename );
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

			$this->add_route( $pattern, $callback, $method, $namespace );
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
	 * @param [type] $pattern   [description]
	 * @param [type] $callback  [description]
	 * @param [type] $method    Accepted request method (defaults to 'GET').
	 * @param string $namespace Endpoint namespace (defaults to 'b3').
	 * @param array  $args      Endpoint arguments (defaults to empty).
	 */
	protected function add_route( $pattern, $callback, $method = 'GET', $namespace = 'b3', $args = array() ) {
		$args = array(
			'methods'  => $method,
			'callback' => $callback,
			'args'     => $args,
		);

		register_json_route( $namespace, $pattern, $args );
	}

}
