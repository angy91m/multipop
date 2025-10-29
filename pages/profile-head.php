<?php
defined( 'ABSPATH' ) || exit;

?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/main.css">
<style type="text/css">
    .user-first-name-wrap,
    .user-last-name-wrap,
    .user-nickname-wrap,
    .user-display-name-wrap,
    .user-url-wrap {
        display: none;
    }
</style>
<script id="__MULTIPOP_DATA__" type="application/json"><?=json_encode([
  'role' => wp_get_current_user()->roles[0]
], JSON_UNESCAPED_SLASHES)?></script>
<script type="text/javascript" src="<?=plugins_url()?>/multipop/js/profile.js"></script>
<?php