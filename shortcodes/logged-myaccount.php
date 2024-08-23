<?php
defined( 'ABSPATH' ) || exit;
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require_once('post/logged-myaccount.php');
    exit;
}
$parsed_user = $this->myaccount_get_profile($current_user, true);

?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/logged-myaccount.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<div id="app">
    <span v-for="(notice, noticeInd) in userNotices" :class="'mpop-app-notice' + ' notice-' + notice.type"><span @click="dismissNotice(noticeInd)"><?=$this::dashicon('no-alt')?></span><span v-html="notice.msg"></span></span>
    <table id="mpop-main-table">
        <tr>
            <td>
                <div>
                    <label for="mpop-tabs-nav" @click="displayNav = !displayNav">Menù</label>
                    <nav id="mpop-tabs-nav" v-if="displayNav">
                        <ul @click="selectTab('summary')">Riepilogo</ul>
                        <ul @click="selectTab('card')">Tessera</ul>
                    </nav>
                </div>
            </td>
            <td>
                <div id="mpop-tabs">
                    <div v-if="selectedTab == 'summary'">
                        <h3>Ciao {{helloName}}</h3>
                        <template v-if="!profileEditing">
                            <button class="mpop-button" @click="editProfile">Modifica profilo</button>
                        </template>
                        <template v-else>
                            <button class="mpop-button btn-error" @click="cancelEditProfile">Annulla</button>
                            <button class="mpop-button btn-success" @click="updateProfile" :disabled="!validProfileForm">Salva</button>
                        </template>
                        <br><br>
                        <table id="mpop-profile-table">
                            <tr>
                                <td><strong>E-mail:</strong></td>
                                <td v-if="!profileEditing">{{user.email}}</td>
                                <td v-else><input type="text" v-model="userInEditing.email"/></td>
                            </tr>
                            <tr>
                                <td><strong>Nome:</strong></td>
                                <td v-if="!profileEditing">{{user.first_name}}</td>
                                <td v-else><input type="text" style="text-transform: uppercase" v-model="userInEditing.first_name"/></td>
                            </tr>
                            <tr>
                                <td><strong>Cognome:</strong></td>
                                <td v-if="!profileEditing">{{user.last_name}}</td>
                                <td v-else><input type="text" style="text-transform: uppercase" v-model="userInEditing.last_name"/></td>
                            </tr>
                            <tr>
                                <td><strong>Data di nascita:</strong></td>
                                <td v-if="!profileEditing">{{displayLocalDate(user.mpop_birthdate)}}</td>
                                <td v-else><input type="date" :max="maxBirthDate" v-model="userInEditing.mpop_birthdate"/></td>
                            </tr>
                            <tr>
                                <td><strong>Luogo di nascita:</strong></td>
                                <td v-if="!profileEditing">{{user.mpop_birthplace ? (user.mpop_birthplace.nome + ' (' + user.mpop_birthplace.provincia.sigla +')' ) : ''}}</td>
                                <td v-else>
                                    <v-select
                                        id="birthplace-select"
                                        v-model="userInEditing.mpop_birthplace"
                                        :options="birthCities"
                                        :disabled="!userInEditing.mpop_birthdate"
                                        @close="birthplaceOpen = false"
                                        @open="birthplaceOpen = true"
                                        :label="birthplaceOpen ? 'label' : 'untouched_label'"
                                        @search="(searchTxt, loading) => {
                                            if (searchTxt.trim().length > 1) {
                                                loading(true);
                                                birthCitiesSearch(searchTxt)
                                                .then(()=> loading(false));
                                            }
                                        }"
                                    >
                                        <template #search="{ attributes, events }">
                                            <input
                                                class="vs__search"
                                                :style="'display: ' + (birthplaceOpen || !userInEditing.mpop_birthplace ? 'unset' : 'none')"
                                                v-bind="attributes"
                                                v-on="events"
                                            />
                                        </template>
                                        <template v-slot:option="city">
                                            {{city.untouched_label}}
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
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Comune di residenza:</strong></td>
                                <td v-if="!profileEditing">{{ user.mpop_billing_city ? user.mpop_billing_city.nome : ''}}</td>
                                <td v-else>
                                    <v-select
                                        id="billing-city-select"
                                        v-model="userInEditing.mpop_billing_city"
                                        :options="billingCities"
                                        @close="billingCityOpen = false"
                                        @open="billingCityOpen = true"
                                        :label="billingCityOpen ? 'label' : 'nome'"
                                        @option:selected="c => {
                                            userInEditing.mpop_billing_state = c.provincia.sigla;
                                            if (c.cap.length == 1) {
                                                userInEditing.mpop_billing_zip = c.cap[0];
                                            }
                                        }"
                                        @option:deselected="() => {
                                            userInEditing.mpop_billing_state = '';
                                            userInEditing.mpop_billing_zip = '';
                                        }"
                                        @search="(searchTxt, loading) => {
                                            if (searchTxt.trim().length > 1) {
                                                loading(true);
                                                billingCitiesSearch(searchTxt)
                                                .then(()=> loading(false));
                                            }
                                        }"
                                    >
                                        <template #search="{ attributes, events }">
                                            <input
                                                class="vs__search"
                                                :style="'display: ' + (billingCityOpen || !userInEditing.mpop_billing_city ? 'unset' : 'none')"
                                                v-bind="attributes"
                                                v-on="events"
                                            />
                                        </template>
                                        <template v-slot:option="city">
                                            {{city.nome}} ({{city.provincia.sigla}})
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
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Provincia di residenza:</strong></td>
                                <td v-if="!profileEditing">{{user.mpop_billing_state}}</td>
                                <td v-else>
                                    <select v-model="userInEditing.mpop_billing_state" disabled>
                                        <option
                                            v-if="userInEditing.mpop_billing_city"
                                            :value="userInEditing.mpop_billing_city.provincia.sigla">{{userInEditing.mpop_billing_city.provincia.sigla}}</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>CAP:</strong></td>
                                <td v-if="!profileEditing">{{user.mpop_billing_zip}}</td>
                                <td v-else>
                                    <select v-model="userInEditing.mpop_billing_zip" :disabled="!userInEditing.mpop_billing_city || userInEditing.mpop_billing_city.cap.length == 1">
                                        <template v-if="userInEditing.mpop_billing_city">
                                            <option v-for="cap in userInEditing.mpop_billing_city.cap" :value="cap">{{cap}}</option>
                                        </template>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Indirizzo di residenza:</strong></td>
                                <td v-if="!profileEditing">{{user.mpop_billing_address}}</td>
                                <td v-else><textarea v-model="userInEditing.mpop_billing_address" :disabled="!userInEditing.mpop_billing_zip"></textarea></td>
                            </tr>
                        </table>
                    </div>
                    <div v-if="selectedTab == 'card'">
                        <?=html_dump($current_user)?>
                        Tessera
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>
<script type="application/json" id="__MULTIPOP_DATA__">{
    "user": <?=json_encode($parsed_user)?>
}</script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/logged-myaccount.js"></script>
<?php