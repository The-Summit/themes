<?php
/**
 * The template for displaying the archives
 *
  */
global $wp_query;

$context = Timber::get_context();
$p = $context['posts'][0];
$type = $p->post_type;
$name = $p->post_name;
$id = $p->ID;
$ps = Timber::get_posts(
	array(
		'post_type' => $type, 
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'order' => 'ASC',
		'orderby' => 'menu_order',
		'post_parent' => $id
	)
);
foreach($ps as $post){
	if (isset($post->background_image) && strlen($post->background_image)){
		$post->background_image = new TimberImage($post->background_image);
	}
}
$landing = new TimberPost($p->landing_page);

$context['posts'] = array_merge(array($p), $ps);
$context['landing'] = $landing;

$templates = array('page-' . $name . '.twig','single-'.$type.'.twig','single.twig','index.twig');
Timber::render($templates,$context);

?>