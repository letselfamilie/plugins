<?php
/**
 * Created by Polina Mahur.
 * User: User
 * Date: 3/25/2019
 * Time: 7:00 PM
 */


add_action('wp_ajax_'.'add_post', 'add_post');
add_action('wp_ajax_nopriv_'.'add_post', 'add_post');

add_action('wp_ajax_'.'get_posts', 'get_forum_posts');
add_action('wp_ajax_nopriv_'.'get_posts', 'get_forum_posts');

add_action('wp_ajax_'.'get_last_post', 'get_last_post');
add_action('wp_ajax_nopriv_'.'get_last_post', 'get_last_post');

add_action('wp_ajax_'.'like', 'like');
add_action('wp_ajax_nopriv_'.'like', 'like');

add_action('wp_ajax_'.'dislike', 'dislike');
add_action('wp_ajax_nopriv_'.'dislike', 'dislike');

add_action('wp_ajax_'.'delete_post', 'delete_post');
add_action('wp_ajax_nopriv_'.'delete_post', 'delete_post');

add_action('wp_ajax_'.'update_post', 'update_post');
add_action('wp_ajax_nopriv_'.'update_post', 'update_post');

add_action('wp_ajax_'.'n_posts_pages', 'n_posts_pages');
add_action('wp_ajax_nopriv_'.'n_posts_pages', 'n_posts_pages');

//add new post
function add_post(){
    global $wpdb;
    $response_to = $_POST['response_to'];
    $topic_id = $_POST['topic_id'];
    $user_id = $_POST['user_id'];
    $post_message = $_POST['post_message'];
    $is_anonym = $_POST['is_anonym'];

    if($response_to != null && $topic_id != null && $user_id != null && $post_message != null && $is_anonym != null){
      

        $sqlQuery = "INSERT INTO {$wpdb->prefix}f_posts (response_to, topic_id, user_id, post_message, is_anonym, create_timestamp) 
                     VALUES ('".$response_to."', '".$topic_id."', '".$user_id."', '".$post_message."', ".$is_anonym.", CURRENT_TIMESTAMP);";
        $sqlQuery = str_replace("'NULL'", "NULL", $sqlQuery);


        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}

//all posts
function get_forum_posts(){
    global $wpdb;
  
    $topic_id = $_POST['topic_id'];
    $user_id = $_POST['user_id'];
    $sqlQuery = "SELECT  p.create_timestamp, 
                         p.post_message, 
                         p.post_id, 
                         p.response_to, 
                         p2.post_message AS respond_message,
                         p.user_id as user_id, 
                         p.is_anonym,
                         p2.user_id AS user_respond_to, 
                         (SELECT COUNT(*)
                          FROM {$wpdb->prefix}f_likes
                          WHERE post_id = p.post_id) AS n_likes, 
                         (SELECT COUNT(*)
                          FROM {$wpdb->prefix}f_posts
                          WHERE response_to = p.post_id) AS n_responds,
                         (SELECT TRUE 
                          FROM {$wpdb->prefix}f_likes
                          WHERE " . $user_id . " = {$wpdb->prefix}f_likes.user_id AND {$wpdb->prefix}f_likes.post_id = p.post_id
                          LIMIT 1) AS liked,
                         (SELECT TRUE 
                          FROM {$wpdb->prefix}f_posts
                          WHERE p.user_id = ". $user_id." AND response_to = p.post_id
                          LIMIT 1) AS responded
                 FROM ({$wpdb->prefix}f_posts p INNER JOIN {$wpdb->prefix}users u ON p.user_id = u.ID) 
                               LEFT OUTER JOIN {$wpdb->prefix}f_posts p2 ON p.response_to = p2.post_id
                 WHERE p.topic_id = $topic_id
                 ORDER BY p.create_timestamp
                 LIMIT $_POST[per_page]
                 OFFSET ". ( $_POST['page_number'] - 1 ) * $_POST['per_page'] . ";";

    $posts = array();

    try{
        foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $post) {
            $user_info = new WP_User( $post['user_id'] );
            $user_respond_to_info = new WP_User($post['user_respond_to'] );

            $post['surname'] = $user_info->last_name;
            $post['first_name'] = $user_info->first_name;
            $post['photo'] = get_avatar_url($post['user_id']);
            $post['user_respond_to'] =  $user_respond_to_info->last_name . " " . $user_respond_to_info->first_name;
            $posts[] = $post;

        }

        echo json_encode($posts, JSON_UNESCAPED_UNICODE);

    }catch (Exception $e) {
        echo 'Exception:', $e->getMessage(), "\n";
        echo $sqlQuery;
    }

    
    die;
}

//last post in category
function get_last_post(){
    global $wpdb;
    $cat_name = $_POST['cat_name'];

    if($cat_name != null){
      

        $sqlQuery1 = "CREATE OR REPLACE VIEW topics_posts AS 
                      SELECT topic_name, response_to, user_id, post_message, is_anonym, create_timestamp
                      FROM {$wpdb->prefix}f_posts INNER JOIN {$wpdb->prefix}f_topics ON {$wpdb->prefix}f_posts.topic_id = {$wpdb->prefix}f_topics.topic_id
                      WHERE cat_name = '".$cat_name."'";

        $sqlQuery2 = "SELECT *
                      FROM topics_posts
                      WHERE create_timestamp IN (SELECT MAX(create_timestamp)
                                                 FROM topics_posts);";

        $posts = array();

        try{
            $wpdb->query($sqlQuery1);

            foreach ($wpdb->get_results($sqlQuery2, ARRAY_A) as $post) {
                $posts[] = $post;
            }
            echo json_encode($posts, JSON_UNESCAPED_UNICODE);

        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery2;
        }
    }

    
    die;
}

function like() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if($post_id != null && $user_id != null){
      

        $sqlQuery = "INSERT INTO {$wpdb->prefix}f_likes (post_id, user_id) 
                     VALUES (".$post_id.", ".$user_id.");";
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}

function dislike() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if($post_id != null && $user_id != null){
      

        $sqlQuery = "DELETE FROM {$wpdb->prefix}f_likes 
                     WHERE post_id=".$post_id." AND user_id=".$user_id.";";
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}

function delete_post() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if($post_id != null && $user_id != null){
      

        $sqlQuery = "DELETE FROM {$wpdb->prefix}f_posts 
                     WHERE post_id=".$post_id." AND user_id=".$user_id.";";
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}

function update_post() {
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];
    $post_message = $_POST['post_message'];

    if($post_id != null && $user_id != null && $post_message != null){



        $sqlQuery = "UPDATE {$wpdb->prefix}f_posts 
                     SET post_message='". $post_message . "'
                     WHERE post_id=".$post_id." AND user_id=".$user_id.";";
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}


function n_posts_pages() {
    global $wpdb;
    echo json_encode(ceil($wpdb->get_var("SELECT COUNT(*) 
                                                FROM {$wpdb->prefix}f_posts
                                                WHERE topic_id = $_POST[topic_id];") / $_POST['per_page']));
    die;
}