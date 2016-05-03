<div class="container front-headline">
	<img class="img-responsive center-block" src="<?php echo get_template_directory_uri().'/logo.png';?>" />
	<h3 class="centered-text"><?php bloginfo('description');?></h3>
</div>

	<?php $three_featured_posts=wp_get_recent_posts(array('numberposts' => 3,'post_type' => 'feature','post_status' => 'publish'))?>
	<?php if ($three_featured_posts):?>
	<div class="container featured-posts">
	<div class="jslide">
	    <div class="jslide-control left nomore"><i class="fa fa-chevron-left"></i></div>
	    <div class="jslide-control right"><i class="fa fa-chevron-right"></i></div>
	    <div class="jslide-outside">
	        <div class="jslide-container clearfix">
	        	<?php foreach ($three_featured_posts as $recent) :?>
			            <div class="jslide-item">
			            	<article class="posts clearfix">
			            		<h2><?php echo get_the_title($recent["ID"]);?></h2>
			            		<div class="post-info col-sm-3">
			            			<span class="post-catergory"><?php echo __("Posted in ");?>
			            				<strong><?php echo get_the_category($recent["ID"])[0]->cat_name;?></strong>
			            			</span>
			            			<div class="post-tags">
			            					<strong>Tags:</strong>
			            					<ul>
			            					<?php 
			            					$post_tags=wp_get_post_tags($recent["ID"]);
			            					foreach ($post_tags as $tags) {
			            						echo '<li>'.$tags->name.'</li>';
			            					}
			            					?>
			            					</ul>
			            			</div>
			            			<div class="post-details">
			            				<div class="post-author">
			            					<span><?php echo __('Posted By')?>:</span>
			            					<a href="<?php echo the_author_meta('user_url',$recent['post_author']);?>">
			            						 <?php echo the_author_meta('display_name',$recent['post_author']);?>
			            					</a>
			            				</div>
			            				<div class="post-date"><?php echo get_post_time( 'D, F j, Y', true, $recent["ID"]);?></div>
			            			</div>
			            		</div>
			            		<div class="post-content col-sm-9">
			            			<?php echo apply_filters('the_content',substr($recent["post_content"],0,1000)); ?>
			            			<a class="read-more" href="<?php echo get_permalink($recent['ID']);?>">
			            				<?php echo __('Read More')?>
			            			</a>
			            		</div>
			            	</article>
			            </div>
	            <?php endforeach;?>
	        </div>
	    </div>
	</div> 
	</div>
	<?php endif;?>