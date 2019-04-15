<?php
/*
Plugin Name: Forum
Plugin URI: https://github.com/mrsn5
Description: The forum plugin
Version: 1.0.0
Author: San Nguyen
Author URI: https://github.com/mrsn5
*/

require_once( __DIR__ . '/categories-all.php' );


wp_enqueue_script('categories-add-script', plugins_url('/js/add-category.js', __FILE__), array('jquery'));
wp_localize_script('categories-add-script', 'url_object',
    array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));


?>