<?php

add_action('wp_ajax_' . 'add_censor', 'add_censor');
add_action('wp_ajax_nopriv_' . 'add_censor', 'add_censor');

add_action('wp_ajax_' . 'filter_censor', 'filter_censor');
add_action('wp_ajax_nopriv_' . 'filter_censor', 'filter_censor');

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

function censor($text) {
    global $wpdb;
    $data = explode(" ", $text);
    foreach ($data as $w) {
        $sqlQuery = "SELECT 1 FROM {$wpdb->prefix}censorship WHERE word = '$w'";
        if ($wpdb->get_var($sqlQuery) == 1) {
            $text =  str_replace(' ' . $w . ' ', ' ' . str_repeat("*", strlen($w)) . ' ', $text);            }
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
