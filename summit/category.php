<?php
/**
 * The template for displaying categories
 *
  */
global $wp_query;

$context = Timber::get_context();

$cat_id = get_query_var('cat');
$cat_slug = get_category($cat_id)->slug; 
$context["cat"]["slug"] = $cat_slug;
$context["cat"]["id"] = $cat_id;
$context["cat"]["img"] = get_cat_img($cat_id);

$context['cat']["children"] = Timber::get_terms('category', array('parent' => $cat_id));

foreach($context['cat']['children'] as $child){
	
	$child->img = get_cat_img($child->id);
	
	// Get category programs & partners
	$args = array(
		'post_type' => array('programs','partners'),
		'post_status' => 'publish',
		'category_name' => $child->slug,
	);
	query_posts($args);
	$child->posts = Timber::get_posts();
}

Timber::render('category.twig',$context);

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