<?php
defined( 'ABSPATH' ) || exit;
switch( $post_data['action'] ) {
    case 'resp_get_master_key':
        if (isset($post_data['master_key']) || !is_string($post_data['master_key']) || !trim($post_data['master_key'])) {
            $res_data['error'] = ['master_key'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Master key non valida']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!$current_user->mpop_personal_master_key) {
            $res_data['error'] = ['data'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Non hai una master key impostata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $master_key = base64_decode(
            $this->decrypt_with_password(
                base64_decode($current_user->mpop_personal_master_key, true),
                $post_data['master_key']
            ),
            true
        );
        if (!$master_key || strlen($master_key) <= 32) {
            $res_data['error'] = ['master_key'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Master key non valida']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $res_data['data'] = [
            'sym' => base64_encode(substr($master_key, 0, 32)),
            'asym' => base64_encode(substr($master_key, 32))
        ];
        break;
    case 'resp_search_users':
        [$users, $total, $limit, $sort_by] = $this->resp_user_search($post_data, $current_user);
        $res_data['data'] = ['users' => $users, 'total' => $total, 'limit' => $limit, 'sortBy' => $sort_by];
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