<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Extends the default Media resource API.
 */
class B3_Media extends B3_API {

	/**
	 * Register new API routes for the Media resource.
	 *
	 * @param  array $routes API routes.
	 * @return array         Changed API routes.
	 */
	public function register_routes( $routes ) {

		$post_routes = array(
			'/media/b3:slug:(?P<slug>.+)' => array(
				array( array( $this, 'get_post' ), WP_JSON_Server::READABLE ),
			),
		);

		return array_merge( $routes, $post_routes );
	}

	/**
	 * Retrieve a post attachment by slug.
	 *
	 * @param  string         $slug    Attachment slug.
	 * @param  string         $context Context in which the attachment appears.
	 *
	 * @return array|WP_Error          Attachment entity, or error.
	 */
	public function get_post( $slug, $context = 'view' ) {
		global $wp_json_media;

		if ( empty( $slug ) ) {
			return B3_JSON_REST_API::error( 'json_post_invalid_slug',
				__( 'Invalid post slug.' ), 404 );
		}

		$posts = get_posts( array(
			'name'           => $slug,
			'post_type'      => 'attachment',
			'posts_per_page' => 1,
		) );

		if ( empty( $posts ) ) {
			return B3_JSON_REST_API::error( 'json_post_invalid_slug',
				__( 'Invalid post slug.', 'b3-rest-api' ), 404 );
		}

		return $wp_json_media->get_post( $posts[0]->ID, $context );
	}

}
