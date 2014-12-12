<?php

/**
 * @todo
 */
class B3_Menus_Controller extends WP_JSON_Controller {

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$menus = $this->get_registered_nav_menus();

		foreach ( $menus as &$menu ) {
			$menu = $this->prepare_item_for_response( $menu, $request );
		}

		return $menus;
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_item( $request ) {
		$location = sanitize_text_field( $request->get_param( 'location' ) );

		$menus = $this->get_registered_nav_menus();

		if ( empty( $location ) || ! isset( $menus[ $location ] ) ) {
			return b3_api_error( 'json_menu_invalid_id',
				__( 'Invalid menu location.', 'b3-rest-api' ), 404 );
		}

		return $this->prepare_item_for_response( $menus[ $location ], $request );
	}

	/**
	 * @param obj $item Item object
	 * @param WP_JSON_Request $request
	 *
	 * @return obj Prepared item object
	 */
	public function prepare_item_for_response( $item, $request ) {
		$location = sanitize_text_field( $request->get_param( 'location' ) );

		$data = $item;

		$data['_links'] = array(
			'self'       => array( 'href' => json_url( sprintf( 'b3/menus/%s', $data['location'] ) ) ),
			'collection' => array( 'href' => json_url( 'b3/menus' ) ),
		);

		if ( ! empty( $location ) ) {
			$menu_locations = get_nav_menu_locations();
			$menu_id        = ifsetor( $menu_locations[ $location ], false );

			if ( $menu_id ) {
				$menu         = get_term_by( 'id', $menu_id, 'nav_menu' );
				$data['menu'] = $this->prepare_menu( $menu );
			}
		}

		return apply_filters( 'b3_json_prepare_menu', $data, $item, $request );
	}

	/**
	 * Get registered menus.
	 * @return array Registered menus.
	 */
	protected function get_registered_nav_menus() {
		$_menus = get_registered_nav_menus();
		$menus  = array();

		foreach ( $_menus as $location => $name ) {
			$menus[ $location ] = array(
				'location' => $location,
				'name'     => $name,
			);
		}

		return $menus;
	}

	/**
	 * [prepare_menu description]
	 * @param  [type] $_menu [description]
	 * @return [type]        [description]
	 */
	protected function prepare_menu( $_menu ) {

		$menu = array(
			'ID'          => $_menu->term_id,
			'name'        => $_menu->name,
			'slug'        => $_menu->slug,
			'description' => $_menu->description,
			'count'       => $_menu->count,
			'items'       => $this->prepare_menu_items( wp_get_nav_menu_items( $_menu ) ),
		);

		return apply_filters( 'b3_menu', $menu );
	}

	/**
	 * [prepare_menu_items description]
	 * @param  [type] $_items [description]
	 * @param  [type] $menu   [description]
	 * @return [type]         [description]
	 */
	protected function prepare_menu_items( $_items ) {

		$items = array();

		foreach ( $_items as $_item ) {
			$item = array(
				'ID'            => (int) $_item->ID,
				'parent'        => (int) $_item->menu_item_parent,
				'order'         => (int) $_item->menu_order,
				'type'          => $_item->post_type,
				'guid'          => $_item->guid,
				'object'        => (int) $_item->object_id,
				'object_parent' => (int) $_item->post_parent,
				'object_type'   => $_item->object,
				'link'          => $_item->url,
				'title'         => $_item->title,
				'attr_title'    => $_item->attr_title,
				'description'   => $_item->description,
				'classes'       => $_item->classes,
				'target'        => $_item->target,
				'xfn'           => $_item->xfn,
			);

			$link = $this->get_object_link( $_item );

			if ( ! empty( $link ) ) {
				$item['_links'] = array( 'object' => array( 'href' => $link ) );
			}

			$items[] = $item;
		}

		return apply_filters( 'b3_menu_items', $items );
	}

	/**
	 * [get_object_link description]
	 * @param  [type] $item [description]
	 * @return [type]       [description]
	 */
	protected function get_object_link( $item ) {

		$link = false;

		if ( 'post_type' === $item->type ) {
			$link = $this->get_post_type_link( $item );

		} elseif ( 'taxonomy' === $item->type ) {
			$link = $this->get_term_link( $item );
		}

		return apply_filters( 'b3_item_link', $link, $item );
	}

	/**
	 * [get_post_type_link description]
	 * @param  [type] $item [description]
	 * @return [type]       [description]
	 */
	protected function get_post_type_link( $item ) {

		switch ( $item->object ) {
			case 'page':
				$link = json_url( sprintf( 'pages/%s', get_page_uri( $item->object_id ) ) );
				break;

			default:
				$link = json_url( sprintf( 'posts/%d', $item->object_id ) );
				break;
		}

		return apply_filters( 'b3_post_type_link', $link, $item->object_id, $item->object );
	}

	/**
	 * [get_term_link description]
	 * @param  [type] $item [description]
	 * @return [type]       [description]
	 */
	protected function get_term_link( $item ) {

		switch ( $item->object ) {
			case 'tag':
				$taxonomy = 'post_tag';
				break;

			default:
				$taxonomy = $item->object;
				break;
		}

		$link = json_url( sprintf( 'wp/terms/%s/%d', $taxonomy, $item->object_id ) );

		return apply_filters( 'b3_taxonomy_link', $link, $item->object_id, $item->object );
	}

}
