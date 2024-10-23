<?php
defined( 'ABSPATH' ) || exit;
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/invitation-confirm.php');
    exit;
}
?>
<div id="app">
    <p class="mpop-form-row">
        <input type="text"  name="user" autocomplete="username" placeholder="Nome utente o e-mail" />
    </p>
    <p class="mpop-form-row">
        <input type="password" name="password" autocomplete="password" placeholder="Password"/>
    </p>
    <p class="mpop-form-row">
        <a href="./?mpop_forgot_password=1">Password dimenticata</a>
    </p>
</div>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/invitation-confirm.js"></script>
<?php