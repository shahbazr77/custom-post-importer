<?php
/*
Plugin Name: Custom Talents Importer
Description: A plugin to register a custom post type for talents and import posts from an XML file via AJAX.
Version: 1.0
Author: Yodo Club Design
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
function register_talents_post_type() {
    $labels2 = array(
        'name'                  => _x('Talents', 'Post Type General Name', 'text_domain'),
        'singular_name'         => _x('Talents Item', 'Post Type Singular Name', 'text_domain'),
        'menu_name'             => __('Talents', 'text_domain'),
        'name_admin_bar'        => __('Talents Item', 'text_domain'),
        'archives'              => __('Talents Archives', 'text_domain'),
        'attributes'            => __('Talents Attributes', 'text_domain'),
        'parent_item_colon'     => __('Parent News:', 'text_domain'),
        'all_items'             => __('All Talents', 'text_domain'),
        'add_new_item'          => __('Add New Talents Item', 'text_domain'),
        'add_new'               => __('Add Talents', 'text_domain'),
        'new_item'              => __('New Talents Item', 'text_domain'),
        'edit_item'             => __('Edit Talents Item', 'text_domain'),
        'update_item'           => __('Update Talents Item', 'text_domain'),
        'view_item'             => __('View Talents Item', 'text_domain'),
        'view_items'            => __('View Talents', 'text_domain'),
        'search_items'          => __('Search Talents', 'text_domain'),
        'not_found'             => __('Not found', 'text_domain'),
        'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
        'featured_image'        => __('Featured Image', 'text_domain'),
        'set_featured_image'    => __('Set featured image', 'text_domain'),
        'remove_featured_image' => __('Remove featured image', 'text_domain'),
        'use_featured_image'    => __('Use as featured image', 'text_domain'),
        'insert_into_item'      => __('Insert into news', 'text_domain'),
        'uploaded_to_this_item' => __('Uploaded to this talents', 'text_domain'),
        'items_list'            => __('Talents list', 'text_domain'),
        'items_list_navigation' => __('Talents list navigation', 'text_domain'),
        'filter_items_list'     => __('Filter talents list', 'text_domain'),
    );
    $args2 = array(
        'label'                 => __('Talents', 'text_domain'),
        'description'           => __('Talents custom post type', 'text_domain'),
        'labels'                => $labels2,
        'supports'              => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields'),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 6,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rewrite'               => array('slug' => 'talents'),
    );

    register_post_type('talents', $args2);
}
add_action('init', 'register_talents_post_type');



function custom_importer_scripts() {
    wp_enqueue_script( 'custom', plugin_dir_url( __FILE__ ) . 'assets/js/custom.js', array('jquery'), '1.0', true );

    wp_localize_script( 'custom', 'localized_data', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ));
}
add_action( 'admin_enqueue_scripts', 'custom_importer_scripts' );

register_deactivation_hook(__FILE__, 'custom_plugin_deactivation');

// Register uninstall hook
register_uninstall_hook(__FILE__, 'custom_plugin_uninstall');
function custom_plugin_deactivation() {
    delete_option('last_imported_post_index');
}

// Uninstall function
function custom_plugin_uninstall() {
    delete_option('last_imported_post_index');
}


// Register menu page
function custom_importer_menu() {
    add_menu_page(
        __('Talent Importer', 'text_domain'),
        __('Talent Importer', 'text_domain'),
        'manage_options',
        'talent-importer',
        'custom_importer_page',
        'dashicons-download',
        6
    );
}
add_action('admin_menu', 'custom_importer_menu');

function custom_importer_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Talent Importer', 'text_domain'); ?></h1>
        <button id="import-posts-button" class="button button-primary"><?php _e('Import Posts', 'text_domain'); ?></button>
    </div>
    <?php
}





function custom_import_posts_from_xml() {
    // Ensure this function is only called via AJAX
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        wp_die();
    }

    // Load the XML file
    //$xml_file = plugin_dir_path(__FILE__) . 'xml-files/poster.xml';

    $xml_file = plugin_dir_path(__FILE__) . 'xml-files/poster-tpt.xml';


    if ( file_exists( $xml_file ) ) {
        // Load the XML file
        $xml = simplexml_load_file( $xml_file );

        // Get the last imported post index or initialize to 0
        $last_imported_post_index = get_option( 'last_imported_post_index', 0 );
        $current_index = 0;
        $imported_count = 0;
        $posts_data = array();

        // Iterate through each item in the XML
        foreach ( $xml->channel->item as $item ) {
            // Skip posts that have already been imported
            if ( $current_index < $last_imported_post_index ) {
                $current_index++;
                continue;
            }
            $post_title = isset($item->title) ? (string) $item->title : 'Untitled';
            $post_status = isset($item->children('wp', true)->status) ? (string) $item->children('wp', true)->status : 'publish';
            $post_content = isset($item->children('content', true)->encoded) ? (string) $item->children('content', true)->encoded : '';
            $post_excerpt = isset($item->children('excerpt', true)->encoded) ? (string) $item->children('excerpt', true)->encoded : '';
            $post_date = isset($item->pubDate) ? (string) $item->pubDate : current_time('mysql');
            $post_data = array(
                'post_title'    => $post_title,
                'post_status'   => $post_status,
                'post_author'   => 1,
                'post_type'     => 'talents',
            );
            $post_id = wp_insert_post( $post_data );
            if ( is_wp_error( $post_id ) ) {
                error_log( 'Error inserting post: ' . $post_id->get_error_message() );
                continue; // Skip to the next post on error
            }

            // Handle the featured image
            $attachment_url = (string) $item->children('wp', true)->attachment_url;

            if ( $attachment_url ) {
                // Download the image and add it to the media library
                $image_id = media_sideload_image( $attachment_url, $post_id, null, 'id' );

                if ( is_wp_error( $image_id ) ) {
                    error_log( 'Error sideloading image: ' . $image_id->get_error_message() );
                } else {
                    // Set the downloaded image as the featured image
                    set_post_thumbnail( $post_id, $image_id );
                }
            }

            // Add post data to the array
            $posts_data[] = $post_data;

            // Increment the counters
            $current_index++;
            $imported_count++;

            // Break the loop after importing 10 posts
            if ( $imported_count >= 10 ) {
                break;
            }
        }

        // Update the last imported post index
        update_option( 'last_imported_post_index', $current_index );

        // Return success response
        wp_send_json_success( array( 'posts' => $posts_data ) );
    } else {
        // XML file not found error handling
        wp_send_json_error( array( 'error' => 'XML file not found' ) );
    }
}
add_action( 'wp_ajax_custom_import_posts_from_xml', 'custom_import_posts_from_xml' );
add_action( 'wp_ajax_nopriv_custom_import_posts_from_xml', 'custom_import_posts_from_xml' );
