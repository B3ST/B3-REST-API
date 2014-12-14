<?php

class B3_Controller_Registry {

    /**
     * [$server description]
     * @var B3_Server
     */
    protected $server;

    /**
     * [$controllers description]
     * @var array
     */
    protected $controllers = array();

    /**
     * [__construct description]
     * @param B3_Server $server [description]
     */
    public function __construct( B3_Server $server ) {
        $this->server = $server;
    }

    /**
     * [register description]
     * @param  [type] $class [description]
     * @return [type]        [description]
     */
    public function register( $class ) {
        if ( ! class_exists( $class ) ) {
            return false;
        }

        if ( ! isset( $this->controllers[ $class ] ) ) {
            $this->controllers[ $class ] = new $class;
        }

        return $this->controllers[ $class ];
    }

    /**
     * Retrieves controller instances.
     *
     * @param  string $class Controller class name.
     *
     * @return WP_JSON_Controller|array Controller instance or instances.
     */
    public function get( $class = null ) {
        if ( $class === null ) {
            return $this->controllers;
        }

        return isset( $this->controllers[ $class ] ) ? $this->controllers[ $class ] : null;
    }

}
