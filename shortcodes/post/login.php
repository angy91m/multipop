<?php
defined( 'ABSPATH' ) || exit;
if (
    !isset($_POST['mpop-login-nonce']) || !is_string($_POST['mpop-login-nonce'])
    || !isset($_POST['user']) || !is_string($_POST['user'])
    || !isset($_POST['password']) || !is_string($_POST['password'])
    || !wp_verify_nonce( $_POST['mpop-login-nonce'], 'mpop-login' )
) {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('location:'.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
}
$_POST['user'] = mb_strtolower(trim($_POST['user']), 'UTF-8');
if (
    !$this::is_valid_email( $_POST['user'] )
    && !$this::is_valid_username( $_POST['user'] )
) {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('location:'.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
}
$res = wp_signon([
    'user_login' => $_POST['user'],
    'user_password' => $_POST['password']
], isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']));
if (get_class($res) == 'WP_Error') {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('location:'.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
} else {
    header('location:'.  explode('?',$this->req_path)[0] . (isset($_GET['redirect_to']) ? '?redirect_to=' . urlencode($_GET['redirect_to']) : ''));
    exit();
}