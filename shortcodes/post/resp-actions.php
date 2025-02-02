<?php
defined( 'ABSPATH' ) || exit;
switch( $post_data['action'] ) {
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