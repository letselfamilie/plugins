<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 23.05.2019
 * Time: 22:22
 */

function new_post_mail($mail, $login, $text, $topic, $url, $photo) {
    $to = $mail;
    $subject = "$login responded to your topic";
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $body = "<b>Topic</b>: <a href='$url'>$topic</a><br/>
             <b>$login</b>: $text";

    $body = " <div style='display:none;'>$topic | $text </div>
        
    <table id='posts' style='-webkit-tap-highlight-color:rgba(0, 0, 0, 0); border:1px solid rgba(0, 0, 0, 0.05); border-collapse:collapse; font-family:'Roboto Condensed', sans-serif; margin:0; padding:0; text-transform:none; width:100%' width='100%'>
    <tbody style='border-collapse:collapse'>
        <tr id='post-header' style='background-color:rgba(0, 0, 0, 0.05); color:rgba(0, 0, 0, 0.42); font-size:16px' bgcolor='rgba(0, 0, 0, 0.05)'>
            <th style='max-width:170px;border-bottom:1px solid rgba(0, 0, 0, 0.05); font-weight:200; height:30px; padding:0; text-align:center' height='30' align='center'>Author</th>
            <th style='border-bottom:1px solid rgba(0, 0, 0, 0.05); font-weight:200; height:30px; padding:0; text-align:center' height='30' align='center'>Post</th>
        </tr>
        <tr class='post-row' style='border-bottom:1px solid rgba(0, 0, 0, 0.05); font-size:16px'>
            <td class='user-info'>
                <img src='$photo' style='border-radius:50%; display:block; margin:auto; margin-top:12px; max-width:50px; width:80%' width='80%'>
                <div class='name' style='align-content:center; font-size:12px; text-align:center; width:100%' align='center' width='100%'>$login</div>
            </td>
            <td class='post-text' style='min-height:132px; padding:15px 11px 30px 6px; position:relative'>
                
                <div class='respond-message'>
                    <span class='respond-to-user'>admin: </span>
                    <div class='text-post-message'>quote</div>
                </div>
                
                <p class='text-post'>
                </p>
                <div class='message'>$text</div>
                <p></p>
            </td>
        </tr>
    </tbody>
</table>";

    return wp_mail( $to, $subject, $body, $headers);
}
?>