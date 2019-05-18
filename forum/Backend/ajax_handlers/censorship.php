<?php

add_action('wp_ajax_' . 'add_censor', 'add_censor');
add_action('wp_ajax_nopriv_' . 'add_censor', 'add_censor');


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