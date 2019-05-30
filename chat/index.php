<?php
/*
Plugin Name: Chat
Plugin URI: https://github.com/mrsn5
Description: The forum plugin
Version: 1.0.0
Author: San Nguyen
Author URI: https://github.com/mrsn5
*/

require_once(__DIR__ . '/ajax/dialogs_ajax.php');

add_filter('template_include', 'chat_page_template', 99);

function chat_page_template($template)
{
    $file_name = 'chat.php';
    if (is_page('chat')) {
        $template = dirname(__FILE__) . '/templates/' . $file_name;
    }
    return $template;
}

function install_events_pg()
{
    $new_page_title = 'Chat';
    $new_page_content = 'This page contains chat';
    $new_page_template = ''; //ex. template-custom.php. Leave blank if you don't want a custom page template.
    //don't change the code below, unless you know what you're doing
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
}//end install_events_pg function to add page to wp on plugin activation

register_activation_hook(__FILE__, 'install_events_pg');


function chat_scripts()
{

    wp_register_script('chat-js', plugins_url('js/compiled/chat.js', __FILE__), array('jquery'), date("h:i:s"), true);
    //wp_register_style('chat-css', plugins_url('less/chat.less', __FILE__), '', date("h:i:s"), 'screen');

    wp_enqueue_script('chat-js');
    //wp_enqueue_style('chat-css');

    wp_localize_script('chat-js', 'url_object',
        array('ajax_url' => admin_url('admin-ajax.php'), 'plugin_directory' => plugins_url('', __FILE__), 'site_url' => get_site_url()));

    $current_user = wp_get_current_user();
    wp_localize_script('chat-js', 'user_object',
        array(
            'id' => $current_user->ID,
            'role' => ((array)(wp_get_current_user()->roles)[0])[0],
            'username' => $current_user->user_login,
            'photo' => get_avatar_data($current_user->ID, null)['url']
        ));

    wp_register_script('chat-system-js', plugins_url('js/compiled/chat-system.js', __FILE__), array('jquery'), date("h:i:s"), true);
    wp_register_style('chat-css-system', plugins_url('less/chat-system.less', __FILE__), '', date("h:i:s"), 'screen');

    wp_enqueue_script('chat-system-js');
    wp_enqueue_style('chat-css-system');

    wp_localize_script('chat-system-js', 'url_object',
        array('ajax_url' => admin_url('admin-ajax.php'),
            'plugin_directory' => plugins_url('', __FILE__),
            'site_url' => get_site_url(),
            'is_post' => is_page('posts')));

    wp_localize_script('chat-system-js', 'wp_object',
        array('plugin_directory' => plugins_url('', __FILE__),
            'is_post' => is_page('posts'),
            'is_chat' => is_page('chat')));

    $current_user = wp_get_current_user();
    wp_localize_script('chat-system-js', 'user_object',
        array(
            'id' => $current_user->ID,
            'role' => ((array)(wp_get_current_user()->roles)[0])[0],
            'username' => $current_user->user_firstname . " " . $current_user->user_lastname,
            'photo' => get_avatar_data($current_user->ID, null)['url']
        ));
}

register_activation_hook(__FILE__, 'chat_db_tables');
function chat_db_tables()
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();

    $sql1 = "CREATE TABLE " . $wpdb->prefix . "c_dialogs (
                dialog_id          mediumint unsigned NOT NULL AUTO_INCREMENT,
                user1_id           bigint(20) unsigned NOT NULL,
                user2_id           bigint(20) unsigned NULL,
                employee_id        bigint(20) unsigned NULL,
                is_employee_chat   bit(1) NOT NULL DEFAULT false,
                dialog_topic       char(100) NULL,
                is_closed          bit(1) NOT NULL DEFAULT false
                PRIMARY KEY  (dialog_id),
                FOREIGN KEY  (user1_id) REFERENCES " . $wpdb->prefix . "users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
                FOREIGN KEY  (user2_id) REFERENCES " . $wpdb->prefix . "users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION,
                FOREIGN KEY  (employee_id) REFERENCES " . $wpdb->prefix . "users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate";
    dbDelta($sql1);

    global $wpdb;
    $sql2 = "CREATE TABLE " . $wpdb->prefix . "c_messages (
                 message_id         mediumint unsigned NOT NULL AUTO_INCREMENT,
                 user_from_id       bigint(20) unsigned NOT NULL,
                 dialog_id          mediumint unsigned NOT NULL,
                 is_read            bit(1) NOT NULL DEFAULT false,
                 is_important       bit(1) NOT NULL DEFAULT false,
                 message_body       text NOT NULL,
                 create_timestamp   timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                 PRIMARY KEY  (message_id),
                 FOREIGN KEY  (dialog_id) REFERENCES " . $wpdb->prefix . "c_dialogs (dialog_id) ON DELETE CASCADE ON UPDATE CASCADE,
                 FOREIGN KEY  (user_from_id) REFERENCES " . $wpdb->prefix . "users (ID) ON DELETE NO ACTION ON UPDATE NO ACTION
            ) $charset_collate";
    dbDelta($sql2);
}

add_action('wp_enqueue_scripts', 'chat_scripts');