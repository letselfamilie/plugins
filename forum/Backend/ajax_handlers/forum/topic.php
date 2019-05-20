<?php
/**
 * Created by Polina Mahur.
 * User: User
 * Date: 3/25/2019
 * Time: 6:59 PM
 */

require_once (__DIR__.'/../../db_helper.php');
require_once ( __DIR__ . '/../censorship.php');

add_action('wp_ajax_'.'add_topic', 'add_topic');
add_action('wp_ajax_nopriv_'.'add_topic', 'add_topic');

add_action('wp_ajax_'.'get_forum_topics', 'get_forum_topics');
add_action('wp_ajax_nopriv_'.'get_forum_topics', 'get_forum_topics');

add_action('wp_ajax_'.'n_topic_pages', 'n_topic_pages');
add_action('wp_ajax_nopriv_'.'n_topic_pages', 'n_topic_pages');

add_action('wp_ajax_'.'get_topic_by_id', 'get_topic_by_id');
add_action('wp_ajax_nopriv_'.'get_topic_by_id', 'get_topic_by_id');

add_action('wp_ajax_'.'delete_topic', 'delete_topic');
add_action('wp_ajax_nopriv_'.'delete_topic', 'delete_topic');

add_action('wp_ajax_'.'change_fav', 'change_fav');
add_action('wp_ajax_nopriv_'.'change_fav', 'change_fav');

function add_topic(){
    global $wpdb;
    
    $topic_name = $_POST['topic_name'];
    $cat_name = $_POST['cat_name'];
    $user_id = $_POST['user_id'];
    $is_anonym = $_POST['is_anonym'];

    if (check_censor($topic_name)) {
        // TODO SEND WARNING
    }

    if($topic_name != null && $cat_name != null && $user_id != null && $is_anonym != null){
        

        $sqlQuery = "INSERT INTO {$wpdb->prefix}f_topics (topic_name, cat_name, user_id, is_anonym, create_timestamp) 
                     VALUES ('$topic_name', '$cat_name', $user_id, $is_anonym, CURRENT_TIMESTAMP)";

        try {
            $wpdb->query($sqlQuery);
            echo json_encode($wpdb->get_var("SELECT topic_id
                                             FROM {$wpdb->prefix}f_topics
                                             WHERE topic_name = '$topic_name' AND 
                                                   user_id = $user_id
                                             ORDER BY create_timestamp
                                             LIMIT 1;") * 1);
        }catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery, '600');
        }
        
        die;
    }
}

function get_forum_topics(){
    global $wpdb;
    $cat_name = $_POST['cat_name'];

    if($cat_name != NULL){
        

        $sqlQuery1 = "SELECT {$wpdb->prefix}f_topics.topic_id, 
                      topic_name, 
                      COUNT(DISTINCT {$wpdb->prefix}f_posts.user_id) AS authors_num,  
                      COUNT({$wpdb->prefix}f_posts.post_id) AS posts_num, 
                      {$wpdb->prefix}f_posts.create_timestamp
                     FROM {$wpdb->prefix}f_topics LEFT JOIN {$wpdb->prefix}f_posts 
                     ON {$wpdb->prefix}f_topics.topic_id = {$wpdb->prefix}f_posts.topic_id
                     WHERE cat_name = '$cat_name'
                     GROUP BY topic_name, {$wpdb->prefix}f_topics.topic_id
                     ORDER BY posts_num DESC, topic_name
                     LIMIT $_POST[per_page]
                     OFFSET ". ( $_POST['page_number'] - 1 ) * $_POST['per_page'] .";";

        $topics = array();
        try {

            foreach ($wpdb->get_results($sqlQuery1, ARRAY_A) as $topic) {
                $curr_topic = $topic['topic_id'];

                $sqlQuery2 = "SELECT {$wpdb->prefix}f_posts.post_id, 
                              {$wpdb->prefix}f_posts.create_timestamp, 
                              {$wpdb->prefix}f_posts.user_id,
                              {$wpdb->prefix}f_posts.is_anonym 
                      FROM {$wpdb->prefix}f_posts INNER JOIN {$wpdb->prefix}users ON {$wpdb->prefix}f_posts.user_id = {$wpdb->prefix}users.ID
                      WHERE topic_id = $curr_topic AND create_timestamp IN (SELECT MAX({$wpdb->prefix}f_posts.create_timestamp)
                                                                                FROM {$wpdb->prefix}f_posts
                                                                                WHERE topic_id =  $curr_topic)
                      ORDER BY {$wpdb->prefix}f_posts.create_timestamp DESC 
                      LIMIT 1";

                foreach ($wpdb->get_results($sqlQuery2, ARRAY_A) as $max_post) {
                    $user_info = new WP_User($max_post['user_id']);

                    $max_post['login'] = ($max_post['is_anonym'] == '1') ? 'Anonym' : $user_info->user_login;
                    $topic['last_post_id'] = $max_post['post_id'];
                    $topic['last_post_time'] = $max_post['create_timestamp'];
                    $topic['last_post_user_id'] = $max_post['user_id'];

                    $topic['topic_name'] = censor($topic['topic_name']);
                    $topic['last_post_user_login'] = $max_post['login'];
                }


                $topics[] = $topic;
            }
            echo json_encode($topics, JSON_UNESCAPED_UNICODE);

        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery1;
        }

        
        die;
    }
}

