<?php

class B3_Router {

	/**
	 * Plugin instance.
	 * @var WP_JSON_Server
	 */
	protected $server;

	/**
	 * Supported HTTP method verbs
	 * @var array
	 */
	protected $supported_methods = array(
		'GET',
		'POST',
		'PUT',
		'DELETE',
		'HEAD',
		'PATCH',
		'OPTIONS',
	);

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
	 * @param  string $filename  Route configuration file name.
	 * @param  string $namespace Routes namespace.
	 *
	 * @todo Parse additional route arguments and types.
	 */
	protected function parse_conf( $filename, $namespace ) {
		$routes  = file( $filename );
		$methods = implode( '|', $this->supported_methods );

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
	 * Parses callback information the configuration file.
	 *
	 * Accepted formats:
	 *
	 * - `{{instance class name}}->{{method name}}`: Instance method.
	 * - `{{class name}}::{{method name}}`:          Class (static) method.
	 * - `{{function name}}`:                        Function.
	 *
	 * These methods and functions must be able to take a request object as
	 * their sole parameter.
	 *
	 * @param  string   $callback Callback description.
	 * @return callable           Corresponding PHP callable.
	 */
	protected function parse_callback( $callback ) {

		if ( strpos( $callback, '->' ) ) {
			list( $class, $method ) = explode( '->', $callback );
			$instance = $this->server->controllers()->register( $class );

			return method_exists( $instance, $method ) ? array( $instance, $method ) : null;
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
