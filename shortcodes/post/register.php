<?php
defined( 'ABSPATH' ) || exit;
$post_data = json_decode( file_get_contents('php://input'), true );
$res_data = [];
if (
    !isset($post_data['nonce']) || !is_string($post_data['nonce'])
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
    !$this::is_valid_email( $post_data['email'] )
    || in_array(explode('@', $post_data['email'])[1], preg_split('/\r\n|\r|\n/', file_get_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt')))
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
    'role' => 'customer',
    'locale' => 'it_IT',
    'meta_input' => [
        'mpop_mail_to_confirm' => true,
        'mpop_mail_changing' => false,
        'mpop_card_active' => false,
        'mpop_user_doc_key' => false
    ]
]);

if (is_int($user_id)) {
    $token = $this->create_temp_token( $user_id, 'email_confirmation_link' );
    $res = $this->send_confirmation_mail($token, $post_data['email']);
    if ($res === true) {
        $res_data['data'] = 'ok';
    } else {
        http_response_code( 500 );
        $res_data['error'] = ['server'];
    }
    echo json_encode( $res_data );
}