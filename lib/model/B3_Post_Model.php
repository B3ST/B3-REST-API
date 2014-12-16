<?php

class B3_Post_Model {

	/**
	 * [$post description]
	 * @var [type]
	 */
	protected $post;

	/**
	 *
	 */
	public function __construct( $post ) {
		$this->post = $post;
	}

	/**
	 * [get_instance_by_id description]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public static function get_instance_by_id( $id ) {
		$post = get_post( $id );

        if ( is_wp_error( $post ) ) {
            throw new B3_API_Exception( null, null, null, $post );
        }

		if ( empty( $post ) ) {
			throw new B3_API_Exception( 'json_post_not_found',
				__( 'Post not found.', 'b3-rest-api' ), 404 );
		}

		return new static( $post );
	}

	/**
	 * [validate description]
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	protected static function validate( $post ) {
		if ( is_wp_error( $post ) ) {
			throw new B3_API_Exception( null, null, null, $post );
		}

		if ( empty( $post ) ) {
			throw new B3_API_Exception( 'json_post_not_found',
				__( 'Not found.', 'b3-rest-api' ), 404 );
		}
	}

	/**
	 * [get_id description]
	 * @return [type] [description]
	 */
	public function get_id() {
		return $this->post->ID;
	}

	/**
	 * [get_instance_by_post description]
	 * @return array|WP_Error [description]
	 */
	public function get_replies() {
		if ( ! $this->is_readable() ) {
			throw new B3_API_Exception( 'json_user_cannot_read',
				__( 'Sorry, you cannot read this post.', 'b3-rest-api' ), 401 );
		}

		$comments = get_comments( array( 'post_id' => $this->post->ID ) );

		if ( is_wp_error( $comments ) ) {
			throw new B3_API_Exception( null, null, null, $comments );
		}

		if ( empty( $comments ) ) {
			throw new B3_API_Exception( 'json_comment_not_found',
				__( 'No replies found for this post.', 'b3-rest-api' ), 404 );
		}

		foreach ( $comments as &$comment ) {
			$comment = new B3_Comment_Model( $comment );
		}

		return $comments;
	}

	/**
	 * [reply_with_data description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function reply_with_data( $data, $parent_comment_id = null ) {
		if ( ! $this->is_readable() || ! $this->is_repliable() ) {
			throw new B3_API_Exception( 'json_user_cannot_reply',
				__( 'Sorry, you cannot reply to this post.', 'b3-rest-api' ), 401 );
		}

		$comment = B3_Comment_Model::new_instance( $data, $this->post->ID, $parent_comment_id );

		return $comment;
	}

	/**
	 * [is_readable description]
	 * @return boolean [description]
	 */
	protected function is_readable() {
		$post_type = get_post_type_object( $this->post->post_type );

		// Ensure the post type can be read
		if ( ! $post_type->show_in_json ) {
			return false;
		}

		// Can we read the post?
		if ( 'publish' === $this->post->post_status || current_user_can( $post_type->cap->read_post, $this->post->ID ) ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $this->post->post_status && $this->post->post_parent > 0 ) {
			$parent = get_post( $this->post->post_parent );

			if ( $this->is_readable( $parent ) ) {
				return true;
			}
		}

		// If we don't have a parent, but the status is set to inherit, assume
		// it's published (as per get_post_status())
		if ( 'inherit' === $this->post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * [is_repliable description]
	 * @return boolean [description]
	 */
	protected function is_repliable() {
		return comments_open( $this->post->ID );
	}

}
