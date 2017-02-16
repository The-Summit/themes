<?php
/* add theme support */
add_theme_support( 'post-thumbnails');
add_theme_support( 'custom-header', array(
	'default-image' => get_template_directory_uri() .'/images/logo-w-above.png'
));

/* jQuery dequeue / theme enqueue */
if (!is_admin()) add_action("wp_enqueue_scripts", "scripts_enqueue", 11);
function scripts_enqueue() {
	wp_deregister_script('jquery');
	if(preg_match('/(?i)msie [2-8]/',$_SERVER['HTTP_USER_AGENT'])) {
		wp_register_script('jquery', "http://code.jquery.com/jquery-1.11.0.min.js", false, null, true);   
	}else{
		wp_register_script('jquery', "http://code.jquery.com/jquery-2.1.0.min.js", false, null, true);   
	}
	wp_register_script('bootstrap', "http://netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js", array('jquery'), null, true); 
	wp_register_script('plugins-script', get_template_directory_uri().'/js/plugins.js', array('jquery'), null, true);
	wp_register_script('theme-script', get_template_directory_uri().'/js/script.js', array('jquery','plugins-script','bootstrap'), null, true); 
	wp_enqueue_script('theme-script');
}

/* Add CSS version for better caching */
update_option( "cssver", file_get_contents(get_template_directory() . "/css.ver"));

/* Allow users to switch between all TS programs */
	
function global_nav(){
	$sites = wp_get_sites();
	$original = get_current_blog_id();
	$header_images = [];
	foreach($sites as $site){
		switch_to_blog($site->blog_id);
		array_push($header_images, array(get_site_url()=>get_header_image()));
	}
	switch_to_blog($original);
	return $header_images;
}

/* Let categories have featured images */
function wptp_add_categories_to_attachments() {
      register_taxonomy_for_object_type( 'category', 'attachment' );  
}  
add_action( 'init' , 'wptp_add_categories_to_attachments' );

if(is_main_site() && !is_admin()){
	Timber::add_route('/', function($params){
		$cat_id = get_category_by_slug( "pillars" )->cat_ID;
		$query = 'cat='. $cat_id;
		Timber::load_template('category.php', $query);
	});
}
/* Clean up HEAD */
remove_action ('wp_head', 'rsd_link');
remove_action( 'wp_head', 'wlwmanifest_link');
remove_action( 'wp_head', 'wp_shortlink_wp_head');
remove_action('wp_head', 'wp_generator');

/* Disable WordPress automatic formatting */
remove_filter('the_content', 'wpautop');

/* Move Gravity Forms scripts to footer */
add_filter( 'gform_cdata_open', 'wrap_gform_cdata_open' );
function wrap_gform_cdata_open( $content = '' ) {
	$content = 'document.addEventListener( "DOMContentLoaded", function() { ';
	return $content;
}
add_filter( 'gform_cdata_close', 'wrap_gform_cdata_close' );
function wrap_gform_cdata_close( $content = '' ) {
	$content = ' }, false );';
	return $content;
}
add_filter("gform_init_scripts_footer", "init_scripts");
function init_scripts() {
	return true;
}

/* Dynamically populate Gravity Forms dropdowns */
add_filter("gform_pre_render", "add_custom_post_dropdowns");
add_filter("gform_admin_pre_render", "add_custom_post_dropdowns");
add_filter("gform_pre_submission_filter", "add_custom_post_dropdowns");


function add_custom_post_dropdowns($form){
	$types = get_post_types();
	foreach($form["fields"] as &$field){
		$t = $field['type'];
		if( $t === 'select' || $t === 'checkbox' ){
			if($t==='checkbox'){
				$t = false;
				$link = true;
			}else{
				$t = true;
				$link = false;
			}
			foreach(explode(" ",$field['cssClass']) as $class){
				if(in_array($class,$types)){
					$field["choices"] = gravity_posts_of_type($class,$t,$link);
				}
			}
		}
	}
   return $form;
}
function gravity_posts_of_type($type,$blank = false,$link = false){
	$posts = get_posts(array(
		'post_type' => $type,
		'nopaging' => true
	));
	$items = array();
	if($blank){
		$items[] = array("text" => "", "value" => "");
	}
	foreach($posts as $post){
		if($link){
			$label = "<a target='_blank' href='/" . $type . "#" . $post->post_name . "'>" . $post->post_title . "</a>";
		}else{
			$label = $post->post_title;
		}
        $items[] = array("value" => $post->post_name, "text" => $label);
	}	
	return $items;
}
function order_menu($query){
	$query->query_vars['orderby'] = 'menu_order';
	if (is_front_page() || is_home()){
		if($query->query_vars['post_type'] != "nav_menu_item"){
			$query->query_vars['meta_key'] = 'front_page_order';
			$query->query_vars['orderby'] = 'meta_value_num';
		}
	}
	$query->query_vars['order'] = 'ASC';
	return $query;
}
/* Return all archives */
function archive_page_setup($query) {
	if ( $query->is_post_type_archive() ) {
		set_query_var('posts_per_archive_page', -1);
		if(is_post_type_archive("partners")){
			set_query_var('orderby', 'rand');
		}
		if(is_post_type_archive("history")){
			$query->query_vars['orderby'] = 'title';
		}
		if(is_post_type_archive("news")){
			$query->query_vars['orderby'] = 'meta_value';
			$query->query_vars['meta_type'] = 'DATE';
			$query->query_vars['meta_key'] = 'article_date';
			$query->query_vars['order'] = 'DESC';
		}
	}
	return $query;
}
add_filter('pre_get_posts', 'order_menu');
add_filter('pre_get_posts', 'archive_page_setup');

