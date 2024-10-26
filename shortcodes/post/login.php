<?php
defined( 'ABSPATH' ) || exit;
if (
    !isset($_POST['mpop-login-nonce']) || !is_string($_POST['mpop-login-nonce'])
    || !isset($_POST['user']) || !is_string($_POST['user'])
    || !isset($_POST['password']) || !is_string($_POST['password'])
    || !wp_verify_nonce( $_POST['mpop-login-nonce'], 'mpop-login' )
    || (
        isset($this->settings['hcaptcha_site_key'])
        && trim($this->settings['hcaptcha_site_key'])
        && (
            !isset($_POST['hcaptcha-response'])
            || !is_string($_POST['hcaptcha-response'])
            || !$this->verify_hcaptcha( $_POST['hcaptcha-response'] )
        )
    )
) {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('Location: '.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
}
$_POST['user'] = mb_strtolower(trim($_POST['user']), 'UTF-8');
if (
    !$this::is_valid_email( $_POST['user'], false, true )
    && !$this::is_valid_username( $_POST['user'], true )
) {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('Location: '.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
}
$res = wp_signon([
    'user_login' => $_POST['user'],
    'user_password' => $_POST['password']
], isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']));
if (get_class($res) == 'WP_Error') {
    $_GET['invalid_mpop_login'] = '1';
    header("Status: 401 Unauthorized");
    header('Location: '.  explode('?',$this->req_path)[0] . '?' . $this->export_GET());
    exit();
} else {
    if (isset($_GET['redirect_to'])) {
        header('Location: '. $_GET['redirect_to']);
    } else {
        header('Location: '. explode('?',$this->req_path)[0]);
    }
    exit();
}