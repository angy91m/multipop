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
    if (file_exists(MULTIPOP_PLUGIN_PATH . '/._update/.update_list')) {
      $lines = preg_split("/(\r\n|\n|\r)/",file_get_contents(MULTIPOP_PLUGIN_PATH . '/._update/.update_list'));
      foreach($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        if (file_exists(MULTIPOP_PLUGIN_PATH . "/._update/$line" )) {
          if (!file_exists(dirname(MULTIPOP_PLUGIN_PATH . "/$line"))) mkdir(dirname(MULTIPOP_PLUGIN_PATH . "/$line"), 0770, true);
          rename(MULTIPOP_PLUGIN_PATH . "/._update/$line", MULTIPOP_PLUGIN_PATH . "/$line");
        }
      }
    }
    if (file_exists(MULTIPOP_PLUGIN_PATH . '/._update/.delete_list')) {
      $lines = preg_split("/(\r\n|\n|\r)/",file_get_contents(MULTIPOP_PLUGIN_PATH . '/._update/.delete_list'));
      foreach($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        if (file_exists(MULTIPOP_PLUGIN_PATH . "/$line")) is_dir(MULTIPOP_PLUGIN_PATH . "/$line") ? remove_dir(MULTIPOP_PLUGIN_PATH . "/$line") : unlink(MULTIPOP_PLUGIN_PATH . "/$line");
      }
    }
  }
  remove_dir(MULTIPOP_PLUGIN_PATH . '/._update/');
  unlink($_FILES["update"]["tmp_name"]);
}