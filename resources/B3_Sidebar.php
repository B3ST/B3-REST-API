<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Implements the Sidebar resource API.
 */
class B3_Sidebar extends B3_API {

	/**
	 * Register API routes for the Sidebar resource.
	 *
	 * @param  array $routes API routes.
	 * @return array         Changed API routes.
	 */
	public function register_routes( $routes ) {

		$sidebar_routes = array(
			'/b3:sidebars' => array(
				array( array( $this, 'get_sidebars' ), WP_JSON_Server::READABLE ),
			),

			'/b3:sidebars/(?P<index>\w+)' => array(
				array( array( $this, 'get_sidebar' ), WP_JSON_Server::READABLE ),
			),
		);

		return array_merge( $routes, $sidebar_routes );
	}

	/**
	 * Retrieve all registered sidebars (without menu item details).
	 *
	 * @return array Array of registered sidebar entities.
	 */
	public function get_sidebars() {
		global $wp_registered_sidebars;

		return $this->prepare_sidebar( $wp_registered_sidebars, 'collection' );
	}

	/**
	 * Retrieve a Sidebar by ID.
	 *
	 * @param  mixed  $index   Sidebar name, ID or number to retrieve.
	 * @param  string $context Context in which the sidebar appears.
	 * @return array           Sidebar entity.
	 */
	public function get_sidebar( $index, $context = 'single' ) {
		global $wp_registered_sidebars, $wp_registered_widgets;

		if ( ! is_active_sidebar( $index ) ) {
			return B3_JSON_REST_API::error( 'json_sidebar_invalid_id',
				__( 'Sidebar is not active.', 'b3-rest-api' ), 404 );
		}

		if ( ! is_dynamic_sidebar( $index ) ) {
			return B3_JSON_REST_API::error( 'json_sidebar_invalid_id',
				__( 'Sidebar has no active widgets.', 'b3-rest-api' ), 404 );
		}

		$sidebar = $wp_registered_sidebars[ $index ];

		$sidebars_widgets = wp_get_sidebars_widgets();

		$widgets = array();

		foreach ( (array) $sidebars_widgets[ $index ] as $id ) {
			if ( ! isset( $wp_registered_widgets[ $id ] ) ) {
				continue;
			}

			$callback    = $wp_registered_widgets[ $id ]['callback'];
			$option_name = $callback[0]->option_name;
			$number      = $callback[0]->number;
			$options     = get_option( $option_name );

			$widget = array(
				'widget_id'    => $id,
				'widget_name'  => $wp_registered_widgets[ $id ]['name'],
				'widget_title' => $options[ $number ]['title'],
			);

			$params = array_merge(
				array( array_merge( $sidebar, $widget ) ),
				(array) $wp_registered_widgets[ $id ]['params']
			);

			$classes = array();
			foreach ( (array) $wp_registered_widgets[ $id ]['classname'] as $cn ) {
				if ( is_string( $cn ) ) {
					$cn = '_' . $cn;

				} elseif ( is_object( $cn ) ) {
					$cn = '_' . get_class( $cn );
				}

				$classes[] = ltrim( $cn, '_' );
			}

			$widget['class'] = $classes;

			$params[0]['before_widget'] = '';
			$params[0]['after_widget']  = '';
			$params[0]['before_title']  = '<!-- ';
			$params[0]['after_title']   = ' -->';

			$params = apply_filters( 'dynamic_sidebar_params', $params );

			if ( is_callable( $callback ) ) {
				ob_start();
				call_user_func_array( $callback, $params );
				$widget['widget_content'] = ob_get_clean();
			}

			$widgets[] = $widget;
		}

		$sidebar['widgets'] = $widgets;

		return $this->prepare_sidebar( $sidebar, 'single' );
	}

	/**
	 * Prepare Sidebar entities returned by the service.
	 *
	 * @param  array  $_sidebars Raw sidebar entity data.
	 * @param  string $context   The context for the prepared sidebar. (single|collection)
	 * @return array             Sidebar entity data.
	 */
	protected function prepare_sidebar( $_sidebars, $context = 'single' ) {

		$single_id = 0;
		$keys      = array( 'name', 'id', 'description', 'class', 'meta' );

		if ( 'single' === $context ) {
			$single_id = $_sidebars['id'];
			$_sidebars = array( $single_id => $_sidebars );
			$keys[]    = 'widgets';
		}

		$sidebars = array();

		foreach ( $_sidebars as $index => $sidebar ) {
			foreach ( $keys as $key ) {
				if ( isset( $sidebar[ $key ] ) ) {
					$sidebars[ $index ][ $key ] = $sidebar[ $key ];
				}
			}

			$sidebars[ $index ]['meta'] = array(
				'links' => array(
					'self' => json_url( sprintf( '/b3:sidebars/%s', $index ) ),
				),
			);
		}

		if ( 'single' === $context ) {
			$sidebars = $sidebars[ $single_id ];
		}

		return apply_filters( 'b3_sidebars', $sidebars, $_sidebars, $context );
	}

}
