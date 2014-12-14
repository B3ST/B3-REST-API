<?php

class B3_Server extends WP_JSON_Server {

	/**
	 * WP API server.
	 * @var WP_JSON_Server
	 */
	protected $server;

	/**
	 * B3 controller registry.
	 * @var B3_Controller_Registry
	 */
	protected $controllers;

	/**
	 * [__construct description]
	 * @param [type] $server [description]
	 */
	public function __construct( $server ) {
		parent::__construct();

		$this->server = $server;
	}

	/**
	 * Controllers accessor.
	 *
	 * @param  B3_Controller_Registry|null $controllers [description]
	 * @return B3_Controller_Registry                   [description]
	 */
	public function controllers( B3_Controller_Registry $controllers = null ) {
		if ( $controllers !== null ) {
			$this->controllers = $controllers;
		}

		return $this->controllers;
	}

}
