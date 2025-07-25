<?php
defined( 'ABSPATH' ) || exit;
if (!isset($this->invited_user) || !$this->invited_user->mpop_invited) {
    header('Location: /');
    exit;
}
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/invitation-confirm.php');
    exit;
}
$invitation_props = [
    'role' => $this->invited_user->roles[0]
];
?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-tel-input.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/invitation-confirm.css">
<script id="invitation-props" type="application/json"><?=json_encode($invitation_props)?></script>
<div id="app" class="mpop-form">
    <?php $this->html_added()?>
    <p v-if="errorFields.has('server')" class="mpop-field-error">Errore sever</p>
    <p v-if="startedFields.has('username') && !isValidUsername() && !errorFields.has('duplicated')" class="mpop-field-error">Il nome utente può contenere solo lettere minuscole, numeri e i simboli . _ -<br>Inoltre non può iniziare e terminare con i simboli . -</p>
    <p v-if="!isValidUsername() && errorFields.has('duplicated')" class="mpop-field-error">Nome utente già registrato</p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <input v-model="user.username" @input="startField('username')" type="text" :class="startedFields.has('username') ? (isValidUsername() ? '' : ' bad-input' ) : ''" id="inv_username" autocomplete="username" placeholder="Nome utente" />
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
        <input v-model="user.password" @input="startField('password')" type="password" :class="startedFields.has('password') ? (isValidPassword() ? '' : ' bad-input' ) : ''" name="password" id="inv_password" placeholder="Password"/>
    </p>
    <p v-if="startedFields.has('passwordConfirm') && !isValidPasswordConfirm()" class="mpop-field-error">Le password non coincidono</p>
    <p class="mpop-form-row">
        <input v-model="user.password_confirm" @input="startField('passwordConfirm')" type="password" :class="startedFields.has('passwordConfirm') ? (isValidPasswordConfirm() ? '' : ' bad-input' ) : ''" id="inv_password_confirm" placeholder="Conferma password"/>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <input v-model="user.first_name" @input="startField('first_name')" type="text" :class="errorFields.has('first_name') ?  ' bad-input' : ''" id="inv_first_name" placeholder="Nome"/>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <input v-model="user.last_name" @input="startField('last_name')" type="text" :class="errorFields.has('first_name') ?  ' bad-input' : ''" id="inv_last_name" placeholder="Cognome"/>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <label>Data di nascita<br>
        <input type="date"
            :class="errorFields.has('mpop_birthdate') ? 'bad-input' : ''"
            min="1910-10-13" :max="maxBirthDate"
            v-model="user.mpop_birthdate"
            @change="()=> {if (!user.mpop_birthdate) user.mpop_birthplace = '';}"
        />
        </label>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <label for="birthplaceCountry-select">Nazione di nascita</label><br>
        <v-select
            id="birthplaceCountry-select"
            :class="errorFields.has('mpop_birthplace_country') ? 'bad-input' : ''"
            v-model="user.mpop_birthplace_country"
            :options="countries"
            @close="birthplaceCountryOpen = false"
            @open="searchOpen('birthplaceCountry')"
            label="name"
            :reduce="c=>c.code"
        >
            <template #search="{ attributes, events }">
                <input
                    class="vs__search"
                    :style="'display: ' + (birthplaceCountryOpen || !user.mpop_birthplace_country ? 'unset' : 'none')"
                    v-bind="attributes"
                    v-on="events"
                />
            </template>
        </v-select>
    </p>
    <p v-if="role == 'multipopolano' && user.mpop_birthplace_country && user.mpop_birthplace_country == 'ita'" class="mpop-form-row">
        <label for="birthplace-select">Comune di nascita</label><br>
        <v-select
            id="birthplace-select"
            :class="errorFields.has('mpop_birthplace') ? 'bad-input' : ''"
            v-model="user.mpop_birthplace"
            :options="birthCities"
            :disabled="!user.mpop_birthdate"
            @close="birthplaceOpen = false"
            @open="searchOpen('birthplace')"
            :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
            :filter="fuseSearch"
            @search="(searchTxt, loading) => {
                if (searchTxt.trim().length < 2) return loading(false);
                triggerSearch(searchTxt, loading, 'birthCitiesSearch');
            }"
        >
            <template #search="{ attributes, events }">
                <input
                    class="vs__search"
                    :style="'display: ' + (birthplaceOpen || !user.mpop_birthplace ? 'unset' : 'none')"
                    v-bind="attributes"
                    v-on="events"
                />
            </template>
            <template v-slot:option="city">
                {{city.untouched_label + addSuppressToLabel(city)}}
            </template>
            <template v-slot:no-options="{search}">
                <template v-if="search.trim().length > 1">
                    Nessun risultato per "{{search}}"
                </template>
                <template v-else>
                    Inserisci almeno 2 caratteri
                </template>
            </template>
        </v-select>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <label for="billingCountry-select">Nazione di residenza</label><br>
        <v-select
            id="billingCountry-select"
            :class="errorFields.has('mpop_billing_country') ? 'bad-input' : ''"
            v-model="user.mpop_billing_country"
            :options="countries"
            @close="billingCountryOpen = false"
            @open="searchOpen('billingCountry')"
            label="name"
            :reduce="c=>c.code"
        >
            <template #search="{ attributes, events }">
                <input
                    class="vs__search"
                    :style="'display: ' + (billingCountryOpen || !user.mpop_billing_country ? 'unset' : 'none')"
                    v-bind="attributes"
                    v-on="events"
                />
            </template>
        </v-select>
    </p>
    <template v-if="user.mpop_billing_country && user.mpop_billing_country == 'ita'">
        <p v-if="role == 'multipopolano'" class="mpop-form-row">
            <label for="billingCity-select">Comune di residenza</label><br>
            <v-select
                id="billingCity-select"
                v-model="user.mpop_billing_city"
                :class="errorFields.has('mpop_billing_city') ? 'bad-input' : ''"
                :options="billingCities"
                @close="billingCityOpen = false"
                @open="searchOpen('billingCity')"
                :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
                :filter="fuseSearch"
                @option:selected="c => {
                    user.mpop_billing_state = c.provincia.sigla;
                    if (c.cap.length == 1) {
                        user.mpop_billing_zip = c.cap[0];
                    } else {
                        user.mpop_billing_zip = '';
                    }
                }"
                @option:deselected="() => {
                    user.mpop_billing_state = '';
                    user.mpop_billing_zip = '';
                }"
                @search="(searchTxt, loading) => {
                    if (searchTxt.trim().length < 2) return loading(false);
                    triggerSearch(searchTxt, loading, 'billingCitiesSearch');
                }"
            >
                <template #search="{ attributes, events }">
                    <input
                        class="vs__search"
                        :style="'display: ' + (billingCityOpen || !user.mpop_billing_city ? 'unset' : 'none')"
                        v-bind="attributes"
                        v-on="events"
                    />
                </template>
                <template v-slot:option="city">
                    {{city.nome}} ({{city.provincia.sigla}}){{addSuppressToLabel(city)}}
                </template>
                <template v-slot:no-options="{search}">
                    <template v-if="search.trim().length > 1">
                        Nessun risultato per "{{search}}"
                    </template>
                    <template v-else>
                        Inserisci almeno 2 caratteri
                    </template>
                </template>
            </v-select>
        </p>
        <p v-if="role == 'multipopolano'" class="mpop-form-row">
            <label for="billing-state-select">Provincia di residenza</label><br>
            <select id="billing-state-select" v-model="user.mpop_billing_state" :class="errorFields.has('mpop_billing_state') ? 'bad-input' : ''" disabled>
                <option
                    v-if="user.mpop_billing_city"
                    :value="user.mpop_billing_city.provincia.sigla">{{user.mpop_billing_city.provincia.sigla}}</option>
            </select>
        </p>
        <p v-if="role == 'multipopolano'" class="mpop-form-row">
            <label for="billing-zip-select">CAP</label><br>
            <select id="billing-zip-select" v-model="user.mpop_billing_zip" :class="errorFields.has('mpop_billing_zip') ? 'bad-input' : ''" :disabled="!user.mpop_billing_city || user.mpop_billing_city.cap.length == 1">
                <template v-if="user.mpop_billing_city">
                    <option v-for="cap in user.mpop_billing_city.cap" :value="cap">{{cap}}</option>
                </template>
            </select>
        </p>
    </template>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <label for="billing-address">Indirizzo</label><br>
        <textarea id="billing-address" v-model="user.mpop_billing_address" :class="errorFields.has('mpop_billing_address') ? 'bad-input' : ''" :disabled="!user.mpop_billing_country || user.mpop_billing_country == 'ita' && !user.mpop_billing_zip"></textarea>
    </p>
    <p v-if="role == 'multipopolano'" class="mpop-form-row">
        <label for="phone">Telefono</label><br>
        <v-intl-phone
            id="phone"
            ref="phoneInput"
            :options="{initialCountry: 'it'}"
            value="+39"
            :class="errorFields.has('mpop_phone') ? 'bad-input' : ''"
            @change-number="()=>user.mpop_phone = parsePhone(phoneInput)"
            @change-country="()=>user.mpop_phone = parsePhone(phoneInput)"
        ></v-intl-phone>
    </p>
    <template v-if="role == 'multipopolano'">
        <br>
        <p class="mpop-form-row">
            <strong>Consensi facoltativi</strong>
        </p>
        <p class="mpop-form-row">
            <label><u class="mpop-click" @click="e => {e.preventDefault(); marketingAgreeShow = !marketingAgreeShow}">Accetto le condizioni marketing</u>&nbsp;
                <input type="checkbox" v-model="user.mpop_subscription_marketing_agree"/>
            </label>
        </p>
        <p class="mpop-form-row" v-show="marketingAgreeShow">
            <button class="mpop-button" @click="marketingAgreeShow = false">Chiudi</button><br><?=nl2br($this->settings['marketing_policy'])?>
        </p>
        <p class="mpop-form-row">
            <label><u class="mpop-click" @click="e => {e.preventDefault(); newsletterAgreeShow = !newsletterAgreeShow}">Accetto le condizioni della newsletter</u>&nbsp;
                <input type="checkbox" v-model="user.mpop_subscription_newsletter_agree"/>
            </label>
        </p>
        <p class="mpop-form-row" v-show="newsletterAgreeShow">
            <button class="mpop-button" @click="newsletterAgreeShow = false">Chiudi</button><br><?=nl2br($this->settings['newsletter_policy'])?>
        </p>
        <p class="mpop-form-row">
            <label><u class="mpop-click" @click="e => {e.preventDefault(); publishAgreeShow = !publishAgreeShow}">Accetto le condizioni di pubblicazione</u>&nbsp;
                <input type="checkbox" v-model="user.mpop_subscription_publish_agree"/>
            </label>
        </p>
        <p class="mpop-form-row" v-show="publishAgreeShow">
            <button class="mpop-button" @click="publishAgreeShow = false">Chiudi</button><br><?=nl2br($this->settings['publish_policy'])?>
        </p>
    </template>
    <p class="mpop-form-row">
        <?php wp_nonce_field( 'mpop-invite', 'mpop-invite-nonce' ); ?>
        <button class="primary" @click="activateAccount" :disabled="requesting || !isValidForm">Attiva l'account</button>
    </p>
    <p v-if="errorFields.size" class="mpop-field-error">Alcuni campi contengono degli errori. Rincontrolla per favore</p>
</div>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/invitation-confirm.js"></script>
<?php