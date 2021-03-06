<?php
    /* Template Name: Categories */
define("PATH", plugins_url('..' , __FILE__));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorieën</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/categories.less" />
    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/pagination.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>


    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <?php wp_head(); ?>
</head>


<body>

<?php get_template_part( 'header' ); ?>

<div class="container forum-area" style="margin-top: 12px; max-width: 750px;">

    <table id="categories">
        <tr id="categories-header">
            <th class="cat-name" style="cursor: auto">Forum</th>
            <th class="onderwerpen">
                <span class="menu_text">Onderwerpen</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/chat.svg">
            </th>
            <th class="berichten">
                <span class="menu_text">Berichten</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/comment.svg">
            </th>
            <th class="last-post">Laatste bericht</th>
        </tr>

        <tbody id="categories_list">

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

<?php get_template_part( 'footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>