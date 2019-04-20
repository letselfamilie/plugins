<?php
/* Template Name: Topics */
define("PATH", plugins_url('..' , __FILE__));
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topics</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/topics.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/loading.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/pagination.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>


    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">

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

<div class="container container-blured">

    <div class="info">
        <a class="back"><img class="back" src="<?php echo PATH?>/images/left.svg"></a>
        <h2 id="cat_name"></h2>
        <img id="add-topic" src="<?php echo PATH?>/images/plus.svg">
    </div>

    <table id="topics">
        <tr id="topics-header">
            <th id="head-name-table" class="topic-name">Topic</th>
            <th class="authors">
                <span class="menu_text">Authors</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/author.svg">
            </th>
            <th class="num-posts">
                <span class="menu_text">Posts</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/comment.svg">
            </th>
            <th class="last-post">Last post</th>
        </tr>

        <tbody id="topics_list">

        </tbody>
    </table>

    <div class="pagination">
        <a href="#">&laquo;</a>
        <a href="#">1</a>
        <a href="#" class="active">2</a>
        <a href="#">3</a>
        <a href="#">4</a>
        <a href="#">5</a>
        <a href="#">6</a>
        <a href="#">&raquo;</a>
    </div>
</div>

<div id="add-panel" style="display: none">
    <div class="add-panel-container">
        <div class="add-content">
            <h2>Add new topic</h2>
            <img id="close-add-panel" src="<?php echo PATH?>/images/x.svg">

            <form id="add-form" autocomplete="off">
                <input type="text" id='new-topic-name' name="topic-name" placeholder="Topic name"><br>
                <span class="label-anonym">anonymously</span>
                <input type="checkbox" id="chech-anonym" name="chech-anonym">
                <button class="enter-butt">Add</button>
            </form>


        </div>
    </div>
</div>

<!--<div id='loader' class="center lds-css ng-scope">-->
<!--    <div class="center lds-spin" style="width:100%;height:100%"><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div></div>-->
<!--</div>-->
<?php get_template_part( 'footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>