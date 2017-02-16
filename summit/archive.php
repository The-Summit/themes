<?php
/**
 * The template for displaying the archives
 *
  */
global $wp_query;

$context = Timber::get_context();
if(!is_post_type_archive("bios")&&!is_post_type_archive("history")&&!is_post_type_archive("partners")){
	$context["body_class"] .= " onepager";
}
Timber::render('archive.twig',$context);

?>