<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if (!defined( 'WPINC' )) {
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
    public function register_routes ( $routes ) {

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
    public function get_post ( $slug, $context = 'view' ) {
        global $wp_json_posts;
        $post = get_page_by_path( $slug, OBJECT, get_post_types( array( 'public' => true ) ) );
        return $wp_json_posts->get_post( $post->ID, $context );
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
    public function json_prepare_post ( $_post, $post, $context ) {

        if ('view-revision' !== $context) {
            $replies = $_post['meta']['links']['replies'];
            $replies = preg_replace( '/\/comments$/', '/b3:replies', $replies );
            $_post['meta']['links']['b3:replies'] = $replies;
        }

        return $_post;
    }

}
