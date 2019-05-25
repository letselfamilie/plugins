<?php
/* Template Name: Posts */
define("PATH", plugins_url('..' , __FILE__));
$role =  ((array)( wp_get_current_user()->roles )[0])[0];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/posts.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/loading.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/pagination.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>

    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">


</head>

<body>

<?php get_template_part( 'header' ); ?>
<?php wp_head(); ?>

<div class="container container-blured blur forum-area" style="max-width: 750px;">
    <style>
        h1, h2, h3, h4, h5, h6 {
            text-transform: none !important;
        }

        .dropdown {
            position: absolute;
        }


        input[type=checkbox]:before, input[type=radio]:before {
            background-color: #0c76cf;
        }

        ::selection {
            background: #0c76cf !important;
        }

        .content-enter {
            position: relative;
        }



    </style>

    <div class="info">
        <a class="back"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
        <label id="topic_name"></label>
<!--        <label id="topic_name-f"></label>-->
        <span id="topic_date"></span>
        <span id="added-by"></span>

        <?php if (is_user_logged_in()) { ?>
            <?php
            global $wpdb;
            if ($role == 'administrator' || $role == 'adviser' ||
                wp_get_current_user()->ID == $wpdb->get_var('SELECT user_id) 
                                                             FROM {$wpbd->prefix}_f_posts   
                                                             WHERE topic_id = $_GET[topic_id];')) { ?>
        <div id="topic-dropdown" class="dropdown">
            <img src="<?php echo PATH?>/images/more.svg">

            <div class="dropdown-content">
                <p class="delete">Delete</p>
            </div>
        </div>
            <?php } ?>
        <img class="star-empty" src="<?php echo PATH?>/images/star-empty.svg">
        <img class="star-full" src="<?php echo PATH?>/images/star-full.svg">
        <?php }?>
    </div>

    <table id="posts">
        <tr id="post-header">
            <th>Author</th>
            <th>Post</th>
        </tr>


        <?php
        $current_user = wp_get_current_user();
        if(is_user_logged_in()) { ?>


        <tfoot>
        <tr class="post-enter">
            <td class="user-info">

                <img id="my-photo" src="<?php echo get_avatar_url(get_current_user_id())?>">

                <a id="my-name">
                    <?php
                    if ($current_user->user_firstname == '') {
                        echo $current_user->user_login;
                    } else {
                        echo $current_user->user_login;
                    }
                    ?></a>
            </td>

            <td class="post-text-enter">
                <div class="content-enter">
                    <div class="respond-info" style="display:none">
                        <span class="respond-text">
                            <span class="respond-to-title">Respond to </span>
                            <span class="quote-text"></span>
                            <img class="del-quote" src="<?php echo PATH?>/images/x.svg"/>
                        </span>
                    </div>
                    <div class="text-enter-container">
                        <textarea id="enter-textarea" placeholder="Enter your message here..."></textarea>
                        <div class="right-align">
                            <span class="label-anonym">Post anonymously</span>
                            <input type="checkbox" id="chech-anonym" name="chech-anonym">
                            <button class="enter-butt">Enter</button>
                        </div>
                    </div>

                </div>
            </td>
        </tr>



<!--        FLOATING COPY -->
        <tr id="floating-enter" style="visibility: hidden">
            <td class="user-info">

                <img id="my-photo" src="<?php echo get_avatar_url(get_current_user_id())?>">

                <a id="my-name">
                    <?php
                    if ($current_user->user_firstname == '') {
                        echo $current_user->user_login;
                    } else {
                        echo $current_user->user_login;
                    }
                    ?></a>
            </td>

            <td class="post-text-enter">
                <div class="content-enter">
                    <div class="respond-info" style="display:none">
                        <span class="respond-text">
                            <span class="respond-to-title">Respond to </span>
                            <span class="quote-text"></span>
                            <img class="del-quote" src="<?php echo PATH?>/images/x.svg"/>
                        </span>
                    </div>
                    <div class="text-enter-container">
                        <textarea id="enter-textarea-f" placeholder="Enter your message here..."></textarea>
                        <div class="right-align">
                            <span class="label-anonym">Post anonymously</span>
                            <input type="checkbox" id="chech-anonym-f" name="chech-anonym">
                            <button class="enter-butt-f">Enter</button>
                        </div>
                    </div>

                </div>
            </td>
        </tr>



        </tfoot>
        <?php } ?>

    </table>


    <?php if(!is_user_logged_in()) {
        echo '<span class="please-login">To add new post please log in.</span>';
    } ?>

    <div class="pagination">
        <a class='back-end-arrow'><i class="fa fa-angle-double-left"></i></a>
        <a class='back-arrow'><i class="fa fa-angle-left"></i></a>
        <a class='before-dots'>…</a>
        <!-- pages -->
        <a class='after-dots'>…</a>
        <a class='forward-arrow'><i class="fa fa-angle-right"></i></i></a>
        <a class='forward-end-arrow'><i class="fa fa-angle-double-right"></i></a>
    </div>

</div>

<div id='loader' class="center lds-css ng-scope">
    <div class="center lds-spin" style="width:100%;height:100%"><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div></div>
</div>

<!--style="display: none"-->
<div id="delete-topic-panel" style="display: none">
    <div class="delete-panel-container">
        <div class="delete-content">
            <h2>Delete</h2>
            <img class="close-delete-panel" src="<?php echo PATH?>/images/x.svg">
            <p>Are you sure you want to delete this topic?</p>
            <button class="delete-butt">Delete</button>
            <button class="cancel-butt">Cancel</button>
        </div>
    </div>
</div>

<div id="delete-post-panel" style="display: none">
    <div class="delete-panel-container">
        <div class="delete-content">
            <h2>Delete</h2>
            <img class="close-delete-panel" src="<?php echo PATH?>/images/x.svg">
            <p>Are you sure you want to delete this post?</p>
            <button class="delete-butt">Delete</button>
            <button class="cancel-butt">Cancel</button>
        </div>
    </div>
</div>

<button id="top-butt" title="Go to top">
    <i class="fa fa-angle-up" aria-hidden="true" style="color: #fff; width: 20px; height: 20px; margin: auto;"></i>
</button>
<button id="down-butt" title="Go to bottom">
    <i class="fa fa-angle-down" aria-hidden="true" style="color: #fff; width: 20px; height: 20px; margin: auto;"></i>
</button>

<?php get_template_part( 'footer' ); ?>

<script src="<?php echo PATH?>/js/autoresize.jquery.js"></script>
<?php wp_footer(); ?>
</body>
