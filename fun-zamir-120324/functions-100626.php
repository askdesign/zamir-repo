<?php
//* Start the engine
include_once( get_template_directory() . '/lib/init.php' );

//* Child theme (do not remove)
define( 'CHILD_THEME_NAME', 'Fun' );
define( 'CHILD_THEME_URL', 'http://www.prettydarncute.com/' );
define( 'CHILD_THEME_VERSION', '1.0.0' );

//* Enqueue Google font
add_action( 'wp_enqueue_scripts', 'genesis_sample_google_fonts' );
function genesis_sample_google_fonts() {
	wp_enqueue_style( 'google-font', '//fonts.googleapis.com/css?family=Alice|Quattrocento+Sans:400,400italic,700,700italic', array(), PARENT_THEME_VERSION );
}


// Load Font Awesome
add_action( 'wp_enqueue_scripts', 'enqueue_font_awesome' );
function enqueue_font_awesome() {

	wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css' );

}


//* Add HTML5 markup structure
add_theme_support( 'html5' );

//* Add viewport meta tag for mobile browsers
add_theme_support( 'genesis-responsive-viewport' );

//* Add support for custom header - Eliminate this support for responsiveness
//* add_theme_support( 'custom-header', array(
//*	'width'           => 422,
//*	'height'          => 200,
//*	'header-selector' => '.site-title a',
//*	'header-text'     => false,
//*) );

//* Add support for 3-column footer widgets
add_theme_support( 'genesis-footer-widgets', 3 );

//* Add featured image sizes
add_image_size( 'sidebar', 350, 350, TRUE );
add_image_size( 'home-products', 400, 400, TRUE );
add_image_size( 'blog-featured-image', 740, 500, TRUE );
add_image_size( 'blog-excerpt-image', 255, 255, TRUE );
add_image_size( 'featured-home-image', 400, 184, TRUE );

//* Add support for additional color styles
add_theme_support( 'genesis-style-selector', array(
	'fun-sparkle'   => __( 'Fun Sparkle', 'fun' ),
) );

//* Register responsive menu script
add_action( 'wp_enqueue_scripts', 'fun_enqueue_scripts' );
function fun_enqueue_scripts() {

	wp_enqueue_script( 'fun-responsive-menu', get_stylesheet_directory_uri() . '/js/responsive-menu.js', array( 'jquery' ), '1.0.0', true ); 

}

//* Add Support for Comment Numbering
add_action ( 'genesis_before_comment', 'fun_numbered_comments' );
function fun_numbered_comments () {

    if (function_exists( 'gtcn_comment_numbering' ))
    echo gtcn_comment_numbering($comment->comment_ID, $args);

}

//* Modify the length of post excerpts
add_filter( 'excerpt_length', 'fun_excerpt_length' );
function fun_excerpt_length( $length ) {

	return 65; // pull first 65 words

}

//* Genesis Previous/Next Post Post Navigation 
add_action( 'genesis_before_comments', 'fun_prev_next_post_nav' );
 
function fun_prev_next_post_nav() {
  
	if ( is_single() ) {
 
		echo '<div class="prev-next-navigation">';
		previous_post_link( '<div class="previous">%link</div>', '%title' );
		next_post_link( '<div class="next">%link</div>', '%title' );
		echo '</div><!-- .prev-next-navigation -->';
	}
}

//* Hook after post widget after the entry content
add_action( 'genesis_before_loop', 'fun_ad_widget', 5 );
function fun_ad_widget() {

	if ( is_singular( 'post' ) )
		genesis_widget_area( 'ad-widget', array(
			'before' => '<div class="ad-widget widget-area">',
			'after'  => '</div>',
		) );
}

//* Hook after post widget after the entry content
add_action( 'genesis_after_entry', 'fun_after_entry', 5 );
function fun_after_entry() {

	if ( is_singular( 'post' ) )
		genesis_widget_area( 'after-entry', array(
			'before' => '<div class="after-entry widget-area">',
			'after'  => '</div>',
		) );
}

