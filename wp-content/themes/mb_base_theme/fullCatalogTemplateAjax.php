<?php
/**
 * Template Name: Full Catalog Ajax Template Page
 */

get_header();

?>



<div id="videoPageGrid">


    <!-- <div id="nav" class="nav">
            NAV
        </div> -->

    <?php
global $query_string;
global $paged;
// $searchText = get_query_var('searchText');
// $searchResourceCategory = get_query_var('searchResourceCategory');
// $searchVideoContrib = get_query_var('searchVideoContrib');
// $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$searchText = "";
$searchResourceCategory = "";
$searchVideoContrib = "";
$paged = "1";

?>

    <div id="enhSearch" class="enhSearch">

        <form autocomplete="off"> 

            <div class="enhInputs">

                <input type="hidden" name="paged" value=<?php echo $paged ?>>
                <!-- search box -->

                <div class="sButtons">
                    <a href="#" id="AjaxClearSearch" class="button redButton">Clear Search</a>
                    <input id="AjaxSubmit" class="button" type="submit" id="searchSubmit" value="Search" />
                </div>

                <div id="searchText">
                    <span>Search: </span>

                    <!-- <input name="searchText" type="text" id="contentF" value="<?php echo htmlspecialchars($searchText) ?>"> -->
                    <input name="searchText" placeholder="(search for)" type="text" id="contentF" value="">
                </div>

                <div id="search-filter" class="search-filter">
                    <div class="searchSection">
                        <div class="firstSection searchOuter">
                            <a class="toggled toggledClosed">Category</a>
                            <div class="currentValue">
                                <?php if (!empty($searchResourceCategory)) { 
                            echo  $searchResourceCategory; 
                        } 
                            else 
                            { ?>
                                &lt;not selected&gt;

                                <?php 
                        } ?>
                            </div>
                            <input name=searchResourceCategory type="hidden" value="">
                            <ul id="searchList1" class="searchList isHidden">

                                <?php echo mb_get_li_taxonomy_options('resource_category', true); // showall =true   ?>

                            </ul>
                        </div>
                    </div>

                    <div class="searchSection">
                        <div class="searchOuter bottomCurve">
                            <a class="toggled toggledClosed">Contributor</a>

                            <div class="currentValue">
                                <?php if (!empty($searchVideoContrib)) { 
                            echo "Set to:".$searchVideoContrib; 
                        } 
                            else 
                            { ?>
                                &lt;not selected&gt;

                                <?php 
                        } ?>
                            </div>

                            <!-- <div class="currentValue">&lt;not selected&gt;</div> -->
                            <input name=searchVideoContrib type="hidden" value="">
                            <ul id="searchList2" class="searchList isHidden">

                                <?php echo mb_get_li_taxonomy_options('video_contributor', true); // showall =true   ?>

                            </ul>
                        </div>
                    </div>

                </div>



            </div>
        </form>



    </div>



    <div id="resourceBlock" class="resourceBlock">
        <div id="listingBlock">
            <h2>Video List</h2>

            <?php

//'posts_per_page' => 50,
$args = [
    'posts_per_page' => 10,  
    'paged' => $paged,
    'post_type' => 'resource',
    'meta_value' => '',
    's' => $searchText,
];


// add first taxonomy if present
if (!empty($searchResourceCategory)) {

    $args['tax_query'] = array(
        array(
            'taxonomy' => 'resource_category',
            'field' => 'name',
            'terms' => $searchResourceCategory,
        ),
    );
}

if (!empty($searchVideoContrib)) {
    $args['tax_query'][] =
    array(
        'taxonomy' => 'video_contributor',
        'field' => 'name',
        'terms' => $searchVideoContrib,

    );
}

$custom_query = new WP_Query($args);

?>

            <div id="VideoItems">

                <ul id="VidItems" class="VidItems">

                    <?php
if (!$custom_query->have_posts()){
    echo "<h3 style='color:red;'>Nothing found!</h3>";
?>


                    <?php      } 

else {

    if (function_exists("pagination")) {
//        pagination($custom_query->max_num_pages);
        pagination($custom_query);
    }
    
    while ($custom_query->have_posts()):
        $custom_query->the_post();
        ?>




                    <li class="vid_item">
                        <h3><a href="<?php the_permalink();?>">
                                <!-- <?php //get_post_meta( $post->ID, 'timeline_dates', true )." - ". the_title(); ?></a></h3> -->
                                <div class="date_col">
                                    <?php echo get_post_meta($post->ID, 'timeline_dates', true); ?>

                                </div>
                                <div class="title_col<?php if (! mb_can_user_access_video(get_current_user_id(), $post->ID)) {echo ' notInc';} ?>">

                                    <?php echo $post->post_title; ?>
                                </div>
                            </a></h3>
                    </li>

                    <?php 
    endwhile;
    ?>

                    <!-- <?php //if (function_exists("pagination")) {
        //pagination($custom_query->max_num_pages);
        //}
    ?> -->

                </ul>
            </div>

            <?php } ?>

            <!-- <h5>Contributor name, and vid category</h5>
        <div id="theVideo" class="theVideo">

            PLAYER or the PURCHASE MESSAGE
            <p>Synopsis</p> -->

        </div>
    </div>
</div>

<div id="smallContent1" class="smallContent1">
    <div class="panel show-for-medium-up">
        <h5>This is more important blurb</h5>
        <p>Special offers for all of November</p>
    </div>
</div>



<br />
<br />

<!-- <div id="shopHome" class="shopHome">
    <?php //echo do_shortcode('[products]'); ?>
</div> -->

<?php get_template_part( 'products_and_login_box'); ?>

<!-- <div id="loginForm" class="loginForm">
    <?php // wp_login_form($args);
//wp_login_form();?>
</div> -->


<!-- <div id="social" class="social">
    <p>fb tw etc</p>
</div> -->




</div>



<?php
get_footer();