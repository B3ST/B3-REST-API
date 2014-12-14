<?php

/**
 * @todo
 */
class B3_Sidebars_Controller extends WP_JSON_Controller {

    /**
     * @param WP_JSON_Request $request Full details about the request
     *
     * @return array|WP_Error
     */
    public function get_items( $request ) {
        global $wp_registered_sidebars;

        $sidebars = $wp_registered_sidebars;

        foreach ( $sidebars as &$sidebar ) {
            $sidebar = $this->prepare_item_for_response( $sidebar, $request );
        }

        return $sidebars;
    }

    /**
     * @param WP_JSON_Request $request Full details about the request
     *
     * @return array|WP_Error
     */
    public function get_item( $request ) {
        global $wp_registered_sidebars;

        $index = sanitize_text_field( $request->get_param( 'index' ) );

        if ( ! is_active_sidebar( $index ) ) {
            return b3_api_error( 'json_sidebar_invalid_id',
                __( 'Sidebar is not active.', 'b3-rest-api' ), 404 );
        }

        if ( ! is_dynamic_sidebar( $index ) ) {
            return b3_api_error( 'json_sidebar_invalid_id',
                __( 'Sidebar has no active widgets.', 'b3-rest-api' ), 404 );
        }

        $sidebar = $wp_registered_sidebars[ $index ];

        return $this->prepare_item_for_response( $sidebar, $request );
    }

    /**
     * @param obj $item Item object
     * @param WP_JSON_Request $request
     *
     * @return obj Prepared item object
     */
    public function prepare_item_for_response( $item, $request ) {
        $is_single = ! empty( $request->get_param( 'index' ) );
        $keys      = array( 'name', 'id', 'description', 'class', 'meta' );

        $data = array();

        foreach ( $keys as $key ) {
            if ( isset( $item[ $key ] ) ) {
                $data[ $key ] = $item[ $key ];
            }
        }

        if ( $is_single ) {
            $data['widgets'] = $this->prepare_sidebar_widgets( $item, $request );
        }

        $data['_links'] = array(
            'self' => array(
                'href' => json_url( sprintf( '/b3/sidebars/%s', $item['id'] ) ),
            ),
        );

        return apply_filters( 'b3_sidebars', $data, $item, $request );
    }

    /**
     * [prepare_sidebar_widgets description]
     * @param  [type] $item    [description]
     * @param  [type] $request [description]
     * @return [type]          [description]
     */
    protected function prepare_sidebar_widgets( $item, $request ) {
        global $wp_registered_widgets;

        $sidebars_widgets = wp_get_sidebars_widgets();
        $widgets          = array();
        $index            = $item['id'];

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
                array( array_merge( $item, $widget ) ),
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

        return $widgets;
    }

}
