<?php
    wp_enqueue_script('categories-add-script', plugins_url('/js/add-category.js', __FILE__), array('jquery'));
    wp_localize_script('categories-add-script', 'url_object',
        array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));


?>


<div class="wrap">
    <h1 id="add-new-category">
        Add new category</h1>


    <div id="ajax-response"></div>

    <p>Create new forum category and add to this site</p>
    <form method="post" name="createcategory" id="createcategory" class="validate" novalidate="novalidate">
        <input name="action" type="hidden" value="createcategory">
        <input type="hidden" id="_wpnonce_create-category" name="_wpnonce_create-category" value="845ed1e85f"><input
                type="hidden" name="_wp_http_referer" value="/LetselFamilie/wp-admin/category-new.php">
        <table class="form-table">
            <tbody>
            <tr class="form-field form-required">
                <th scope="row"><label for="category_name">Category name<span
                                class="description"> (required)</span></label></th>
                <td><input name="category_name" type="text" id="category_name" value="" aria-required="true"
                           autocapitalize="none" autocorrect="off" maxlength="60"></td>
            </tr>


            </tbody>
        </table>


        <p class="submit"><input type="submit" name="createcategory" id="create_category"
                                 class="button button-primary" value="Add new category"></p>
    </form>
</div>