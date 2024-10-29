<?php
/* 

Plugin Name: CoastAMS All Media Tags
Plugin URI: http://plugins.coastams.co.uk/wordpress/media-tags-wordpress-plugin/

Description: A library to store content snippets to use on multiple pages - edit all in one place. Now with modal pop-up and also the option to wrap content in a collapse.

Version: 1.0.2

Author: CoastAMS
Author URI: http://www.coastams.co.uk/

License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/



//Register plugin activation hook for v3.5+

register_activation_hook(__FILE__, 'camsGC_mediatags_install');

function camsGC_mediatags_install() {
    
    global $wp_version;
    
    if (version_compare($wp_version, '3.5', '<')){
        
        wp_die('This plugin requires WordPress version 3.5 or higher.');
        
    }//end version check

}



// hook into the init action and call cams_create_media_taxonomies when it fires
add_action( 'init', 'cams_create_media_taxonomies', 0 );

// create "mediatag" taxonomy for "attachments"
function cams_create_media_taxonomies() {

	// Add new "tag" taxonomy
	$camsMT_labels = array(
		'name'                       => _x( 'Media Tags', 'taxonomy general name', 'ideasone' ),
		'singular_name'              => _x( 'Media Tag', 'taxonomy singular name', 'ideasone' ),
		'search_items'               => __( 'Search Media Tags', 'ideasone' ),
		'popular_items'              => __( 'Popular Media Tags', 'ideasone' ),
		'all_items'                  => __( 'All Media Tags', 'ideasone' ),
		'parent_item'                => null,
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Media Tag', 'ideasone' ),
		'update_item'                => __( 'Update Media Tag', 'ideasone' ),
		'add_new_item'               => __( 'Add New Media Tag', 'ideasone' ),
		'new_item_name'              => __( 'New Media Tag', 'ideasone' ),
		'separate_items_with_commas' => __( 'Separate media tags with commas', 'ideasone' ),
		'add_or_remove_items'        => __( 'Add or remove media tags', 'ideasone' ),
		'choose_from_most_used'      => __( 'Choose from the most used media tags', 'ideasone' ),
		'not_found'                  => __( 'No media tags found.', 'ideasone' ),
		'menu_name'                  => __( 'Media Tags', 'ideasone' ),
	);

	$camsMT_args = array(
		'hierarchical'          => false,
		'labels'                => $camsMT_labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'mediatag' ),
	);

	register_taxonomy( 'mediatag', 'attachment', $camsMT_args );
}


// This function joins the attachment "terms_relationships" and "term_taxonomy" tables
function cams_media_search_join( $camsMT_join, $camsMT_query )
{
    global $wpdb;

    // admin check or current search is not media
    if(!is_admin() || (!isset($camsMT_query->query['post_type']) || $camsMT_query->query['post_type'] != 'attachment')) 
        return $camsMT_join;

    //  check if current query is the main query and a search
    if( is_main_query() && is_search() ) {
		
        $camsMT_join .= "LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id LEFT JOIN {$wpdb->terms} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id ";
		
    }

    return $camsMT_join;
}
add_filter( 'posts_join', 'cams_media_search_join', 10, 2 );



// This function adds our new taxonomy to the search parameters
function cams_media_search_where( $camsMT_where, $camsMT_query )
{
    global $wpdb;

    // admin check or current search is not media
    if(!is_admin() || (!isset($camsMT_query->query['post_type']) || $camsMT_query->query['post_type'] != 'attachment'))
        return $camsMT_where;

    // check query is main query and a search
    if( is_main_query() && is_search() ) {
		
        //  explictly search post_tag taxonomies
        $camsMT_where .= " OR ( ( {$wpdb->term_taxonomy}.taxonomy IN('mediatag') AND {$wpdb->terms}.name LIKE '%" . $wpdb->escape( get_query_var('s') ) . "%' ) )";
	
    }

    return $camsMT_where;
}
add_filter( 'posts_where', 'cams_media_search_where', 10, 2 );



// This function will group results to avoid duplicate results showing up
function cams_media_search_groupresult( $camsMT_groupby, $camsMT_query )
{

    global $wpdb;

    // admin check or current search is not media
    if(!is_admin() || (!isset($camsMT_query->query['post_type']) || $camsMT_query->query['post_type'] != 'attachment'))
        return $camsMT_groupby;

    // check query is main query and a search
    if( is_main_query() && is_search() ) {
		
        //  assign the GROUPBY
        $camsMT_groupby = "{$wpdb->posts}.ID";
		
    }

    return $camsMT_groupby;

}
add_filter( 'posts_groupby', 'cams_media_search_groupresult', 10, 2 );

?>