<?php
/*
Plugin Name: Forum
Plugin URI: https://github.com/mrsn5
Description: The chat plugin
Version: 1.0.0
Author: San Nguyen
Author URI: https://github.com/mrsn5
Heheh
*/

require_once ( __DIR__ . '/Backend/ajax_handlers/forum/category.php');
require_once ( __DIR__ . '/Backend/ajax_handlers/forum/post.php');
require_once ( __DIR__ . '/Backend/ajax_handlers/forum/topic.php');
require_once ( __DIR__ . '/Backend/ajax_handlers/forum/user.php');

require_once( __DIR__ . '/categories-all.php' );


wp_enqueue_script('categories-add-script', plugins_url('/js/add-category.js', __FILE__), array('jquery'));
wp_localize_script('categories-add-script', 'url_object',
    array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));



// Adding custom template pages
add_filter('template_include', 'forum_page_template', 99);
function forum_page_template($template)
{
    if (is_page('categories')) {
        $template = dirname(__FILE__) . '/templates/' . 'forum_categories.php';
    } else if (is_page('topics')) {
        $template = dirname(__FILE__) . '/templates/' . 'forum_topics.php';
    } else if (is_page('posts')) {
        $template = dirname(__FILE__) . '/templates/' . 'forum_posts.php';
    }
    return $template;
}

// Adding pages when activating plugin
register_activation_hook(__FILE__, 'add_forum_pages');
function add_forum_pages()
{
    $pages = ['Categories', 'Topics', 'Posts'];

    foreach ($pages as $p) {
        $new_page_title = $p;
        $new_page_content = '';
        $new_page_template = '';

        $page_check = get_page_by_title($new_page_title);
        $new_page = array(
            'post_type' => 'page',
            'post_title' => $new_page_title,
            'post_content' => $new_page_content,
            'post_status' => 'publish',
            'post_author' => 1,
        );
        if (!isset($page_check->ID)) {
            $new_page_id = wp_insert_post($new_page);
            if (!empty($new_page_template)) {
                update_post_meta($new_page_id, '_wp_page_template', $new_page_template);
            }
        }
    }
}



add_action('wp_enqueue_scripts', 'forum_scripts');
function forum_scripts()
{
    if (is_page('categories')) {
        wp_enqueue_script('categories-script', plugins_url('js/compiled/categories.js', __FILE__), array('jquery'), date("h:i:s") , true);
        wp_localize_script('categories-script', 'url_object',
            array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));
    } else if (is_page('topics')) {
        wp_enqueue_script('topics-script', plugins_url('js/compiled/topics.js', __FILE__), array('jquery'), date("h:i:s") , true);
        wp_localize_script('topics-script', 'url_object',
            array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));
        wp_localize_script('topics-script', 'user_object',
            array(
                'id' => wp_get_current_user()->ID
            ));
    } else if (is_page('posts')) {
        wp_enqueue_script('posts-script', plugins_url('js/compiled/posts.js', __FILE__), array('jquery'), date("h:i:s") , true);
        wp_localize_script('posts-script', 'url_object',
            array('ajax_url' => admin_url('admin-ajax.php'), 'template_directory' => plugins_url('', __FILE__), 'site_url' => get_site_url()));
        wp_dequeue_style( 'bootstrap' );
        wp_localize_script('posts-script', 'user_object',
            array(
                'id' => wp_get_current_user()->ID,
                'role' => ((array)( wp_get_current_user()->roles )[0])[0]
            ));
    }
}



register_activation_hook( __FILE__, 'forum_db_tables' );
function forum_db_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    $sql1 = "CREATE TABLE ".$wpdb->prefix."f_categories (
                cat_name char(50) NOT NULL ,
                PRIMARY KEY  (cat_name)
            ) $charset_collate";
    dbDelta( $sql1 );

    global $wpdb;
    $sql2 = "CREATE TABLE ".$wpdb->prefix."f_topics (
                 topic_id         mediumint unsigned NOT NULL AUTO_INCREMENT ,
                 topic_name       char(100) NOT NULL ,
                 cat_name         char(50) NOT NULL ,
                 user_id          bigint(20) unsigned NOT NULL ,
                 is_anonym        bit(1) NOT NULL ,
                 create_timestamp timestamp NOT NULL,
                PRIMARY KEY  (topic_id) ,
                FOREIGN KEY  (cat_name) REFERENCES ".$wpdb->prefix."f_categories (cat_name) ON DELETE CASCADE ON UPDATE CASCADE ,
                FOREIGN KEY  (user_id) REFERENCES ".$wpdb->prefix."users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate";
    dbDelta( $sql2 );

    global $wpdb;
    $sql3 = "CREATE TABLE ".$wpdb->prefix."f_posts (
                 post_id          int unsigned NOT NULL AUTO_INCREMENT ,
                 response_to      int unsigned ,
                 topic_id         mediumint unsigned NOT NULL ,
                 user_id          bigint(20) unsigned NOT NULL ,
                 post_message     text NOT NULL ,
                 is_anonym        bit(1) NOT NULL ,
                 is_reaction      bit(1) NOT NULL DEFAULT 0,
                 create_timestamp timestamp NOT NULL DEFAULT 0,
                PRIMARY KEY  (post_id),
                FOREIGN KEY  (topic_id) REFERENCES ".$wpdb->prefix."f_topics (topic_id) ON DELETE CASCADE ON UPDATE CASCADE ,
                FOREIGN KEY  (user_id) REFERENCES ".$wpdb->prefix."users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate";
    dbDelta( $sql3 );

    global $wpdb;
    $sql4 = "CREATE TABLE ".$wpdb->prefix."f_favorites (
                 user_id  bigint(20) unsigned NOT NULL ,
                 topic_id mediumint unsigned NOT NULL ,
                PRIMARY KEY  (user_id, topic_id),
                FOREIGN KEY  (topic_id) REFERENCES ".$wpdb->prefix."f_topics (topic_id) ON DELETE CASCADE ON UPDATE CASCADE ,
                FOREIGN KEY  (user_id) REFERENCES ".$wpdb->prefix."users (ID) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate";
    dbDelta( $sql4 );

    global $wpdb;
    $sql5 = "CREATE TABLE ".$wpdb->prefix."f_likes (
                 post_id int unsigned NOT NULL ,
                 user_id bigint(20) unsigned NOT NULL ,
                PRIMARY KEY  (post_id, user_id),
                FOREIGN KEY  (post_id) REFERENCES ".$wpdb->prefix."f_posts (post_id) ON DELETE CASCADE ON UPDATE CASCADE ,
                FOREIGN KEY  (user_id) REFERENCES ".$wpdb->prefix."users (ID) ON DELETE CASCADE ON UPDATE CASCADE
            ) $charset_collate";
    dbDelta( $sql5 );
}



function add_category_admin_bar()
{
    global $wp_admin_bar;
    $wp_admin_bar->add_menu(array(
        'parent' => 'new-content', // use 'false' for a root menu, or pass the ID of the parent menu
        'id' => 'add_forum_category', // link ID, defaults to a sanitized title value
        'title' => __('Forum category'), // link title
        'href' => get_site_url() . "/wp-admin/admin.php?page=sn_categories_add"
    ));
}

add_action( 'wp_before_admin_bar_render', 'add_category_admin_bar' );









?>