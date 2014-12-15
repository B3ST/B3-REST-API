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
		$id = (int) $request->get_param( 'id' );

		if ( ! $id ) {
			return b3_api_error( 'json_post_invalid_id',
				__( 'Invalid post ID.', 'b3-rest-api' ), 404 );
		}

		$post = get_post( $id );

		if ( ! $this->check_read_permission( $post ) ) {
			return b3_api_error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read this post.', 'b3-rest-api' ), 401 );
		}

		$comments = get_comments( array( 'post_id' => $id ) );

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
		$id = (int) $request->get_param( 'id' );

		if ( ! $id ) {
			return b3_api_error( 'json_comment_invalid_id',
				__( 'Invalid comment ID.', 'b3-rest-api' ), 404 );
		}

		$comment = get_comment( $id );
		$post    = get_post( $comment->comment_post_ID );

		if ( ! $this->check_read_permission( $post ) ) {
			return b3_api_error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read this post.', 'b3-rest-api' ), 401 );
		}

		$comments = get_comments( array( 'parent' => $id ) );

		if ( empty( $comments ) ) {
			return b3_api_error( 'json_user_not_found',
				__( 'No replies to this comment were found.', 'b3-rest-api' ), 404 );
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
		$id = (int) $request->get_param( 'id' );

		if ( ! $id ) {
			return b3_api_error( 'json_comment_invalid_id',
				__( 'Invalid comment ID.', 'b3-rest-api' ), 404 );
		}

		$comment = get_comment( $id );

		$post = get_post( $comment->comment_post_ID );

		if ( ! $this->check_read_permission( $post ) ) {
			return b3_api_error( 'json_user_cannot_read',
				__( 'Sorry, you cannot read replies to this post.', 'b3-rest-api' ), 403 );
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

		$timezone = json_get_timezone();
		$date     = WP_JSON_DateTime::createFromFormat( 'Y-m-d H:i:s', $item->comment_date, $timezone );

		$data = array(
			'ID'       => (int) $item->comment_ID,
			'post'     => (int) $item->comment_post_ID,
			'content'  => apply_filters( 'comment_text', $item->comment_content, $item ),
			'status'   => $this->get_comment_status( $item ),
			'type'     => apply_filters( 'get_comment_type', $item->comment_type ),
			'parent'   => (int) $item->comment_parent,
			'author'   => $this->get_comment_author( $item ),
			'date'     => $date->format( 'c' ),
			'date_tz'  => $date->format( 'e' ),
			'date_gmt' => date( 'c', strtotime( $item->comment_date_gmt ) ),
			'_links'   => $this->prepare_comment_links( $item ),
		);

		if ( empty( $data['type'] ) ) {
			$data['type'] = 'comment';
		}

		return apply_filters( 'b3_prepare_comment', $data, $item, $request );
	}

	/**
	 * Get comment status.
	 *
	 * @param  object $comment Raw comment data.
	 * @return string          Comment status.
	 */
	protected function get_comment_status( $comment ) {

		switch ( $comment->comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;

			case 'approve':
			case '1':
				$status = 'approved';
				break;

			default:
				$status = $comment->comment_approved;
				break;
		}

		return $status;
	}

	/**
	 * Get comment author.
	 *
	 * @param  object $comment Raw comment data.
	 * @return array           Comment author data.
	 */
	protected function get_comment_author( $comment ) {

		if ( (int) $comment->user_id > 0 ) {
			$user = get_user_by( 'id', $comment->user_id );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
				return array(
					'ID'     => (int) $user->ID,
					'name'   => $user->display_name,
					'URL'    => $user->user_url,
					'avatar' => json_get_avatar_url( $user->user_email ),
				);
			}
		}

		return array(
			'ID'     => 0,
			'name'   => $comment->comment_author,
			'URL'    => $comment->comment_author_url,
			'avatar' => json_get_avatar_url( $comment->comment_author_email ),
		);
	}

	/**
	 * Prepare comment links.
	 *
	 * @param  object $comment Raw comment data.
	 * @return array           Comment meta links.
	 */
	protected function prepare_comment_links( $comment ) {
		$links = array();

		$links['up']['href']         = json_url( sprintf( 'posts/%d', (int) $comment->comment_post_ID ) );
		$links['collection']['href'] = json_url( sprintf( 'b3/posts/%d/replies', (int) $comment->comment_post_ID ) );
		$links['self']['href']       = json_url( sprintf( 'b3/comments/%d', (int) $comment->comment_ID ) );

		if ( 0 !== (int) $comment->comment_parent ) {
			$links['in-reply-to']['href'] = json_url( sprintf( 'b3/comments/%d', (int) $comment->comment_parent ) );
		}

		return $links;
	}

	/**
	 * Check if the current user is allowed to read a post.
	 *
	 * @param  object  $post Post data.
	 * @return boolean       Whether the current user is allowed to
	 *                       read this post.
	 */
	protected function check_read_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );

		// Ensure the post type can be read
		if ( ! $post_type->show_in_json ) {
			return false;
		}

		// Can we read the post?
		if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );

			if ( $this->check_read_permission( $parent ) ) {
				return true;
			}
		}

		// If we don't have a parent, but the status is set to inherit, assume
		// it's published (as per get_post_status())
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
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
