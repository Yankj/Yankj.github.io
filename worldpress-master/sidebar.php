<?php
/**
 * The Sidebar containing the main widget areas.
 *
 * @package Worldpress
 * @since 0.0.1
 */
?>
	<div id="secondary" class="widget-area col-sm-3" role="complementary">
		<?php if ( is_active_sidebar( 'home_right_1' ) ) : ?>

			<aside id="widget" class="widget widget_search">
				<?php dynamic_sidebar( 'home_right_1' ); ?>
			</aside>

		<?php endif; ?>
	</div>
