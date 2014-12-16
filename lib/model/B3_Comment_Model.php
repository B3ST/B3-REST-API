<?php

/**
 *
 */
class B3_Comment_Model {

	/**
	 * [$comment description]
	 * @var [type]
	 */
	protected $comment;

	/**
	 * Constructor.
	 */
	public function __construct( $comment ) {
		$this->comment = $comment;
	}

	/**
	 * [get_instance_by_id description]
	 * @param  int                    $id [description]
	 * @return B3_Post_Model|WP_Error     [description]
	 */
	public static function get_instance_by_id( $id ) {
		$comment = get_comment( $id );

		static::validate( $comment );

		return new static( $comment );
	}

	/**
	 * [validate description]
	 * @param  [type] $comment [description]
	 * @return [type]          [description]
	 */
	protected static function validate( $comment ) {
		if ( is_wp_error( $comment ) ) {
			throw new B3_API_Exception( null, null, null, $comment );
		}

		if ( empty( $comment ) ) {
			throw new B3_API_Exception( 'json_comment_not_found',
				__( 'Not found.', 'b3-rest-api' ), 404 );
		}
	}

	/**
	 * [reply_with_data description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function reply_with_data( $data ) {
		$data['comment_parent'] = $this->comment->comment_ID;
		$post = B3_Post_Model::get_instance_by_id( $this->comment->comment_post_ID );
		return $post->reply_with_data( $data );
	}

	/**
	 * [get_comment description]
	 * @return [type] [description]
	 */
	public function get_comment() {
		return $this->comment;
	}

	/**
	 * [get_id description]
	 * @return [type] [description]
	 */
	public function get_id() {
		return $this->comment->comment_ID;
	}

	/**
	 * [get_instance_by_post description]
	 * @return array|WP_Error [description]
	 */
	public function get_replies() {
		$comments = get_comments( array( 'parent' => $this->comment->comment_ID ) );

		static::validate( $comments );

		foreach ( $comments as &$comment ) {
			$comment = new static( $comment );
		}

		return $comments;
	}

	/**
	 * [is_readable description]
	 * @return boolean [description]
	 */
	public function is_readable() {
		$post = B3_Post_Model::get_instance_by_id( $this->comment->comment_post_ID );
		return $post->is_readable();
	}

	/**
	 * [prepare_for_response description]
	 * @return [type] [description]
	 */
	public function get_response() {
		$timezone = json_get_timezone();
		$date     = WP_JSON_DateTime::createFromFormat( 'Y-m-d H:i:s', $this->comment->comment_date, $timezone );

		$response = array(
			'ID'       => (int) $this->comment->comment_ID,
			'post'     => (int) $this->comment->comment_post_ID,
			'content'  => apply_filters( 'comment_text', $this->comment->comment_content, $this->comment ),
			'status'   => $this->get_response_status(),
			'type'     => apply_filters( 'get_comment_type', $this->comment->comment_type ),
			'parent'   => (int) $this->comment->comment_parent,
			'author'   => $this->get_response_author(),
			'date'     => $date->format( 'c' ),
			'date_tz'  => $date->format( 'e' ),
			'date_gmt' => date( 'c', strtotime( $this->comment->comment_date_gmt ) ),
			'_links'   => $this->get_response_links(),
		);

		if ( empty( $response['type'] ) ) {
			$response['type'] = 'comment';
		}

		return $response;
	}

	/**
	 * Get comment status.
	 *
	 * @return string Comment status.
	 */
	protected function get_response_status() {

		switch ( $this->comment->comment_approved ) {
			case 'hold':
			case '0':
				$status = 'hold';
				break;

			case 'approve':
			case '1':
				$status = 'approved';
				break;

			default:
				$status = $this->comment->comment_approved;
				break;
		}

		return $status;
	}

	/**
	 * Get comment author.
	 *
	 * @return array Comment author data.
	 */
	protected function get_response_author() {

		if ( (int) $this->comment->user_id > 0 ) {
			$user = get_user_by( 'id', $this->comment->user_id );

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
			'name'   => $this->comment->comment_author,
			'URL'    => $this->comment->comment_author_url,
			'avatar' => json_get_avatar_url( $this->comment->comment_author_email ),
		);
	}

	/**
	 * Prepare comment links.
	 *
	 * @return array Comment meta links.
	 */
	protected function get_response_links() {
		$links = array();

		$post_id    = (int) $this->comment->comment_post_ID;
		$comment_id = (int) $this->comment->comment_ID;
		$parent_id  = (int) $this->comment->comment_parent;

		$links['up']['href']         = json_url( sprintf( 'posts/%d', $post_id ) );
		$links['collection']['href'] = json_url( sprintf( 'b3/posts/%d/replies', $post_id ) );
		$links['self']['href']       = json_url( sprintf( 'b3/comments/%d', $comment_id ) );

		if ( $parent_id ) {
			$links['in-reply-to']['href'] = json_url( sprintf( 'b3/comments/%d', $parent_id ) );
		}

		return $links;
	}

}
