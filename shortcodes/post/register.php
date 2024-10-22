<?php
defined( 'ABSPATH' ) || exit;
$post_data = json_decode( file_get_contents('php://input'), true );
$res_data = [];
if (
    !isset($post_data['nonce']) || !is_string($post_data['nonce'])
    || !isset($post_data['hcaptcha-response']) || !is_string($post_data['hcaptcha-response'])
    || !isset($post_data['username']) || !is_string($post_data['username'])
    || !isset($post_data['email']) || !is_string($post_data['email'])
    || !isset($post_data['password']) || !is_string($post_data['password'])
    || !wp_verify_nonce( $post_data['nonce'], 'mpop-register' )
) {
    $res_data['error'] = [];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
$post_data['email'] = mb_strtolower(trim($post_data['email']), 'UTF-8');
if (
    !$this::is_valid_email( $post_data['email'], true )
) {
    $res_data['error'] = ['email'];
}
if ( !$this::is_valid_username( $post_data['username'] ) ) {
    if (!isset($res_data['error'])) {
        $res_data['error'] = [];
    }
    $res_data['error'][] = 'username';
}
if ( !$this::is_valid_password( $post_data['password'] ) ) {
    if (!isset($res_data['error'])) {
        $res_data['error'] = [];
    }
    $res_data['error'][] = 'password';
}
if ( !$this->verify_hcaptcha( $post_data['hcaptcha-response'] ) ) {
    if (!isset($res_data['error'])) {
        $res_data['error'] = [];
    }
    $res_data['error'][] = 'captcha';
}

if (isset($res_data['error'])) {
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (get_user_by( 'email', $post_data['email'] )) {
    $res_data['error'] = ['email', 'duplicated'];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
$duplicated = get_users([
    'meta_key' => '_new_email',
    'meta_value' => $post_data['email'],
    'meta_compare' => '='
]);
if (count($duplicated)) {
    $res_data['error'] = ['email', 'duplicated'];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (get_user_by( 'login', $post_data['username'] )) {
    $res_data['error'] = ['username', 'duplicated'];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}

$user_id = wp_insert_user([
    'user_login' => $post_data['username'],
    'user_nicename' => $post_data['username'],
    'user_email' => $post_data['email'],
    'user_pass' => $post_data['password'],
    'role' => 'multipopolano',
    'locale' => 'it_IT',
    'meta_input' => [
        'mpop_mail_to_confirm' => true
        // '_new_email' => false,
        // 'mpop_card_active' => false,
        // 'mpop_birthdate' => false,
        // 'mpop_birthplace' => false,
        // 'mpop_billing_address' => false,
        // 'mpop_billing_city' => false,
        // 'mpop_billing_state' => false,
        // 'mpop_billing_zip' => false,
        // 'mpop_phone' => false,
        // 'mpop_id_card_type' => false,
        // 'mpop_id_card_number' => false,
        // 'mpop_id_card_expiration' => false,
        // 'mpop_profile_pending_edits' => false,
        // 'mpop_marketing_agree' => false,
        // 'mpop_newsletter_agree' => false,
        // 'mpop_publish_agree' => false,
        // 'mpop_org_role' => false,
        // 'mpop_invited' => true
    ]
]);

if (is_int($user_id)) {
    $token = $this->create_temp_token( $user_id, 'email_confirmation_link' );
    $res = $this->send_confirmation_mail($token, $post_data['email']);
    if ($res === true) {
        $res_data['data'] = 'ok';
    } else {
        $this->delete_temp_token( $token );
        http_response_code( 500 );
        $res_data['error'] = ['server'];
    }
    echo json_encode( $res_data );
}