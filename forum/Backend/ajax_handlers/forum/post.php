<?php
/**
 * Created by Polina Mahur.
 * User: User
 * Date: 3/25/2019
 * Time: 7:00 PM
 */

require_once(__DIR__ . '/../censorship.php');
require_once(__DIR__ . '/../mailer.php');

use Ratchet\Client;
use Ratchet\ConnectionInterface;

require dirname(__FILE__) . '/../../../../chat/vendor/autoload.php';

add_action('wp_ajax_' . 'add_post', 'add_post');
add_action('wp_ajax_nopriv_' . 'add_post', 'add_post');

add_action('wp_ajax_' . 'get_posts', 'get_forum_posts');
add_action('wp_ajax_nopriv_' . 'get_posts', 'get_forum_posts');

add_action('wp_ajax_' . 'get_last_post', 'get_last_post');
add_action('wp_ajax_nopriv_' . 'get_last_post', 'get_last_post');

add_action('wp_ajax_' . 'like', 'like');
add_action('wp_ajax_nopriv_' . 'like', 'like');

add_action('wp_ajax_' . 'dislike', 'dislike');
add_action('wp_ajax_nopriv_' . 'dislike', 'dislike');

add_action('wp_ajax_' . 'delete_post', 'delete_post');
add_action('wp_ajax_nopriv_' . 'delete_post', 'delete_post');

add_action('wp_ajax_' . 'update_post', 'update_post');
add_action('wp_ajax_nopriv_' . 'update_post', 'update_post');

add_action('wp_ajax_' . 'n_posts_pages', 'n_posts_pages');
add_action('wp_ajax_nopriv_' . 'n_posts_pages', 'n_posts_pages');

add_action('wp_ajax_' . 'add_post_report', 'add_post_report');
add_action('wp_ajax_nopriv_' . 'add_post_report', 'add_post_report');



function add_report_to_db($post_id){
    global $wpdb;

    $sqlQuery = "INSERT INTO {$wpdb->prefix}reports  (post_id, message_id, create_timestamp) 
                  VALUES ({$post_id}, NULL, CURRENT_TIMESTAMP);";
    try {
        $wpdb->query($sqlQuery);
        echo ' - ' . $sqlQuery;
    } catch (Exception $e) {
        echo 'Exception:', $e->getMessage(), "\n";
        echo $sqlQuery;
    }
}

function add_post_report()
{
    $post_id = $_POST['post_id'];
    add_report_to_db($post_id);
    die;
}


