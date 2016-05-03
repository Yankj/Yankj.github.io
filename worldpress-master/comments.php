<?php
/**
 * 
 *
 * @package WordPress
 * @since 0.0.1
 */
if ( post_password_required() ) {
	return;
}
?>
<section class="post-comments" id="comments">
<?php if (have_comments()) : ?>
		<h4><?php echo __('There Are ');comments_number();?> On"<em><?php the_title();?></em>"</h4>
		<ol class="commentlist">
			<?php wp_list_comments( array( 'walker' => new Walker_Comment(),'avatar_size' =>0,'style' => 'ol' ) ); ?>
		</ol>
		<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : ?>
			<div id="comment-nav-below" class="navigation" role="navigation">
				<div class="nav-wrapper">
		      		<p class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', 'worldpress' ) ); ?> </p>
					<p class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'worldpress' ) ); ?></p>
	      		</div>
			</div>
		<?php endif; ?>
<?php else:?>
	<h4 class="no-comments"><?php _e( 'No Comments Yet', 'worldpress' ); ?></h4>
<?php endif;?>
	<?php 
	if (comments_open()) {
		comment_form();
	}
	else { ?>
		<p class="no-comments"><?php _e( 'Comments are closed.', 'worldpress' ); ?></p>
	<?php 
	}
	?>
</section>