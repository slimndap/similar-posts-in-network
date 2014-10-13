<?php
/*
Plugin Name: Similar Posts in Network
Plugin URI: http://slimndap.com
Description: Show posts with a similar title in the network.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.2
Author URI: http://slimndap.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Calls the class on the post edit screen.
 */
function call_simpin() {
    new simpin();
}

if ( is_admin() ) {
	//add_action( 'load-post.php', 'call_simpin' );
}

class simpin {
	function __construct() {
		add_action('add_meta_boxes',array($this,'add_meta_boxes'),10,2);		
		add_action('wp_loaded', array( $this, 'wp_loaded' ));
	}
	
	function add_meta_boxes($post_type, $post) {
		if (is_admin()) {
			add_meta_box( 
				'simpin_meta_box',
				__( 'Similar posts in network', 'simpin' ),
				array($this,'meta_box'),
				'artikel',
				'side',
				'core'
			);			
		}
	}
	
	function create_similar_post_in($site_id, $post) {
		
		if (is_numeric($post)) {
			$post = get_post($post);
			
		}

		unset($post->ID);
		unset($post->post_category);

		switch_to_blog($site_id);
		
		wp_insert_post($post);
		
		restore_current_blog();	
		
		
	}
	
	function delete_similar_post_in($site_id, $post_id) {

		switch_to_blog($site_id);

		wp_delete_post($post_id);
		
		restore_current_blog();	
		
	}
	
	function meta_box($post, $metabox) {
	
		echo $this->render_meta_box($post);
	}
	
	function get_name_for_site($site_id) {
		return get_blog_details($site_id)->blogname;
	}
	
	function get_sites() {
		return wp_get_sites();
	}
	
	function get_similar_posts_for_site($site_id, $post) {
		switch_to_blog($site_id);

		$args = array(
			'post_type' => $post->post_type,
			'posts_per_page' => -1,
			'post_status' => 'published',
			's' => $post->post_title
		);
		$posts = get_posts($args);

		restore_current_blog();	
		
		return $posts;	
	}
	
	function render_meta_box($post) {
		$html = '';
		
		foreach($this->get_sites() as $site) {
			if ($site['blog_id']!=get_current_blog_id()) {
				$html.= '<h4>'.$this->get_name_for_site($site['blog_id']).'</h4>';
				
				$similar_posts = $this->get_similar_posts_for_site($site['blog_id'], $post);
				
				if (empty($similar_posts)) {
					$html.= $this->render_post_actions(NULL, $site['blog_id']);
				} else {
					
					$html.= '<ul>';
					foreach ($similar_posts as $similar_post) {
						$html.= '<li>';
						$html.= $this->render_post_for_site($similar_post, $site['blog_id']);
						$html.= $this->render_post_actions($similar_post, $site['blog_id']);
						$html.= '</li>';
					}
					$html.= '</ul>';

				}
				
			}
		}
		
		return $html;
	}
	
	function render_post_actions($post=NULL, $site_id) {

		
		$actions= array();
		
		if (empty($post)) {
			$actions['create'] = array(
				'label' => 	__('create','simpin'),
				'url' => 	wp_nonce_url(
								add_query_arg('create_similar_post_in', $site_id),
								'create_similar_post_in',
								'simpin_nonce'
							),

			);
		}
		
		if (!empty($post)) {
			switch_to_blog($site_id);

			$actions['edit'] = array(
				'label' => __('edit','simpin'),
				'url' => get_edit_post_link($post->ID)
			);
			
			$url = add_query_arg('delete_similar_post', $post->ID);
			$url = add_query_arg('delete_similar_post_in', $site_id, $url);
			$actions['delete'] = array(			
				'label' => __('delete','simpin'),
				'url' => 	wp_nonce_url(
								$url,
								'delete_similar_post',
								'simpin_nonce'
							),
			);

			restore_current_blog();	
		}
		
		$html = '';
		$html.= '<ul class="simpin_actions">';
		foreach($actions as $action) {
			$html.= '<li>';
			$html.= '<a href="'.$action['url'].'">'.$action['label'].'</a>';
		}
		$html.= '</ul>';
		

		return $html;

	}
	
	function render_post_for_site($post, $site_id) {

		switch_to_blog($site_id);

		$html = '';
		$html.= '<a href="'.get_edit_post_link($post->ID).'">';
		$html.= $post->post_title;
		$html.= '</a>';

		restore_current_blog();	

		return $html;
	}

	function wp_loaded() {
		if (!empty($_GET['create_similar_post_in']) && check_admin_referer( 'create_similar_post_in' , 'simpin_nonce')) {
			$this->create_similar_post_in($_GET['create_similar_post_in'],$_GET['post']);
			$url = remove_query_arg('create_similar_post_in');
			$url = remove_query_arg('simpin_nonce', $url);
			wp_redirect($url);
			die();
		}
		
		if (!empty($_GET['delete_similar_post']) && check_admin_referer( 'delete_similar_post' , 'simpin_nonce')) {
			$this->delete_similar_post_in($_GET['delete_similar_post_in'],$_GET['delete_similar_post']);
			$url = remove_query_arg('delete_similar_post');
			$url = remove_query_arg('delete_similar_post_in', $url);
			$url = remove_query_arg('simpin_nonce', $url);
			wp_redirect($url);
			die();
		}
	}
	
}

new simpin();
