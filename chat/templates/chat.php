<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 15.04.2019
 * Time: 23:00
 */

/* Template Name: Chat Page */
define("PATH", plugins_url('..' , __FILE__));
$role =  ((array)( wp_get_current_user()->roles )[0])[0];

$consultant = false;
if ($role == 'adviser') $consultant = true;

if(!is_user_logged_in()) {
    wp_redirect( get_site_url(). '/login', 302);
}
?>

<!DOCTYPE html>
<html class=''>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/chat.less" />

    <link href='https://fonts.googleapis.com/css?family=Roboto:400,600,700,300' rel='stylesheet' type='text/css'>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">

    <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css'>
    <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.2/css/font-awesome.min.css'>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>

    <?php wp_head(); ?>

    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>

    <style>
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed,
        figure, figcaption, footer, header, hgroup,
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            font-size: 100%;
            font: inherit;
            vertical-align: baseline;
        }
        /* HTML5 display-role reset for older browsers */
        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }
        body {
            line-height: 1;
        }
        ol, ul {
            list-style: none;
        }
        blockquote, q {
            quotes: none;
        }
        blockquote:before, blockquote:after,
        q:before, q:after {
            content: '';
            content: none;
        }
        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        .fa {
            font-family: FontAwesome !important;
        }

        <?php if (!$consultant) { ?>
        #chat-frame #sidepanel #conversations {
            height: calc(100% - 168px);
            overflow-y: scroll;
            overflow-x: hidden;
        }
        <?php } else { ?>
        #chat-frame #sidepanel #conversations {
            height: 100%;
            overflow-y: scroll;
            overflow-x: hidden;
        }
        <?php } ?>
    </style>

</head>
<body>

<?php get_template_part( 'header' ); ?>


<div id='loader' class="center lds-css ng-scope">
    <div class="center lds-spin" style="width:100%;height:100%"><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div></div>
</div>

<div id="chat-frame">
    <div id="sidepanel">
        <div id="profile">
            <div class="wrap">
                <img id="profile-img" src="<?php echo PATH?>/images/user.png" alt="profile img"/>
                <p></p>
            </div>
        </div>

        <div id="search">
            <label for="inputSearch"><i class="fa fa-search" aria-hidden="true"></i></label>
            <input id="inputSearch" type="text" placeholder="Zoeken..."/>
        </div>
        <div id="conversations">
            <ul>
                <!--<li id="2" class="conversation active">
                    <div class="wrap">
                        <img src="<?php /*echo PATH*/?>/images/question.png" alt=""/>
                        <div class="meta">
                            <p class="name">I have a question about car accidents</p>
                            <p class="preview"><span>You:</span>What if...?</p>
                        </div>
                    </div>
                </li>-->
            </ul>
        </div>
        <?php if (!$consultant) { ?>
        <div id="bottom-bar">
            <button id="btn-newmessage">
                <i class="fa fa-comments fa-fw" aria-hidden="true"></i>
                <span>Nieuw bericht</span>
            </button>
        </div>
        <?php } ?>
    </div>
    <div class="content-chat">
        <?php if (!$consultant) { ?>
        <div class="new-convo">
            <div class="new-convo-header">
                <p>Nieuwe vraag</p>
            </div>
            <form id="form-question">
                <div class="form-group">
                    <label>Onderwerp</label>
                    <input id="inputTopic" type="text" class="form-control" name="topic" placeholder="Ik heb een vraag over...">
                </div>
                <div class="form-group">
                    <label>Bericht</label>
                    <textarea id="inputFirstMessage" class="form-control" name="text" placeholder="Tell us about your problem here" rows="3"></textarea>
                </div>
                <button id="addNewDialog">Ask</button>
            </form>
        </div>
        <?php } ?>

        <div class="contact-profile hidden">
            <img src="<?php echo PATH?>/images/question.png" alt="question"/>
            <p id="chat-title"></p>

            <div id="chat_options" class="btn-group dropleft float-right">
                <button id="convOptions" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-bars"></i>
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" id="resolve-btn">Markeer als opgelost</a>
                    <?php if ($consultant) { ?>
                        <a id="redirect_choose_consultant" class="dropdown-item" href="#redirectCollapse" data-toggle="collapse"
                           role="button" aria-controls="redirectCollapse" aria-expanded="false">
                            Redirect to another consultant
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php if ($consultant) { ?>
        <div class="redirect collapse multi-collapse" id="redirectCollapse">
            <form id="redirect_line" class="input-group">
                <select class="form-control custom-select" id="consultantSelect">
                    <!--<option value="1">Consultant 1</option>
                    <option value="2">Consultant 2</option>
                    <option value="3">Consultant 3</option>
                    <option value="4">Consultant 4</option>
                    <option value="5">Consultant 5</option>-->
                </select>
                <div id="redirect_btn" class="input-group-append">
                    <button type="submit">Redirect</button>
                </div>
            </form>
        </div>
        <?php } ?>

        <div id="date-bubble" class="hidden"> DATE </div>

        <div id="messages-container" class="messages">
            <ul>
            </ul>
        </div>
        <div class="message-input hidden">
            <div class="wrap">
                <input id="message-input" type="text" placeholder="Typ hier uw bericht…"/>
                <button id="send-message-butt" class="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
</div>

<?php get_template_part( 'footer' ); ?>

<!--<script src='https://code.jquery.com/jquery-2.2.4.min.js'></script>-->
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<!--<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>-->
<?php wp_footer(); ?>

</body>
</html>
