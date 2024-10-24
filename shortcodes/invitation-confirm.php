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
<div id="app" class="mpop-form">
    <?php $this->html_added()?>
    <p class="mpop-form-row">
        <input type="text"  name="user" autocomplete="username" placeholder="Nome utente o e-mail" />
    </p>
    <p class="mpop-form-row">
        <input type="password" name="password" autocomplete="password" placeholder="Password"/>
    </p>
    <p class="mpop-form-row">
        <?php wp_nonce_field( 'mpop-login', 'mpop-login-nonce' ); ?>
        <button class="primary" name="login" value="login">Accedi</button>
    </p>
</div>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/invitation-confirm.js"></script>
<?php