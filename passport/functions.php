<?php
add_action( 'wp_enqueue_scripts', 'load_my_child_styles', 20 );
function load_my_child_styles() {
    wp_enqueue_style( 'child-theme', get_stylesheet_uri() );
}
?>