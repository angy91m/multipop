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
    require('logged-myaccount.php');
}