<?php

add_action('wp_ajax_' . 'add_censor', 'add_censor');
add_action('wp_ajax_nopriv_' . 'add_censor', 'add_censor');

add_action('wp_ajax_' . 'filter_censor', 'filter_censor');
add_action('wp_ajax_nopriv_' . 'filter_censor', 'filter_censor');

add_action('wp_ajax_' . 'filter_censor', 'get_censor');
add_action('wp_ajax_nopriv_' . 'filter_censor', 'get_censor');

function add_censor() {
    global $wpdb;
    $word = $_POST['word'];

    if ($word != null) {
        $sqlQuery = "INSERT INTO {$wpdb->prefix}censorship (word) VALUES ('" . $word . "')";

        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery, '600');
        }
        die;
    }
}

function is_in_censor($w) {
    global $wpdb;
    $sqlQuery = "SELECT 1 FROM {$wpdb->prefix}censorship WHERE word = '$w'";
    return $wpdb->get_var($sqlQuery) == 1;
}

function censor($text) {

    $data = explode(" ", $text);
    foreach ($data as $w) {
         if (is_in_censor($w)) {
            $text = str_replace($w, str_repeat("*", strlen($w)), $text);
         }
    }
    return $text;
}

function filter_censor() {
    $text = $_POST['text'];
    if ($text != null) {
        echo censor($text);
        die;
    }
}

function check_censor($text) {
    $data = explode(" ", $text);
    $contain = false;

    foreach ($data as $w) {
        $contain = $contain || is_in_censor($w);
    }

    return $contain;
}

function get_censor() {
    global $wpdb;
    echo json_encode($wpdb->get_var("SELECT COUNT(*) 
                                     FROM {$wpdb->prefix}f_categories;"));
    die;

}