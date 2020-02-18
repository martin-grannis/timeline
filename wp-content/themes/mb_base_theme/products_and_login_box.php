<div id="shopHome" class="shopHome">
    <?php 

//    if (!isRemoteAuth() && !is_user_logged_in()) {
//only show shop to people without current subscription
    

$teams = wc_memberships_for_teams_get_teams();
if (!isRemoteAuth() && ! $teams && ! wcs_user_has_subscription( '', '', ['active','pending-cancellation'] )) {
//if (!isRemoteAuth() && !has_active_subscription()) {

       ?>
    <h4>Products available</h4>
        <?php echo do_shortcode('[products orderby="menu_order"]'); ?>
        <?php }?> 
    
    </div>

	<?php // wp_login_form($args);
            if (!is_user_logged_in()){
                ?>
            <div id="loginForm" class="loginForm">
            <?php 
            $param= isset($_GET['url'])?["redirect"=>$_GET['url']]:"";
            wp_login_form($param);
            ?>
            </div>
            <?php } ?>