/* History JSON*/
function historyJSON(){
	$args = array(
		'post_type' => 'history',
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
		'posts_per_page' => -1
	); 
	$posts = get_posts($args);
	$timeline = array(
		"timeline"=>array(
			"headline" => "We Believe in Our History",
			"type" => "default",
			"startDate" => $posts[0]->post_title
		)
	);

	$dates = array();
	foreach($posts as $post){
			$media1 = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'full');
			$media = $media1[0];
			$baseurl = wp_upload_dir();
			$media = $baseurl["baseurl"] . preg_replace("/(.*)uploads/", "", $media);
			$date = array(
					"startDate" => $post->post_title,
					"headline" => $post->post_title,
					"text" => "<p>" . $post->post_content . "</p>",
					"asset" => array(
							"media" => $media
					)
			);
			array_push($dates,$date);
	}
	$timeline["timeline"]["date"] = $dates;
	return str_replace('\\/', '/',json_encode($timeline));
}

/* Setup Admin Menus */
add_theme_support( 'menus' );
function remove_menus () {
	$remove_menu_items = array(__('Posts'),__('Comments'));
	global $menu;
	end ($menu);
	while (prev($menu)){
		$item = explode(' ',$menu[key($menu)][0]);
		if(in_array($item[0] != NULL?$item[0]:"" , $remove_menu_items)){
		unset($menu[key($menu)]);}
	}
}
add_action('admin_menu', 'remove_menus');

/* Add stuff to the global context */
add_filter('timber_context', 'add_to_context');
function add_to_context($context){
	$context['menu'] = new TimberMenu();
	$context['posts'] = Timber::get_posts();
	if($context['posts']){
		foreach($context['posts'] as $post){
			if (isset($post->background_image) && strlen($post->background_image)){
				$post->background_image = new TimberImage($post->background_image);
			}
		}
		if($context['posts'][0]->post_name=="campus-map" || $context['posts'][0]->post_name=="room-configurator" ){
			$context['partners'] = Timber::get_posts(array('post_type' => 'partners', 'nopaging' => true));
			$context['properties'] = Timber::get_posts(array('post_type' => 'property', 'nopaging' => true));
			$context['buildings'] = Timber::get_posts(array('post_type' => 'buildings', 'nopaging' => true));
			$search = array($context['partners'],$context['properties']);
		}elseif(is_archive()){
			$search = array($context['posts']);
		}else{
			return $context;
		}
		foreach($search as $arr){
			foreach($arr as $item){
				if($item->building > 0){
					$build = new TimberPost($item->building);
					$item->building = $build;
				}else{
					unset($item->building);
				}
			}
		}		
	}
	return $context;
}

/*
	******Allow Custom Post Archives in WP menus******
   Copyright 2012  soulseekah  (twitter: @soulseekah)
*/

// don't load directly
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


if (!class_exists("Custom_Post_Type_Archive_Menu_Links")) :

class Custom_Post_Type_Archive_Menu_Links {

  /* boot'er up */
  function init(){

    // Set-up Action and Filter Hooks
    add_action( 'admin_head-nav-menus.php', array(__CLASS__,'inject_cpt_archives_menu_meta_box' ));
    add_filter( 'wp_get_nav_menu_items', array(__CLASS__,'cpt_archive_menu_filter'), 10, 3 );
  }
  
  /* inject cpt archives meta box */
  
  function inject_cpt_archives_menu_meta_box() {
    add_meta_box( 'add-cpt', __( 'CPT Archives', 'default' ), array(__CLASS__,'wp_nav_menu_cpt_archives_meta_box'), 'nav-menus', 'side', 'default' );
  }

