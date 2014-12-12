<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'b3_api_error' ) ) {
	/**
	 * Generates a REST API error.
	 *
	 * @param  string   $code    Error code.
	 * @param  string   $message Error message.
	 * @param  int      $status  HTTP status code (default: 500).
	 * @return WP_Error          Error object.
	 */
	function b3_api_error( $code, $message, $status = 500 ) {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}

if ( ! function_exists( 'ifsetor' ) ) {
	/**
	 * Returns the value if it exists or a given default value.
	 *
	 * @param  mixed $variable Variable to evaluate and return if set.
	 * @param  mixed $default  Default value to return if `$variable` is not set.
	 * @return mixed           The variable's value if it exists, otherwise the
	 *                         default is returned.
	 *
	 * @link https://wiki.php.net/rfc/ifsetor
	 */
	function ifsetor( &$variable, $default = null ) {
		if ( ! isset( $variable ) ) {
			return $default;
		}

		return $variable;
	}
}

