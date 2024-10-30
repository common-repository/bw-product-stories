<?php
/*
Plugin Name: BW WP Stories
Plugin URI: https://www.baja.website/
Description: Show posts and products like Instagram Stories, create your own style and config how it will show.
Version: 3.1.4
Author: Federico Jauregui
Author URI: https://www.baja.website/
Text Domain: bw_product_stories
*/

define( 'BW_WPSTORIES_SLUG', 'bw_wpstories' );

require_once 'class-story.php';
require_once 'class-stories.php';
require_once 'functions.php';

if ( function_exists( 'add_theme_support' ) ) {
    add_theme_support( 'post-thumbnails' );
    set_post_thumbnail_size( 150, 150, true ); // default Featured Image dimensions (cropped)
 
    // additional image sizes
    add_image_size( 'stories-thumb', 450, 9999 ); // 450 pixels wide (and unlimited height) for mobiles
}

function bajaw_submenu_page() {
	    add_menu_page( 'WP Stories by posts', 'WP Stories', 'edit_posts', BW_WPSTORIES_SLUG, 'bajaw_submenu_page_callback', plugins_url('/img/wpstories_icon_20.png', __FILE__ ), 33 ); 
        //add_submenu_page( BW_WPSTORIES_SLUG, __('All Stories'), __('All Stories'), 'edit_posts', 'bw_wpstories');
        add_submenu_page( BW_WPSTORIES_SLUG, __('Stories Settings'), __('Settings'), 'edit_posts', 'bw_wpstories');
        add_submenu_page( BW_WPSTORIES_SLUG, __('How WPStories Work'), __('About'), 'edit_posts', 'bw_wpstories_about', 'bw_wpstories_about');

}
add_action('admin_menu', 'bajaw_submenu_page');

function bajaw_init_check(){

    if( !current_user_can( 'edit_posts' ) )
        return;
    
    if( !is_admin() || !isset($_GET['page']) || $_GET['page'] != BW_WPSTORIES_SLUG )
        return;

    if(isset($_POST) && isset( $_REQUEST['bajaw-update'] ) && $_POST['submit'] && wp_verify_nonce( $_REQUEST['bajaw-update'], 'bajaw-update' ) )
    {
        if(bajaw_save_stories_config())
            bajaw_add_notice( __('Stories configuration update success'), 'success');
    }
    if( $_GET['create_test'] ){
        bajaw_create_test_stories();
    }
}
add_action( 'init', 'bajaw_init_check' );