  /* render custom post type archives meta box */
  function wp_nav_menu_cpt_archives_meta_box() {
    /* get custom post types with archive support */
    $post_types = get_post_types( array( 'show_in_nav_menus' => true, 'has_archive' => true ), 'object' );

    /* hydrate the necessary object properties for the walker */
    foreach ( $post_types as &$post_type ) {
        $post_type->classes = array();
        $post_type->type = $post_type->name;
        $post_type->object_id = $post_type->name;
        $post_type->title = $post_type->labels->name . ' ' . __( 'Archive', 'default' );
        $post_type->object = 'cpt-archive';
    }


    $walker = new Walker_Nav_Menu_Checklist( array() );

    ?>
    <div id="cpt-archive" class="posttypediv">
      <div id="tabs-panel-cpt-archive" class="tabs-panel tabs-panel-active">
        <ul id="ctp-archive-checklist" class="categorychecklist form-no-clear">
          <?php
            echo walk_nav_menu_tree( array_map('wp_setup_nav_menu_item', $post_types), 0, (object) array( 'walker' => $walker) );
          ?>
        </ul>
      </div><!-- /.tabs-panel -->
    </div>
    <p class="button-controls">
      <span class="add-to-menu">
        <img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
        <input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-ctp-archive-menu-item" id="submit-cpt-archive" />
      </span>
    </p>
    <?php
  }


  /* take care of the urls */
  function cpt_archive_menu_filter( $items, $menu, $args ) {
    /* alter the URL for cpt-archive objects */
    foreach ( $items as &$item ) {
      if ( $item->object != 'cpt-archive' ) continue;
      $item->url = get_post_type_archive_link( $item->type );
      
      /* set current */
      if ( get_query_var( 'post_type' ) == $item->type ) {
        $item->classes []= 'current-menu-item';
        $item->current = true;
      }
    }

    return $items;
  }


} // end class
endif;

/**
* Launch the whole plugin
*/
if (class_exists("Custom_Post_Type_Archive_Menu_Links")) Custom_Post_Type_Archive_Menu_Links::init(); 

/*Remove marketing boxes*/
function my_remove_meta_boxes() {
	$types = get_post_types();
	foreach($types as $type){
		remove_meta_box( 'wpcf-marketing', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'my_remove_meta_boxes' );

/*Allow pages to have categories (for putting a page on the Front Page*/
function page_categories() {  
	register_taxonomy_for_object_type('category', 'page');  
}
add_action( 'admin_init', 'page_categories' );

add_filter('upload_dir', 'move_upload_dir');
function move_upload_dir($upload) {
	$upload['path'] = ROOT_DIR . AE_UPLOAD_DIR;
	$upload['basedir'] = ROOT_DIR . AE_UPLOAD_DIR;
	if ( !defined('DEV')  || DEV == false){
		$upload['url'] = home_url() ."/shared/uploads";
		$upload['baseurl'] = home_url() ."/shared/uploads";
	}else{
		$upload['url'] = home_url() ."/wp-content/uploads";
		$upload['baseurl'] = home_url() ."/wp-content/uploads";
	}
	$upload['error'] = false;	
	return $upload;
}
add_action('twig_apply_filters', 'add_ae_twig_filters');
function add_ae_twig_filters($twig) {
	$twig->addFilter('better_resize', new Twig_Filter_Function('better_resize'));
	
	$twig = apply_filters('get_twig', $twig);
	return $twig;
}
function better_resize($src, $w, $h = 0){
	$path_parts = pathinfo($src);
	$basename = $path_parts['filename'];
	$ext = $path_parts['extension'];
	$newname = $basename . '-r-' . $w . 'x' . $h . '.' . $ext;
	$new_root_path = ROOT_DIR . AE_UPLOAD_DIR . $newname;
	$old_root_path = ROOT_DIR . AE_UPLOAD_DIR . $basename . '.' . $ext;
	if (file_exists($new_root_path)) {
		return home_url() . AE_UPLOAD_DIR . $newname;
	}
	$image = wp_get_image_editor($old_root_path);
	if (!is_wp_error($image)) {
		$current_size = $image->get_size();
		$ow = $current_size['width'];
		$oh = $current_size['height'];
		$old_aspect = $ow / $oh;
		if ($h) {
			$new_aspect = $w / $h;
			if ($new_aspect > $old_aspect) {
				//cropping a vertical photo horizontally
				$oht = $ow / $new_aspect;
				$oy = ($oh - $oht) / 6;
				$image->crop(0, $oy, $ow, $oht, $w, $h);
			} else {
				$owt = $oh * $new_aspect;
				$ox = $ow / 2 - $owt / 2;
				$image->crop($ox, 0, $owt, $oh, $w, $h);
			}
		} else {
			$h = $w;
			if ($old_aspect < 1){
				$h = $w / $old_aspect;
				$image->crop(0, 0, $ow, $oh, $w, $h);
			} else {
				$image->resize($w, $h);
			}
		}
		$result = $image->save($new_root_path);
		if (is_wp_error($result)){
			error_log('Error resizing image');
		}else{
			return home_url() . AE_UPLOAD_DIR . $newname;
		}
	}
	return $src;
}
/* Allow SVG Uploads */
function allow_svg_upload_mimes( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'allow_svg_upload_mimes' );

?>