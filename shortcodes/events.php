<?php
defined( 'ABSPATH' ) || exit;
?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<div id="app" style="max-width: unset">
    <mpop-map style="min-height: 500px;"></mpop-map>
</div>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/events.js"></script>
<?php