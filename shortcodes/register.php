<?php
defined( 'ABSPATH' ) || exit;
if (get_current_user_id() && !$this->current_user_is_admin()) {
    wp_redirect('/');
    exit;
}
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/register.php');
    exit;
}
$this->show_hcaptcha_script();
?>
<div id="app">
    <?php $this->html_added()?>
    <template v-if="!registered">
        <form class="mpop-form mpop-form-register">
            <p v-if="errorFields.has('server')" class="mpop-field-error">Errore sever</p>
            <p v-if="startedFields.has('username') && !isValidUsername() && !errorFields.has('duplicated')" class="mpop-field-error">Il nome utente può contenere solo lettere minuscole, numeri e i simboli . _ -<br>Inoltre non può iniziare e terminare con i simboli . -</p>
            <p v-if="!isValidUsername() && errorFields.has('duplicated')" class="mpop-field-error">Nome utente già registrato</p>
            <p class="mpop-form-row">
                <input v-model="username" @input="startField('username')" type="text" :class="startedFields.has('username') ? (isValidUsername() ? '' : ' bad-input' ) : ''" id="reg_username" autocomplete="username" placeholder="Nome utente" />
            </p>
            <p v-if="startedFields.has('email') && !isValidEmail() && !errorFields.has('duplicated')" class="mpop-field-error">Indirizzo e-mail non valido</p>
            <p v-if="!isValidEmail() && errorFields.has('duplicated')" class="mpop-field-error">Indirizzo e-mail già registrato</p>
            <p class="mpop-form-row">
                <input v-model="email" @input="startField('email')" type="email" :class="startedFields.has('email') ? (isValidEmail() ? '' : ' bad-input' ) : ''" name="email" id="reg_email" autocomplete="email" placeholder="Indirizzo e-mail" />
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
            <p class="mpop-form-row">
                <input v-model="password" @input="startField('password')" type="password" :class="startedFields.has('password') ? (isValidPassword() ? '' : ' bad-input' ) : ''" name="password" id="reg_password" autocomplete="new-password" placeholder="Password"/>
            </p>
            <p v-if="startedFields.has('passwordConfirm') && !isValidPasswordConfirm()" class="mpop-field-error">Le password non coincidono</p>
            <p class="mpop-form-row">
                <input v-model="passwordConfirm" @input="startField('passwordConfirm')" type="password" :class="startedFields.has('passwordConfirm') ? (isValidPasswordConfirm() ? '' : ' bad-input' ) : ''" name="passwordConfirm" id="reg_password_confirm" autocomplete="new-password-confirm" placeholder="Conferma password"/>
            </p>

            <p>Riceverai un link per confermare l'indirizzo</p>
            <?=$this->create_hcaptcha()?>
            <p class="mpop-form-row">
                <?php wp_nonce_field( 'mpop-register', 'mpop-register-nonce' ); ?>
                <button :disabled="!isValidForm || requesting" @click="register" class="primary" name="register" value="register">Registrati</button>
            </p>
        </form>
    </template>

    <template v-else>
        <div class="registered">
            <h2>Registrazione completata</h2>
            <p>Il tuo account è stato creato con successo</p>
        </div>
    </template>
</div>

<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/register.js"></script>
<?php

