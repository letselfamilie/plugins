<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 4/21/2019
 * Time: 12:59 PM
 */


add_action('wp_ajax_' . 'get_dialogs', 'get_dialogs');
add_action('wp_ajax_' . 'get_general_dialogs', 'get_general_dialogs');
add_action('wp_ajax_' . 'sound_prop', 'sound_prop');
add_action('wp_ajax_' . 'push_notif_prop', 'push_notif_prop');
add_action('wp_ajax_' . 'get_n_unread', 'get_n_unread');
//add_action('wp_ajax_nopriv_' . 'get_dialogs', 'get_dialogs');

/**
 *
 * to call this ajax function in js file send such query
 *
 * $.ajax({
 * url: url_object.ajax_url,
 * type: 'POST',
 * data: {
 * action: 'get_dialogs'
 * },
 * success: function (res) {
 * },
 * error: function (error) {
 * }
 * });
 */

function sound_prop(){
    $user_id = get_current_user_id();
    $chat_sound = get_user_meta( $user_id, "chat_sound", true);

    $chat_sound_prop = empty ($chat_sound) ? 0 : $chat_sound[0];
    echo json_encode($chat_sound_prop, JSON_UNESCAPED_UNICODE);
    die;
}

function push_notif_prop(){
    $user_id = get_current_user_id();
    $push_notif = get_user_meta( $user_id, "push_notifications", true);

    $push_notif_prop = empty ($push_notif) ? 0 : $push_notif[0];
    echo json_encode($push_notif_prop, JSON_UNESCAPED_UNICODE);
    die;
}

