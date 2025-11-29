<?php
defined( 'ABSPATH' ) || exit;
?>
<h3>Ciao {{helloName}}</h3>
<q-expansion-item
    v-model="regInstructionOpen"
    icon="info"
    label="Istruzioni tesseramento"
    class="mpop-instructions"
    >
    <q-card>
        <q-card-section>
        Per proseguire nell'iscrizione, completa il tuo profilo (se non l'hai già fatto) e continua la procedura seguendo le istruzioni nel menù <i class="mpop-click" @click="selectTab({name: 'card', label: 'Tesseramento'})">Tesseramento</i>.
        </q-card-section>
    </q-card>
</q-expansion-item>
<template v-if="!profileEditing">
    <button class="mpop-button" @click="editProfile">Modifica profilo</button>
</template>
<table id="mpop-profile-table">
    <tr>
        <td><strong>ID Tesserato:</strong></td>
        <td>{{profile.ID}}</td>
    </tr>
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
    <tr v-if="profile.role == 'multipopolare_resp'">
        <td><strong>Zone:</strong></td>
        <td v-html="showZones(profile.mpop_resp_zones)"></td>
    </tr>
    <tr>
        <td><strong>Tessera attiva:</strong></td>
        <td>{{profile.mpop_card_active ? 'Sì' : 'No'}}</td>
    </tr>
    <tr v-if="['administrator', 'multipopolare_resp'].includes(profile.role) && (profile.mpop_old_card_number || profileEditing)">
        <td><strong>Tessera cartacea (vecchia numerazione):</strong></td>
        <td v-if="!profileEditing">{{profile.mpop_old_card_number}}</td>
        <td v-else>
            <input type="text" style="text-transform: uppercase" v-model="profileInEditing.mpop_old_card_number"/>
        </td>
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
            <mpop-select
                id="birthplaceCountry-select"
                :class="savingProfileErrors.includes('mpop_birthplace_country') ? 'bad-input' : ''"
                v-model="profileInEditing.mpop_birthplace_country"
                :options="countries"
                label="name"
                :reduce="c=>c.code"
            >
            </mpop-select>
        </td>
    </tr>
    <tr v-if="(profileEditing ? profileInEditing : profile).mpop_birthplace_country == 'ita'">
        <td><strong>Comune di nascita:</strong></td>
        <td v-if="!profileEditing">{{profile.mpop_birthplace ? (profile.mpop_birthplace.nome + ' (' + profile.mpop_birthplace.provincia.sigla +')' + addSuppressToLabel(profile.mpop_birthplace) ) : ''}}</td>
        <td v-else>
            <mpop-select
                fuse-search
                :minLen="2"
                id="birthplace-select"
                :class="savingProfileErrors.includes('mpop_birthplace') ? 'bad-input' : ''"
                v-model="profileInEditing.mpop_birthplace"
                :options="birthCities"
                :disabled="!profileInEditing.mpop_birthdate"
                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'birthCitiesSearch')"
            >
                <template v-slot:option="city">
                    {{city.untouched_label + addSuppressToLabel(city)}}
                </template>
            </mpop-select>
        </td>
    </tr>
    <tr>
        <td><strong>Nazione di residenza:</strong></td>
        <td v-if="!profileEditing">{{showCountryName(profile.mpop_billing_country)}}</td>
        <td v-else>
            <mpop-select
                id="billingCountry-select"
                :class="savingProfileErrors.includes('mpop_billing_country') ? 'bad-input' : ''"
                v-model="profileInEditing.mpop_billing_country"
                :options="countries"
                label="name"
                :reduce="c=>c.code"
            >
            </mpop-select>
        </td>
    </tr>
    <template v-if="(profileEditing ? profileInEditing : profile).mpop_billing_country == 'ita'">
        <tr>
            <td><strong>Comune di residenza:</strong></td>
            <td v-if="!profileEditing">{{ profile.mpop_billing_city ? profile.mpop_billing_city.nome + addSuppressToLabel(profile.mpop_billing_city) : ''}}</td>
            <td v-else>
                <mpop-select
                    fuse-search
                    :minLen="2"
                    id="billingCity-select"
                    v-model="profileInEditing.mpop_billing_city"
                    :class="savingProfileErrors.includes('mpop_billing_city') ? 'bad-input' : ''"
                    :options="billingCities"
                    :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
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
                        <option v-for="cap in profileInEditing.mpop_billing_city.cap" :key="cap" :value="cap">{{cap}}</option>
                    </template>
                </select>
            </td>
        </tr>
    </template>
    <tr>
        <td><strong>Indirizzo di residenza:</strong></td>
        <td v-if="!profileEditing">{{profile.mpop_billing_address}}</td>
        <td v-else><textarea v-model="profileInEditing.mpop_billing_address" :class="savingProfileErrors.includes('mpop_billing_address') ? 'bad-input' : ''" :disabled="!profileInEditing.mpop_billing_country || profileInEditing.mpop_billing_country == 'ita' && !profileInEditing.mpop_billing_zip"></textarea></td>
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
<template v-if="profileEditing">
    <button class="mpop-button btn-error" @click="cancelEditProfile" :disabled="saving">Annulla</button>
    <button class="mpop-button btn-success" @click="updateProfile" :disabled="!validProfileForm || saving">Salva</button>
</template>
<template v-if="!profileEditing && profile.mpop_profile_pending_edits">
    <hr>
    <h3 class="text-h3">Modifiche in attesa di conferma</h3>
    <ul>
        <li v-for="(v, k) in profile.mpop_profile_pending_edits">{{showPendingEdit(k, v)}}</li>
    </ul>
    <button class="mpop-button btn-error" @click="cancelProfilePendingEdits" :disabled="saving">Annulla modifiche</button>
</template>
<q-table
    v-if="profile.role == 'administrator'"
    title="Richieste"
    :rows="profile.mpop_my_subscriptions || []"
    :columns="subscriptionColumns"
    row-key="id"
    :pagination="{page:1,rowsPerPage:0}"
    hide-bottom
    @row-click="(e,row) => viewSub(row.id)"
>
</q-table>