//add new post
function add_post()
{
    \Ratchet\Client\connect('ws://178.128.202.94:8000')->then(function ($conn) {
        global $wpdb;
        $response_to = $_POST['response_to'];
        $topic_id = $_POST['topic_id'];
        $user_id = $_POST['user_id'];
        $post_message = $_POST['post_message'];
        $is_anonym = $_POST['is_anonym'];
        $is_reaction = $_POST['is_reaction'];

        if ($response_to != null && $topic_id != null && $user_id != null && $post_message != null && $is_anonym != null) {
            if ($is_reaction == null) $is_reaction = 0;

            $sqlQuery = "INSERT INTO {$wpdb->prefix}f_posts (response_to, topic_id, user_id, post_message, is_anonym, create_timestamp, is_reaction) 
                     VALUES ('$response_to', '$topic_id', '$user_id', '$post_message', $is_anonym, CURRENT_TIMESTAMP, $is_reaction);";
            $sqlQuery = str_replace("'NULL'", "NULL", $sqlQuery);
            $sqlQuery = str_replace("'null'", "NULL", $sqlQuery);

            try {
                $wpdb->query($sqlQuery);

                echo 'added)';
                header("Content-Length: ".ob_get_length());
                header("Connection: close");
                flush();

                $user_info = get_userdata($user_id);
                $user_topic_owner = get_userdata($wpdb->get_var("SELECT user_id
                                                             FROM {$wpdb->prefix}f_topics
                                                             WHERE topic_id = $topic_id;"));

                $response_to = $wpdb->get_row("SELECT user_id, post_message
                                             FROM {$wpdb->prefix}f_posts
                                             WHERE post_id = $response_to;");

                $user_response = get_userdata($response_to->user_id);

                global $ultimatemember;

                new_post_mail($user_topic_owner->user_email,
                    $is_anonym ? 'Anonym' : $user_info->user_login,
                    censor($post_message),
                    censor($wpdb->get_var("SELECT topic_name
                                                   FROM {$wpdb->prefix}f_topics
                                                   WHERE topic_id = $topic_id;")),
                    get_site_url() . "/posts/?topic_id=$topic_id",
                    $is_anonym ? um_get_default_avatar_uri() : get_avatar_url($user_id),
                    $user_response->user_login,
                    $response_to->post_message);

                if (check_censor($post_message)) {
                    echo "Message " . $post_message;

                    $id = $wpdb->get_var("SELECT post_id FROM {$wpdb->prefix}f_posts
                                WHERE user_id = $user_id AND topic_id = $topic_id
                                ORDER BY create_timestamp DESC
                                LIMIT 1");

                    add_report_to_db($id);

                    $messageToSocket = array();
                    $messageToSocket['command'] = 'notification';
                    $messageToSocket['type'] = 'bad_word';
                    $messageToSocket['user_id_from'] = $user_id;
                    $messageToSocket['user_login'] = $user_info->user_login;
                    $messageToSocket['message_text'] = $post_message;

//                    $conn->on('message', function ($msg) use ($conn) {
//                        echo "Received: {$msg}\n";
//                        $conn->close();
//                    });

                    $conn->send(json_encode($messageToSocket));
                    $conn->close();
                } else {
                    $conn->close();
                }
            } catch (Exception $e) {
                echo 'Exception:', $e->getMessage(), "\n";
                echo $sqlQuery;
            }
        }
    }, function ($e) {
        echo "Could not connect: {$e->getMessage()}\n";
    });
    die;
}

//all posts
function get_forum_posts()
{
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
                         p2.is_anonym AS responder_anonym,
                         p2.user_id AS user_respond_to, 
                         p.is_reaction, 
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
                          WHERE p.user_id = " . $user_id . " AND response_to = p.post_id
                          LIMIT 1) AS responded
                 FROM ({$wpdb->prefix}f_posts p INNER JOIN {$wpdb->prefix}users u ON p.user_id = u.ID) 
                               LEFT OUTER JOIN {$wpdb->prefix}f_posts p2 ON p.response_to = p2.post_id
                 WHERE p.topic_id = $topic_id
                 ORDER BY p.create_timestamp
                 LIMIT $_POST[per_page]
                 OFFSET " . ($_POST['page_number'] - 1) * $_POST['per_page'] . ";";

    $posts = array();

    try {
        foreach ($wpdb->get_results($sqlQuery, ARRAY_A) as $post) {
            $user_info = new WP_User($post['user_id']);
            $user_respond_to_info = new WP_User($post['user_respond_to']);

            $post['login'] = ($post['is_anonym'] == '1') ? 'Anonym' : $user_info->user_login;
            $post['photo'] = get_avatar_url($post['user_id']);
            $post['user_respond_to'] = ($post['responder_anonym'] == '1') ? 'Anonym' : $user_respond_to_info->user_login;
            $post['post_message'] = censor($post['post_message']);
            $post['respond_message'] = censor($post['respond_message']);
            $posts[] = $post;

        }

        echo json_encode($posts, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo 'Exception:', $e->getMessage(), "\n";
        echo $sqlQuery;
    }


    die;
}

//last post in category
function get_last_post()
{
    global $wpdb;
    $cat_name = $_POST['cat_name'];

    if ($cat_name != null) {


        $sqlQuery1 = "CREATE OR REPLACE VIEW topics_posts AS 
                      SELECT topic_name, response_to, user_id, post_message, is_anonym, create_timestamp
                      FROM {$wpdb->prefix}f_posts INNER JOIN {$wpdb->prefix}f_topics ON {$wpdb->prefix}f_posts.topic_id = {$wpdb->prefix}f_topics.topic_id
                      WHERE cat_name = '" . $cat_name . "'";

        $sqlQuery2 = "SELECT *
                      FROM topics_posts
                      WHERE create_timestamp IN (SELECT MAX(create_timestamp)
                                                 FROM topics_posts);";

        $posts = array();

        try {
            $wpdb->query($sqlQuery1);

            foreach ($wpdb->get_results($sqlQuery2, ARRAY_A) as $post) {
                $posts[] = $post;
            }
            echo json_encode($posts, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery2;
        }
    }


    die;
}

function like()
{
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if ($post_id != null && $user_id != null) {


        $sqlQuery = "INSERT INTO {$wpdb->prefix}f_likes (post_id, user_id) 
                     VALUES (" . $post_id . ", " . $user_id . ");";
        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }

        die;
    }
}

function dislike()
{
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if ($post_id != null && $user_id != null) {


        $sqlQuery = "DELETE FROM {$wpdb->prefix}f_likes 
                     WHERE post_id=" . $post_id . " AND user_id=" . $user_id . ";";
        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }

        die;
    }
}

function delete_post()
{
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];

    if ($post_id != null && $user_id != null) {


        $sqlQuery = "DELETE FROM {$wpdb->prefix}f_posts 
                     WHERE post_id=$post_id;";
        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }

        die;
    }
}

function update_post()
{
    global $wpdb;
    $post_id = $_POST['post_id'];
    $user_id = $_POST['user_id'];
    $post_message = $_POST['post_message'];

    if ($post_id != null && $user_id != null && $post_message != null) {

//
//        $time = $wpdb->get_var("SELECT create_timestamp FROM {$wpdb->prefix}f_posts WHERE  post_id=$post_id;");
//
        $sqlQuery = "UPDATE {$wpdb->prefix}f_posts 
                     SET post_message='$post_message'
                     WHERE post_id=$post_id;";
        try {
            $wpdb->query($sqlQuery);
        } catch (Exception $e) {
            echo 'Exception:', $e->getMessage(), "\n";
            echo $sqlQuery;
        }

        die;
    }
}


function n_posts_pages()
{
    global $wpdb;
    echo json_encode(ceil($wpdb->get_var("SELECT COUNT(*) 
                                                FROM {$wpdb->prefix}f_posts
                                                WHERE topic_id = $_POST[topic_id];") / $_POST['per_page']));
    die;
}
