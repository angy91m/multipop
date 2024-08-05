<?php
defined( 'ABSPATH' ) || exit;
$current_user_id = get_current_user_id();
if (!$current_user_id) {
    exit;
}
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' ) &&
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' ) &&
    $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/password-change.php');
    exit;
}

?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/main.css">
<div id="app" class="woocommerce woocommerce-page">
    <form class="woocommerce-form woocommerce-form-password-change password-change">
        <p v-if="errorFields.has('server')" class="mpop-field-error">Errore sever</p>
        <div v-if="startedFields.has('password') && !isValidPassword()" class="mpop-field-error">
            <p>La password deve essere lunga dagli 8 ai 64 caratteri e deve contenere 3 dei seguenti gruppi di caratteri<p>
            <ul>
                <li>Una lettera minuscola</li>
                <li>Una lettera maiuscola</li>
                <li>Un numero</li>
                <li>Un carattere speciale tra questi: {{ acceptedSymbols }}</li>
            </ul>
        </div>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <input v-model="password" @input="startField('password')" type="password" :class="'woocommerce-Input woocommerce-Input--text input-text' + (startedFields.has('password') ? (isValidPassword() ? '' : ' bad-input' ) : '')" name="password" id="reg_password" autocomplete="new-password" placeholder="<?php esc_html_e( 'Password', 'woocommerce' ); ?>"/>
        </p>
        <p v-if="startedFields.has('passwordConfirm') && !isValidPasswordConfirm()" class="mpop-field-error">Le password non coincidono</p>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <input v-model="passwordConfirm" @input="startField('passwordConfirm')" type="password" :class="'woocommerce-Input woocommerce-Input--text input-text' + (startedFields.has('passwordConfirm') ? (isValidPasswordConfirm() ? '' : ' bad-input' ) : '')" name="passwordConfirm" id="reg_password_confirm" autocomplete="new-password-confirm" placeholder="<?=str_replace(' new ', ' ', str_replace( ' nuova ', ' ', esc_html__( 'Confirm new password', 'woocommerce' ))); ?>"/>
        </p>
        <p class="woocommerce-form-row form-row">
            <?php wp_nonce_field( 'mpop-password-change', 'mpop-password-change-nonce' ); ?>
            <button :disabled="!isValidForm || requesting" @click="send" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="register"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
        </p>
    </form>
</div>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/password-change.js"></script>
<?php