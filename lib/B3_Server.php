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
    public $controllers;

    /**
     * [$router description]
     * @var B3_Router
     */
    public $router;

    /**
     * [__construct description]
     * @param [type] $server [description]
     */
    public function __construct( $server ) {
        parent::__construct();

        $this->server = $server;
    }

}
