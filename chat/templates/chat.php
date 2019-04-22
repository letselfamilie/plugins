<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 15.04.2019
 * Time: 23:00
 */

/* Template Name: Chat Page */
define("PATH", plugins_url('..' , __FILE__));

$consultant = true;
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

        .messages::-webkit-scrollbar, #conversations::-webkit-scrollbar
        {
            width: 12px !important;  /* for vertical scrollbars */
            height: 12px !important; /* for horizontal scrollbars */
        }

        .messages::-webkit-scrollbar-track, #conversations::-webkit-scrollbar-track
        {
            background: rgba(15, 97, 185, 0.1) !important;
        }

        .messages::-webkit-scrollbar-thumb
        {
            background: #0f61b9 !important;
        }

        #conversations::-webkit-scrollbar-thumb
        {
            background: #0c76cf !important;
        }
    </style>

</head>
<body>

<?php //get_template_part( 'header' ); ?>


<div id="chat-frame">
    <div id="sidepanel">
        <div id="profile">
            <div class="wrap">
                <img id="profile-img" src="<?php echo PATH?>/images/user.png" alt="profile img"/>
                <p>Mary Cooper</p>
            </div>
        </div>
        <div id="search">
            <label for="inputSearch"><i class="fa fa-search" aria-hidden="true"></i></label>
            <input id="inputSearch" type="text" placeholder="Search..."/>
        </div>
        <div id="conversations">
            <ul>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/question.png" alt=""/>
                        <div class="meta">
                            <p class="name">I have a question about car accidents</p>
                            <p class="preview"><span>You:</span>What if...?</p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="http://emilcarlsson.se/assets/harveyspecter.png" alt=""/>
                        <div class="meta">
                            <p class="name">John Smith</p>
                            <p class="preview">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
                                tempor incididunt ut labore et dolore magna aliqua.</p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="http://emilcarlsson.se/assets/rachelzane.png" alt=""/>
                        <div class="meta">
                            <p class="name">Jane</p>
                            <p class="preview">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi
                                ut aliquip ex ea commodo consequat. </p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/user.png" alt=""/>
                        <div class="meta">
                            <p class="name">Karen Brown</p>
                            <p class="preview">Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
                                dolore eu fugiat nulla pariatur.</p>
                        </div>
                    </div>
                </li>
                <li class="conversation active">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/question.png" alt=""/>
                        <div class="meta">
                            <p class="name">Vaccination</p>
                            <p class="preview">Sed nisi lacus sed viverra tellus in. Non odio euismod lacinia at quis
                                risus sed vulputate odio. </p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="http://emilcarlsson.se/assets/haroldgunderson.png" alt=""/>
                        <div class="meta">
                            <p class="name">Jason</p>
                            <p class="preview"><span>You:</span>Thank you.</p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/user.png" alt=""/>
                        <div class="meta">
                            <p class="name preview">User</p>
                            <p class="preview">Hello.</p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/user.png" alt=""/>
                        <div class="meta">
                            <p class="name">Kate</p>
                            <p class="preview">Turpis egestas sed tempus urna et pharetra pharetra massa massa. </p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/question.png" alt=""/>
                        <div class="meta">
                            <p class="name">Another question</p>
                            <p class="preview"><span>You:</span>Nec ultrices dui sapien eget. </p>
                        </div>
                    </div>
                </li>
                <li class="conversation">
                    <div class="wrap">
                        <img src="<?php echo PATH?>/images/question.png" alt=""/>
                        <div class="meta">
                            <p class="name">A question</p>
                            <p class="preview"><span>You:</span>Nam aliquam sem et tortor consequat id porta. Sagittis
                                vitae et leo duis ut diam quam nulla. </p>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <?php if (!$consultant) { ?>
        <div id="bottom-bar">
            <button id="btn-newmessage">
                <i class="fa fa-comments fa-fw" aria-hidden="true"></i>
                <span>New message</span>
            </button>
        </div>
        <?php } ?>
    </div>
    <div class="content-chat">
        <?php if (!$consultant) { ?>
        <div class="new-convo">
            <div class="new-convo-header">
                <p>New question</p>
            </div>
            <form>
                <div class="form-group">
                    <label>Question summary</label>
                    <input type="text" class="form-control" name="topic" placeholder="My question is regarding...">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea class="form-control" name="text" placeholder="Tell us about your problem here" rows="3"></textarea>
                </div>
                <button type="submit">Ask</button>
            </form>
        </div>
        <?php } ?>

        <div class="contact-profile">
            <img src="<?php echo PATH?>/images/question.png" alt="question"/>
            <p id="chat-title">Vaccination</p>

            <div class="btn-group dropleft float-right">
                <button id="convOptions" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-bars"></i>
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" id="resolve-btn">Mark as resolved</a>
                    <?php if ($consultant) { ?>
                    <a class="dropdown-item" href="#redirectCollapse" data-toggle="collapse"
                       role="button" aria-controls="redirectCollapse" aria-expanded="false">
                        Redirect to another consultant
                    </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php if ($consultant) { ?>
        <div class="redirect collapse multi-collapse" id="redirectCollapse">
            <form class="input-group">
                <select class="form-control custom-select" id="consultantSelect">
                    <option value="1">Consultant 1</option>
                    <option value="2">Consultant 2</option>
                    <option value="3">Consultant 3</option>
                    <option value="4">Consultant 4</option>
                    <option value="5">Consultant 5</option>
                </select>
                <div class="input-group-append">
                    <button type="submit">Redirect</button>
                </div>
            </form>
        </div>
        <?php } ?>
        <div class="messages">
            <ul>
                <li class="sent">
                    <img src="<?php echo PATH?>/images/user.png" alt=""/>
                    <p>
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
                        labore et dolore magna aliqua. In nisl nisi scelerisque eu ultrices.
                        <br/>
                        <small class="float-right mt-2">14:02:59</small>
                    </p>
                </li>
                <li class="replies">
                    <img src="<?php echo PATH?>/images/logo.png" alt=""/>
                    <p>
                        Cursus vitae congue mauris rhoncus aenean vel elit scelerisque.
                        <br/>
                        <small class="float-right mt-2">14:08:30</small>
                    </p>
                </li>
                <li class="replies">
                    <img src="<?php echo PATH?>/images/logo.png" alt=""/>
                    <p>
                        Pellentesque diam volutpat commodo sed egestas egestas fringilla.
                        <br/>
                        <small class="float-right mt-2">14:11:32</small>
                    </p>
                </li>
                <li class="sent">
                    <img src="<?php echo PATH?>/images/user.png" alt=""/>
                    <p>
                        Porta nibh venenatis cras sed felis eget velit aliquet sagittis. Sodales ut etiam sit amet nisl
                        purus in mollis.
                        <br/>
                        <small class="float-right mt-2">14:13:08</small>
                    </p>
                </li>
                <li class="replies">
                    <img src="<?php echo PATH?>/images/logo.png" alt=""/>
                    <p>
                        Eleifend quam adipiscing vitae proin sagittis nisl rhoncus mattis rhoncus.
                        <br/>
                        <small class="float-right mt-2">14:14:54</small>
                    </p>
                </li>
                <li class="replies">
                    <img src="<?php echo PATH?>/images/logo.png" alt=""/>
                    <p>
                        Eget sit amet tellus cras adipiscing enim eu.
                        <br/>
                        <small class="float-right mt-2">14:16:21</small>
                    </p>
                </li>
                <li class="sent">
                    <img src="<?php echo PATH?>/images/user.png" alt=""/>
                    <p>
                        Id aliquet lectus proin nibh.
                        <br/>
                        <small class="float-right mt-2">14:18:23</small>
                    </p>
                </li>
                <li class="mes-break">
                    <p>New messages<i class="fa fa-chevron-down ml-1"></i></p>
                </li>
                <li class="replies">
                    <img src="<?php echo PATH?>/images/logo.png" alt=""/>
                    <p>
                        Sed nisi lacus sed viverra tellus in. Non odio euismod lacinia at quis risus sed vulputate odio.
                        <br/>
                        <small class="float-right mt-2">14:21:39</small>
                    </p>
                </li>
            </ul>
        </div>
        <div class="message-input">
            <div class="wrap">
                <input id="message-input" type="text" placeholder="Write your message..."/>
                <button id="send-message-butt" class="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
</div>

<?php //get_template_part( 'footer' ); ?>

<script src='https://code.jquery.com/jquery-2.2.4.min.js'></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<?php wp_footer(); ?>

</body>
</html>
