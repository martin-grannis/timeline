<?php

// custom posts
function cptui_register_my_cpts()
{

    /**
     * Post Type: Resources.
     */

    $labels = array(
        "name" => __("Resources", ""),
        "singular_name" => __("Resource", ""),
    );

    $args = array(
        "label" => __("Resources", ""),
        "labels" => $labels,
        "description" => "",
        "public" => true,
        "publicly_queryable" => true,
        "show_ui" => true,
        "show_in_rest" => false,
        "rest_base" => "",
        "has_archive" => false,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "exclude_from_search" => false,
        "capability_type" => "post",
        "map_meta_cap" => true,
        "hierarchical" => true,
        "rewrite" => array("slug" => "resource", "with_front" => true),
        "query_var" => true,
        "menu_position" => 22,
        "supports" => array("title", "editor", "thumbnail", "page-attributes"),
        "taxonomies" => array("resource_category", "video_contributor", "document_contributor"),
    );

    register_post_type("resource", $args);
}

add_action('init', 'cptui_register_my_cpts');

// taxonomies
function cptui_register_my_taxes()
{

    /**
     * Taxonomy: Resource Categories.
     */

    $labels = array(
        "name" => __("Resource Categories", ""),
        "singular_name" => __("Resource Category", ""),
    );

    $args = array(
        "label" => __("Resource Categories", ""),
        "labels" => $labels,
        "public" => true,
        "hierarchical" => false,
        "label" => "Resource Categories",
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "query_var" => true,
        "rewrite" => array('slug' => 'resource_category', 'with_front' => true),
        "show_admin_column" => false,
        "show_in_rest" => false,
        "rest_base" => "resource_category",
        "show_in_quick_edit" => false,
    );
    register_taxonomy("resource_category", array("resource"), $args);

    /**
     * Taxonomy: Video Contributors.
     */

    $labels = array(
        "name" => __("Video Contributors", ""),
        "singular_name" => __("Video Contributor", ""),
    );

    $args = array(
        "label" => __("Video Contributors", ""),
        "labels" => $labels,
        "public" => true,
        "hierarchical" => false,
        "label" => "Video Contributors",
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "query_var" => true,
        "rewrite" => array('slug' => 'video_contributor', 'with_front' => true),
        "show_admin_column" => false,
        "show_in_rest" => false,
        "rest_base" => "video_contributor",
        "show_in_quick_edit" => false,
    );
    register_taxonomy("video_contributor", array("resource"), $args);

    /**
     * Taxonomy: Document Contributors.
     */

    $labels = array(
        "name" => __("Document Contributors", ""),
        "singular_name" => __("Document Contributors", ""),
    );

    $args = array(
        "label" => __("Document Contributors", ""),
        "labels" => $labels,
        "public" => true,
        "hierarchical" => false,
        "label" => "Document Contributors",
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "query_var" => true,
        "rewrite" => array('slug' => 'document_contributor', 'with_front' => true),
        "show_admin_column" => false,
        "show_in_rest" => false,
        "rest_base" => "document_contributor",
        "show_in_quick_edit" => false,
    );
    register_taxonomy("document_contributor", array("resource"), $args);
}

add_action('init', 'cptui_register_my_taxes');

function mb_get_taxonomy_options($tax, $showAll, $current)
{
    


// Get all the taxonomies for this post type
    $terms = get_terms($tax, array('hide_empty' => !$showAll));

    $retString = '<option value=""></option>';
    foreach ($terms as $term) {
        $retString .= '<option '; 
        if ($current == $term->name){
            $retString.= "selected='1' ";}
        $retString .= 'value="'.$term->name.'">' . $term->name . '</option>';
    }
    return $retString;

}

function mb_get_li_taxonomy_options($tax, $showAll)
{
   
// Get all the terms for this post taxonomy
    $terms = get_terms($tax, array('hide_empty' => !$showAll));

    $retString = '';
    foreach ($terms as $term) {
        $retString .= '<li>'.$term->name.'</li>';
    }
    return $retString;

}
