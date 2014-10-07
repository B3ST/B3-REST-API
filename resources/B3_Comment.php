<?php
/**
 * @package B3
 * @subpackage B3/API
 */

if (!defined( 'WPINC' )) {
	die;
}

/**
 * Implements a Comment resource API.
 */
class B3_Comment extends B3_API {

	/**
	 * Register API routes for the Comment resource.
	 *
	 * @param  array $routes API routes.
	 * @return array         Changed API routes.
	 */
	public function register_routes ( $routes ) {

		$comment_routes = array(
			'/(posts|pages)/(?P<id>\d+)/b3:replies' => array(
				array( array( $this, 'get_post_replies' ),    WP_JSON_Server::READABLE ),
				array( array( $this, 'new_post_reply' ),      WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
			),

			'/b3:comments/(?P<id>\d+)' => array(
				array( array( $this, 'get_comment' ),         WP_JSON_Server::READABLE ),
				// array( array( $this, 'update_comment' ),      WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
				// array( array( $this, 'delete_comment' ),      WP_JSON_Server::DELETABLE ),
			),

			'/b3:comments/(?P<id>\d+)/b3:replies' => array(
				array( array( $this, 'get_comment_replies' ), WP_JSON_Server::READABLE ),
				array( array( $this, 'new_comment_reply' ),   WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
			),
		);

		return array_merge( $comment_routes, $routes );
	}

	/**
	 * Retrieve all responses to a post.
	 *
	 * @param  int   $post_id Post ID to retrieve comments for.
	 * @return array          List of Comment entities.
	 */
	public function get_post_replies ( $id ) {
		global $wp_json_posts;

		$post = get_post( $id, ARRAY_A );

		if (empty( $post['ID'] )) {
			return new WP_Error( 'json_post_invalid_id',
				__( 'Invalid post ID.', 'b3-rest-api' ),
				array( 'status' => 404 ) );
		}

		if (!$this->check_read_permission( $post )) {
			return new WP_Error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read this post.', 'b3-rest-api' ),
				array( 'status' => 401 ) );
		}

		$comments = get_comments( array( 'post_id' => $id ) );

		$response = array();

		foreach ($comments as $comment) {
			$response[] = $this->prepare_comment( $comment, array( 'comment', 'meta' ), 'collection' );
		}

		return $response;
	}

	/**
	 * Add a reply to a post.
	 *
	 * @param  int    $id   Post ID to comment on.
	 * @param  array  $data New comment data.
	 * @return array        Comment entity for the new comment.
	 */
	public function new_post_reply ( $id, $data ) {
		global $wp_json_posts;

		$post = get_post( $id, ARRAY_A );

		$new_comment = $this->prepare_new_comment( $data, $post );

		if (is_wp_error( $new_comment )) {
			return $new_comment;
		}

		$comment_ID  = wp_new_comment( $new_comment );

		if (!$comment_ID) {
			return new WP_Error( 'json_insert_error',
				__( 'There was an error processing your comment.', 'b3-rest-api' ),
				array( 'status' => 500 ) );
		}

		return $this->get_comment( $comment_ID );
	}

	/**
	 * Retrieve a single comment by ID.
	 *
	 * @param  int   $id Comment ID.
	 * @return array     Comment entity.
	 */
	public function get_comment ( $id ) {
		global $wp_json_posts;

		$comment = get_comment( $id );

		if (empty( $comment->comment_ID )) {
			return new WP_Error( 'json_comment_invalid_id',
				__( 'Invalid comment ID.', 'b3-rest-api' ),
				array( 'status' => 404 ) );
		}

		$post = get_post( $comment->comment_post_ID, ARRAY_A );

		if (!$this->check_read_permission( $post )) {
			return new WP_Error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read replies to this post.', 'b3-rest-api' ),
				array( 'status' => 403 ) );
		}

		return $this->prepare_comment( $comment, array( 'comment', 'meta' ), 'single' );
	}

	/**
	 * Edit a single comment by ID.
	 *
	 * @param  int    $comment_id Comment ID to edit.
	 * @param  array  $data       Updated comment data.
	 * @return [type]             [description]
	 *
	 * @todo
	 */
	public function update_comment ( $id, $data ) {
		return new WP_Error( 'json_not_implemented',
			__( 'Not yet implemented.', 'b3-rest-api' ),
			array( 'status' => 501 ) );
	}

	/**
	 * Remove a single comment.
	 *
	 * @param  int    $comment_id Comment ID to be removed.
	 * @return [type]             [description]
	 *
	 * @todo
	 */
	public function delete_comment ( $id ) {
		return new WP_Error( 'json_not_implemented',
			__( 'Not yet implemented.', 'b3-rest-api' ),
			array( 'status' => 501 ) );
	}

