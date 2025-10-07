<?php
defined( 'ABSPATH' ) || exit;
if ( !wp_verify_nonce( $_REQUEST['mpop-admin-update-nonce'], 'mpop-admin-update' ) ) {
  $this->add_admin_notice("Invalid request");
} elseif (isset($_POST["submit"]) && !empty($_FILES["update"]["tmp_name"])) {
  $zip = new ZipArchive;
  $res = $zip->open($_FILES["update"]["tmp_name"]);
  if ($res === TRUE) {
    $zip->extractTo(MULTIPOP_PLUGIN_PATH . '/testzip/');
    $zip->close();
  }
  unlink($_FILES["update"]["tmp_name"]);
}