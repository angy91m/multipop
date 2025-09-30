<?php
defined( 'ABSPATH' ) || exit;
$all_headers = getallheaders_lower();
if (isset($all_headers['content-type']) && $all_headers['content-type'] == 'application/json') {
  $post_data = json_decode( file_get_contents('php://input'), true );
} elseif (isset($_POST['data']) && $_POST['data']) {
  $jData = urldecode($_POST['data']);
  $jData = @json_decode($jData, true);
  if (is_array($jData)) {
    $post_data = $jData;
  }
}
save_test($all_headers);
save_test($post_data, 1);
$res_data = [];
if (!isset($post_data['action']) || !is_string($post_data['action']) || !trim($post_data['action'])) {
  $res_data['error'] = ['action'];
  $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
  http_response_code( 400 );
  echo json_encode( $res_data );
  exit;
}
if (!isset($post_data['mpop-events-page-nonce']) || !is_string($post_data['mpop-events-page-nonce'])) {
  $res_data['error'] = ['nonce'];
  $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
  http_response_code( 400 );
  echo json_encode( $res_data );
  exit;
}
if (!wp_verify_nonce($post_data['mpop-events-page-nonce'], 'mpop-events-page')) {
  $res_data['error'] = ['nonce'];
  $res_data['notices'] = [['type'=>'error', 'msg' => 'Pagina scaduta. Ricarica la pagina e riprova']];
  http_response_code( 401 );
  echo json_encode( $res_data );
  exit;
}

switch ($post_data['action']) {
  case 'search_zones':
    $res_data['data'] = MultipopPlugin::$instances[0]->search_zones($post_data['search'], false, false);
    break;
  default:
    $res_data['error'] = ['action'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
echo json_encode( $res_data );
exit;