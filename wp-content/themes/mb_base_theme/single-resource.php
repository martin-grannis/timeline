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

	<?php while (have_posts()): the_post();?>

	<!-- dont want this as it does the security checks all over again -->

	<!-- <?php //get_template_part('content', 'page');?>  -->



	<div id="enhSearch" class="enhSearch" style="display:none;">
		<p>RESOURCE PAGE</p>
		<h5></h5>
	</div>



	<div id="resourceBlock" class="resourceBlock">
		<br />
		<p class="vidTit">
			<?php the_title();?></h2>
			<p class="subDet">Contributors:<span class="vhList">
					<?php echo mb_get_contributors($post->ID) ?></span></p>
			<p class="subDet">Category: <span class="vhList">
					<?php echo mb_get_categories($post->ID) ?></span></p>


			<!-- decide if user can see this video? -->
			<!-- yes -->

			<?php if (mb_can_user_access_video(get_current_user_id(), $post->ID)) {
        ?>

			<!-- YES -->
			<div class='embed-container'>
				<iframe src='https://player.vimeo.com/video<?php echo mb_get_video_id($post) ?>' frameborder='0'
					webkitAllowFullScreen mozallowfullscreen allowFullScreen>
				</iframe>
			</div>

			<?php } else {?>

			<!-- or NO  -->

			<?php if (get_current_user_id()){ ?>
			<div id="needTo" class="Purchase">Your current subscription doesn't include this video.</div>

			<?php } else {	 ?>

			<div id="mustBuy">

				<div id="needTo" class="Purchase"><a href="
				<?php global $wp;
				    $thisUrl= home_url(add_query_arg(array($_GET), $wp->request));
					$url=home_url();
					//$pos = strpos($url,"/",10); // 10 so we skip the //
					//$thisUrl .= "?url=".$url;
					//echo $url;
					 echo $url."?url=".$thisUrl."/#loginForm";?>
					">login</a> or <a href="/#shopHome">purchase</a>
					to view.</div>

				<?php }?>


				<?php $prods = mb_get_products_that_include_this_video($post->ID);
        if ($prods) {
            ?>
				<div class="availV">Products that include this video:-
					<!-- list products that include this video category -->
					<ul>
						<?php

            foreach (mb_get_products_that_include_this_video($post->ID) as $p) {
                ?>
						<li><a href=" <?php echo $p['productLink'] ?>" target="_blank">
								<?php echo $p['productName'] ?></a></li>

						<?php
    }

            ?>
					</ul>
				</div>
				<?php } else {?>

				<div class="availV">
					There are currently no products available which include this video.

				</div>

				<!-- must buy end	 -->
			</div>

			<?php }}?>



	<br>
	<p class="vsyn">
		<span class="synopsisText">Synopsis:</span>
		<?php echo get_post_meta($post->ID, 'video_synopsis', true) ?>
	</p>





</div>
</div>

<?php endwhile;?>




</div>



<?php
get_footer();