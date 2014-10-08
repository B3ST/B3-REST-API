<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Implements the Base API super class.
 *
 * @abstract
 */
abstract class B3_API {

	/**
	 * WP API server.
	 * @var WP_JSON_ResponseHandler
	 */
	protected $server;

	/**
	 * Resource handler constructor.
	 *
	 * @param WP_JSON_ResponseHandler $server WP API response handler.
	 */
	public function __construct( WP_JSON_ResponseHandler $server ) {
		$this->server = $server;
	}

	/**
	 * Register routes to the resources exposed by this endpoint.
	 *
	 * This method must be defined by B3_API subclasses.
	 *
	 * @abstract
	 * @param  array $routes Route array.
	 * @return array         Modified route array.
	 */
	abstract public function register_routes( $routes );
}
