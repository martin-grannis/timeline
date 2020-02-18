<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0"> -->

    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>St John's Timeline</title>
    <!-- <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous"> -->
    <?php wp_head();?>
</head>

<body>

    <div id="headerGrid">

        <div id="ssLogoH">

        <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/ss_logo2.png" alt="sp logo"> -->
        <img src="/wp-content/uploads/2019/07/logo12b.png" alt="sp logo">
        </div>

        
        <!-- home -->
        <div id="homeH">
            <div class="fIcon">
                <a href="/">
                <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-home-page-64.png" alt="home link"> -->
                <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-home-50.png" alt="home link">
                    <div class="linkText">Home</div>
                </a>
            </div>
        </div>

        <!-- creds login out  -->
        <div id="credsH">
            <div class="fIcon">
                
            <?php if (!is_user_logged_in()) {?>
            
                <a href="/#loginForm">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-home-page-64.png" alt="home link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-login-50.png" alt="home link">
                    <div class="linkText">Login</div>
                </a>

                <?php } else { ?>

                    <a href="/wp-login.php?action=logout">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-home-page-64.png" alt="home link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-logout-50.png" alt="home link">
                    <div class="linkText">Logout</div>
                </a>

                    <?php }  ?>

            </div>
        </div>

       
        <!-- buy -->
        <div id="buyH">
            <div class="fIcon">

            <?php if (!is_user_logged_in()) {?>
            
                <a href="/#shopHome">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-home-page-64.png" alt="home link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-buy-50.png" alt="home link">
                    <div class="linkText">Buy</div>
                </a>

            <?php } else { 

        // if remote auth then no profile link                
            if (isRemoteAuth()){
                    ?>
                <div>
                    
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-profile-50-disabled.png" alt="home link">
                    <div class="linkText-disabled">Profile</div>
                </div>

                <?php }
                else {
                    ?>
                    <a href="/my-account">
                        <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-home-page-64.png" alt="home link"> -->
                        <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-profile-50.png" alt="home link">
                        <div class="linkText">Profile</div>
                    </a>
    
                    
                <?php }
                
                }  ?>

            </div>
        </div>

        <!-- videos -->
        <div id="vidListH">
            <div class="fIcon">
                <a href="/full-catalog">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-search-64.png" alt="video list link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-video-50.png" alt="home link">
                    <div class="linkText">Videos</div>
                </a>
            </div>
        </div>

        <!-- about -->
        <div id="aboutH">
            <div class="fIcon">
                <a href="/about">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-info-64.png" alt="home link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-about-50.png" alt="home link">
                    <div class="linkText">About</div>
                </a>
            </div>
        </div>

        <!-- contact -->
        <div id="contactH">
            <div class="fIcon">
                <a href="/contact">
                    <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/frontI/icons8-secured-letter-64.png" alt="contact link"> -->
                    <img src="/wp-content/themes/mb_base_theme/siteImages/i/icons8-contact-50.png" alt="home link">
                    <div class="linkText">Contact</div>
                </a>
            </div>

        </div>

        <div id="loginbarH"> <span class="lia">

        <?php if (is_user_logged_in()) { 
            $current_user = wp_get_current_user(); ?>
            
            <?php if(!isRemoteAuth()){
            ?>
             Logged in as </span><br class="onlyMobile"><span class="logname"><?php echo $current_user->user_email; ?>
            <?php } else { ?>

             Logged in by external system

        <?php }
            ?></span></div>
            <?php } else {?>
                 Not logged in</span></div>
                <?php }?>

                <?php get_template_part("cartNotice"); ?>


        </div>
        



    </div>

