<?php
    /* Template Name: Categories */
define("PATH", plugins_url('..' , __FILE__));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories</title>

    <link rel="stylesheet/less" type="text/css" href="<?php echo PATH?>/less/categories.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.9.0/less.min.js" ></script>


    <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
    <?php wp_head(); ?>
</head>


<body>

<?php get_template_part( 'header' ); ?>

<div class="container">

    <table id="categories">
        <tr id="categories-header">
            <th class="cat-name">Forum</th>
            <th class="onderwerpen">
                <span class="menu_text">Topics</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/chat.svg">
            </th>
            <th class="berichten">
                <span class="menu_text">Posts</span>
                <img class="menu_icon" src="<?php echo PATH?>/images/comment.svg">
            </th>
            <th class="last-post">Last post</th>
        </tr>

        <tbody id="categories_list">

        </tbody>
    </table>

</div>

<?php get_template_part( 'footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>