<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 23.05.2019
 * Time: 22:22
 */

function new_post_mail($mail, $login, $text, $topic, $url) {
    $to = $mail;
    $subject = "$login responded to your topic";
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $body = "<b>Topic</b>: <a href='$url'>$topic</a><br/>
             <b>$login</b>: $text";

    return wp_mail( $to, $subject, $body, $headers);
}
?>