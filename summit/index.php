<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme and one of the
 * two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * For example, it puts together the home page when no home.php file exists.
 *
 * @link http://codex.wordpress.org/Template_Hierarchy
 *
 */
	if (!class_exists('Timber')){
		echo 'Timber not activated. Make sure you activate the plugin in <a href="/wp-admin/plugins.php#timber">/wp-admin/plugins.php</a>';
	}
	$context = Timber::get_context();
	$templates = array('index.twig');

	if (is_front_page() || is_home()){
		
	}else if(is_single()){
		$context['post'] = $context['posts'][0];
		$type = $context['post']->post_type;
		$templates = array('page-'.$context['posts'][0]->post_name.'.twig','single-'.$type.'.twig','single.twig','index.twig');
	}else if(is_page()){
		$templates = array('page-'.$context['posts'][0]->post_name.'.twig','page.twig','index.twig');
	}else if(is_search()){
		$context['query'] = get_search_query();
		$templates = array('search.twig');
	}
	Timber::render($templates, $context);



// Helper functions
function get_cat_img($id){
	// Get category image
	$args = array(       
		'post_type' => 'attachment',
		'post_status' => 'inherit',
		'category__in' => array($id)
	);
	$query = query_posts($args);
	if($query){
		$query = array_pop($query);		
		return new TimberImage($query->ID);
	}
	return false;
}
?>