//* Hook after post widget after the entry content
add_action( 'genesis_after_entry', 'fun_related_posts_area', 5 );
function fun_related_posts_area() {

	if ( is_singular( 'post' ) )
		genesis_widget_area( 'related-posts-area', array(
			'before' => '<div class="related-posts-area widget-area">',
			'after'  => '</div>',
		) );
}

//* Add Read More Link for Custom Excerpts
function excerpt_read_more_link($output) {
	global $post;
	return $output . '<a href="'. get_permalink($post->ID) . '"> <div class="readmorelink"><div class="rmtext">[ Read More ]</div></div></a>';
}
add_filter('the_excerpt', 'excerpt_read_more_link');

add_filter('excerpt_more','__return_false');

//* Customize the post info function
add_filter( 'genesis_post_info', 'fun_post_info_filter' );
function fun_post_info_filter($post_info) {
if ( !is_page() ) {

	$post_info = '[post_date] [post_author before="by: "]';
	return $post_info;

}}

//* Footer credits
add_filter('genesis_footer_creds_text', 'fun_footer_creds_filter');
function fun_footer_creds_filter( $creds ) {

	$creds = '[footer_copyright] &middot; Fun Genesis WordPress Theme by, <a href="http://prettydarncute.com">Pretty Darn Cute Design</a>';
	return $creds;

}

//* Reposition the secondary navigation menu
remove_action( 'genesis_after_header', 'genesis_do_subnav' );
add_action( 'genesis_before_header', 'genesis_do_subnav' );

//* Expand the secondary navigation menu to two level depth
add_filter( 'wp_nav_menu_args', 'fun_secondary_menu_args' );
function fun_secondary_menu_args( $args ){

	if( 'secondary' != $args['theme_location'] )
	return $args;

	$args['depth'] = 2;
	return $args;
}

// Assign menus conditionally in Secondary Navigation Menu location - Sridhar
add_filter( 'wp_nav_menu_args', 'replace_menu_in_secondary' );
function replace_menu_in_secondary( $args ) {
	if ( $args['theme_location'] != 'secondary' ) {
		return $args;
	}

if ( wp_emember_is_member_logged_in('2') ) {//Show this menu to members of membership level 2
		$args['menu'] = 'Tutti';
	} else if ( wp_emember_is_member_logged_in('3') ) {//Show this menu to members of membership level 3
		$args['menu'] = 'Board';
	} else if ( wp_emember_is_member_logged_in('4') ) {//Show this menu to members of membership level 4
		$args['menu'] = 'Staff';
	}
	return $args;
}
 
//* Subscribe Widget
add_action( 'genesis_before_header', 'custom_subscribe_widget' );
function custom_subscribe_widget() {
	genesis_widget_area( 'subscribewidget', array(
'before' => '<div class=subscribe-widget widget-area">',
'after' => '</div>',
) );
}

//* Remove post meta
remove_action( 'genesis_entry_footer', 'genesis_post_meta' );

//* Position post info above post title
remove_action( 'genesis_entry_header', 'genesis_post_info', 12);
add_action( 'genesis_entry_header', 'genesis_post_info', 9 );

//* Add new image size
add_image_size( 'home-featured', 800, 400, true );
add_image_size( 'home-featured-side', 400, 190, true );

add_action( 'genesis_after_header', 'header_top', 1 );
function header_top() {
 
	echo '<div class="header-top"><div class="wrap">';
 
	genesis_widget_area( 'left-header', array(
		'before' => '<div class="left-header">',
		'after' => '</div>',
	) );
 
	genesis_widget_area( 'right-header', array(
		'before' => '<div class="right-header">',
		'after' => '</div>',
	) );
 
	echo '</div></div>';
 
}

//* Enqueue Dashicons
add_action( 'wp_enqueue_scripts', 'enqueue_dashicons' );
function enqueue_dashicons() {
 
	wp_enqueue_style( 'dashicons' );
 
}

//* Add woocommerce support
add_theme_support( 'genesis-connect-woocommerce' );

/* create_function was removed in PHP 8.0. -- comment out this section
//* Woocommerce products per page
add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 24;' ), 20 ); */

//* Customize search form input box text
add_filter( 'genesis_search_text', 'fun_search_text' );
function fun_search_text( $text ) {

	return esc_attr( 'search here..' );

}

