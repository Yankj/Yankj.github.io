<?php
/**
 *
 *
 * @package worldpress
 * @since 0.0.1
 */

/**
 * Set the content width based on the theme's design and stylesheet.
 */

//main navigation
function register_main_menu() {
  register_nav_menu('main-menu',__( 'Main Menu' ));
}
add_action( 'init', 'register_main_menu' );

//right sidebar
function sidebar_widgets_init() {
	register_sidebar( array(
		'name'          => 'Right Sibebar',
		'id'            => 'home_right_1',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
}
add_action( 'widgets_init', 'sidebar_widgets_init' );

//footer widget area 
function footer_first_widget_init() {
	register_sidebar( array(
		'name'          => 'First Footer',
		'id'            => 'first_footer_widget',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'footer_first_widget_init' );

function footer_second_widget_init() {
	register_sidebar( array(
		'name'          => 'Second Footer',
		'id'            => 'second_footer_widget',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'footer_second_widget_init' );

function footer_third_widget_init() {
	register_sidebar( array(
		'name'          => 'Third Footer',
		'id'            => 'third_footer_widget',
		'before_widget' => '<div>',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'footer_third_widget_init' );


//customizer to let administrator input the social networking profile 
function worldpress_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'worldpress_social' , array(
    'title'      => __( 'Social Network Profile', 'worldpress' ),
    'priority'   => 50,
	) );
	$wp_customize->add_setting('facebook_profile_link',array(
		'default'=>'https://www.facebook.com/',
		'transport'   => 'refresh',
	));
	$wp_customize->add_setting('twitter_profile_link',array(
		'default'=>'https:/twitter.com/',
		'transport'   => 'refresh',
	));
	$wp_customize->add_setting('google_profile_link',array(
		'default'=>'https://plus.google.com/',
		'transport'   => 'refresh',
	));
	$wp_customize->add_setting('pinterest_profile_link',array(
		'default'=>'https://www.pinterest.com/',
		'transport'   => 'refresh',
	));	
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'facebook_profile', array(
		'label'        => __( 'Link to your Facebook page', 'worldpress' ),
		'section'    => 'worldpress_social',
		'settings'   => 'facebook_profile_link',
		'type' => 'text',
	) ) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'twitter_profile', array(
		'label'        => __( 'Link to your Twitter page', 'worldpress' ),
		'section'    => 'worldpress_social',
		'settings'   => 'twitter_profile_link',
		'type' => 'text',
	) ) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'google_profile', array(
		'label'        => __( 'Link to your Google Plus page', 'worldpress' ),
		'section'    => 'worldpress_social',
		'settings'   => 'google_profile_link',
		'type' => 'text',
	) ) );
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'pinterest_profile', array(
		'label'        => __( 'Link to your Pinterest page', 'worldpress' ),
		'section'    => 'worldpress_social',
		'settings'   => 'pinterest_profile_link',
		'type' => 'text',
	) ) );
}
add_action( 'customize_register', 'worldpress_customize_register' );

/*
*Add our css and js asset to the theme
*/
function wordpress_assets() {
	wp_enqueue_style( 'fonts', get_template_directory_uri() . '/css/fonts.css' );
	wp_enqueue_style( 'bootstrapcss', get_template_directory_uri() . '/css/bootstrap.min.css' );
	wp_enqueue_style( 'sitecss', get_template_directory_uri().'/style.css',array('bootstrapcss') );
	wp_enqueue_script( 'sitejs', get_template_directory_uri() . '/js/script.min.js', array('jquery'), null, false );
}
add_action( 'wp_enqueue_scripts', 'wordpress_assets' );

//add featured post type to be displayed in home page carousel
function create_post_type() {
  register_post_type( 'feature',
    array(
      'labels' => array(
        'name' => __( 'Featured Post' ),
      ),
      'public'=>true,
      'description'=>'Featured Post Will be promoted',
      'public' => true,
      'has_archive' => true,
      'taxonomies' => array('category','post_tag'),
      'rewrite'=>false
    )
  );
}
add_action( 'init', 'create_post_type' );

