<?php

function mb_get_video_id($p)
{
    $x = get_post_meta($p->ID, "video_url", true);
// find last forward slash
    $pos = strrpos($x, "/");
    return substr($x, $pos);

}

function mb_can_user_access_content($user_id,$post_id){
    //check if there's a force public on this content
    if(get_post_meta($post_id,'_wc_memberships_force_public',true)=='yes') return true;
    $args = array( 'status' => array( 'active' ));
    $plans = wc_memberships_get_user_memberships( $user_id, $args );
    $user_plans = array();

    $PlanCategories = [];

    foreach($plans as $plan){
            array_push($user_plans,$plan->plan_id);

            // get this plan categories
//              $terms = get_terms( array(
//              'taxonomy' => 'resource_category',
//              'hide_empty' => false,
//      ) );

//              $tax =  get_object_taxonomies($plan->plan->post->id);


                    $myResourceCategories = wc_get_object_terms($plan['id'],"resource_category");

    }
    // plan taxonomies
//      $rules = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id );
    $rules = wc_memberships()->get_rules_instance()->get_post_content_restriction_rules( $post_id );

    foreach($rules as $rule){
            if(in_array($rule->get_membership_plan_id(), $user_plans)){
                    return true;
            }
    }
    return false;
}
 