function bajaw_submenu_page_callback() {
    
    if( !current_user_can( 'edit_posts' ) )
        return;
    
    $boton = get_option('bw_stories_boton');
    $baja_cpt = get_option('bw_stories_cpt');
    $baja_cat_post = get_option('bw_stories_cat_post');
    $num_icons = get_option('bw_num_icons');
    $cant_hist = get_option('bw_stories_cant_hist');
    $baja_tiempo = get_option('bw_tiempo');

    if( !$boton )
        $boton = __('View More');
    if( !$num_icons )
        $num_icons = 10;
    if( !$cant_hist )
        $cant_hist = 3;
    if( !$baja_tiempo )
        $baja_tiempo = 3;
	
    $have_posts = false;
    $args = array('post_type' => $baja_cpt, 'meta_query' => array(
        array(
         'key' => '_thumbnail_id',
         'compare' => 'EXISTS'
        ),
    ));
    $cpt_query = new WP_Query( $args );

    if( $cpt_query->have_posts() )
        $have_posts = true;

    ?>
    <h1><?php _e('Stories Configuration'); ?></h1>
    <form method="post" action="" novalidate="novalidate">
    <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="for_post"><?php _e('Post Type to use in stories'); ?></label></th>
                <td>
                    <?php 
                    $listado_pt = bajaw_stories_clean_posttypes();
                    ?>
                    <select name="baja_cpt" id="baja_cpt" class="regular-dropdown <?php echo $baja_cpt; ?>">
                        <?php
                        foreach($listado_pt as $postt) {
                            $selected = ($baja_cpt == $postt) ? 'selected' : '';
                            echo '<option value="' . $postt . '" '.$selected.' >' . ucfirst($postt) . '</option>';
                        }
                        ?>
                    </select>
                    <p><?php _e('Stories will be displayed by taking the current items of the Post Type you select.'); ?></p>
                    <?php if(!$have_posts): ?> 
                        <div class="notice notice-error">
                            <p><?php 
                                global $wp;
                                $url_create = add_query_arg( array( 'page' => 'bw_wpstories', 'create_test' => '1'), $wp->request );
                            _e('You must have <b>'.$baja_cpt.' with Featured image</b> to see this in action. <a href="' . $url_create .'" >Click here to create test <b>'.$baja_cpt.'</b></a>'); ?></p>
                        </div>
                    <?php endif; ?>
                    </td>
            </tr>
            <tr>
                <th scope="row"><label for="for_cat_post"><?php _e('Loop by category or by post'); ?></label></th>
                <td>
                    <?php 
                    $listado_lo = array('category', 'post');
                    ?>
                    <select name="for_cat_post" id="for_cat_post" class="regular-dropdown <?php echo $baja_cat_post; ?>">
                        <?php
                        foreach($listado_lo as $loop_option) {
                            $selected = ($baja_cat_post == $loop_option) ? 'selected' : '';
                            echo '<option value="' . $loop_option . '" '.$selected.' >' . ucfirst($loop_option) . '</option>';
                        }
                        ?>
                    </select>
                    <p><?php _e('If you select <b>Category</b> the circular icons will be the categories of the Post Type and the stories will be the featured images of the latest Posts. <br>
                                If you select <b>Post</b> the circular icons will be the last posts of the Post Type and the stories will be the internal images of that post.'); ?></p>
                    </td>
            </tr>
            <tr>
                <th scope="row"><label for="boton"><?php _e('Link Text'); ?></label></th>
                <td><input name="boton" type="text" id="boton" value="<?php echo $boton; ?>" class="regular-text">
                <p><?php _e('Text that will appear as a bottom button in the history'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="num_icons"><?php _e('Number of icons'); ?></label></th>
                <td><input name="num_icons" type="number" id="num_icons" value="<?php echo $num_icons; ?>" class="regular-text">
                <p><?php _e('Maximum number of icons'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="cant_hist"><?php _e('Number of stories'); ?></label></th>
                <td><input name="cant_hist" type="number" id="cant_hist" value="<?php echo $cant_hist; ?>" class="regular-text">
                <p><?php _e('Maximum number of stories to display per icon'); ?></p>
                </td>
            </tr>
			<tr>
                <th scope="row"><label for="tiempo"><?php _e('Story time'); ?></label></th>
                <td><input name="tiempo" type="number" id="tiempo" value="<?php echo $baja_tiempo; ?>" class="regular-text"></td>
            </tr>
			
            
    </table>
        
    <?php wp_nonce_field( 'bajaw-update', 'bajaw-update' ); ?>
        
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes'); ?>"></p>
    </form>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="shortcode"><?php _e('Shortcode'); ?></label></th>
            <td><input type="text" name="shortcode" value="[bw_show_stories]" readonly=""></td>
        </tr> 
        <tr>
            <th scope="row"><label for="phpcode"><?php _e('PHP Code'); ?></label></th>
            <td><code>&lt;?php echo do_shortcode( '[bw_show_stories]' ); ?&gt;</code></td>
        </tr>
    </table>
    <?php

    
}

function bw_wpstories_about(){

    ?>
    <div class="about__section">
        <div class="column">
            <h1 class="aligncenter"><?php _e('Welcome to WP Stories'); ?></h1>
            <p class="is-subheading">
                <?php _e('Welcome to this new version of WP Stories by post where you can create Instagram style stories based on Posts.'); ?>
            </p>
        </div>
        <div class="column">
            <h2 class="aligncenter"><?php _e('How to use it'); ?></h2>
            <p class="is-subheading">
                <?php _e('To use the plugin 2 steps are necessary.'); ?>
            </p>
            <ul>
                <li><?php _e('Configure the way stories are displayed.'); ?></li>
                <li><?php _e('Insert the Shortcode in the section of your choice.'); ?></li>
            </ul>
        </div>
        <div class="column">
            <h2 class="aligncenter"><?php _e('Video'); ?></h2>
            <p class="is-subheading">
            <iframe width="560" height="315" src="https://www.youtube.com/embed/LtcS-wvxRzU" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </p>
        </div>
        
    </div>
    <?php
}

function bajaw_mostrar_historias( $user_atts = array() )
{
    $user_atts = array_change_key_case( (array) $user_atts, CASE_LOWER );
    $categorias = array();

    $boton = get_option('bw_stories_boton');
    if(!$boton) $boton = 'View more';
    $baja_cpt = get_option('bw_stories_cpt');           //Get the post type
    $num_icons = get_option('bw_num_icons');            //Icons limit
    $cant_hist = get_option('bw_stories_cant_hist');    //Stories limit per icon
    $baja_cat_post = get_option('bw_stories_cat_post'); //Are we working with categories or posts?
    $baja_tiempo = get_option('bw_tiempo');             //Time on screen each story

    $thumb_size = ( wp_is_mobile() ) ? 'stories-thumb' : 'full';
    $thumb_size = 'full';
   
    $wporg_atts = shortcode_atts(
        array(
            'post_type'     => $baja_cpt,
            'orderby'       => '',
            'show_count'    => 0,
            'pad_counts'    => 0,
            'hierarchical'  => 1,
            'button'        => $boton,
            'num_icons'     => $num_icons,
            'stories_qty'   => $cant_hist,
            'time'          => $baja_tiempo,
            'limit'         => 0,
            'in_cat'        => ''
        ), $user_atts, 'bw_mostrar_historias'
    );

    if($wporg_atts['in_cat'] )
        $categorias = explode(",", $wporg_atts['in_cat']);
    
    $historias = array();
    if($baja_cat_post == 'category')
    {
        $all_categories = array();
        $ppal_categories = get_object_taxonomies($baja_cpt);
        foreach($ppal_categories as $cpt_cat){
            $list_categories = array();
            $args = array(
                'taxonomy'     => $cpt_cat,
                'orderby'      => $wporg_atts['orderby'],
                'show_count'   => $wporg_atts['show_count'],
                'pad_counts'   => $wporg_atts['pad_counts'],
                'hierarchical' => $wporg_atts['hierarchical'],
                'title_li'     => $wporg_atts['title'],
                'hide_empty'   => 1
            );
            $list_categories = get_categories( $args );
            foreach($list_categories as $list_cpt){
                $all_categories[] = $list_cpt;
            }
        }
        if($all_categories){

            $historias = array();
            foreach ($all_categories as $cat)
            {
                if($cat->category_parent == 0) 
                {

                    if($categorias && !in_array($cat->slug, $categorias) )
                        continue;

                    $args22 = array(
                        'post_type'             => $baja_cpt,
                        'post_status'           => 'publish',
                        'status'                => 'publish',
                        'limit'                 => $wporg_atts['cant_hist'],
                        'posts_per_page'        => $wporg_atts['num_icons'],
                        'return'                => 'ids',
                        'hide_empty'            => 1,
                        $cat->taxonomy.'_name'  => $cat->slug
                    );

                    $loop = new WP_Query( $args22 );
                    
                    if( $cat->slug != 'uncategorized' && $loop->have_posts() )
                    {
						
						$historias[$cat->slug] = new Story();
                        $historias[$cat->slug]->id = $cat->term_id;
                        $historias[$cat->slug]->name = $cat->name;
                        $historias[$cat->slug]->link = $cat->term_id;
                        $historias[$cat->slug]->seen = false;

                        $category_id = $cat->term_id;    

                        while ( $loop->have_posts() ){
                            $loop->the_post();         
                            $thumb = get_the_post_thumbnail_url(null, $thumb_size);
                            if(!$thumb)
                                continue;
    						
							$historias[$cat->slug]->stories[$loop->post->ID] = new Stories();
                            $historias[$cat->slug]->stories[$loop->post->ID]->id = $loop->post->ID;
                            $historias[$cat->slug]->stories[$loop->post->ID]->type = 'photo';//$loop->post->ID;
                            $historias[$cat->slug]->stories[$loop->post->ID]->length = $wporg_atts['tiempo'];
                            $historias[$cat->slug]->stories[$loop->post->ID]->src = $thumb;
                            $historias[$cat->slug]->stories[$loop->post->ID]->preview = get_the_post_thumbnail_url(null, 'thumbnail');
                            $historias[$cat->slug]->stories[$loop->post->ID]->link = get_permalink( $loop->post->ID );
                            $historias[$cat->slug]->stories[$loop->post->ID]->linkText = $wporg_atts['boton'];
                            $historias[$cat->slug]->stories[$loop->post->ID]->time = strtotime($loop->post->post_date);
                            $historias[$cat->slug]->stories[$loop->post->ID]->seen = false;

                            if(!isset($historias[$cat->slug]->photo))
                                $historias[$cat->slug]->photo = get_the_post_thumbnail_url(null, 'thumbnail');
                        }
                    }
                    wp_reset_query();

                }
            }
        }
    }
    elseif($baja_cat_post == 'post')
    {
        $args22 = array(
            'post_type'             => $baja_cpt,
            'post_status'           => 'publish',
            'status'                => 'publish',
            'posts_per_page'        => $wporg_atts['num_icons'], 
            /*'limit'                 => $wporg_atts['cant_hist'],*/
            'return'                => 'ids',
            'hide_empty'            => 1,
        );
        $loop = new WP_Query( $args22 );
        
        if( $loop->have_posts() )
        {
            while ( $loop->have_posts() ){
                    $loop->the_post();         
                    $thumb = get_the_post_thumbnail_url(null, $thumb_size);
                    if(!$thumb)
                        continue;
                    $id_post = $loop->post->ID;
				
					$historias[$id_post] = new Story();
                    $historias[$id_post]->id = $id_post;
                    $historias[$id_post]->name = $loop->post->post_title;
                    $historias[$id_post]->link = 3;
                    $historias[$id_post]->seen = false;
                    
					$historias[$id_post]->stories[$id_post] = new Stories();
                    $historias[$id_post]->stories[$id_post]->id = $id_post;
                    $historias[$id_post]->stories[$id_post]->type = 'photo';//$loop->post->ID;
                    $historias[$id_post]->stories[$id_post]->length = $wporg_atts['tiempo'];
                    $historias[$id_post]->stories[$id_post]->src = $thumb;
                    $historias[$id_post]->stories[$id_post]->preview = get_the_post_thumbnail_url(null, 'thumbnail');
                    $historias[$id_post]->stories[$id_post]->link = get_permalink( $loop->post->ID );
                    $historias[$id_post]->stories[$id_post]->linkText = $wporg_atts['boton'];
                    $historias[$id_post]->stories[$id_post]->time = strtotime($loop->post->post_date);
                    $historias[$id_post]->stories[$id_post]->seen = false;
                    
                    $historias[$id_post]->photo = $thumb;
            }
        }
       
        wp_reset_query();
    }
    ob_start(); 
    

    if($historias){

        echo '<div id="bw_wpstories" class="storiesWrapper" style="max-width: 1030px" ></div>';
        ?>
        <script>
            jQuery( document ).ready(function() {
                var stories = new Zuck('bw_wpstories', {
                    backNative: true,
                    previousTap: true,
                    skin: "Snapgram",
                    autoFullScreen: false,
                    avatars: true,
                    paginationArrows: false,
                    list: false,
                    cubeEffect: true,
                    localStorage: true,
                    stories: [

                        <?php foreach($historias as $key => $historia): ?>
                            Zuck.buildTimelineItem(
                            "<?php echo $historia->id; ?>", 
                            "<?php echo $historia->photo; ?>",
                            "<?php echo $historia->name; ?>",
                            "<?php echo site_url(); ?>",
                            "<?php echo $historia->time; ?>",
                            [
                                <?php foreach($historia->stories as $key => $stories): 
                                    echo '["'.$historia->name.'-'.$stories->id.'", "'.$stories->type.'", '.$stories->length.', "'.$stories->src.'", "'.$stories->preview.'", "'.$stories->link.'", "'.$stories->linkText.'", false, "'.$stories->time.'"],';
                                endforeach; ?>
                            ]
                            ),
                        <?php endforeach; ?>

                    ]
                });
             });
        </script>
        <?php  
    }
    else{
        _e('No Stories to show');
    }
    
    $output = ob_get_contents(); 
    ob_end_clean();
    return $output; 
}
add_shortcode('bw_show_stories', 'bajaw_mostrar_historias');
