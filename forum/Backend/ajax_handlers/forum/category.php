<?php
/**
 * Created by Polina Mahur.
 * User: User
 * Date: 3/25/2019
 * Time: 6:58 PM
 */



add_action('wp_ajax_' . 'add_category', 'add_category');
add_action('wp_ajax_nopriv_' . 'add_category', 'add_category');

add_action('wp_ajax_' . 'get_forum_categories', 'get_forum_categories');
add_action('wp_ajax_nopriv_' . 'get_forum_categories', 'get_forum_categories');

add_action('wp_ajax_' . 'n_pages', 'n_pages');
add_action('wp_ajax_nopriv_' . 'n_pages', 'n_pages');

function add_category()
{
    global $wpdb;
    $cat_name = $_POST['cat_name'];

    if ($cat_name != null) {
        $sqlQuery = "INSERT INTO {$wpdb->prefix}f_categories (cat_name) VALUES ('" . $cat_name . "')";

        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery, '600');
        }
        die;
    }
}

function get_forum_categories()
{
    global $wpdb;
    $cats = array();

    $sqlQuery1 = "SELECT {$wpdb->prefix}f_categories.cat_name, 
                  COUNT(DISTINCT {$wpdb->prefix}f_topics.topic_id) AS topics_num,
                  COUNT(post_id) AS posts_num
                  FROM ({$wpdb->prefix}f_categories LEFT OUTER JOIN {$wpdb->prefix}f_topics 
                  ON {$wpdb->prefix}f_categories.cat_name = {$wpdb->prefix}f_topics.cat_name) 
                  LEFT OUTER JOIN {$wpdb->prefix}f_posts 
                  ON {$wpdb->prefix}f_topics.topic_id = {$wpdb->prefix}f_posts.topic_id
                  GROUP BY {$wpdb->prefix}f_categories.cat_name
                  LIMIT $_POST[per_page]
                  OFFSET ". ( $_POST['page_number'] - 1 ) * $_POST['per_page'] . ";";

    $sqlQuery2 = "";
    $sqlQuery3 = "";

    try {
        foreach ($wpdb->get_results($sqlQuery1, ARRAY_A) as $cat) {
            $sqlQuery2 = "SELECT {$wpdb->prefix}f_topics.topic_id, topic_name
                          FROM {$wpdb->prefix}f_topics
                          WHERE cat_name = '".$cat['cat_name']."';";

            foreach ($wpdb->get_results($sqlQuery2, ARRAY_A) as $topic){
                $sqlQuery3 = "SELECT {$wpdb->prefix}f_topics.topic_name, 
                                      {$wpdb->prefix}f_posts.topic_id, 
                                      {$wpdb->prefix}f_posts.post_id, 
                                      {$wpdb->prefix}f_posts.create_timestamp AS post_time, 
                                      {$wpdb->prefix}f_posts.user_id
                              FROM {$wpdb->prefix}f_posts INNER JOIN {$wpdb->prefix}f_topics 
                                ON {$wpdb->prefix}f_topics.topic_id = {$wpdb->prefix}f_posts.topic_id
                              WHERE {$wpdb->prefix}f_posts.topic_id = '".$topic['topic_id']."'
                                AND {$wpdb->prefix}f_posts.create_timestamp IN (SELECT MAX({$wpdb->prefix}f_posts.create_timestamp)
                                                         FROM {$wpdb->prefix}f_posts
                                                         WHERE topic_id =  ".$topic['topic_id'].")";



                foreach ($wpdb->get_results($sqlQuery3, ARRAY_A) as $post){
                    $cat['last_topic_id'] = $post['topic_id'];
                    $cat['last_topic_name'] = $post['topic_name'];
                    $cat['last_post_id'] = $post['post_id'];
                    $cat['last_post_time'] = $post['post_time'];
                    $cat['last_user_id'] = $post['user_id'];
               //     $cat['last_user_login'] = $post['login'];
                }
            }
            $cats[] = $cat;
        }
        echo json_encode($cats, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo 'Exception:', $e->getMessage(), "\n";
        echo $sqlQuery1. "\n" . $sqlQuery2 . "\n". $sqlQuery3;
    }
    die;
}

function n_pages() {
    global $wpdb;
    echo json_encode(ceil($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}f_categories;") / $_POST['per_page']));
    die;
}