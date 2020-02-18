<?php

// {

//add_action('init', 'myTest');

$textD = "";

function myTest()
{
    global $textD;

    $my_posts = get_posts(array('post_type' => 'resource', 'numberposts' => 500));
    //$my_posts = get_posts(array('post_type' => 'resource'));

    //ob_start();

    // delete ALL video_contributors globally

    $t = get_terms('resource_category', ['hide_empty' => false]);
    foreach ($t as $term) {
        wp_delete_term($term->term_id, 'resource_category');
    }

    $t = get_terms('video_contributor', ['hide_empty' => false]);
    foreach ($t as $term) {
        wp_delete_term($term->term_id, 'video_contributor');
    }

    //wp_delete_term( 655, 'resource_category' );

    foreach ($my_posts as $my_post):

        echo $my_post->post_title . "<br>";

        //$my_post['post_content'] = 'This is the updated content.';
        // randomly assign biblical and modernity taxonomy
        // randomly assign dates.

        // read custom field
        //get_post_meta( $my_post->ID, 'document_private__url', true );
        //update_post_meta( $my_post->ID, 'document_private__url', "" );

        $myContribObjects = wp_get_object_terms($my_post->ID, 'video_contributor');
        $myCatObjects = wp_get_object_terms($my_post->ID, 'resource_category');
        // these will be arrays if more than one

        // delete one term from a post taxonomy globally
        // must be ID of the WP_Term object and not slug
        //wp_delete_term( 655, 'resource_category' );
        //wp_remove_object_terms( $postID, 'term', 'taxonomy' );

        // to delete all
        wp_set_object_terms($my_post->ID, '', 'resource_category');
        wp_set_object_terms($my_post->ID, '', 'video_contributor');

        //wp_remove_object_terms( $my_post->ID, 382, 'resource_category' );
        // wp_remove_object_terms( $my_post->ID, 'john-close', 'video_contributor' );

        // adds biblical taxonomy to video_category in this post
        // creates a new term if doesn't exist
        //wp_set_object_terms( $my_post->ID, 'biblical', 'resource_category', true );
        //wp_set_object_terms( $my_post->ID, 'harry_wilson', 'resource_category', true );

        $cType = [
            'Biblical',
            'Modernity',
        ];

        $whichType = rand(0, 1);
        wp_set_object_terms($my_post->ID, $cType[$whichType], 'resource_category', true);

        $cats = [
            'Old testament',
            'New testament',
            'Revelation',
            'History',
            'Judges',
            'Timothy',
        ];
        $howManyCats = rand(0, 3);
        for ($x = 0; $x <= $howManyCats; $x++) {
            $newCat = rand(0, 5);
            wp_set_object_terms($my_post->ID, $cats[$newCat], 'resource_category', true);
        }

        $contribs = [
            'John Smith',
            'Fred Jones',
            'Paul Arnold',
            'Graham Fletcher',
            'Mason Kinsella',
            'Robert Forestone',
        ];

        $howManyContribs = rand(0, 3);
        for ($x = 0; $x <= $howManyContribs; $x++) {
            $newCat = rand(0, 5);
            wp_set_object_terms($my_post->ID, $contribs[$newCat], 'video_contributor', true);
        }

        $whichDate = rand(-1000, 2000);

        $textD = $orgD = abs($whichDate);

        // two in 5 times set a range
        if (rand(0, 5) <= 1) {
            $textD .= "-" . intval($orgD + rand(100, 350));
        }
        // 50% set BC
        if (rand(0, 1)) {
                $textD .= " BC";
        }

        // only works if post is saved !
        update_post_meta($my_post->ID, "timeline_dates", $textD);

//        wp_update_post( $my_post ); // nothing to change in the main post
        //update_field('field_5b8fc2dd4bea4', $textD, $my_post->ID); // uses meta key
        //wp_update_post( $my_post );
    endforeach;
    //ob_end_flush();

    die;
}

// function my_acf_save_post( $post_id ) {
//     global $textD;
//     update_field('field_5b8fc2dd4bea4', $textD, $post_id); // uses meta key

//     }

//  //   add_action('save_post', 'my_acf_save_post', 20);
