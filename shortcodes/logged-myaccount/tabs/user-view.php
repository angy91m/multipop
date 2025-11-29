<?php
defined( 'ABSPATH' ) || exit;
?>
<template v-if="userInView">
    <h3>{{userInView.ID}} - {{userInView.login}}</h3>
    <template v-if="profile.role == 'administrator'">
        <a :href="'/wp-admin/user-edit.php?user_id='+userInView.ID" target="_blank">Vedi in dashboard&nbsp;<?=$this::dashicon('external')?></a>
        <br><br>
    </template>
    <template v-if="!userEditing && userInView.is_editable">
        <button class="mpop-button" @click="editUser">Modifica utente</button>
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
                    :class="savingUserErrors.includes('email') ? 'bad-input' : ''"
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
            <td v-else><input type="text" :class="savingUserErrors.includes('first_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInEditing.first_name"/></td>
        </tr>
        <tr>
            <td><strong>Cognome:</strong></td>
            <td v-if="!userEditing">{{userInView.last_name}}</td>
            <td v-else><input type="text" :class="savingUserErrors.includes('last_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInEditing.last_name"/></td>
        </tr>
        <tr>
            <td><strong>Ruolo:</strong></td>
            <td>{{showRole(userInView.role)}}</td>
        </tr>
        <tr>
            <td><strong>Tessera attiva:</strong></td>
            <td>{{userInView.mpop_card_active ? 'Sì' : 'No'}}</td>
        </tr>
        <tr v-if="['administrator', 'multipopolare_resp'].includes(profile.role) && (userInView.mpop_old_card_number || userEditing)">
            <td><strong>Tessera cartacea (vecchia numerazione):</strong></td>
            <td v-if="!userEditing">{{userInView.mpop_old_card_number}}</td>
            <td v-else>
                <input type="text" style="text-transform: uppercase" v-model="userInEditing.mpop_old_card_number"/>
            </td>
        </tr>
        <tr v-if="userInView.role == 'multipopolare_resp'">
            <td><strong>Zone:</strong></td>
            <td v-if="!userEditing || profile.role != 'administrator'">
                <template v-if="userInView.mpop_resp_zones.length">
                    <ul>
                        <li v-for="(z, index) in userInView.mpop_resp_zones" :key="index">{{z.untouched_label + addSuppressToLabel(z)}}</li>
                    </ul>
                </template>
                <template v-else>
                    Nessuna zona assegnata
                </template>
            </td>
            <td v-else>
                <mpop-select
                    multiple
                    fuse-search
                    :minLen="2"
                    id="userEditingRespZone-select"
                    v-model="userInEditing.mpop_resp_zones"
                    :options="zoneSearch.mpop_resp"
                    :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                    @option:selected="zones => reduceZones(zones, userInEditing, 'mpop_resp_zones')"
                    @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'searchZones', 'mpop_resp', userInEditing, 'mpop_resp_zones')"
                >
                    <template v-slot:option="zone">
                        {{zone.untouched_label + addSuppressToLabel(zone)}}
                    </template>
                </mpop-select>
            </td>
        </tr>
        <tr>
            <td><strong>Data di nascita:</strong></td>
            <td v-if="!userEditing">{{displayLocalDate(userInView.mpop_birthdate)}}</td>
            <td v-else>
                <input type="date"
                    :class="savingUserErrors.includes('mpop_birthdate') ? 'bad-input' : ''"
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
                <mpop-select
                    id="birthplaceCountry-select"
                    :class="savingUserErrors.includes('mpop_birthplace_country') ? 'bad-input' : ''"
                    v-model="userInEditing.mpop_birthplace_country"
                    :options="countries"
                    label="name"
                    :reduce="c=>c.code"
                >
                </mpop-select>
            </td>
        </tr>
        <tr v-if="(userEditing ? userInEditing : userInView).mpop_birthplace_country == 'ita'">
            <td><strong>Comune di nascita:</strong></td>
            <td v-if="!userEditing">{{userInView.mpop_birthplace ? (userInView.mpop_birthplace.nome + ' (' + userInView.mpop_birthplace.provincia.sigla +')' + addSuppressToLabel(userInView.mpop_birthplace) ) : ''}}</td>
            <td v-else>
                <mpop-select
                    fuse-search
                    :minLen="2"
                    id="birthplace-select"
                    :class="savingUserErrors.includes('mpop_birthplace') ? 'bad-input' : ''"
                    v-model="userInEditing.mpop_birthplace"
                    :options="birthCities"
                    :disabled="!userInEditing.mpop_birthdate"
                    :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                    @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'birthCitiesSearch', true)"
                >
                    <template v-slot:option="city">
                        {{city.untouched_label + addSuppressToLabel(city)}}
                    </template>
                </mpop-select>
            </td>
        </tr>
        <tr>
            <td><strong>Nazione di residenza:</strong></td>
            <td v-if="!userEditing">{{showCountryName(userInView.mpop_billing_country)}}</td>
            <td v-else>
                <mpop-select
                    id="billingCountry-select"
                    :class="savingUserErrors.includes('mpop_billing_country') ? 'bad-input' : ''"
                    v-model="userInEditing.mpop_billing_country"
                    :options="countries"
                    label="name"
                    :reduce="c=>c.code"
                >
                </mpop-select>
            </td>
        </tr>
        <template v-if="(userEditing ? userInEditing : userInView).mpop_billing_country == 'ita'">
            <tr>
                <td><strong>Comune di residenza:</strong></td>
                <td v-if="!userEditing">{{ userInView.mpop_billing_city ? userInView.mpop_billing_city.nome + addSuppressToLabel(userInView.mpop_billing_city) : ''}}</td>
                <td v-else>
                    <mpop-select
                        fuse-search
                        :minLen="2"
                        id="billingCity-select"
                        v-model="userInEditing.mpop_billing_city"
                        :class="savingUserErrors.includes('mpop_billing_city') ? 'bad-input' : ''"
                        :options="billingCities"
                        :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
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
                        @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'billingCitiesSearch')"
                    >
                        <template v-slot:option="city">
                            {{city.nome}} ({{city.provincia.sigla}}){{addSuppressToLabel(city)}}
                        </template>
                    </mpop-select>
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
                            <option v-for="cap in userInEditing.mpop_billing_city.cap" :key="cap" :value="cap">{{cap}}</option>
                        </template>
                    </select>
                </td>
            </tr>
        </template>
        <tr>
            <td><strong>Indirizzo di residenza:</strong></td>
            <td v-if="!userEditing">{{userInView.mpop_billing_address}}</td>
            <td v-else><textarea v-model="userInEditing.mpop_billing_address" :class="savingUserErrors.includes('mpop_billing_address') ? 'bad-input' : ''" :disabled="!userInEditing.mpop_billing_country || userInEditing.mpop_billing_country == 'ita' && !userInEditing.mpop_billing_zip"></textarea></td>
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
    <template v-if="userEditing">
        <button class="mpop-button btn-error" @click="cancelEditUser" :disabled="saving">Annulla</button>
        <button class="mpop-button btn-success" @click="updateUser" :disabled="!validUserForm || saving">Salva</button>
    </template>
    <template v-if="!userEditing && userInView.mpop_profile_pending_edits">
        <hr>
        <h3 class="text-h3">Modifiche in attesa di conferma</h3>
        <ul>
            <li v-for="(v, k) in userInView.mpop_profile_pending_edits">{{showPendingEdit(k, v)}}</li>
        </ul>
        <button @click="confirmUserPendingEdits" class="mpop-button btn-success" :disabled="saving">Conferma le modifiche</button>
        <button @click="refuseUserPendingEdits" class="mpop-button btn-error" :disabled="saving">Rifiuta le modifiche</button>
    </template>
    <hr>
    <q-table
        title="Richieste"
        :rows="userInView.mpop_my_subscriptions || []"
        :columns="subscriptionColumns"
        row-key="id"
        :pagination="{page:1,rowsPerPage:0}"
        hide-bottom
        @row-click="(e,row) => viewSub(row.id)"
    >
        <template v-slot:top>
            <h5 class="text-h5">Richieste</h5>&nbsp;&nbsp;
            <q-btn v-if="userAvailableYearsToOrder.length && userInView.is_editable" color="primary" icon="add" @click="subAddBegin"></q-btn>
        </template>
    </q-table>
</template>