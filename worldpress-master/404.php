<?php
/**
 * The 404 page (Not Found) template file.
 * @package Worldpress
 * @since 0.0.1
*/
get_header(); ?>
<div id="main-content" class="container main-content">
  <div class="posts-area col-sm-9">
    <div class="not-found">
    	<h2 class="text-center"><?php echo __('Page Not Founded');?></h2>
    	<p class="text-center"><?php echo __('You Can Search For Posts');?></p>
		<div class="error-search-form"><?php get_search_form();?></div>
		<p class="text-center"><?php echo __('Or Check Out Latest Posts');?></p>
		<?php 
		$args = array (
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'pagination'             => false,
			'posts_per_page'         => '10',
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			echo '<ul class="recent-posts">';
			while ( $query->have_posts() ) {
				$query->the_post(); ?>
				<li><a href="<?php the_permalink();?>"><?php the_title();?></a></li>
		<?php 	
			}
			echo '</ul>';
		} else {
			echo "<strong>no posts found</strong>";
		}
		wp_reset_postdata();
		?>
    </div>
  </div>
<?php get_sidebar(); ?>
</div>

<?php
get_footer();
wp_footer();