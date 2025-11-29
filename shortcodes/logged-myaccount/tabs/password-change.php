<?php
defined( 'ABSPATH' ) || exit;
?>
<button class="mpop-button" :disabled="pwdChanging ||pwdChangeErrors.length || !pwdChangeFields.current" @click="changePassword">Cambia password</button>
<div id="mpop-passwordChange">
    <input v-model="pwdChangeFields.current" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('current') ? 'bad-input' : ''" type="password" placeholder="Password attuale"/>
    <input v-model="pwdChangeFields.new" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('new') ? 'bad-input' : ''" type="password" placeholder="Nuova password"/>
    <input v-model="pwdChangeFields.confirm" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('confirm') ? 'bad-input' : ''" type="password" placeholder="Conferma"/>
</div>