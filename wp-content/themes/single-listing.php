<?php
/** 
 * Template Name: Full Catalog Template Page
 */

get_header();

?>



<div id="videoPageGrid">


    <!-- <div id="nav" class="nav">
            NAV
        </div> -->


    
        <div id="enhSearch" class="enhSearch">
            <p>SINGLE LISTING PAGE</p>
            <h5>to look for other vid</h5>
        </div>
    


    <div id="resourceBlock" class="resourceBlock">
        <h2>Video Title</h2>
        <h5>Contributor name, and vid category</h5>
        <div id="theVideo" class="theVideo">

            PLAYER or the PURCHASE MESSAGE
            <p>Synopsis</p>

        </div>
    </div>

    <div id="smallContent1" class="smallContent1">
        <div class="panel show-for-medium-up">
            <h5>This is more important blurb</h5>
            <p>Special offers for all of November</p>
        </div>
    </div>


    <div id="smallContent2" class="smallContent2">
        <div class="panel">
            <h5>This is more important blurb</h5>
            <p>Timeline has 1000000 viewers every day</p>
        </div>
    </div>


    <div id="morelikeThis" class="morelikeThis">
        <h2>More like this video</h2>
        <h5>full catalog listing</h5>
    </div>


    <div id="shopHome" class="shopHome">
        <?php echo do_shortcode('[products]'); ?>
    </div>


    <div id="loginForm" class="loginForm">
        <?php // wp_login_form($args);
            wp_login_form();?>
    </div>


    <div id="social" class="social">
        <p>fb tw etc</p>
    </div>

        


</div>



<?php
get_footer();