<?php 

$ccount = WC()->cart->get_cart_contents_count();

$teams = wc_memberships_for_teams_get_teams();

// $user_id = get_current_user_id();

// $team_manager=false;

// foreach ($teams as $team){
//     $owner = $team->get_owner_id();
//     if ($user_id == $owner){
//       $team_manager=true;
//       break;
//     }
// }


$team_manager= is_team_manager();


if (!isRemoteAuth() && $ccount && !is_cart() ) { ?>
  
<div id="cartBar"> 
    <span class="cartCount">
        <?php echo $ccount ?>
        </span>
        <a href="<?php echo wc_get_cart_url(); ?>">item in Shopping Cart <img class="cartImg" src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-buy-50-white.png"></i>
</a>
</div>

<?php }

//if (!isRemoteAuth() && $teams && is_front_page()) 
if (!isRemoteAuth() && $teams) 

{ //?>
  
    <div id="teamBar"> 
    You are a team 
    <?php 
    if ($team_manager) {
        echo "manager. <a href='".esc_url( wc_get_account_endpoint_url( "teams"))."'>Go to Team Manager</a>";}
    else {
        echo "member. ";
    }
    
    ?>
        

    </div>
    
    <?php }

?>