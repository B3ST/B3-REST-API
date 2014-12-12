<?php

/**
 * @todo
 */
class B3_Posts_Controller extends WP_JSON_Controller {

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 *
	 * @todo Temporary workaround until we get a new posts controller to subclass.
	 */
	public function get_item( $request ) {
		$slug       = sanitize_text_field( $request->get_param( 'slug' ) );

		$controller = new WP_JSON_Posts();

		$post       = get_page_by_path( $slug, OBJECT, get_post_types( array( 'show_in_json' => true ) ) );

		return $controller->get( $post->ID );
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

			/**
			 * @todo Not working because the WP-API is currently overwriting links.
			 */
			$_post['_links']['replies']['href'] = json_url( sprintf( 'b3/%s/%d/replies', $resource, $_post['id'] ) );
		}

		return $_post;
	}

}
