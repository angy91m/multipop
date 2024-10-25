<?php
defined( 'ABSPATH' ) || exit;
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/login.php');
    exit;
}
$this->show_hcaptcha_script();
?>
<form class="mpop-form mpop-form-login" method="post">
    <p class="mpop-form-row">
        <input type="text"  name="user" autocomplete="username" placeholder="Nome utente o e-mail" />
    </p>
    <p class="mpop-form-row">
        <input type="password" name="password" autocomplete="password" placeholder="Password"/>
    </p>
    <p class="mpop-form-row">
        <a href="./?mpop_forgot_password=1">Password dimenticata</a>
    </p>
    <?=$this->create_hcaptcha()?>
    <p class="mpop-form-row">
        <?php wp_nonce_field( 'mpop-login', 'mpop-login-nonce' ); ?>
        <button type="submit" class="primary" name="login" value="login">Accedi</button>
    </p>
</form>
<?php