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
		$id = (int) $request->get_param( 'id' );

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
		$id = (int) $request->get_param( 'id' );

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
		$id   = (int) $request->get_param( 'id' );
		$data = $request->get_params();

		try {
			$post        = B3_Post_Model::get_instance_by_id( $id );
			$comment_id  = $post->reply_with_data( $data );
			$new_comment = B3_Comment_Model::get_instance_by_id( $comment_id );
		} catch ( B3_API_Exception $exception ) {
			return $exception->get_wp_error();
		}

		return $this->prepare_item_for_response( $new_comment, $request );
	}

	/**
	 * Create a comment in response to another comment.
	 *
	 * @param WP_JSON_Request $request Full details about the request
	 *
	 * @return array|WP_Error
	 */
	public function create_subitem( $request ) {
		$id   = (int) $request->get_param( 'id' );
		$data = $request->get_params();

		try {
			$comment     = B3_Comment_Model::get_instance_by_id( $id );
			$new_comment = $comment->reply_with_data( $data );
		} catch ( B3_API_Exception $exception ) {
			return $exception->get_wp_error();
		}

		return $this->prepare_item_for_response( $new_comment, $request );
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

}