//add the feature image field
add_theme_support( 'post-thumbnails' ); 

//html5 form handling
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption', 'widgets' ) );

//add about us text widget  
class worldpress_about_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'worldpress_about',  
			__( 'About Us', 'text_domain' ),  
			array( 'description' => __( 'About Us Widget', 'text_domain' ), ) //  
		);
	}
	public function widget($args,$instance) {
		extract( $args );
   		$title = apply_filters('widget_title', $instance['title']);
   		$textarea = $instance['textarea'];
   		echo $before_widget;
   		?>
   		<div class="widget-text worldpress-about-widget">
   		<?php echo $before_title . $title . $after_title;?>
   		<p><?php echo $textarea;?></p>
   		</div>
   		<?php 
   		echo $after_widget;
	}
	public function form($instance) {
		if( $instance['title']) {
		     $title = esc_attr($instance['title']); 
		} else {
		     $title = 'About Us';
		}
		if ($instance['textarea']) {
			$textarea=esc_textarea($instance['textarea']);
		} else {
			$textarea="Just about us text";
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('textarea'); ?>"><?php _e('Textarea:', 'wp_widget_plugin'); ?></label>
		<textarea class="widefat" id="<?php echo $this->get_field_id('textarea'); ?>" name="<?php echo $this->get_field_name('textarea'); ?>"><?php echo $textarea; ?></textarea>
		</p>
		<?php
	}
	public function update($new_instance,$old_instance){
		$instance = $old_instance;
      	$instance['title'] = strip_tags($new_instance['title']);
      	$instance['textarea'] = strip_tags($new_instance['textarea']);
     	return $instance;
	}
}

function wordpress_about_register_widget() {
    register_widget( 'worldpress_about_widget' );
}
add_action( 'widgets_init', 'wordpress_about_register_widget' );

//add media gallery widget  
class worldpress_gallery_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'worldpress_gallery',  
			__( 'Image Gallery', 'text_domain' ),  
			array( 'description' => __( 'Show Recent Uploaded Pictures in Thumbnails', 'text_domain' ), ) 
		);
	}
	public function widget($args,$instance) {
		extract( $args );
   		$title = apply_filters('widget_title', $instance['title']);
   		$number=$instance['number'];
   		echo $before_widget;
   		?>
   		<div class="widget-text worldpress-gallery-widget">
   		<?php echo $before_title . $title . $after_title;;?>
   		<?php 
			$args = array(
			   'post_type'      => 'attachment',  
			   'post_status'    => 'inherit',  
			   'post_mime_type' => 'image',  
			   'posts_per_page' => $number 
			);
			$attachments = get_posts($args); 
			foreach($attachments as $a)
			{
			?>
			   <div class="col-xs-3 worldpress-gallery-image">
			   		<a href="<?php echo wp_get_attachment_url( $a->ID );?>" title="<?php echo $a->post_title;?>">
			   		<img width="150" height="150" class="img-responsive img-thumbnail" src="<?php echo wp_get_attachment_image_src($a->ID)[0];?>" />
			   		</a>
			   </div>
			<?php 
			}
   		?>
   		</div>
   		<?php 
   		echo $after_widget;
	}
	public function form($instance) {
		if( $instance['title']) {
		     $title = esc_attr($instance['title']); 
		} else {
		     $title = __('Gallery');
		}
		if ($instance['number']) {
			$number= $instance['number']; 
		} else {
			$number=8;
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title', 'wp_widget_plugin'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of Images To Show', 'wp_widget_plugin'); ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>">
			<option>4</option>
			<option>8</option>
			<option>12</option>
			<option>16</option>
		</select>
		</p>
		<?php
	}
	public function update($new_instance,$old_instance){
		$instance = $old_instance;
      	$instance['title'] = strip_tags($new_instance['title']);
      	$instance['number'] = strip_tags($new_instance['number']);
     	return $instance;
	}
}

function wordpress_gallery_register_widget() {
    register_widget( 'worldpress_gallery_widget' );
}
add_action( 'widgets_init', 'wordpress_gallery_register_widget' );