//* Unregister header right widget area
unregister_sidebar( 'header-right' );

//* Register Widget Areas
genesis_register_sidebar( array(
	'id'          	=> 'ad-widget',
	'name'        	=> __( 'Before Blog Post Widget Area', 'fun' ),
	'description' 	=> __( 'This widget area appears before blog posts', 'fun' ),
) );
genesis_register_sidebar( array(
	'id'          	=> 'after-entry',
	'name'        	=> __( 'After Blog Post', 'fun' ),
	'description' 	=> __( 'This is the after blog post widget area.', 'fun' ),
) );
genesis_register_sidebar( array(
	'id'          	=> 'related-posts-area',
	'name'        	=> __( 'Another After Blog Post Widget', 'fun' ),
	'description' 	=> __( 'This widget will appear after your posts.', 'fun' ),
) );
genesis_register_sidebar( array(
	'id'				=> 'subscribewidget',
	'name'			=> __( 'Subscribe Widget', 'fun' ),
	'description'	=> __( 'This is the subscribe widget.', 'fun' ),
) );
genesis_register_sidebar( array(
	'id'				=> 'home-featured',
	'name'			=> 'Home Top Widget Area',
	'description'	=> 'This is the Home Featured section'
) );
genesis_register_sidebar( array(
	'id'				=> 'home-blog-featured',
	'name'			=> 'Home Blog Posts Featured',
	'description'	=> 'This is the Home Featured section for your blog posts. Place the featured posts widget here'
) );
genesis_register_sidebar( array(
	'id'				=> 'home-products',
	'name'			=> 'Home Products Widget Area',
	'description'	=> 'This is the section on your home page where you display your latest products.'
) );
genesis_register_sidebar( array(
	'id' 			=> 'left-header',
	'name' 			=> __( 'Left Header Widget', 'fun' ),
	'description' 	=> __( 'This is the left side of your header.', 'fun' ),
) );
genesis_register_sidebar( array(
	'id' 			=> 'right-header',
	'name' 			=> __( 'Right Header Widget', 'fun' ),
	'description' 	=> __( 'This is the right side of your header.', 'fun' ),
) );


/** Customize the credits */
add_filter('genesis_footer_creds_text', 'custom_footer_creds_text');
function custom_footer_creds_text() {
    echo '<div class="creds"><p>';
    echo 'Copyright &copy; ';
    echo date('Y');
 	echo ' &middot; Site Design and Maintenance by <a href="https://www.askdesign.biz/">ASK Design</a>';
    echo '</p></div>';
}

/** Genesis 2.2.2 - full array is headings, drop-down-menu, search-form, skip-links, rems **/
add_theme_support( 'genesis-accessibility', 
  array( 'headings', 'drop-down-menu', 'search-form' ) 
);

/** COMMENTS MODIFICATIONS **/
//* Modify the speak your mind title in comments
add_filter( 'comment_form_defaults', 'sp_comment_form_defaults' );
function sp_comment_form_defaults( $defaults ) {
 
	$defaults['title_reply'] = __( 'Write a Tribute' );
	return $defaults;
 
}
//* Change comment label above comment field to tribute
//* Brad Dalton's code snippet
function wpsites_modify_comment_form_text_area($arg) {
    $arg['comment_field'] = '<p class="comment-form-comment"><label for="comment">' . _x( 'Your Tribute', 'noun' ) . '</label><textarea id="comment" name="comment" cols="45" rows="10" aria-required="true"></textarea></p>';
    return $arg;
}

add_filter('comment_form_defaults', 'wpsites_modify_comment_form_text_area');

//* Modify comments title text in comments - original head was Discussions
add_filter( 'genesis_title_comments', 'sp_genesis_title_comments' );
function sp_genesis_title_comments() {
	$title = '<h3>Tributes</h3>';
	return $title;
}

//* Customize the submit button text in comments
add_filter( 'comment_form_defaults', 'sp_comment_submit_button' );
function sp_comment_submit_button( $defaults ) {
 
        $defaults['label_submit'] = __( 'Submit', 'custom' );
        return $defaults;
 
}