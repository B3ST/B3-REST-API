<?php

class B3_Router {

	/**
	 * Plugin instance.
	 * @var WP_JSON_Server
	 */
	protected $server;

	/**
	 * Router constructor.
	 *
	 * @param WP_JSON_Server $server WP-API server instance.
	 */
	public function __construct( WP_JSON_Server $server ) {
		$this->server = $server;
	}

	/**
	 * Read routes file and generate routes.
	 *
	 * The format is inspired by Play! Framework's routing configuration
	 * and supports most of its specification.
	 *
	 * @param string $filename  Configuration file path.
	 * @param string $namespace Routes namespace.
	 *
	 * @see https://www.playframework.com/documentation/2.0/ScalaRouting
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
	 * @param string $filename  Route configuration file name.
	 * @param string $namespace Routes namespace.
	 *
	 * @todo Parse additional route arguments and types.
	 */
	protected function parse_conf( $filename, $namespace ) {
		$routes   = file( $filename );

		$preg_name     = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
		$preg_method   = '(?P<method> GET | POST | PUT | DELETE | HEAD | PATCH | OPTIONS )';
		$preg_pattern  = '(?P<pattern> [^\s]+ )';
		$preg_callback = "(?P<callback> $preg_name ((::|->) $preg_name)? )";

		$preg_conf     = "/^ \s* $preg_method \s+ $preg_pattern \s+ $preg_callback /x";

		foreach ( $routes as $route ) {

			$match = preg_match( $preg_conf, $route, $matches );

			if ( ! $match ) {
				continue;
			}

			$args     = array();
			$method   = $matches['method'];
			$pattern  = $this->parse_pattern( $matches['pattern'], $args );
			$callback = $this->parse_callback( $matches['callback'] );

			if ( empty( $callback ) ) {
				continue;
			}

			$this->add_route( $pattern, $callback, $method, $namespace, $args );
		}
	}

	/**
	 * Parse route pattern into a regular expression.
	 *
	 * Applies the following transformations:
	 *
	 * - `:param` is replaced with `(?P<param>[^/]+)`
	 * - `*param` is replaced with `(?P<param>.+)`
	 * - `$param<regex>` is replaced with `(?P<param>regex)`
	 *
	 * @param  string $route Route pattern.
	 * @return string        Route pattern regular expression.
	 */
	protected function parse_pattern( $route, $args = array() ) {
		$preg_name = '([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)';

		// Turn `:param` into a `[^\]+` regular expression
		$route = preg_replace( "/:$preg_name/i", '(?P<\1>[^/]+)', $route );

		// Turn `*param` into a `.+` regular expression
		$route = preg_replace( "/\*$preg_name/i", '(?P<\1>.+)', $route );

		// Turn `$param<regex>` into a `regex` regular expression
		$route = preg_replace( "/\\\$$preg_name\<([^>]+)\>/i", '(?P<\1>\2)', $route );

		return $route;
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
	 * @param string   $pattern   [description]
	 * @param callable $callback  [description]
	 * @param string   $method    Accepted request method (defaults to 'GET').
	 * @param string   $namespace Endpoint namespace (defaults to 'b3').
	 * @param array    $args      Endpoint arguments (defaults to empty).
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
