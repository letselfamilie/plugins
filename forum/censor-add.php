<?php
wp_enqueue_script('censor-add-script', plugins_url('/js/add-censor.js', __FILE__), array('jquery'));
wp_localize_script('censor-add-script', 'url_object',
    array('ajax_url' => admin_url('admin-ajax.php'), 'site_url' => get_site_url()));


?>


<div class="wrap">
    <h1 id="add-new-censor">
        Censuur toevoegen
    </h1>


    <div id="ajax-response"></div>

    <p>Voeg nieuw woord toe om deze te censureren in de forum en chat.
    </p>
    <form method="post" name="createcensor" id="createcensor" class="validate" novalidate="novalidate">
        <input name="action" type="hidden" value="createcensor">

        <input type="hidden" id="_wpnonce_create-censor" name="_wpnonce_create-censor" value="845ed1e85f"><input
            type="hidden" name="_wp_http_referer" value="/LetselFamilie/wp-admin/censor-new.php">

        <table class="form-table">
            <tbody>
            <tr class="form-field form-required">
                <th scope="row"><label for="censor_name">Nieuw woord censureren<span
                            class="description"> (required)</span></label></th>
                <td><input name="censor_name" type="text" id="censor_name" value="" aria-required="true"
                           autocapitalize="none" autocorrect="off" maxlength="60"></td>
            </tr>


            </tbody>
        </table>


        <p class="submit"><input type="submit" name="createcensor" id="create_censor"
                                 class="button button-primary" value="Add new censor"></p>
    </form>
</div>