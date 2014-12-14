<?php

class B3_Loader {

	protected static $classes = array(
		'controllers' => array(
			'B3_Comments_Controller',
			'B3_Menus_Controller',
			'B3_Posts_Controller',
			'B3_Settings_Controller',
			'B3_Sidebars_Controller',
		),
		'helpers' => array(
			'B3_RoutesHelper',
			'B3_SettingsHelper',
		),
	);

	protected static $files = array(
		'lib/registry/B3_Controller_Registry',
		'lib/B3_Server',
		'lib/B3_Router',
		'lib/functions',
	);

	public static function ready() {
		foreach ( static::$classes as $dir => $classes ) {
			foreach ( $classes as $class ) {
				require_once dirname( __FILE__ ) . "/lib/$dir/$class.php";
			}
		}

		foreach ( static::$files as $file ) {
			require_once dirname( __FILE__ ) . "/$file.php";
		}
	}

}
