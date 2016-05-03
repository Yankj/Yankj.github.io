<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package WordPress
 * @since Twenty Fifteen 1.0
 */

get_header(); ?>

	<div id="main-content" class="container main-content">
  		<div class="posts-area col-sm-9">

		<?php
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post(); ?>
				<section <?php post_class('blog-posts');?>>
				<h1><a href="<?php the_permalink();?>"><?php the_title(); ?></a></h1>
				<div class="row">
					<div class="col-sm-3 post-date"><?php echo __("Posted On")?> <?php the_date(); ?></div>	
					<div class="col-sm-6 post-catergory"> <i class="fa fa-file"></i> <?php the_category(" ","single"); ?></div>	
				</div>
				<article class="post-content">
				<?php if (has_post_thumbnail()): ?>
					<div class="featured-image"><?php echo get_the_post_thumbnail(); ?> </div>
				<?php endif;?>
				<?php the_content(); ?>
				<div class="no-display forlikedata" data-postid="<?php echo $post->ID;?>" data-action="<?php echo get_template_directory_uri().'/like.php'?>"></div>
				<?php if(!get_post_custom($post->ID)['dislike']) {
					add_post_meta($post->ID,'dislike',0,true);
					}
					else {
						echo '<span class="dislike"><i class="fa fa-thumbs-o-down"></i><span>'.get_post_custom_values('dislike',$post->ID)[0].'</span></span>';
					}
				?>
				<?php if(!get_post_custom($post->ID)['like']) {
					add_post_meta($post->ID,'like',0,true);
					}
					else {
						echo '<span class="like"><i class="fa fa-thumbs-o-up"></i><span>'.get_post_custom_values('like',$post->ID)[0].'</span></span>';
					}
				?>
				<div class="tags"><?php echo __('Tagged:') ?> <?php the_tags(" "," ")?></div>
				<div class="post-info clearfix">
					<div class="authorship float-left col-sm-4"><?php echo __("Posted By ")?><?php the_author_posts_link(); ?></div>
					<div class="links float-right col-sm-4 col-sm-offset-4">
						<span>
							<a href="<?php the_permalink(); ?> ">
								<?php echo __('Permanent Link');?>
							</a>
						</span>
						<span><a href=" <?php comments_link(); ?>"><?php comments_number(__('No Comments Yet')); ?></a></span>
					</div>
				</article>
				<nav class="post-nav row">
					<div class="prev-link col-sm-6"><?php previous_post_link("&laquo; Last Post: %link");?></div>
					<div class="next-link col-sm-6"><?php next_post_link("Next Post: %link &raquo");?></div>
				</nav>
				</section>
				
					<?php comments_template(); ?> 
				
			<?php }
		}
	?>

		</div><!-- .site-main -->
		<?php get_sidebar(); ?>
	</div><!-- .content-area -->

<?php
get_footer();
wp_footer();