function n_topic_pages() {
    global $wpdb;
    echo json_encode(ceil($wpdb->get_var("SELECT COUNT(*) 
                                                FROM {$wpdb->prefix}f_topics
                                                WHERE cat_name = '$_POST[cat_name]';") / $_POST['per_page']));
    die;
}

function get_topic_by_id(){
    global $wpdb;
    $topic_id = $_POST['topic_id'];
    $user_id = $_POST['user_id'];

    if($topic_id != null){
        

        $sqlQuery = "SELECT topic_id, 
                            topic_name, 
                            cat_name, 
                            is_anonym,
                            IF(is_anonym, 0, user_id) AS user_id, 
                            create_timestamp,
                            (SELECT COUNT(*) FROM {$wpdb->prefix}f_favorites 
                            WHERE topic_id = $topic_id AND user_id = $user_id) AS fav
                     FROM {$wpdb->prefix}f_topics
                     WHERE topic_id = $topic_id;";

        $topics = array();
        try {
            foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $topic){
                $user_info = get_userdata(absint($topic['user_id']));


                $topic['user_name'] = ($topic['is_anonym']) ? 'Anonym' : $user_info->user_login;
                $topic['photo'] = get_avatar_url($topic['user_id']);

                $topic['topic_name'] = censor($topic['topic_name']);

                $topics[] = $topic;

            }
            echo json_encode($topics[0], JSON_UNESCAPED_UNICODE);

        }catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }
        
        die;
    }
}

function delete_topic() {
    global $wpdb;
    $topic_id = $_POST['topic_id'];
    $user_id = $_POST['user_id'];

    if($topic_id != null && $user_id != null){
        

        $sqlQuery = "DELETE FROM {$wpdb->prefix}f_topics 
                     WHERE topic_id=".$topic_id." AND user_id=".$user_id.";";
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery);
        }
        
        die;
    }
}

function change_fav() {
    global $wpdb;
    $topic_id = $_POST['topic_id'];
    $user_id = $_POST['user_id'];
    $state = $_POST['state'];


    if($topic_id != null && $user_id != null && $state != null){
        

        $sqlQuery = '';
        if ($state == 'add') {
            $sqlQuery = "INSERT INTO {$wpdb->prefix}f_favorites (user_id, topic_id) VALUES ($user_id, $topic_id)";
        } else if ($state == 'delete') {
            $sqlQuery = "DELETE FROM {$wpdb->prefix}f_favorites WHERE user_id = $user_id AND topic_id = $topic_id";
        }
        try {
            $wpdb->query($sqlQuery);
        }catch (Exception $e) {
            wp_send_json_error($e->getMessage() . '\n' . $sqlQuery);
        }
        
        die;
    }
}