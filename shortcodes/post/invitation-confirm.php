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
switch( $post_data['action'] ) {
    case 'get_birth_cities':
        if (!isset($post_data['mpop_birthplace'])) {
            $res_data['error'] = ['mpop_birthplace'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!isset($post_data['mpop_birthdate'])) {
            $res_data['error'] = ['mpop_birthdate'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $post_birthdate = '';
        try {
            $filtered_comuni = $this->get_birth_cities($post_data['mpop_birthplace'], $post_data['mpop_birthdate']);
            $res_data['data'] = ['comuni' => $filtered_comuni];
        } catch (Exception $err) {
            $res_data['error'] = explode(',',$err->getMessage());
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'get_billing_cities':
        if (!isset($post_data['mpop_billing_city'])) {
            $res_data['error'] = ['mpop_billing_city'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            $filtered_comuni = $this->get_billing_cities($post_data['mpop_billing_city']);
            $res_data['data'] = ['comuni' => $filtered_comuni];
        } catch(Exception $e) {
            $res_data['error'] = explode(',',$err->getMessage());
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    default:
        $res_data['error'] = ['action'];
        $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
        http_response_code( 400 );
        echo json_encode( $res_data );
        exit;
}
if (!isset($res_data['data'])) {
    $res_data['error'] = ['action'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (isset($res_data['data'])) {
    echo json_encode( $res_data );
    exit;
}