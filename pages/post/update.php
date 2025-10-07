<?php
defined( 'ABSPATH' ) || exit;
if ( !wp_verify_nonce( $_REQUEST['mpop-admin-update-nonce'], 'mpop-admin-update' ) ) {
  $this->add_admin_notice("Invalid request");
} elseif (isset($_POST["submit"])) {
  $zip = new ZipArchive;
  $res = $zip->open($_FILES["update"]["tmp_name"]);
  if ($res === TRUE) {
    $zip->extractTo('/myzips/extract_path/');
    $zip->close();
    echo 'woot!';
  }
}
@unlink($_FILES["update"]["tmp_name"]);