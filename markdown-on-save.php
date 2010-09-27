<?php
/*
Plugin Name: Markdown on Save
Description: Allows you to compose posts in Markdown on a once-off basis. The markdown version is stored separately, so you can deactivate this plugin and your posts won't spew out Markdown.
Version: 1.0
Author: Mark Jaquith
Author URI: http://coveredwebservices.com/
*/

class CWS_Markdown {
	const PM = '_cws_is_markdown';
	var $instance;

	public function __construct() {
		$this->instance =& $this;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		load_plugin_textdomain( 'markdown-on-save', NULL, basename( dirname( __FILE__ ) ) );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_action( 'add_meta_boxes_post', array( $this, 'add_meta_boxes_post' ) );
		add_filter( 'edit_post_content', array( $this, 'edit_post_content' ), 10, 2 );
		add_filter( 'edit_post_content_filtered', array( $this, 'edit_post_content_filtered' ), 10, 2 );
	}

	public function wp_insert_post_data( $data, $postarr ) {
		if ( isset( $_POST['cws_using_markdown'] ) ) {
			$data['post_content_filtered'] = $data['post_content'];
			$data['post_content'] = $this->unp( Markdown( $data['post_content'] ) );
			if ( $postarr['ID'] )
				update_post_meta( $postarr['ID'], self::PM, true );
		} else {
			$data['post_content_filtered'] = '';
			if ( $postarr['ID'] )
				delete_post_meta( $postarr['ID'], self::PM );
		}
		return $data;
	}

	public function add_meta_boxes_post() {
		add_meta_box( 'cws-markdown', __('Markdown'), array( $this, 'meta_box' ), 'post', 'side', 'high' );
	}

	public function meta_box() {
		global $post;
		echo '<p><input type="checkbox" name="cws_using_markdown" id="cws_using_markdown" value="1" ';
		checked( !! get_post_meta( $post->ID, self::PM, true ) );
		echo ' /> <label for="cws_using_markdown">' . __( 'This post is formatted with Markdown', 'markdown-on-save' ) . '</label></p>';
	}

	protected function unp( $content ) {
		// return preg_replace( '<p>', '<foo>', $content );
		return preg_replace( "#<p>(.*?)</p>(\n|$)#", '$1$2', $content );
	}

	private function is_markdown( $id ) {
		return !! get_post_meta( $id, self::PM, true );
	}

	public function edit_post_content( $content, $id ) {
		if ( $this->is_markdown( $id ) ) {
			$post = get_post( $id );
			if ( $post )
				$content = $post->post_content_filtered;
		}
		return $content;
	}

	public function edit_post_content_filtered( $content, $id ) {
		return $content;
	}

}

require_once( dirname( __FILE__) . '/markdown-extra/markdown-extra.php' );
new CWS_Markdown;
