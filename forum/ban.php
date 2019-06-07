<?php
/**
 * Created by PhpStorm.
 * User: San Nguyen
 * Date: 20.05.2019
 * Time: 23:33
 */
function sn_admin_init(){

    // Edit user profile
    add_action( 'edit_user_profile', 'sn_edit_user_profile' );
    add_action( 'edit_user_profile_update', 'sn_edit_user_profile_update' );

}

add_action('admin_init', 'sn_admin_init' );


function sn_edit_user_profile() {
    if ( !current_user_can( 'edit_users' ) ) {
        return;
    }

    global $user_id;

    // User cannot disable itself
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    if ( $current_user_id == $user_id ) {
        return;
    }
    ?>
    <h3></h3>
    <table class="form-table">
        <tr>
            <th scope="row">Blokkeren</th>
            <td><label for="sn_ban"><input name="sn_ban" type="checkbox" id="sn_ban"
                        <?php if (sn_is_user_banned( $user_id )) echo 'checked'; ?>
                    /></label></td>
        </tr>
    </table>
    <?php
}

function sn_edit_user_profile_update() {
    echo '+';
    if ( !current_user_can( 'edit_users' ) ) {
        return;
    }

    global $user_id;

    // User cannot disable itself
    $current_user    = wp_get_current_user();
    $current_user_id = $current_user->ID;
    if ( $current_user_id == $user_id ) {
        return;
    }

    // Lock
    if( isset( $_POST['sn_ban'] ) && $_POST['sn_ban'] = 'on' ) {
        sn_ban_user( $user_id );
    } else { // Unlock
        sn_unban_user( $user_id );
    }

}

function sn_ban_user( $user_id ) {

    $old_status = sn_is_user_banned( $user_id );

    // Update status
    if ( !$old_status ) {
        update_user_option( $user_id, 'sn_banned', true, false );
    }
}

function sn_unban_user( $user_id ) {

    $old_status = sn_is_user_banned( $user_id );

    // Update status
    if ( $old_status ) {
        update_user_option( $user_id, 'sn_banned', false, false );
    }
}

function sn_is_user_banned( $user_id ) {
    return get_user_option( 'sn_banned', $user_id, false );
}

function sn_authenticate_user( $user ) {

    if ( is_wp_error( $user ) ) {
        return $user;
    }

    // Return error if user account is banned
    $banned = get_user_option( 'sn_banned', $user->ID, false );
    if ( $banned ) {
        return new WP_Error( 'sn_banned', __('<strong>ERROR</strong>: This user account is disabled.', 'sn') );
    }

    return $user;
}

add_filter( 'wp_authenticate_user', 'sn_authenticate_user', 1 );

add_action('wp_head', 'sn_ban_check');
function sn_ban_check(){
    if (sn_is_user_banned(get_current_user_id())) {
        wp_logout();
    }


}