	/**
	 * Retrieve all responses to a comment.
	 *
	 * @param  int   $comment_id Unique ID for the comment whose
	 *                           replies are being retrieved.
	 * @return array             Collection of Comment entities.
	 */
	public function get_comment_replies ( $id ) {
		global $wp_json_posts;

		$comment = get_comment( $id );

		if (empty( $comment->comment_ID )) {
			return new WP_Error( 'json_comment_invalid_id',
				__( 'Invalid comment ID.', 'b3-rest-api' ),
				array( 'status' => 404 ) );
		}

		$post = get_post( $comment->comment_post_ID, ARRAY_A );

		if (!$this->check_read_permission( $post )) {
			return new WP_Error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read this post.', 'b3-rest-api' ),
				array( 'status' => 401 ) );
		}

		$comments = get_comments( array( 'parent' => $id ) );

		$response = array();

		foreach ($comments as $comment) {
			$response[] = $this->prepare_comment( $comment, array( 'comment', 'meta' ), 'collection' );
		}

		return $response;
	}

	/**
	 * Add a reply to a comment.
	 *
	 * @param  int    id    Unique ID for the comment being replied to.
	 * @param  array  $data Comment data.
	 * @return array        The newly created comment entity.
	 */
	public function new_comment_reply ( $id, $data ) {
		global $wp_json_posts;

		$comment = get_comment( $id );

		if (empty( $comment->comment_ID )) {
			return new WP_Error( 'json_comment_invalid_id',
				__( 'Invalid comment ID.', 'b3-rest-api' ),
				array( 'status' => 404 ) );
		}

		$post = get_post( $comment->comment_post_ID, ARRAY_A );

		$new_comment = $this->prepare_new_comment( $data, $post, $comment );

		if (is_wp_error( $new_comment )) {
			return $new_comment;
		}

		$comment_ID  = wp_new_comment( $new_comment );

		if (!$comment_ID) {
			return new WP_Error( 'json_insert_error',
				__( 'There was an error processing your comment.', 'b3-rest-api' ),
				array( 'status' => 500 ) );
		}

		return $this->get_comment( $comment_ID );
	}

	/**
	 * Check if the current user is allowed to read a post.
	 *
	 * @param  array   $post Post data.
	 * @return boolean       Whether the current user is allowed to
	 *                       read this post.
	 */
	protected function check_read_permission ( $post ) {
		$post_type = get_post_type_object( $post['post_type'] );

		// Ensure the post type can be read
		if (!$post_type->show_in_json) {
			return false;
		}

		// Can we read the post?
		if ('publish' === $post['post_status'] || current_user_can( $post_type->cap->read_post, $post['ID'] )) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ('inherit' === $post['post_status'] && $post['post_parent'] > 0) {
			$parent = get_post( $post['post_parent'], ARRAY_A );

			if ($this->check_read_permission( $parent )) {
				return true;
			}
		}

		// If we don't have a parent, but the status is set to inherit, assume
		// it's published (as per get_post_status())
		if ( 'inherit' === $post['post_status'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the current user is allowed to comment on a post.
	 *
	 * @param  array   $post Post data.
	 * @return boolean       Whether the current user is allowed to
	 *                       comment on this post.
	 */
	protected function check_reply_permission ( $post ) {
		return comments_open( $post['ID'] );
	}

	/**
	 * Check if the current user is allowed to edit a comment.
	 *
	 * @param  array   $comment Comment data.
	 * @return boolean          Whether the current user is allowed to
	 *                          edit this resource.
	 *
	 * @todo
	 */
	protected function check_edit_permission ( $comment ) {
		return false;
	}

	/**
	 * Check if the current user is allowed to delete a comment.
	 *
	 * @param  array   $comment Comment data.
	 * @return boolean          Whether the current user is allowed to
	 *                          delete this resource.
	 *
	 * @todo
	 */
	protected function check_delete_permission ( $comment ) {
		return false;
	}

	/**
	 * Prepare Comment entity for returning.
	 *
	 * @param  array  $comment          Raw comment data.
	 * @param  array  $requested_fields Fields to include.
	 * @param  string $context          Request context. (single|collection)
	 * @return array                    Prepared comment entity.
	 */
	protected function prepare_comment ( $comment, $requested_fields = array( 'comment', 'meta' ), $context = 'single' ) {
		$fields = array(
			'ID'   => (int) $comment->comment_ID,
			'post' => (int) $comment->comment_post_ID,
		);

		// Content
		$fields['content'] = apply_filters( 'comment_text', $comment->comment_content, $comment );

		// Status
		switch ($comment->comment_approved) {
			case 'hold':
			case '0':
				$fields['status'] = 'hold';
				break;

			case 'approve':
			case '1':
				$fields['status'] = 'approved';
				break;

			case 'spam':
			case 'trash':
			default:
				$fields['status'] = $comment->comment_approved;
				break;
		}

		// Type
		$fields['type'] = apply_filters( 'get_comment_type', $comment->comment_type );

		if (empty( $fields['type'] )) {
			$fields['type'] = 'comment';
		}

		// Parent
		$fields['parent'] = (int) $comment->comment_parent;

		// Author
		if ((int) $comment->user_id !== 0) {
			$user = get_user_by( 'id', $comment->user_id );

			$fields['author'] = array(
				'ID'     => (int) $user->ID,
				'name'   => $user->display_name,
				'URL'    => $user->user_url,
				'avatar' => json_get_avatar_url( $user->user_email ),
			);
		} else {
			$fields['author'] = array(
				'ID'     => 0,
				'name'   => $comment->comment_author,
				'URL'    => $comment->comment_author_url,
				'avatar' => json_get_avatar_url( $comment->comment_author_email ),
			);
		}

		// Date
		$timezone = json_get_timezone();

		$date               = WP_JSON_DateTime::createFromFormat( 'Y-m-d H:i:s', $comment->comment_date, $timezone );
		$fields['date']     = $date->format( 'c' );
		$fields['date_tz']  = $date->format( 'e' );
		$fields['date_gmt'] = date( 'c', strtotime( $comment->comment_date_gmt ) );

		// Meta
		$meta = array(
			'links' => array(
				'up' => json_url( sprintf( '/posts/%d', (int) $comment->comment_post_ID ) )
			),
		);

		if (0 !== (int) $comment->comment_parent) {
			$meta['links']['in-reply-to'] = json_url( sprintf( '/b3:comments/%d', (int) $comment->comment_parent ) );
		}

		if ('single' !== $context) {
			$meta['links']['self'] = json_url( sprintf( '/b3:comments/%d', (int) $comment->comment_ID ) );
		}

		// Remove unneeded fields
		$data = array();

		if (in_array( 'comment', $requested_fields )) {
			$data = array_merge( $data, $fields );
		}

		if (in_array( 'meta', $requested_fields )) {
			$data['meta'] = $meta;
		}

		return apply_filters( 'b3_prepare_comment', $data, $comment, $context );
	}

	/**
	 * Prepares a new comment for insertion.
	 *
	 * The resulting array should still be passed to `wp_new_comment()` for
	 * sanitization.
	 *
	 * @param  array $data    Comment data.
	 * @param  array $post    Data for the post being replied to.
	 * @param  array $comment Data for the comment being replied to.
	 * @return array          Prepared comment data.
	 */
	protected function prepare_new_comment( $data, $post, $comment = null ) {

		if ( empty( $post['ID'] ) ) {
			return new WP_Error( 'json_post_invalid_id',
				__( 'Invalid post ID.', 'b3-rest-api' ),
				array( 'status' => 404 ) );
		}

		if ( ! $this->check_read_permission( $post ) ) {
			return new WP_Error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read replies to this post.', 'b3-rest-api' ),
				array( 'status' => 403 ) );
		}

		if ( ! $this->check_reply_permission( $post ) ) {
			return new WP_Error( 'json_user_cannot_reply',
				__( 'Sorry, you cannot reply to this post.', 'b3-rest-api' ),
				array( 'status' => 403 ) );
		}

		$new_comment = array(
			'comment_post_ID'      => $post['ID'],
			'comment_parent'       => isset( $data['parent_comment']  ) ? $data['parent_comment']  : null,
			'comment_content'      => isset( $data['content']         ) ? $data['content']         : null,
			'comment_author'       => isset( $data['author']['name']  ) ? $data['author']['name']  : null,
			'comment_author_email' => isset( $data['author']['email'] ) ? $data['author']['email'] : null,
			'comment_author_url'   => isset( $data['author']['URL']   ) ? $data['author']['URL']   : null,
		);

		if (!empty( $comment )) {
			$new_comment['comment_parent'] = $comment->comment_ID;
		}

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();

			if ( $user && $user->ID ) {
				$new_comment['user_ID']              = $user->ID;
				$new_comment['user_id']              = $user->ID;
				$new_comment['comment_author']       = $user->display_name;
				$new_comment['comment_author_email'] = $user->user_email;
				$new_comment['comment_author_url']   = $user->user_url;
			}
		}

		return $this->validate_comment( $new_comment );
	}

	/**
	 * Validate comment.
	 *
	 * @param  array 		  $comment Comment data to validate.
	 * @return array|WP_Error          Validated comment or error.
	 */
	protected function validate_comment( $comment ) {
		if ( get_option( 'require_name_email' ) ) {
			if ( empty( $comment['comment_author_email'] ) || '' == $comment['comment_author'] ) {
				return new WP_Error( 'json_bad_comment',
					__( 'Comment author name and email are required.', 'b3-rest-api' ),
					array( 'status' => 400 ) );
			}

			if ( ! is_email( $comment['comment_author_email'] ) ) {
				return new WP_Error( 'json_bad_comment',
					__( 'A valid email address is required.', 'b3-rest-api' ),
					array( 'status' => 400 ) );
			}
		}

		if ( empty( $comment['comment_content'] ) ) {
			return new WP_Error( 'json_bad_comment',
				__( 'Your comment must not be empty.', 'b3-rest-api' ),
				array( 'status' => 400 ) );
		}

		return $comment;
	}

}
