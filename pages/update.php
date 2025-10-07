<?php
defined( 'ABSPATH' ) || exit;
if ( !$this->current_user_is_admin() ) {
  echo '<p>Accesso non consentito</p>';
  exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mpop-admin-update-nonce'])) {
  require('post/update.php');
}
?>
<form method="POST" enctype="multipart/form-data">
  Carica il file zip:
  <input type="file" name="update" id="update-file" accept=".zip,application/zip">
  <?php wp_nonce_field( 'mpop-admin-update', 'mpop-admin-update-nonce' ); ?>
  <input type="submit" value="Invia" name="submit">
</form>
<?php