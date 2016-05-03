<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme and one
 * of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query,
 * e.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WorldPress
 * @since Worldpress 0.1
 */

?>

<!DOCTYPE html>
<html>
	<head>
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo("charset");?>">
		<title><?php bloginfo('title');?><?php wp_title();?></title>
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="width=device-width, initial-scale=1">
    	<!--[if lt IE 9]>
      		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    	<![endif]-->
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( $class ); ?>>
		<header>
			<div class="fixed top-bar">
				<div class="container">

					<div class="col-sm-9 hover-navbar">
						<div class="blogname col-sm-2">
							 <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo('name');?></a>
						</div>
						<div class="col-sm-10">
							<?php wp_nav_menu(
								array( 
									'container_class' => 'main-menu clearfix',
									'theme_location' => 'main-menu',
									'menu_class' => 'nav navbar-nav',
								)
							); ?> 
						</div>
					</div>
					<div class="navbar-header">
				      <button class="navbar-toggle collapsed" type="button">
				        <span class="sr-only">Toggle navigation</span>
				        <span class="icon-bar"></span>
				        <span class="icon-bar"></span>
				        <span class="icon-bar"></span>
				      </button>
				      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar-brand"><?php bloginfo('name');?></a>
				    </div>
				    <div class="navbar-mobile">
				    	<?php wp_nav_menu(
							array( 
								'container_class' => 'mobile-menu clearfix',
								'theme_location' => 'main-menu',
								'menu_class' => 'nav navbar-nav',
							)
						); ?> 
				    </div>
					<div class="sns-link col-sm-2 col-xs-6 clearfix">
						<a href="<?php echo get_theme_mod('facebook_profile_link', 'http://www.facebook.com'); ?>">
							<i class="fa fa-facebook"></i>
						</a>
						<a href="<?php echo get_theme_mod('twitter_profile_link', 'http://www.twitter.com'); ?>">
							<i class="fa fa-twitter"></i>
						</a>
						<a href="<?php echo get_theme_mod('google_profile_link', 'http://plus.google.com'); ?>">
							<i class="fa fa-google-plus"></i>
						</a>
						<a href="<?php echo get_theme_mod('pinterest_profile_link', 'http://www.pinterest.com'); ?>">
							<i class="fa fa-pinterest-p"></i>
						</a>
					</div>	
					<div class="col-sm-1 col-xs-3 search-bar">
						<div class="search-icon"><i class="fa fa-search"></i></div>
						<div class="search-toggle"><?php get_search_form();?></div>
					</div>
				</div>
			</div>
		</header>

