<?php
defined( 'ABSPATH' ) || exit;
if ( !wp_verify_nonce( $_REQUEST['mpop-admin-update-nonce'], 'mpop-admin-update' ) ) {
  $this->add_admin_notice("Invalid request");
} elseif (isset($_POST["submit"]) && !empty($_FILES["update"]["tmp_name"])) {
  $zip = new ZipArchive;
  $res = $zip->open($_FILES["update"]["tmp_name"]);
  if ($res === TRUE) {
    $zip->extractTo(MULTIPOP_PLUGIN_PATH . '/._update/');
    $zip->close();
    $ul_path = MULTIPOP_PLUGIN_PATH . '/._update/.update_list';
    if (file_exists($ul_path)) {
      $lines = preg_split("/(\r\n|\n|\r)/",file_get_contents($ul_path));
      foreach($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        $ofn = MULTIPOP_PLUGIN_PATH . "/._update/$line";
        if (file_exists($ofn)) {
          $nfn = MULTIPOP_PLUGIN_PATH . "/$line";
          $dir = dirname($nfn);
          if (!file_exists($dir)) mkdir($dir, 0750, true);
          rename($ofn, $nfn);
        }
      }
    }
    $dl_path = MULTIPOP_PLUGIN_PATH . '/._update/.delete_list';
    if (file_exists($dl_path)) {
      $lines = preg_split("/(\r\n|\n|\r)/",file_get_contents($dl_path));
      foreach($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        $fn = MULTIPOP_PLUGIN_PATH . "/$line";
        if (file_exists($fn)) is_dir($fn) ? remove_dir($fn) : unlink($fn);
      }
    }
  }
  remove_dir(MULTIPOP_PLUGIN_PATH . '/._update/');
  unlink($_FILES["update"]["tmp_name"]);
}