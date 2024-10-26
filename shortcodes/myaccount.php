<?php
defined( 'ABSPATH' ) || exit;
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    // NOT LOGGED IN
    if (isset($_REQUEST['mpop_forgot_password'])) {
        require('forgot-password.php');
    } else if (isset($_REQUEST['mpop_invite_token'])) {
        require('invitation-confirm.php');
    } else {
        require('login.php');
    }
} else {
    // LOGGED IN
    if (isset($current_user->roles[0]) && $current_user->roles[0] != 'administrator') {
        $redirect_url = '/';
        if (isset($current_user->roles[0]) && $current_user->roles[0] == 'multipopolare_friend' ||  $current_user->mpop_card_active) {
            $discourse_connect_options = get_option('discourse_connect');
            if (is_array($discourse_connect_options) && isset($discourse_connect_options['url']) && $discourse_connect_options['url']) {
                $redirect_url = $discourse_connect_options['url'] . '/login';
            }
        }
        header('Location: ' . $redirect_url);
    }
    require('logged-myaccount.php');
}