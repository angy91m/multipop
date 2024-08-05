<?php
defined( 'ABSPATH' ) || exit;
$post_data = json_decode( file_get_contents('php://input'), true );
$res_data = [];

if (
    !isset($post_data['nonce']) || !is_string($post_data['nonce'])
    || !isset($post_data['password']) || !is_string($post_data['password'])
    || !wp_verify_nonce( $post_data['nonce'], 'mpop-password-change' )
) {
    $res_data['error'] = [];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
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
wp_set_password($post_data['password'], $current_user_id);

// if (is_int($user_id)) {
//     $token = $this->create_temp_token( $user_id, 'email_confirmation_link' );
//     $mail = [
//         'subject' => 'Conferma email',
//         'body' => 'Clicca sul link per confermare la tua email: <a href="'. $confirmation_link . '?mpop_mail_token=' . $token . '" target="_blank">'. $confirmation_link . '?mpop_mail_token=' . $token . '</a>',
//         'to' => $post_data['email']
//     ];
//     $res = $this->send_mail($mail);
//     if ($res === true) {
//         $res_data['data'] = 'ok';
//     } else {
//         http_response_code( 500 );
//         $res_data['error'] = ['server'];
//     }
//     echo json_encode( $res_data );
// }