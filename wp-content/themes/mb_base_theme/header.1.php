<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Scholarium</title>

    <?php wp_head();?>
</head>

<body>

<!-- <div id="topB">

<img src="/wp-content/uploads/2019/02/SJC_DistanceLearning_ScholarsSpeak.png" alt="topB">
</div> -->


    <div id="headerGrid">

        <!--   <div id="contactUs" class="contactUs">
            <button class="button" type="button" data-toggle="contact-dropdown">Contact</button>

            <div class="dropdown-pane" id="contact-dropdown" data-dropdown data-auto-focus="true">

                <div class="grid-container">
                    <div class="">
                        <!~~ <div class="grid-x grid-margin-x"> ~~>
                        <div class="">
                            <p>Call on 0115 9251114</p>
                        </div>
                        <!~~ <div class="cell medium-6"> ~~>
                        <div class="">
                            <p>Email at enquiries@stjohns-nottm.ac.uk</p>
                        </div>
                    </div>
                </div>

            </div>

        </div> -->

        <div id="logo" class="logo">
            <a href="/">
                <!-- <img src="https://stjohns-nottm.ac.uk/themes/stjohns2015/images/SJC_logo_webQuality.jpg" alt="logo"> -->
                <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/SS_newLogo.jpg" alt=""> -->
                <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/SS_holdingLogo.jpg" alt=""> -->
                <!-- <img src="/wp-content/themes/mb_base_theme/siteImages/ss_logo_aff1.jpg" alt=""> -->
                <img src="/wp-content/themes/mb_base_theme/siteImages/ss_logoCrop.jpg" alt="">


            </a>

        </div>

        <div id="topbar" class="topbar">

            <div id="cartIcon" class="cartIcon">
                <!-- are we on the cart page ? -->
                <?php if (!is_cart() && sizeof(WC()->cart->get_cart()) != 0 && !isRemoteAuth()) {?>
                <!-- // show the cart icon if not empty -->
                <a href="<?php echo WC()->cart->get_cart_url(); ?>">
                    Cart [<span style="color:red;">
                        <?php echo sizeof(WC()->cart->get_cart()) ?></span>]
                    <?php }?>
                    </a>
            </div>

            <ul class="contactUsList">
                <li><a href="mailto:enquiries@stjohns-nottm.ac.uk">Email</a></li>
                <li>0115 9251114</li>
                <li><a href="https://www.facebook.com/StJohnsCollegeNottingham/" target="_blank">Facebook</a></li>
                <li><a href="https://twitter.com/stjohnsnottm?lang=en" target="_blank">Twitter</a></li>
            </ul>

        </div>


        <!-- // </div> -->

        <!-- // </div> -->




        <div id="Vbuttons" class="buttons">

            <div class="button-group small">

                <a href="/#shopHome" class="button" <?php if (isRemoteAuth()) {?> disabled<?php }?> >Buy</a>

                <?php if (!is_user_logged_in()) {?>
                <a href="/#loginForm" class="button">Login</a>

                <?php } else {?>
                <a href="my-account/" class="button" <?php if (isRemoteAuth()) {?> disabled<?php }?> >My Profile</a>

                <?php }?>

                <a href="/full-catalog" class="button">Video List</a>

            </div>

            <!-- <div class="fullList">
            <a href="/full-catalog">Full Video List</a>
        </div> -->

        </div>

<!-- if user logged in -->
<?php if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    ?>

<div id="loggedinDetails">
<?php if (!isRemoteAuth() && is_user_logged_in()) {?>
            <div id="lname"> Logged in as <span class="logname"><?php echo $current_user->user_email; ?></span></div>

            <?php }?>

            <div id="lout"><a href="/wp-login.php?action=logout">Logout</a></div>
    </div>
<?php }?>


    </div>
