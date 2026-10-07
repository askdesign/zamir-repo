<?php

add_action( 'genesis_meta', 'fun_home_genesis_meta' );
/**
 * Add widget support for homepage. If no widgets active, display the default loop.
 *
 */
function fun_home_genesis_meta() {

if ( is_active_sidebar( 'home-featured' ) || is_active_sidebar( 'home-products' ) || is_active_sidebar( 'home-blog-featured' ) ) {

remove_action( 'genesis_loop', 'genesis_do_loop' );
add_filter( 'genesis_pre_get_option_site_layout', '__genesis_return_full_width_content' );

}
}

add_action( 'genesis_after_header', 'fun_home_featured' );
function fun_home_featured() {
	if ( is_home() || is_front_page() ) {
		genesis_widget_area( 'home-featured', array(
			'before'	=> '<div class="home-featured widget-area"><div class="wrap">',
			'after'		=> '</div></div>',
		) );
	}
}

add_action( 'genesis_after_header', 'fun_home_products' );
function fun_home_products() {
	if ( is_home() || is_front_page() ) {
		genesis_widget_area( 'home-products', array(
			'before'	=> '<div class="home-products widget-area"><div class="wrap">',
			'after'		=> '</div></div>',
		) );
	}
}

add_action( 'genesis_after_header', 'fun_home_blog_featured' );
function fun_home_blog_featured() {
	if ( is_home() || is_front_page() ) {
		genesis_widget_area( 'home-blog-featured', array(
			'before'	=> '<div class="home-blog-featured widget-area"><div class="wrap">',
			'after'		=> '</div></div>',
		) );
	}
}


genesis();