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
$parsed_user = $this->myaccount_get_profile($current_user, true, true);

?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-tel-input.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/logged-myaccount.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/logged-myaccount-fonts.css">
<div id="loaded-scripts" style="display:none"></div>
<div id="app">
    <div style="display:none">
        <v-intl-phone
            ref="intPhoneInstance"
            :options="{initialCountry: 'it'}"
        ></v-intl-phone>
    </div>
    <span v-for="(notice, noticeInd) in userNotices" :class="'mpop-app-notice' + ' notice-' + notice.type"><span @click="dismissNotice(noticeInd)"><?=$this::dashicon('no-alt')?></span><span v-html="notice.msg"></span></span>
    <div class="q-pa-md">
        <q-layout view="hHh Lpr lff" class="shadow-2 rounded-borders">
            <q-header elevated class="bg-red-9" style="position: relative">
                <q-toolbar>
                <q-btn flat @click="displayNav = !displayNav" round dense icon="menu" />
                <q-toolbar-title>{{selectedTab.label}}</q-toolbar-title>
                </q-toolbar>
            </q-header>

            <q-drawer
                v-model="displayNav"
                :width="200"
                :breakpoint="500"
                bordered
                dark
            >
                <q-scroll-area class="fit">
                <q-list>

                    <template v-for="(menuItem, index) in menuItems" :key="index">
                        <q-item v-if="!menuItem.admin" clickable @click="selectTab(menuItem)" :active="menuItem.name === selectedTab.name" v-ripple>
                            <q-item-section avatar>
                            <!-- <q-icon :name="menuItem.icon" /> -->
                            </q-item-section>
                            <q-item-section>
                            {{ menuItem.label }}
                            </q-item-section>
                        </q-item>
                    </template>
                    <template v-if="profile.role == 'administrator'">
                        <q-separator></q-separator>
                        <template v-for="(menuItem, index) in menuItems" :key="index">
                            <q-item v-if="menuItem.admin" clickable @click="selectTab(menuItem)" :active="menuItem.name === selectedTab.name" v-ripple>
                                <q-item-section avatar>
                                <!-- <q-icon :name="menuItem.icon" /> -->
                                </q-item-section>
                                <q-item-section>
                                {{ menuItem.label }}
                                </q-item-section>
                            </q-item>
                        </template>
                    </template>
                </q-list>
                </q-scroll-area>
            </q-drawer>

        <q-page-container>
            <q-page padding>
            <div v-if="selectedTab.name == 'summary'">
                <h3>Ciao {{helloName}}</h3>
                <template v-if="!profileEditing">
                    <button class="mpop-button" @click="editProfile">Modifica profilo</button>
                </template>
                <template v-else>
                    <button class="mpop-button btn-error" @click="cancelEditProfile" :disabled="saving">Annulla</button>
                    <button class="mpop-button btn-success" @click="updateProfile" :disabled="!validProfileForm || saving">Salva</button>
                </template>
                <table id="mpop-profile-table">
                    <tr>
                        <td><strong>E-mail:</strong></td>
                        <td v-if="!profileEditing">{{profile.email}}<template v-if="profile._new_email"><br>Da confermare: {{profile._new_email}}</template></td>
                        <td v-else><input type="text" :class="savingProfileErrors.includes('email') ? 'bad-input' : ''" v-model="profileInEditing.email"/></td>
                    </tr>
                    <tr>
                        <td><strong>Nome:</strong></td>
                        <td v-if="!profileEditing">{{profile.first_name}}</td>
                        <td v-else><input type="text" :class="savingProfileErrors.includes('first_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="profileInEditing.first_name"/></td>
                    </tr>
                    <tr>
                        <td><strong>Cognome:</strong></td>
                        <td v-if="!profileEditing">{{profile.last_name}}</td>
                        <td v-else><input type="text" :class="savingProfileErrors.includes('last_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="profileInEditing.last_name"/></td>
                    </tr>
                    <tr>
                        <td><strong>Ruolo:</strong></td>
                        <td>{{showRole(profile.role)}}</td>
                    </tr>
                    <tr>
                        <td><strong>Data di nascita:</strong></td>
                        <td v-if="!profileEditing">{{displayLocalDate(profile.mpop_birthdate)}}</td>
                        <td v-else>
                            <input type="date"
                                :class="savingProfileErrors.includes('mpop_birthdate') ? 'bad-input' : ''"
                                min="1910-10-13" :max="maxBirthDate"
                                v-model="profileInEditing.mpop_birthdate"
                                @change="()=> {if (!profileInEditing.mpop_birthdate) profileInEditing.mpop_birthplace = '';}"
                            />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Nazione di nascita:</strong></td>
                        <td v-if="!profileEditing">{{showCountryName(profile.mpop_birthplace_country)}}</td>
                        <td v-else>
                            <v-select
                                id="birthplaceCountry-select"
                                :class="savingProfileErrors.includes('mpop_birthplace_country') ? 'bad-input' : ''"
                                v-model="profileInEditing.mpop_birthplace_country"
                                :options="countries"
                                @close="birthplaceCountryOpen = false"
                                @open="searchOpen('birthplaceCountry')"
                                label="name"
                                :reduce="c=>c.code"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (birthplaceCountryOpen || !profileInEditing.mpop_birthplace_country ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                            </v-select>
                        </td>
                    </tr>
                    <tr v-if="(profileEditing ? profileInEditing : profile).mpop_birthplace_country == 'ita'">
                        <td><strong>Comune di nascita:</strong></td>
                        <td v-if="!profileEditing">{{profile.mpop_birthplace ? (profile.mpop_birthplace.nome + ' (' + profile.mpop_birthplace.provincia.sigla +')' + addSuppressToLabel(profile.mpop_birthplace) ) : ''}}</td>
                        <td v-else>
                            <v-select
                                id="birthplace-select"
                                :class="savingProfileErrors.includes('mpop_birthplace') ? 'bad-input' : ''"
                                v-model="profileInEditing.mpop_birthplace"
                                :options="birthCities"
                                :disabled="!profileInEditing.mpop_birthdate"
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
                                        :style="'display: ' + (birthplaceOpen || !profileInEditing.mpop_birthplace ? 'unset' : 'none')"
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
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Nazione di residenza:</strong></td>
                        <td v-if="!profileEditing">{{showCountryName(profile.mpop_billing_country)}}</td>
                        <td v-else>
                            <v-select
                                id="billingCountry-select"
                                :class="savingProfileErrors.includes('mpop_billing_country') ? 'bad-input' : ''"
                                v-model="profileInEditing.mpop_billing_country"
                                :options="countries"
                                @close="billingCountryOpen = false"
                                @open="searchOpen('billingCountry')"
                                label="name"
                                :reduce="c=>c.code"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (billingCountryOpen || !profileInEditing.mpop_billing_country ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                            </v-select>
                        </td>
                    </tr>
                    <template v-if="(profileEditing ? profileInEditing : profile).mpop_billing_country == 'ita'">
                        <tr>
                            <td><strong>Comune di residenza:</strong></td>
                            <td v-if="!profileEditing">{{ profile.mpop_billing_city ? profile.mpop_billing_city.nome + addSuppressToLabel(profile.mpop_billing_city) : ''}}</td>
                            <td v-else>
                                <v-select
                                    id="billingCity-select"
                                    v-model="profileInEditing.mpop_billing_city"
                                    :class="savingProfileErrors.includes('mpop_billing_city') ? 'bad-input' : ''"
                                    :options="billingCities"
                                    @close="billingCityOpen = false"
                                    @open="searchOpen('billingCity')"
                                    :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
                                    :filter="fuseSearch"
                                    @option:selected="c => {
                                        profileInEditing.mpop_billing_state = c.provincia.sigla;
                                        if (c.cap.length == 1) {
                                            profileInEditing.mpop_billing_zip = c.cap[0];
                                        } else {
                                            profileInEditing.mpop_billing_zip = '';
                                        }
                                    }"
                                    @option:deselected="() => {
                                        profileInEditing.mpop_billing_state = '';
                                        profileInEditing.mpop_billing_zip = '';
                                    }"
                                    @search="(searchTxt, loading) => {
                                        if (searchTxt.trim().length < 2) return loading(false);
                                        triggerSearch(searchTxt, loading, 'billingCitiesSearch');
                                    }"
                                >
                                    <template #search="{ attributes, events }">
                                        <input
                                            class="vs__search"
                                            :style="'display: ' + (billingCityOpen || !profileInEditing.mpop_billing_city ? 'unset' : 'none')"
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
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Provincia di residenza:</strong></td>
                            <td v-if="!profileEditing">{{profile.mpop_billing_state}}</td>
                            <td v-else>
                                <select v-model="profileInEditing.mpop_billing_state" :class="savingProfileErrors.includes('mpop_billing_state') ? 'bad-input' : ''" disabled>
                                    <option
                                        v-if="profileInEditing.mpop_billing_city"
                                        :value="profileInEditing.mpop_billing_city.provincia.sigla">{{profileInEditing.mpop_billing_city.provincia.sigla}}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>CAP:</strong></td>
                            <td v-if="!profileEditing">{{profile.mpop_billing_zip}}</td>
                            <td v-else>
                                <select v-model="profileInEditing.mpop_billing_zip" :class="savingProfileErrors.includes('mpop_billing_zip') ? 'bad-input' : ''" :disabled="!profileInEditing.mpop_billing_city || profileInEditing.mpop_billing_city.cap.length == 1">
                                    <template v-if="profileInEditing.mpop_billing_city">
                                        <option v-for="cap in profileInEditing.mpop_billing_city.cap" :value="cap">{{cap}}</option>
                                    </template>
                                </select>
                            </td>
                        </tr>
                    </template>
                    <tr>
                        <td><strong>Indirizzo di residenza:</strong></td>
                        <td v-if="!profileEditing">{{profile.mpop_billing_address}}</td>
                        <td v-else><textarea v-model="profileInEditing.mpop_billing_address" :class="savingProfileErrors.includes('mpop_billing_address') ? 'bad-input' : ''" :disabled="!profileInEditing.mpop_billing_zip"></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>Telefono:</strong></td>
                        <td v-if="!profileEditing">{{profile.mpop_phone}}</td>
                        <td v-else>
                            <v-intl-phone
                                ref="profilePhoneInput"
                                :options="{initialCountry: 'it'}"
                                :value="profile.mpop_phone || ''"
                                :class="savingProfileErrors.includes('mpop_phone') ? 'bad-input' : ''"
                                @change-number="()=>profileInEditing.mpop_phone = parsePhone(profilePhoneInput)"
                                @change-country="()=>profileInEditing.mpop_phone = parsePhone(profilePhoneInput)"
                            ></v-intl-phone>
                        </td>
                    </tr>
                </table>
            </div>
            <div v-if="selectedTab.name == 'passwordChange'">
                <button class="mpop-button" :disabled="pwdChanging ||pwdChangeErrors.length || !pwdChangeFields.current" @click="changePassword">Cambia password</button>
                <div id="mpop-passwordChange">
                    <input v-model="pwdChangeFields.current" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('current') ? 'bad-input' : ''" type="password" placeholder="Password attuale"/>
                    <input v-model="pwdChangeFields.new" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('new') ? 'bad-input' : ''" type="password" placeholder="Nuova password"/>
                    <input v-model="pwdChangeFields.confirm" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('confirm') ? 'bad-input' : ''" type="password" placeholder="Conferma"/>
                </div>
            </div>
            <!--CARD-->
            <div v-if="selectedTab.name == 'card'">
                <template v-if="profile.mpop_my_subscriptions">
                    <h4 v-if="profile.mpop_card_active">La tua tessera è attiva</h4>
                    <div>
                        <ul v-if="nearActiveSub">
                            <li>Codice tessera: {{nearActiveSub.card_number ? nearActiveSub.card_number : 'Da assegnare'}}</li>
                            <li>Stato attivazione: {{showSubscriptionStatus(nearActiveSub)}}</li>
                            <li>Anno: {{nearActiveSub.year}}</li>
                            <li>ID richiesta: {{nearActiveSub.id}}</li>
                            <template v-if="nearActiveSub.pp_order_id">
                                <li>PayPal ID: {{nearActiveSub.pp_order_id}}</li>
                                <li v-if="nearActiveSub.status == 'seen'">Paga</li>
                            </template>
                        </ul>
                        <div v-if="availableYearsToOrder.length" id="mpop-avail-years-to-order">
                            <p v-if="isProfileCompleted">
                                Ordina per i seguenti anni: {{availableYearsToOrder}}
                            </p>
                            <p v-else>Per richiedere una nuova tessera è necessario completare i tuoi dati del profilo</p>
                        </div>
                        <div v-if="!profile.mpop_card_active && !availableYearsToOrder.length" id="mpop-avail-years-to-order">
                            <p>Al momento non è possibile richiedere nuove tessere</p>
                        </div>
                        <template v-if="otherSubscriptions.length">
                            <hr>
                            <h4>Altre richieste</h4>
                            <table id="mpop-other-subscriptions">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="card in otherSubscriptions">
                                        <td>{{card.id}}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </template>
                    </div>
                </template>
            </div>
            <!--USER_SEARCH-->
            <div v-if="selectedTab.name == 'users'" id="mpop-user-search">
                <div class="mpop-user-search-field">
                    <input type="text" v-model="userSearch.txt" @input="triggerSearchUsers" placeholder="Nome, e-mail, username" />
                </div>
                <div class="mpop-user-search-field" v-for="role in userRoles">
                    <label :for="'user-search-'+role">{{showRole(role)}}&nbsp;
                        <input :id="'user-search-'+role" type="checkbox" v-model="userSearch.roles" @change="triggerSearchUsers" :value="role"/>
                    </label>
                </div>
                <div class="mpop-user-search-field">
                    <label for="user-seach-card-active">Email da confermare&nbsp;
                        <select id="user-seach-card-active" v-model="userSearch.mpop_mail_to_confirm" @change="triggerSearchUsers">
                            <option :value="null"></option>
                            <option :value="true">Sì</option>
                            <option :value="false">No</option>
                        </select>
                    </label>
                </div>
                <div class="mpop-user-search-field">
                    <label for="user-seach-card-active">Tessera attiva&nbsp;
                        <select id="user-seach-card-active" v-model="userSearch.mpop_card_active" @change="triggerSearchUsers">
                            <option :value="null"></option>
                            <option :value="true">Sì</option>
                            <option :value="false">No</option>
                        </select>
                    </label>
                </div>
                <div>
                    <div class="mpop-user-search-field mpop-50-wid">
                        <label for="userSearchZone-select">Residenza&nbsp;
                            <v-select
                                multiple
                                id="userSearchZone-select"
                                v-model="userSearch.zones"
                                :options="zoneSearch.users"
                                @close="userSearchZoneOpen = false"
                                @open="searchOpen('userSearchZone')"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                :filter="fuseSearch"
                                @option:selected="zones => {
                                    const oldLen = zones.length;
                                    reduceZones(zones, userSearch);
                                    if (oldLen == zones.length) triggerSearchUsers();
                                }"
                                @option:deselected="triggerSearchUsers"
                                @search="(searchTxt, loading) => {
                                    if (searchTxt.trim().length < 2) return loading(false);
                                    triggerSearch(searchTxt, loading, 'searchZones', 'users', userSearch);
                                }"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (userSearchZoneOpen ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                                <template v-slot:option="zone">
                                    {{zone.untouched_label + addSuppressToLabel(zone)}}
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
                        </label>
                    </div>
                    <div class="mpop-user-search-field mpop-50-wid">
                        <label for="userSearchRespZone-select">Zone gestite&nbsp;
                            <v-select
                                multiple
                                id="userSearchRespZone-select"
                                v-model="userSearch.resp_zones"
                                :options="zoneSearch.users_resp"
                                @close="userSearchRespZoneOpen = false"
                                @open="searchOpen('userSearchRespZone')"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                :filter="fuseSearch"
                                @option:selected="zones => {
                                    const oldLen = zones.length;
                                    reduceZones(zones, userSearch, 'resp_zones');
                                    if (oldLen == zones.length) triggerSearchUsers();
                                }"
                                @option:deselected="triggerSearchUsers"
                                @search="(searchTxt, loading) => {
                                    if (searchTxt.trim().length < 2) return loading(false);
                                    triggerSearch(searchTxt, loading, 'searchZones', 'users_resp', userSearch, 'resp_zones');
                                }"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (userSearchRespZoneOpen ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                                <template v-slot:option="zone">
                                    {{zone.untouched_label + addSuppressToLabel(zone)}}
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
                        </label>
                    </div>
                </div>
                <div>Totale: {{foundUsersTotal}}</div>
                <div id="mpop-page-buttons">
                    <button class="mpop-button" @click="changeUserSearchPage(1)" v-if="userSearch.page != 1 && !pageButtons.includes(1) && userSearch.page -2 > 0" style="width:auto">Inizio</button>
                    <button class="mpop-button" @click="changeUserSearchPage(userSearch.page -1)" v-if="userSearch.page != 1 && !pageButtons.includes(userSearch.page -1)" style="padding:1px"><?=$this->dashicon('arrow-left')?></button>
                    <button :class="'mpop-button' + (p == userSearch.page ? ' mpop-page-selected' : '')" v-for="p in pageButtons" @click="changeUserSearchPage(p)">{{p}}</button>
                    <button class="mpop-button" @click="changeUserSearchPage(userSearch.page +1)" v-if="userSearch.page != foundUsersPageTotal && !pageButtons.includes(userSearch.page +1)" style="padding:1px"><?=$this->dashicon('arrow-right')?></button>
                    <button class="mpop-button" @click="changeUserSearchPage(foundUsersPageTotal)" v-if="userSearch.page != foundUsersPageTotal && !pageButtons.includes(foundUsersPageTotal) && userSearch.page +2 <= foundUsersPageTotal" style="width:auto">Fine</button>
                </div>
                <br>
                <q-table
                    :rows-per-page-options="[0]"
                    :rows="foundUsers || []"
                    :columns="foundUsersColumns"
                    no-data-label="Nessun utente trovato"
                    hide-bottom
                    :loading="userSearching"
                    binary-state-sort
                    v-model:pagination="userSearchTablePagination"
                    @request="searchUsers"
                >
                    <template #body="props">
                        <q-tr :props="props" @click="()=>viewUser(props.row.ID)" class="mpop-click">
                            <q-td v-for="prop in foundUsersColumns" :key="prop.name">
                                <template v-if="prop.name == 'mpop_resp_zones'">
                                    <span v-html="showZones(props.row.mpop_resp_zones)"></span>
                                </template>
                                <template v-else>{{prop.format ? prop.format(props.row[prop.name]) : props.row[prop.name]}}</template>
                            </q-td>
                        </q-tr>
                    </template>
                </q-table>
            </div>
            <!--USER_VIEW-->
            <div v-if="selectedTab.name == 'userView'" id="mpop-user-view"><template v-if="userInView">
                <h3>{{userInView.ID}} - {{userInView.login}}</h3>
                <template v-if="profile.role == 'administrator'">
                    <a :href="'/wp-admin/user-edit.php?user_id='+userInView.ID" target="_blank">Vedi in dashboard&nbsp;<?=$this::dashicon('external')?></a>
                    <br><br>
                </template>
                <template v-if="!userEditing">
                    <button class="mpop-button" @click="editUser">Modifica utente</button>
                </template>
                <template v-else>
                    <button class="mpop-button btn-error" @click="cancelEditUser" :disabled="saving">Annulla</button>
                    <button class="mpop-button btn-success" @click="updateUser" :disabled="!validUserForm || saving">Salva</button>
                </template>
                <table id="mpop-user-table">
                    <tr>
                        <td><strong>E-mail:</strong></td>
                        <td v-if="!userEditing">{{userInView.email}}<template v-if="userInView.mpop_mail_to_confirm"> - (Da confermare)</template><template v-if="userInView._new_email"><br>Da confermare: {{userInView._new_email}}</template></td>
                        <td v-else>
                            <input
                                type="email"
                                @input="() => {
                                    if (userInEditing.email == userInView.email) {
                                        userInEditing.mpop_mail_confirmed = true;
                                    } else if (userInEditing.emailOldValue == userInView.email) {
                                        userInEditing.mpop_mail_confirmed = false;
                                    }
                                    userInEditing.emailOldValue = userInEditing.email;
                                }"
                                :class="savingProfileErrors.includes('email') ? 'bad-input' : ''"
                                v-model="userInEditing.email"
                            />
                            <template v-if="userInView._new_email">&nbsp;<button class="mpop-button" style="margin-top:5px" @click="()=>{userInEditing.email = userInView.email; userInEditing.mpop_mail_confirmed = true;}">Ripristina</button></template></td>
                    </tr>
                    <tr v-if="userEditing">
                        <td><strong>Confermata:</strong></td>
                        <td><input type="checkbox" v-model="userInEditing.mpop_mail_confirmed"></td>
                    </tr>
                    <tr v-if="userInView.mpop_invited && !userEditing">
                        <td><strong>Utente invitato</strong></td>
                        <td><button class="mpop-button" @click="resendInvitationMail">Reinvia e-mail di invito</button></td>
                    </tr>
                    <tr>
                        <td><strong>Nome:</strong></td>
                        <td v-if="!userEditing">{{userInView.first_name}}</td>
                        <td v-else><input type="text" :class="savingProfileErrors.includes('first_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInEditing.first_name"/></td>
                    </tr>
                    <tr>
                        <td><strong>Cognome:</strong></td>
                        <td v-if="!userEditing">{{userInView.last_name}}</td>
                        <td v-else><input type="text" :class="savingProfileErrors.includes('last_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInEditing.last_name"/></td>
                    </tr>
                    <tr>
                        <td><strong>Ruolo:</strong></td>
                        <td>{{showRole(userInView.role)}}</td>
                    </tr>
                    <tr v-if="userInView.role == 'multipopolare_resp'">
                        <td><strong>Zone:</strong></td>
                        <td v-if="!userEditing || profile.role != 'administrator'">
                            <template v-if="userInView.mpop_resp_zones.length">
                                <ul>
                                    <li v-for="z in userInView.mpop_resp_zones">{{z.untouched_label + addSuppressToLabel(z)}}</li>
                                </ul>
                            </template>
                            <template v-else>
                                Nessuna zona assegnata
                            </template>
                        </td>
                        <td v-else>
                            <v-select
                                multiple
                                id="userEditingRespZone-select"
                                v-model="userInEditing.mpop_resp_zones"
                                :options="zoneSearch.mpop_resp"
                                @close="userEditingRespZoneOpen = false"
                                @open="searchOpen('userEditingRespZone')"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                :filter="fuseSearch"
                                @option:selected="zones => reduceZones(zones, userInEditing, 'mpop_resp_zones')"
                                @search="(searchTxt, loading) => {
                                    if (searchTxt.trim().length < 2) return loading(false);
                                    triggerSearch(searchTxt, loading, 'searchZones', 'mpop_resp', userInEditing, 'mpop_resp_zones');
                                }"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (userEditingRespZoneOpen ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                                <template v-slot:option="zone">
                                    {{zone.untouched_label + addSuppressToLabel(zone)}}
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
                        <td><strong>Data di nascita:</strong></td>
                        <td v-if="!userEditing">{{displayLocalDate(userInView.mpop_birthdate)}}</td>
                        <td v-else>
                            <input type="date"
                                :class="savingProfileErrors.includes('mpop_birthdate') ? 'bad-input' : ''"
                                min="1910-10-13" :max="maxBirthDate"
                                v-model="userInEditing.mpop_birthdate"
                                @change="()=> {if (!userInEditing.mpop_birthdate) userInEditing.mpop_birthplace = '';}"
                            />
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Nazione di nascita:</strong></td>
                        <td v-if="!userEditing">{{showCountryName(userInView.mpop_birthplace_country)}}</td>
                        <td v-else>
                            <v-select
                                id="birthplaceCountry-select"
                                :class="savingUserErrors.includes('mpop_birthplace_country') ? 'bad-input' : ''"
                                v-model="userInEditing.mpop_birthplace_country"
                                :options="countries"
                                @close="birthplaceCountryOpen = false"
                                @open="searchOpen('birthplaceCountry')"
                                label="name"
                                :reduce="c=>c.code"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (birthplaceCountryOpen || !userInEditing.mpop_birthplace_country ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                            </v-select>
                        </td>
                    </tr>
                    <tr v-if="(userEditing ? userInEditing : userInView).mpop_birthplace_country == 'ita'">
                        <td><strong>Comune di nascita:</strong></td>
                        <td v-if="!userEditing">{{userInView.mpop_birthplace ? (userInView.mpop_birthplace.nome + ' (' + userInView.mpop_birthplace.provincia.sigla +')' + addSuppressToLabel(userInView.mpop_birthplace) ) : ''}}</td>
                        <td v-else>
                            <v-select
                                id="birthplace-select"
                                :class="savingUserErrors.includes('mpop_birthplace') ? 'bad-input' : ''"
                                v-model="userInEditing.mpop_birthplace"
                                :options="birthCities"
                                :disabled="!userInEditing.mpop_birthdate"
                                @close="birthplaceOpen = false"
                                @open="searchOpen('birthplace')"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                :filter="fuseSearch"
                                @search="(searchTxt, loading) => {
                                    if (searchTxt.trim().length < 2) return loading(false);
                                    triggerSearch(searchTxt, loading, 'birthCitiesSearch', true);
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
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Nazione di residenza:</strong></td>
                        <td v-if="!userEditing">{{showCountryName(userInView.mpop_billing_country)}}</td>
                        <td v-else>
                            <v-select
                                id="billingCountry-select"
                                :class="savingUserErrors.includes('mpop_billing_country') ? 'bad-input' : ''"
                                v-model="userInEditing.mpop_billing_country"
                                :options="countries"
                                @close="billingCountryOpen = false"
                                @open="searchOpen('billingCountry')"
                                label="name"
                                :reduce="c=>c.code"
                            >
                                <template #search="{ attributes, events }">
                                    <input
                                        class="vs__search"
                                        :style="'display: ' + (billingCountryOpen || !userInEditing.mpop_billing_country ? 'unset' : 'none')"
                                        v-bind="attributes"
                                        v-on="events"
                                    />
                                </template>
                            </v-select>
                        </td>
                    </tr>
                    <template v-if="(userEditing ? userInEditing : userInView).mpop_billing_country == 'ita'">
                        <tr>
                            <td><strong>Comune di residenza:</strong></td>
                            <td v-if="!userEditing">{{ userInView.mpop_billing_city ? userInView.mpop_billing_city.nome + addSuppressToLabel(userInView.mpop_billing_city) : ''}}</td>
                            <td v-else>
                                <v-select
                                    id="billingCity-select"
                                    v-model="userInEditing.mpop_billing_city"
                                    :class="savingUserErrors.includes('mpop_billing_city') ? 'bad-input' : ''"
                                    :options="billingCities"
                                    @close="billingCityOpen = false"
                                    @open="searchOpen('billingCity')"
                                    :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
                                    :filter="fuseSearch"
                                    @option:selected="c => {
                                        userInEditing.mpop_billing_state = c.provincia.sigla;
                                        if (c.cap.length == 1) {
                                            userInEditing.mpop_billing_zip = c.cap[0];
                                        } else {
                                            userInEditing.mpop_billing_zip = '';
                                        }
                                    }"
                                    @option:deselected="() => {
                                        userInEditing.mpop_billing_state = '';
                                        userInEditing.mpop_billing_zip = '';
                                    }"
                                    @search="(searchTxt, loading) => {
                                        if (searchTxt.trim().length < 2) return loading(false);
                                        triggerSearch(searchTxt, loading, 'billingCitiesSearch');
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
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Provincia di residenza:</strong></td>
                            <td v-if="!userEditing">{{userInView.mpop_billing_state}}</td>
                            <td v-else>
                                <select v-model="userInEditing.mpop_billing_state" :class="savingUserErrors.includes('mpop_billing_state') ? 'bad-input' : ''" disabled>
                                    <option
                                        v-if="userInEditing.mpop_billing_city"
                                        :value="userInEditing.mpop_billing_city.provincia.sigla">{{userInEditing.mpop_billing_city.provincia.sigla}}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>CAP:</strong></td>
                            <td v-if="!userEditing">{{userInView.mpop_billing_zip}}</td>
                            <td v-else>
                                <select v-model="userInEditing.mpop_billing_zip" :class="savingUserErrors.includes('mpop_billing_zip') ? 'bad-input' : ''" :disabled="!userInEditing.mpop_billing_city || userInEditing.mpop_billing_city.cap.length == 1">
                                    <template v-if="userInEditing.mpop_billing_city">
                                        <option v-for="cap in userInEditing.mpop_billing_city.cap" :value="cap">{{cap}}</option>
                                    </template>
                                </select>
                            </td>
                        </tr>
                    </template>
                    <tr>
                        <td><strong>Indirizzo di residenza:</strong></td>
                        <td v-if="!userEditing">{{userInView.mpop_billing_address}}</td>
                        <td v-else><textarea v-model="userInEditing.mpop_billing_address" :class="savingUserErrors.includes('mpop_billing_address') ? 'bad-input' : ''" :disabled="!userInEditing.mpop_billing_zip"></textarea></td>
                    </tr>
                    <tr>
                        <td><strong>Telefono:</strong></td>
                        <td v-if="!userEditing">{{userInView.mpop_phone}}</td>
                        <td v-else>
                            <v-intl-phone
                                ref="userEditPhoneInput"
                                :options="{initialCountry: 'it'}"
                                :value="userInView.mpop_phone || ''"
                                :class="savingUserErrors.includes('mpop_phone') ? 'bad-input' : ''"
                                @change-number="()=>userInEditing.mpop_phone = parsePhone(userEditPhoneInput)"
                                @change-country="()=>userInEditing.mpop_phone = parsePhone(userEditPhoneInput)"
                            ></v-intl-phone>
                        </td>
                    </tr>
                    <tr v-if="profile.role == 'administrator' && profile.mpop_has_master_key && ['administrator', 'multipopolare_resp'].includes(userInView.role)">
                        <td><strong>Master key:</strong></td>
                        <td>{{userInView.mpop_has_master_key ? 'Impostata': 'Non impostata'}}</td>
                    </tr>
                </table>
            </template></div>
            <!--UPLOAD_USER_CSV-->
            <div v-if="selectedTab.name == 'uploadUserCsv'">
                <input type="file" @change="loadUsersFromCsv" :disabled="saving">
                <q-table
                    :rows-per-page-options="[0]"
                    :rows="csvUsers || []"
                    :columns="userCsvFields"
                    no-data-label="Nessun utente trovato"
                    hide-bottom
                    :pagination="{page:1,rowsPerPage:0}"
                ></q-table>
                <div class="q-gutter-sm" style="padding-top: 5px">
                    <button class="mpop-button" @click="uploadCsvRows" :disabled="saving || !csvUsers.length || !!csvUsers[0].esito">Carica righe</button>
                    <q-checkbox left-label v-model="csvImportOptions.forceYear" label="Forza anno"></q-checkbox>
                    <q-checkbox left-label v-model="csvImportOptions.forceQuote" label="Forza valore quota"></q-checkbox>
                    <q-checkbox left-label v-model="csvImportOptions.delayedSend" label="Invio ritardato (un invio ogni 5 sec.)"></q-checkbox>
                </div>
            </div>
            </q-page>
        </q-page-container>
        </q-layout>
    </div>
</div>
<?php wp_nonce_field( 'mpop-logged-myaccount', 'mpop-logged-myaccount-nonce' ); ?>
<script type="application/json" id="__MULTIPOP_DATA__">{
    "user": <?=json_encode($parsed_user)?>
}</script>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/logged-myaccount.js"></script>
<?php