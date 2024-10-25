<?php
defined( 'ABSPATH' ) || exit;
$post_data = json_decode( file_get_contents('php://input'), true );
$res_data = [];
if (!isset($post_data['action']) || !is_string($post_data['action']) || !trim($post_data['action'])) {
    $res_data['error'] = ['action'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (!isset($post_data['mpop-invite-nonce']) || !is_string($post_data['mpop-invite-nonce'])) {
    $res_data['error'] = ['mpop-invite-nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (!wp_verify_nonce($post_data['mpop-invite-nonce'], 'mpop-invite')) {
    $res_data['error'] = ['nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Pagina scaduta. Ricarica la pagina e riprova']];
    http_response_code( 401 );
    echo json_encode( $res_data );
    exit;
}