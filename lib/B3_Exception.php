<?php
/**
 * @since 0.2.0
 */

/**
 * Allows throwing a `WP_Error` object as an an exception.
 *
 * `WP_Error` sucks. Errors are meant to be thrown, not returned by methods like they
 * were valid results. This extends the default `Exception` class to allow converting
 * exceptions to and from `WP_Error` objects.
 *
 * Unfortunately, because a `WP_Error` object may contain multiple messages and error
 * codes, only the first message for the first error code in the instance will be
 * accessible through the exception's methods.
 *
 * @since 0.2.0
 */
class B3_API_Exception extends Exception {

	/**
	 * Error instance.
	 * @var WP_Error
	 */
	protected $wp_error;

	/**
	 * WordPress exception constructor.
	 *
	 * The class constructor accepts either the traditional `Exception` creation
	 * parameters or a `WP_Error` instance in place of the previous exception.
	 *
	 * If a `WP_Error` instance is given in this way, the `$message` and `$code`
	 * parameters are ignored in favour of the message and code provided by the
	 * `WP_Error` instance.
	 *
	 * Depending on whether a `WP_Error` instance was received, the instance is kept
	 * or a new one is created from the provided parameters.
	 *
	 * @param string             $code     Exception code (optional, defaults to 'json_api_error').
	 * @param string             $message  Exception message (optional, defaults to empty).
	 * @param integer            $status   Exception status (optional, defaults to 500).
	 * @param Exception|WP_Error $previous Previous exception or error (optional).
	 *
	 * @uses WP_Error
	 * @uses WP_Error::get_error_code()
	 * @uses WP_Error::get_error_message()
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct( $code = 'json_api_error', $message = '', $status = 500, $previous = null ) {
		$exception = $previous;

		if ( $previous instanceof WP_Error ) {
			$code      = $previous->get_error_code();
			$message   = $previous->get_error_message( $code );
			$exception = null;
		}

		parent::__construct( $message, $status, $exception );

		$this->wp_error = new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Obtain the exception's `WP_Error` object.
	 *
	 * @return WP_Error WordPress error.
	 */
	public function get_wp_error() {
		return $this->wp_error;
	}

}
