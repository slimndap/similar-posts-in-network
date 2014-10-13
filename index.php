<?php
/*
Plugin Name: Similar Posts in Network
Plugin URI: http://slimndap.com
Description: Show posts with a similar title in the network.
Author: Jeroen Schmit, Slim & Dapper
Version: 0.1
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
	add_action( 'load-post.php', 'call_simpin' );
}

class simpin {
	function __construct() {
		add_action('add_meta_boxes',array($this,'add_meta_boxes'),10,2);		
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
	
	function meta_box($post, $metabox) {
		$sites = wp_get_sites();
		$current_blog_id = get_current_blog_id();
		foreach($sites as $site) {
			if ($site['blog_id']!=$current_blog_id) {
				switch_to_blog($site['blog_id']);
				$args = array(
					'post_type' => $post->post_type,
					'posts_per_page' => -1,
					's' => $post->post_title
				);
				$similar_posts = get_posts($args);
				if (!empty($similar_posts)) {
					echo '<h4>'.get_bloginfo('title').'</h4>';
					echo '<ul>';
					foreach($similar_posts as $similar_post) {
						if ($similar_post->post_title == $post->post_title) {
							echo '<li>';
							echo '<a href="'.get_edit_post_link($similar_post->ID).'">';
							echo $similar_post->post_title;
							echo '</a>';
							echo '</li>';
							
						}
					}			
					echo '</ul>';		
				}
			}
		}
		
		restore_current_blog();
	}
}
