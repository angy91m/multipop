<?php
defined( 'ABSPATH' ) || exit;
?>
<form class="mpop-form mpop-form-forgot-password" action="/wp-login.php?action=lostpassword" method="post">
    <p class="mpop-form-row">
        <input type="text"  name="user_login" autocomplete="username" placeholder="Nome utente o e-mail" />
    </p>
    <input type="hidden" name="redirect_to" value="<?=get_permalink($this->settings['myaccount_page'])?>?mpop_sent_reset_mail=1">
    <p class="mpop-form-row">
        <?php wp_nonce_field( 'mpop-login', 'mpop-login-nonce' ); ?>
        <input type="submit" class="primary" name="wp-submit" value="Ottieni una nuova password" />
    </p>
</form>