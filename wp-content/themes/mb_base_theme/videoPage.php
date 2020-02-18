<?php
/**
 * Template Name: new Grid Template
 */
get_header();

?>



<div id="videoPageGrid">


    <!-- <div id="nav" class="nav">
            NAV
        </div> -->


    <div class="enhSearch">
        <div class="enhSearch">
            <p>Enhanced search bar</p>
            <h5>to look for other vid</h5>
        </div>
    </div>


    <div class="videoBlock">
        <h2>Video Title</h2>
        <h5>Contributor name, and vid category</h5>
        <div id="theVideo" class="theVideo">

            PLAYER or the PURCHASE MESSAGE
        <p>Synopsis</p>

        </div>
    </div>



    <div class="moreLikeThis">
        <h2>MOre like this video</h2>
        <h5>full catalog listing</h5>
    </div>


    <div id="shopHome" class="shopHome">
        <?php echo do_shortcode('[products]'); ?>
    </div>


    <div id="loginForm" class="loginForm">
        <?php // wp_login_form($args);
            wp_login_form();?>
    </div>

    <!-- <div id="signUp" class="signUp">
            SIGNUP (TRY)
        </div> -->


</div>



<?php
get_footer();