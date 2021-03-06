<?php
/* Template Name: Topics */
define("PATH", plugins_url('..' , __FILE__));
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onderwerpen</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/topics.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/loading.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/pagination.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>


    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.2/css/font-awesome.min.css'>

    <?php wp_head(); ?>
</head>
<body>

<?php get_template_part( 'header' ); ?>

<style>
    input[type='checkbox'] {
        top: inherit;
        margin: 0;
        bottom: 16px !important;
    }

    input[type=checkbox]:before {
        background-color: #0c76cf !important;
    }

    ::selection {
        background: #0c76cf !important;
    }

    #add-panel {
        z-index: 100;
    }

    h1, h2, h3, h4, h5, h6 {
        text-transform: none !important;
    }

</style>

<div class="container container-blured forum-area" style="max-width: 750px;">

    <div class="info">
        <a class="back"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
        <label id="cat_name"></label>
        <?php if(is_user_logged_in()) { ?>
        <img id="add-topic" src="<?php echo PATH?>/images/plus.svg">
        <?php } ?>
    </div>

    <div class="topic-search input-group">
        <input id="search-post-input" class="form-control" type="text" placeholder="Zoek onderwerp...">
        <div class="input-group-append">
            <button class="btn" id="search-topic-btn" type="button">
                <i class="fa fa-search fa-fw" style="color: #fff;" aria-hidden="true"></i>
            </button>
        </div>
    </div>


    <table id="topics">
        <tr id="topics-header">
            <th id="head-name-table" class="topic-name">Onderwerp</th>
            <th class="authors">
                <span class="menu_text">Auteurs</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/author.svg">
            </th>
            <th class="num-posts">
                <span class="menu_text">Berichten</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/comment.svg">
            </th>
            <th class="last-post">Laatste bericht</th>
        </tr>

        <tbody id="topics_list">

        </tbody>
    </table>

    <div class="pagination">
        <a href="#" class='back-end-arrow'><i class="fa fa-angle-double-left"></i></a>
        <a href="#" class='back-arrow'><i class="fa fa-angle-left"></i></a>
        <a href="#" class='before-dots'>…</a>
        <!-- pages -->
        <a href="#" class='after-dots'>…</a>
        <a href="#" class='forward-arrow'><i class="fa fa-angle-right"></i></i></a>
        <a href="#" class='forward-end-arrow'><i class="fa fa-angle-double-right"></i></a>
    </div>
</div>

<div id="add-panel" style="display: none">
    <div class="add-panel-container">
        <div class="add-content">
            <label>Nieuw onderwerp toevoegen</label>
            <img id="close-add-panel" src="<?php echo PATH?>/images/x.svg">

            <form id="add-form" autocomplete="off">
                <input type="text" id='new-topic-name' name="topic-name" placeholder="Onderwerp"><br>
                <span class="label-anonym">Plaats anoniem</span>
                <input type="checkbox" id="chech-anonym" name="chech-anonym">
                <button class="enter-butt">Toevoegen</button>
            </form>


        </div>
    </div>
</div>

<!--<div id='loader' class="center lds-css ng-scope">-->
<!--    <div class="center lds-spin" style="width:100%;height:100%"><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div></div>-->
<!--</div>-->
<?php get_template_part( 'footer' ); ?>

<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
<?php wp_footer(); ?>
</body>
</html>