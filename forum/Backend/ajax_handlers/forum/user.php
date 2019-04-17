<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 26.03.2019
 * Time: 21:28
 */



add_action('wp_ajax_'.'get_user', 'get_user');
add_action('wp_ajax_nopriv_'.'get_user', 'get_user');


function get_user() {
    global $wpdb;
    $user_id = $_POST['user_id'];
    $user_info = new WP_User( $user_id );

    $user = array();
    $user['photo'] = get_avatar_url($user_id);
    $user['first_name'] = $user_info->first_name;
    $user['surname'] = $user_info->last_name;
    echo json_encode($user, JSON_UNESCAPED_UNICODE);

    die;

}