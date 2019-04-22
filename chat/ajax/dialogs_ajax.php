<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/21/2019
 * Time: 12:59 PM
 */


add_action('wp_ajax_' . 'get_dialogs', 'get_dialogs');
//add_action('wp_ajax_nopriv_' . 'get_dialogs', 'get_dialogs');

/**
 *
 * to call this ajax function in js file send such query
 *
 * $.ajax({
    url: url_object.ajax_url,
    type: 'POST',
    data: {
        action: 'get_dialogs'
    },
    success: function (res) {
    },
    error: function (error) {
    }
   });
 */

function get_dialogs(){
    global $wpdb;

    $user_id = get_current_user_id();

    $sqlQuery = "SELECT dialog_id, is_employee_chat, dialog_topic, user1_id, COALESCE (user2_id, employee_id) AS user2_id
                 FROM {$wpdb->prefix}c_dialogs 
                 WHERE user1_id = ".$user_id." OR 
                    IF (user2_id IS NOT NULL, user2_id = ".$user_id." , employee_id = ".$user_id.");";

    $dialogs = array();
    $dialogs['curr_user'] = $user_id;
        try {
            foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $dialog){
                $second_id = $dialog['user1_id'] == $user_id ? $dialog['user2_id'] : $dialog['user1_id'];
                $dialog['second_user_nickname'] = get_user_meta($second_id, 'nickname', true);

                if(um_profile('profile_photo')){
                    $dialog['second_user_photo'] = um_get_avatar_uri( um_profile('profile_photo'), null);
                } else{
                    $dialog['second_user_photo'] = um_get_default_avatar_uri();
                }

                $sqlQuery2 = "SELECT *
                              FROM {$wpdb->prefix}c_messages
                              WHERE dialog_id = '".$dialog['dialog_id']."'
                              ORDER BY create_timestamp;";

                $dialog['messages'] = array();
                foreach ($wpdb->get_results($sqlQuery2, ARRAY_A) as $message){
                    $dialog['messages'][] = $message;
                }
                $dialogs[] = $dialog;
            }
            echo json_encode($dialogs, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery, '600');
        }
        die;
}
