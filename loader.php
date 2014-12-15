<?php

class B3_Loader {

	/**
	 * Class files.
	 * @var array
	 */
	protected static $classes = array(
		'registry' => array(
			'B3_Controller_Registry',
		),
		'controllers' => array(
			'B3_Comments_Controller',
			'B3_Menus_Controller',
			'B3_Posts_Controller',
			'B3_Settings_Controller',
			'B3_Sidebars_Controller',
		),
		'model' => array(
			'B3_Comment_Model',
			'B3_Post_Model',
		),
		'helpers' => array(
			'B3_RoutesHelper',
			'B3_SettingsHelper',
		),
	);

	/**
	 * Source files.
	 * @var array
	 */
	protected static $files = array(
		'B3_Server',
		'B3_Router',
		'functions',
	);

	/**
	 * Loads sources.
	 */
	public static function ready() {
		foreach ( static::$classes as $dir => $classes ) {
			foreach ( $classes as $class ) {
				require_once dirname( __FILE__ ) . "/lib/$dir/$class.php";
			}
		}

		foreach ( static::$files as $file ) {
			require_once dirname( __FILE__ ) . "/lib/$file.php";
		}
	}

}
