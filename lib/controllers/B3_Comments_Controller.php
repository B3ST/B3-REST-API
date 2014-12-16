<?php

/**
 * @todo
 */
class B3_Comments_Controller extends WP_JSON_Controller {

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$id   = (int) $request->get_param( 'id' );

		try {
			$post     = B3_Post_Model::get_instance_by_id( $id );
			$comments = $post->get_replies();
		} catch ( B3_API_Exception $exception ) {
			return $exception->get_wp_error();
		}

		foreach ( $comments as &$comment ) {
			$comment = $this->prepare_item_for_response( $comment, $request );
		}

		return $comments;
	}

	/**
	 * [get_subitems description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_subitems( $request ) {
		$id      = (int) $request->get_param( 'id' );

		try {
			$comment  = B3_Comment_Model::get_instance_by_id( $id );
			$comments = $comment->get_replies();
		} catch ( B3_API_Exception $exception ) {
			return $exception->get_wp_error();
		}

		foreach ( $comments as &$comment ) {
			$comment = $this->prepare_item_for_response( $comment, $request );
		}

		return $comments;
	}

	/**
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function get_item( $request ) {
		$id      = (int) $request->get_param( 'id' );

		try {
			$comment = B3_Comment_Model::get_instance_by_id( $id );
		} catch ( B3_API_Exception $exception ) {
			return $exception->get_wp_error();
		}

		return $this->prepare_item_for_response( $comment, $request );
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
	 * Create a comment in response to another comment.
	 *
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function create_subitem( $request ) {
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
		$response = $item->get_response();
		return apply_filters( 'b3_prepare_comment', $response, $item->get_comment(), $request );
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
			return b3_api_error( 'json_post_invalid_id',
				__( 'Invalid post ID.', 'b3-rest-api' ), 404 );
		}

		if ( ! $this->check_read_permission( $post ) ) {
			return b3_api_error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read replies to this post.', 'b3-rest-api' ), 403 );
		}

		if ( ! $this->check_reply_permission( $post ) ) {
			return b3_api_error( 'json_user_cannot_reply',
				__( 'Sorry, you cannot reply to this post.', 'b3-rest-api' ), 403 );
		}

		$new_comment = array(
			'comment_post_ID'      => $post['ID'],
			'comment_parent'       => ifsetor( $data['parent_comment'] ),
			'comment_content'      => ifsetor( $data['content'] ),
			'comment_author'       => ifsetor( $data['author']['name'] ),
			'comment_author_email' => ifsetor( $data['author']['email'] ),
			'comment_author_url'   => ifsetor( $data['author']['URL'] ),
		);

		if ( ! empty( $comment ) ) {
			$new_comment['comment_parent'] = $comment->comment_ID;
		}

		$new_comment = $this->prepare_new_comment_author( $new_comment );

		return $this->validate_comment( $new_comment );
	}

	/**
	 * Populate comment with data from the currently logged in user.
	 *
	 * @param  array $comment New comment data.
	 * @return array          New comment data with author information.
	 */
	protected function prepare_new_comment_author( $comment ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();

			if ( $user && $user->ID ) {
				$comment['user_ID']              = $user->ID;
				$comment['user_id']              = $user->ID;
				$comment['comment_author']       = $user->display_name;
				$comment['comment_author_email'] = $user->user_email;
				$comment['comment_author_url']   = $user->user_url;
			}
		}

		return $comment;
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
				return b3_api_error( 'json_bad_comment',
					__( 'Comment author name and email are required.', 'b3-rest-api' ), 400 );
			}

			if ( ! is_email( $comment['comment_author_email'] ) ) {
				return b3_api_error( 'json_bad_comment',
					__( 'A valid email address is required.', 'b3-rest-api' ), 400 );
			}
		}

		if ( empty( $comment['comment_content'] ) ) {
			return b3_api_error( 'json_bad_comment',
				__( 'Your comment must not be empty.', 'b3-rest-api' ), 400 );
		}

		return $comment;
	}

}
