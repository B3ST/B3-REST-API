<?php

/**
 * @todo
 */
class B3_Posts_Controller extends WP_JSON_Controller {

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		// TODO
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_item( $request ) {
		// TODO
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function create_item( $request ) {
		// TODO
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function update_item( $request ) {
		// TODO
	}

	/**
	 * @param array $args
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function delete_item( $request ) {
		// TODO
	}

	/**
	 * @param obj $item Item object
	 * @param WP_JSON_Request $request
	 *
	 * @return obj Prepared item object
	 */
	public function prepare_item_for_response( $item, $request ) {
		// TODO
	}

	/**
	 * Alter Post entities returned by the service.
	 *
	 * - Changes the reply link to use the `/posts/{id}/b3:replies` endpoint.
	 *
	 * @param  array  $_post   Post entity data.
	 * @param  array  $post    Raw post data.
	 * @param  string $context The context for the prepared post. (view|view-revision|edit|embed)
	 * @return array           Changed post entity data.
	 */
	public function json_prepare_post( $_post, $post, $context ) {

		if ( 'view-revision' !== $context ) {
			switch ( $_post['type'] ) {
				case 'page':
					$resource = 'pages';
					break;

				case 'attachment':
					$resource = 'media';
					break;

				default:
					$resource = 'posts';
					break;
			}

			$data = array(
				'_links' => array(
					'replies' => array(
						'href' => json_url( sprintf( 'b3/%s/%d/replies', $resource, $_post['id'] ) ),
					),
				),
			);

			$_post = array_merge_recursive( $_post, $data );
		}

		return $_post;
	}

}
