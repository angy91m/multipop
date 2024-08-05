<?php
defined( 'ABSPATH' ) || exit;
if (get_current_user_id() && !$this->current_user_is_admin()) {
    wp_redirect('/');
    exit;
}

if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' ) &&
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' ) &&
    $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/register.php');
    exit;
}

?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/main.css">
<div id="app" class="woocommerce woocommerce-page">
    <template v-if="!registered">
        <form class="woocommerce-form woocommerce-form-register register">
            <p v-if="errorFields.has('server')" class="mpop-field-error">Errore sever</p>
            <p v-if="startedFields.has('username') && !isValidUsername() && !errorFields.has('duplicated')" class="mpop-field-error">Il nome utente può contenere solo lettere minuscole, numeri e i simboli . _ -<br>Inoltre non può iniziare e terminare con i simboli . -</p>
            <p v-if="!isValidUsername() && errorFields.has('duplicated')" class="mpop-field-error">Nome utente già registrato</p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <input v-model="username" @input="startField('username')" type="text" :class="'woocommerce-Input woocommerce-Input--text input-text' + (startedFields.has('username') ? (isValidUsername() ? '' : ' bad-input' ) : '')" id="reg_username" autocomplete="username" placeholder="<?php esc_html_e( 'Username', 'woocommerce' ); ?>" />
            </p>
            <p v-if="startedFields.has('email') && !isValidEmail() && !errorFields.has('duplicated')" class="mpop-field-error">Indirizzo e-mail non valido</p>
            <p v-if="!isValidEmail() && errorFields.has('duplicated')" class="mpop-field-error">Indirizzo e-mail già registrato</p>
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                <input v-model="email" @input="startField('email')" type="email" :class="'woocommerce-Input woocommerce-Input--text input-text' + (startedFields.has('email') ? (isValidEmail() ? '' : ' bad-input' ) : '')" name="email" id="reg_email" autocomplete="email" placeholder="<?php esc_html_e( 'Email address', 'woocommerce' ); ?>" />
            </p>
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

            <p>Riceverai un link per confermare l'indirizzo</p>

            <?php do_action( 'woocommerce_register_form' ); ?>

            <p class="woocommerce-form-row form-row">
                <?php wp_nonce_field( 'mpop-register', 'mpop-register-nonce' ); ?>
                <button :disabled="!isValidForm || requesting" @click="register" class="woocommerce-Button woocommerce-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> woocommerce-form-register__submit" name="register" value="register"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
            </p>

            <?php do_action( 'woocommerce_register_form_end' ); ?>
        </form>
    </template>

    <template v-else>
        <div class="registered">
            <h2>Registrazione completata</h2>
            <p>Il tuo account e' stato creato con successo</p>
        </div>
    </template>
</div>

<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/register.js"></script>
<?php