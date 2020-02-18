<?php

add_action( 'wp_ajax_mb_search', 'my_ajax_search_handler' );
add_action( 'wp_ajax_nopriv_mb_search', 'my_ajax_search_handler' );

function my_ajax_search_handler($postedData) {
    global $paged;
    
    // Make your response and echo it.

    $s_string = $_POST['s_string'];
    $s_cat = $_POST['s_cat'];
    $s_contrib = $_POST['s_contrib'];
    $s_paged = $_POST['s_paged'];
    $paged = $_POST['s_paged'];

// do the query 

$args = [
    'posts_per_page' => 10,
    'paged' => $s_paged,
    'post_type' => 'resource',
    'meta_value' => '',
    's' => $s_string,
];


// add first taxonomy if present
if (!empty($s_cat)) {

    $args['tax_query'] = array(
        array(
            'taxonomy' => 'resource_category',
            'field' => 'name',
            'terms' => $s_cat,
        ),
    );
}

if (!empty($s_contrib)) {
    $args['tax_query'][] =
    array(
        'taxonomy' => 'video_contributor',
        'field' => 'name',
        'terms' => $s_contrib,

    );
}

// get the current query and modify it
//global $wp_query;

$custom_query = new WP_Query($args);

$retStr='<ul id="VidItems" class="VidItems">';

if (!$custom_query->have_posts()){
    $retStr.="<h3 style='color:red;'>Nothing found!</h3>";
}
else {
         if (function_exists("pagination")) {
           //pagination($custom_query->max_num_pages);
           pagination($custom_query);
           
        // now fixup $temp with the correct link to make another Ajax call
         }


while ($custom_query->have_posts()): $custom_query->the_post();
                
//$purchasedClass =  mb_can_user_access_video(get_current_user_id(), $post->ID)?"":" notInc";
$purchasedClass =  mb_can_user_access_video(get_current_user_id(), get_the_ID())?"":" notInc";
    
        $retStr.='<li class="vid_item"><h3><a href="'. get_the_permalink().'"><div class="date_col">';
                                $retStr.= get_post_meta(get_the_ID(), 'timeline_dates', true).
                                '</div>
                                <div class="title_col'.
                                $purchasedClass.'">';
                                $retStr.= get_the_title(). '</div>
                            </a></h3>
                    </li>';
    
 endwhile;
          
    }
    
    $retStr.='</ul>';

    wp_reset_postdata();

    echo $retStr;



    // Don't forget to stop execution afterward.
    wp_die();
}