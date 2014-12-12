<?php

/**
 * @todo
 */
class B3_Settings_Controller extends WP_JSON_Controller {

	/**
	 * Settings object.
	 * @var B3_SettingsHelper
	 */
	protected $settings;

	/**
	 * Options returned by the endpoint.
	 * @var array
	 * @see B3_SettingsHelper::__get()
	 */
	protected $options = array(
		'name',
		'description',
		'site_url',
		'site_path',
		'wp_url',
		'api_url',
		'root_url',
		'routes',
		'page_on_front',
		'page_for_posts',
		'charset',
		'text_direction',
		'language',
	);

	public function __construct() {
		$this->settings = new B3_SettingsHelper();
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$settings = array();

		foreach ( $this->options as $option ) {
			$value               = $this->get_option( $option );
			$settings[ $option ] = $this->prepare_item_for_response( $value, $request );
		}

		return $settings;
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_item( $request ) {
		$option = sanitize_text_field( $request->get_param( 'option' ) );

		if ( empty( $option ) || ! in_array( $option, $this->options ) ) {
			return b3_api_error( 'json_invalid_option',
				__( 'Option not found.', 'b3-rest-api' ), 404 );
		}

		return $this->prepare_item_for_response( $this->get_option( $option ), $request );
	}

	/**
	 * @param obj $item Item object
	 * @param WP_JSON_Request $request
	 *
	 * @return obj Prepared item object
	 */
	public function prepare_item_for_response( $item, $request ) {
		$option = $item['option'];
		$value  = $item['value'];
		$data   = $item;

		$data['_links'] = array(
			'self' => array(
				'href' => json_url( sprintf( 'b3/settings/%s', $option ) ),
			),
		);

		if ( 'page_on_front' === $option && ! empty( $value ) ) {
			$data['_links']['page'] = array( 'href' => json_url( 'pages/' . $value ) );
		}

		$data['_links']['collection'] = array( 'href' => json_url( 'b3/settings' ) );

		return apply_filters( 'b3_prepare_setting', $data, $item, $request );

	}

	/**
	 * Option getter.
	 *
	 * @param  string $option Option name.
	 *
	 * @return mixed          Option value.
	 */
	protected function get_option( $option ) {

		/**
		 * Filters the returned option.
		 */
		$value = apply_filters( 'b3_get_option', $this->settings->get_option( $option ), $option );

		return array( 'option' => $option, 'value' => $value );
	}

}
