<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Extends the default Post resource API.
 */
class B3_Post extends B3_API {

	/**
	 * Register new API routes for the Post resource.
	 *
	 * @param  array $routes API routes.
	 * @return array         Changed API routes.
	 */
	public function register_routes( $routes ) {

		$post_routes = array(
			'/posts/b3:slug:(?P<slug>.+)' => array(
				array( array( $this, 'get_post' ), WP_JSON_Server::READABLE ),
			),
		);

		return array_merge( $routes, $post_routes );
	}

	/**
	 * Retrieve a post by slug.
	 *
	 * @uses get_post()
	 * @param  string $slug    Post slug.
	 * @param  string $Context Context in which the post appears.
	 * @return array           Post entity.
	 */
	public function get_post( $slug, $context = 'view' ) {
		global $wp_json_posts;

		if ( empty( $slug ) ) {
			return B3_JSON_REST_API::error( 'json_post_invalid_slug',
				__( 'Invalid post slug.' ), 404 );
		}

		$post = get_page_by_path( $slug, OBJECT, get_post_types( array( 'show_in_json' => true ) ) );

		if ( ! $post ) {
			return B3_JSON_REST_API::error( 'json_post_invalid_slug',
				__( 'Invalid post slug.', 'b3-rest-api' ), 404 );
		}

		return $wp_json_posts->get_post( $post->ID, $context );
	}

}
