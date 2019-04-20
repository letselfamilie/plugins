<?php
/* Template Name: Posts */
define("PATH", plugins_url('..' , __FILE__));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/posts.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/loading.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>

    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">


</head>

<body>

<?php get_template_part( 'header' ); ?>
<?php wp_head(); ?>

<div class="container container-blured blur">
    <style>
        h1, h2, h3, h4, h5, h6 {
            text-transform: none !important;
        }

        .dropdown {
            position: absolute;
        }

        input[type='checkbox'] {
            top: inherit;
            margin: 0;
            bottom: 18px !important;
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

        tfoot tr {
            position: absolute;
        }

    </style>

    <div class="info">
        <a class="back"><img class="back" src="<?php echo PATH?>/images/left.svg"></a>
        <h2 id="topic_name"></h2>
        <span id="topic_date"></span>
        <span id="added-by"></span>

        <?php if (is_user_logged_in()) { ?>
        <div id="topic-dropdown" class="dropdown">
            <img src="<?php echo PATH?>/images/more.svg">
            <div class="dropdown-content">
                <p class="delete">Delete</p>
            </div>
        </div>

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

                <a id="my-name" href="<?php PATH?>/">
                    <?php
                    if ($current_user->user_firstname == '') {
                        echo $current_user->user_login;
                    } else {
                        echo $current_user->user_firstname . " " . $current_user->user_lastname;
                    }
                    ?></a>
            </td>

            <td class="post-text-enter">
                <div class="content-enter">
                    <div class="respond-info" style="display:none">
                        <span class="respond-text">
                            <span class="respond-to-title">Respond to </span>
                            <span id="quote-text"></span>
                            <img id="del-quote" src="<?php echo PATH?>/images/x.svg"/>
                        </span>
                    </div>
                    <div class="text-enter-container">
                        <textarea id="enter-textarea" placeholder="Enter your message here..."></textarea>
                        <span class="label-anonym">anonymously</span>
                        <input type="checkbox" id="chech-anonym" name="chech-anonym">
                        <button class="enter-butt">Enter
                        </button>
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

<?php get_template_part( 'footer' ); ?>

<script src="<?php echo PATH?>/js/autoresize.jquery.js"></script>
<?php wp_footer(); ?>
</body>