function get_general_dialogs(){
    global $wpdb;

  //  $user_id = get_current_user_id();

    $sqlQuery = "SELECT dialog_id, is_employee_chat, dialog_topic, user1_id, 1 AS without_employee, is_closed,
                        (SELECT COUNT(*)
                         FROM {$wpdb->prefix}c_messages
                         WHERE dialog_id = D.dialog_id
                              AND is_read = 0) AS unread_msg,
                        (SELECT MAX(create_timestamp)
                         FROM {$wpdb->prefix}c_messages
                         WHERE dialog_id = D.dialog_id) AS last_message_timestamp                                                                  
                 FROM {$wpdb->prefix}c_dialogs D
                 WHERE employee_id IS NULL AND is_employee_chat = '1'
                 ORDER BY last_message_timestamp;";

    $dialogs = array();
 //   $dialogs['curr_user'] = $user_id;
    try {
        foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $dialog) {
            $second_id = $dialog['user1_id']; //== $user_id ? $dialog['user2_id'] : $dialog['user1_id'];

            $dialog['second_user_nickname'] = get_user_meta($second_id, 'nickname', true);
            $dialog['second_user_photo'] = get_avatar_url($second_id, null);

            $sqlQuery2 = "SELECT *
                              FROM {$wpdb->prefix}c_messages
                              WHERE dialog_id = '" . $dialog['dialog_id'] . "';";

            $dialog['messages'] = array();
            foreach (array_reverse($wpdb->get_results($sqlQuery2, ARRAY_A)) as $message) {
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

function get_dialogs()
{
    global $wpdb;

    $user_id = get_current_user_id();

    $sqlQuery = "SELECT dialog_id, is_employee_chat, dialog_topic, user1_id, 0 AS without_employee, is_closed,
                        COALESCE (user2_id, employee_id) AS user2_id, 
                        (SELECT COUNT(*)
                         FROM {$wpdb->prefix}c_messages
                         WHERE dialog_id = D.dialog_id
                              AND is_read = 0) AS unread_msg,
                        (SELECT MAX(create_timestamp)
                         FROM {$wpdb->prefix}c_messages
                         WHERE dialog_id = D.dialog_id) AS last_message_timestamp                                                                  
                 FROM {$wpdb->prefix}c_dialogs D
                 WHERE user1_id = " . $user_id . " OR 
                    IF (user2_id IS NOT NULL, user2_id = " . $user_id . " , employee_id = " . $user_id . ")
                 ORDER BY last_message_timestamp;";

    $dialogs = array();
    $dialogs['curr_user'] = $user_id;
    try {
        foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $dialog) {
            $second_id = $dialog['user1_id'] == $user_id ? $dialog['user2_id'] : $dialog['user1_id'];
            $dialog['second_user_nickname'] = get_user_meta($second_id, 'nickname', true);

            // if(um_profile('profile_photo')) {
            $dialog['second_user_photo'] = get_avatar_url($second_id, null);
            // } else{
            //     $dialog['second_user_photo'] = um_get_default_avatar_uri();
            // }


            $from_message = (isset($_POST['from'])) ? $_POST['from'] : 0;
            $to_message = (isset($_POST['to'])) ? $_POST['to'] : 19;

            $sqlQuery2 = "SELECT *
                              FROM {$wpdb->prefix}c_messages
                              WHERE dialog_id = '" . $dialog['dialog_id'] . "'
                              ORDER BY create_timestamp DESC
                              LIMIT " . ($to_message - $from_message + 1) . " 
                              OFFSET $from_message;";

            $dialog['messages'] = array();
            foreach (array_reverse($wpdb->get_results($sqlQuery2, ARRAY_A)) as $message) {
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

add_action('wp_ajax_' . 'get_messages', 'get_messages');
add_action('wp_ajax_nopriv_' . 'get_messages', 'get_messages');

function get_messages()
{
    global $wpdb;

    $from_message = $_POST['from'];
    $to_message = $_POST['to'];
    $dialog_id = $_POST['dialog_id'];


    if (isset($dialog_id) && isset($to_message) && isset($from_message)) {

        $sqlQuery = "SELECT *
                      FROM {$wpdb->prefix}c_messages
                      WHERE dialog_id = '" . $dialog_id . "'
                      ORDER BY create_timestamp DESC
                      LIMIT " . ($to_message - $from_message + 1) . " 
                      OFFSET $from_message;";
        try {
            $messages = array();
            foreach (array_reverse($wpdb->get_results($sqlQuery, ARRAY_A)) as $message) {
                $messages[] = $message;
            }
            echo json_encode($messages, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery, '600');
        }
    }
    die;
}


add_action('wp_ajax_' . 'add_dialog', 'add_dialog');
add_action('wp_ajax_nopriv_' . 'add_dialog', 'add_dialog');

function add_dialog()
{
    global $wpdb;
    $user_id = get_current_user_id();

    $user_id_to = $_POST['user_to'];
    $employee_id = $_POST['employee_id'];
    $dialog_topic = $_POST['dialog_topic'];

    try {
        if ($user_id_to != null && $user_id != null) {

            $sqlQuery = "SELECT dialog_id
                         FROM {$wpdb->prefix}c_dialogs
                         WHERE (user1_id = $user_id_to AND user2_id = $user_id) 
                            OR (user1_id = $user_id AND user2_id = $user_id_to)
                         LIMIT 1;";
            if ($wpdb->get_var($sqlQuery) == null) {
                $wpdb->query("INSERT INTO {$wpdb->prefix}c_dialogs 
                              (user1_id, user2_id, employee_id, is_employee_chat, dialog_topic) 
                              VALUES ($user_id, $user_id_to, null, 0, null)");
            }
            echo json_encode($wpdb->get_var($sqlQuery) * 1, JSON_UNESCAPED_UNICODE);
        }
        // to creat chat with employee
//    else if ($user_id != null && $employee_id != null && $dialog_topic != null) {
//        $wpdb->query("INSERT INTO {$wpdb->prefix}c_dialogs
//                      (user1_id, user2_id, employee_id, is_employee_chat, dialog_topic)
//                      VALUES ($user_id, null, $employee_id, 1, $dialog_topic)");
//    }
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), '600');
    }
    die;
}



function get_n_unread() {
    global $wpdb;
    $user_id = get_current_user_id();

    try {
        if ($user_id != null) {
            $sqlQuery = "SELECT COUNT(*)
                         FROM {$wpdb->prefix}c_dialogs
                         WHERE (user1_id = $user_id 
                            OR user2_id = $user_id);";
            echo json_encode($wpdb->get_var($sqlQuery), JSON_UNESCAPED_UNICODE);
        }
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage(), '600');
    }
    die;
}