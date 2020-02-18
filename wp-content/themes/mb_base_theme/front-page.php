<?php
/**
 * Template Name: new Grid Template
 */
get_header();

?>

<div id="pageGrid">

<?php while ( have_posts() ) : the_post(); 

//$postVars = get_post_meta($post->ID, "", true);

$H = get_field('headline'); 
$vid_array = get_field('home_video'); 
$mainText= get_field('homepage_text'); 

$mp4 = isset($vid_array['url'])?$vid_array['url']:"";
$webm = str_replace(".mp4",".webm",$mp4);

if (strlen($mp4)>0){ ?>

<div id="bigvid" class="bigvid">

    <video autoplay muted loop>
        <source src="<?php echo $mp4; ?>" type="video/mp4">
        <source src="<?php echo $webm; ?>" type="video/webm">
        <div style="border: 1px solid black ; padding: 8px ;">
            Sorry, your browser does not support the &lt;video&gt; tag used in this demo.
        </div>
    </video>

</div>

<?php }   ?>


<div id="hero" class="hero">
            <h1><?php echo $H;?></h1>
    </div>


    <div id="mainContent" class="mainContent">
    <p>
        <?php echo $mainText;?></p>
    </div>


<?php // get the testimonials into an array 

$testimonial1= get_field('testimonial1'); 
$testimonial2= get_field('testimonial2'); 
$testimonial3= get_field('testimonial3'); 

$testimonial_array = [];

if (strlen($testimonial1)>0){
    $tmp = explode("|",$testimonial1);
    $testimonial_array[]= [
        'ttext' => $tmp[0],
        'tauthor' =>$tmp[1],
        'torganisation' =>$tmp[2],
    ];
}

if (strlen($testimonial2)>0){
    $tmp = explode("|",$testimonial2);
    $testimonial_array[]= [
        'ttext' => $tmp[0],
        'tauthor' =>$tmp[1],
        'torganisation' =>$tmp[2],
    ];
}

if (strlen($testimonial3)>0){
    $tmp = explode("|",$testimonial3);
    $testimonial_array[]= [
        'ttext' => isset($tmp[0])?$tmp[0]:"",
        'tauthor' =>isset($tmp[1])?$tmp[1]:"",
        'torganisation' =>isset($tmp[2])?$tmp[2]:"",
    ];
}

?>

    <div id="testimonials" class="testimonials">
        <!-- slider code -->

        <div class="orbit testimonial-slider-container" role="region" aria-label="testimonial-slider" data-orbit>
            <ul class="orbit-container">
                <button class="orbit-previous"><span class="show-for-sr">Previous Slide</span>&#9664;&#xFE0E;</button>
                <button class="orbit-next"><span class="show-for-sr">Next Slide</span>&#9654;&#xFE0E;</button>

                <!-- content slide 1 -->


<?php foreach($testimonial_array as $ta){ ?>

                <li class="is-active orbit-slide">
                    <div class="testimonial-slide row">
                        <div class="small-12 large-9 column">
                            <div class="row align-middle testimonial-slide-content">
                                <div class="small-12 medium-8 column testimonial-slide-text">
                                    <p class="testimonial-slide-quotation">
                                    
                                    <?php echo $ta['ttext']; ?>
                                    
                                    </p>
                                    <div class="testimonial-slide-author-container">
                                        <p class="testimonial-slide-author-info">
                                        <?php echo $ta['tauthor']; ?>
                                         <br><i class="subheader">
                                         <?php echo $ta['torganisation']; ?>
                                            </i></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

<?php } 
?>

               
            </ul>
        </div>
        <!-- slider close -->


    </div>

    <!-- <div id="smallContent1" class="smallContent1">
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
    </div> -->

    <?php get_template_part( 'products_and_login_box'); ?>
    

    <!-- <div id="signUp" class="signUp">
            SIGNUP (TRY)
        </div> -->


</div>

<?php endwhile; ?>


<?php
get_footer();
