<?php

/**
 * Save
 */
function bajaw_save_stories_config(){
    $saved = false;
    
    if($_POST['baja_cpt'] && is_string($_POST['baja_cpt']) )
    {
        $baja_cpt = sanitize_text_field( $_POST['baja_cpt'] );
        update_option('bw_stories_cpt', $baja_cpt );
        $saved = true;
    }
    if($_POST['for_cat_post'] && is_string($_POST['for_cat_post']) )
    {
        $baja_cat_post = sanitize_text_field( $_POST['for_cat_post'] );
        update_option('bw_stories_cat_post', $baja_cat_post);
        $saved = true;
    }
    if($_POST['boton'] && is_string($_POST['boton']) )
    {
        $boton = sanitize_text_field( $_POST['boton'] );
        update_option('bw_stories_boton', $boton );
        $saved = true;
    }
    if( $_POST['num_icons'] || $_POST['num_icons'] == '0')
    {
        $num_icons = intval( sanitize_text_field( $_POST['num_icons'] ) );
        update_option('bw_num_icons', $num_icons);
        $saved = true;
    }
    if( $_POST['cant_hist'] || $_POST['cant_hist'] == '0')
    {
        $cant_hi = intval( sanitize_text_field( $_POST['cant_hist'] ) );
        update_option('bw_stories_cant_hist', $cant_hi);
        $saved = true;
    }
    if( $_POST['tiempo'] || $_POST['tiempo'] == '0') 
    {
        $baja_tiempo = intval( sanitize_text_field( $_POST['tiempo'] ) );
        update_option('bw_tiempo', $baja_tiempo);
        $saved = true;
    }
    
    return $saved;
}

function bajaw_add_notice($message, $type = 'success'){
    global $bw_notice;
    $bw_notice[] = array('message' => $message, 'type' => $type);
}

function bajaw_admin_notice() {
    global $bw_notice;

    if($bw_notice)
    foreach( $bw_notice as $notice ){
        $class = 'notice notice-' . $notice['type'] . ' is-dismissible';

	    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $notice['message'] ) ); 
    }
	
}
add_action( 'admin_notices', 'bajaw_admin_notice' );



function bajaw_include_scripts() {
    wp_register_style( 'bajaw-zuck', plugins_url( '/css/zuck.min.css' , __FILE__ ) );
    wp_register_style( 'bajaw-stories', plugins_url( '/css/bwstories.css' , __FILE__ ) );
    wp_register_script('bajaw-zuck', plugins_url( '/js/zuck.min.js' , __FILE__ ) );
    wp_register_script('bajaw-stories', plugins_url( '/js/bwmainscript.js' , __FILE__ ) );

    wp_enqueue_style( 'bajaw-zuck' );
    wp_enqueue_style( 'bajaw-stories' );
    wp_enqueue_script( 'bajaw-zuck' );
    wp_enqueue_script( 'bajaw-stories' );
}
add_action( 'wp_enqueue_scripts', 'bajaw_include_scripts' );

function bajaw_insert_test_featured_image( $post_id, $image_num ){
    
    $image_url        = 'https://baja.website/wp-content/uploads/2022/10/stories_test_' . $image_num . '.jpg';
    $image_name       = 'stories_test_' . $image_num . '.jpg';
    $upload_dir       = wp_upload_dir();
    $image_data       = file_get_contents($image_url);
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name );
    $filename         = basename( $unique_file_name );

    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents( $file, $image_data );

    $wp_filetype = wp_check_filetype( $filename, null );

    $attachment = array( 'post_mime_type' => $wp_filetype['type'], 'post_title' => sanitize_file_name( $filename ), 'post_content' => '', 'post_status' => 'inherit' );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    wp_update_attachment_metadata( $attach_id, $attach_data );

    set_post_thumbnail( $post_id, $attach_id );
}

function bajaw_create_test_stories(){
    global $user_ID;

    $baja_cpt = get_option('bw_stories_cpt');

    $cat1 = 0;
    $cat2 = 0;
    $test_cat1 = get_term_by('name', 'Story1', 'category');
    $test_cat2 = get_term_by('name', 'Story2', 'category');
    $success = false;

    if( $test_cat1->term_id )
        $cat1 = $test_cat1->term_id;
    else{
        $test_cat1 = wp_insert_term('Story1', 'category');
        $cat1 = $test_cat1['term_id'];
    }
    if( $test_cat2->term_id )
        $cat2 = $test_cat2->term_id;
    else{
        $test_cat2 = wp_insert_term('Story2', 'category');
        $cat2 = $test_cat2['term_id'];
    }


    $test_categories = array( $cat1, $cat2);
    $test_posts = array();

    for($bwi = 0; $bwi <3; $bwi++){
        $cat_id = ($bwi % 2 == 0) ? 0 : 1;
        $new_post = array( 'post_title' => 'Test Story ' . ($bwi + 1), 'post_content' => 'This is just a test', 'post_status' => 'publish',
            'post_date' => date('Y-m-d H:i:s'), 'post_author' => $user_ID, 'post_type' => $baja_cpt,
            'post_category' => array( $test_categories[$cat_id])
        );

        $post_id = wp_insert_post($new_post);
        if( $post_id ){
            bajaw_insert_test_featured_image( $post_id, $bwi );
            $success = true;
        }
    }

    if(  $success )
        bajaw_add_notice( __('Test posts created successfully') );
}

function bajaw_stories_pluginprefix_activate() {
    update_option('bw_stories_boton', 'View more' );
    update_option('bw_stories_cpt', 'post');
    update_option('bw_stories_cat_post', 'category');
    update_option('bw_stories_cant_hist', '3');
    update_option('bw_num_icons', '10');
    update_option('bw_tiempo', '3');

}
register_activation_hook( __FILE__, 'bajaw_stories_pluginprefix_activate' );

function bajaw_stories_clean_posttypes()
{
    $args = array(
        'public'   => true
     );
 
    $output = 'names'; // names or objects, note names is the default
    $operator = 'and'; // 'and' or 'or'
    $allpost_types = get_post_types( $args, $output, $operator );
    
    return $allpost_types;
}

function bw_stories_settings_link( $links ) {
	$url = esc_url( add_query_arg( 'page', BW_WPSTORIES_SLUG, get_admin_url() . 'admin.php' ) );
	$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
    
    $url_pro = 'https://baja.website/wpstories';
	$settings_link_pro = "<a href='$url_pro' target='_blank'><b>" . __( 'Get Pro' ) . '</b></a>';

	array_push(
		$links,
		$settings_link, $settings_link_pro
	);
	return $links;
}
add_filter( 'plugin_action_links_bw-product-stories/product-stories.php', 'bw_stories_settings_link' );

?>