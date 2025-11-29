<?php
defined( 'ABSPATH' ) || exit;
?>
<h3>Nuovo tesserato</h3>
<table id="mpop-user-table">
    <tr>
        <td><strong>E-mail:</strong></td>
        <td>
            <input
                type="email"
                :class="savingUserAddErrors.includes('email') ? 'bad-input' : ''"
                v-model="userInAdd.email"
            />
        </td>
    </tr>
    <tr>
        <td><strong>Nome:</strong></td>
        <td><input type="text" :class="savingUserAddErrors.includes('first_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInAdd.first_name"/></td>
    </tr>
    <tr>
        <td><strong>Cognome:</strong></td>
        <td><input type="text" :class="savingUserAddErrors.includes('last_name') ? 'bad-input' : ''" style="text-transform: uppercase" v-model="userInAdd.last_name"/></td>
    </tr>
    <tr>
        <td><strong>Data di nascita:</strong></td>
        <td >
            <input type="date"
                :class="savingUserAddErrors.includes('mpop_birthdate') ? 'bad-input' : ''"
                min="1910-10-13" :max="maxBirthDate"
                v-model="userInAdd.mpop_birthdate"
                @change="()=> {if (!userInAdd.mpop_birthdate) userInAdd.mpop_birthplace = '';}"
            />
        </td>
    </tr>
    <tr>
        <td><strong>Nazione di nascita:</strong></td>
        <td>
            <mpop-select
                id="birthplaceCountry-select"
                :class="savingUserAddErrors.includes('mpop_birthplace_country') ? 'bad-input' : ''"
                v-model="userInAdd.mpop_birthplace_country"
                :options="countries"
                label="name"
                :reduce="c=>c.code"
            >
            </mpop-select>
        </td>
    </tr>
    <tr v-if="userInAdd.mpop_birthplace_country == 'ita'">
        <td><strong>Comune di nascita:</strong></td>
        <td>
            <mpop-select
                fuse-search
                :minLen="2"
                id="birthplace-select"
                :class="savingUserAddErrors.includes('mpop_birthplace') ? 'bad-input' : ''"
                v-model="userInAdd.mpop_birthplace"
                :options="birthCities"
                :disabled="!userInAdd.mpop_birthdate"
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
        <td>
            <mpop-select
                id="billingCountry-select"
                :class="savingUserAddErrors.includes('mpop_billing_country') ? 'bad-input' : ''"
                v-model="userInAdd.mpop_billing_country"
                :options="countries"
                label="name"
                :reduce="c=>c.code"
            >
            </mpop-select>
        </td>
    </tr>
    <template v-if="userInAdd.mpop_billing_country == 'ita'">
        <tr>
            <td><strong>Comune di residenza:</strong></td>
            <td>
                <mpop-select
                    fuse-search
                    :minLen="2"
                    id="billingCity-select"
                    v-model="userInAdd.mpop_billing_city"
                    :class="savingUserAddErrors.includes('mpop_billing_city') ? 'bad-input' : ''"
                    :options="billingCities"
                    :get-option-label="(option) => option.nome + addSuppressToLabel(option)"
                    @option:selected="c => {
                        userInAdd.mpop_billing_state = c.provincia.sigla;
                        if (c.cap.length == 1) {
                            userInAdd.mpop_billing_zip = c.cap[0];
                        } else {
                            userInAdd.mpop_billing_zip = '';
                        }
                    }"
                    @option:deselected="() => {
                        userInAdd.mpop_billing_state = '';
                        userInAdd.mpop_billing_zip = '';
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
            <td>
                <select v-model="userInAdd.mpop_billing_state" :class="savingUserAddErrors.includes('mpop_billing_state') ? 'bad-input' : ''" disabled>
                    <option
                        v-if="userInAdd.mpop_billing_city"
                        :value="userInAdd.mpop_billing_city.provincia.sigla">{{userInAdd.mpop_billing_city.provincia.sigla}}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><strong>CAP:</strong></td>
            <td>
                <select v-model="userInAdd.mpop_billing_zip" :class="savingUserAddErrors.includes('mpop_billing_zip') ? 'bad-input' : ''" :disabled="!userInAdd.mpop_billing_city || userInAdd.mpop_billing_city.cap.length == 1">
                    <template v-if="userInAdd.mpop_billing_city">
                        <option v-for="cap in userInAdd.mpop_billing_city.cap" :key="cap" :value="cap">{{cap}}</option>
                    </template>
                </select>
            </td>
        </tr>
    </template>
    <tr>
        <td><strong>Indirizzo di residenza:</strong></td>
        <td><textarea v-model="userInAdd.mpop_billing_address" :class="savingUserAddErrors.includes('mpop_billing_address') ? 'bad-input' : ''" :disabled="!userInAdd.mpop_billing_country || userInAdd.mpop_billing_country == 'ita' && !userInAdd.mpop_billing_zip"></textarea></td>
    </tr>
    <tr>
        <td><strong>Telefono:</strong></td>
        <td>
            <v-intl-phone
                ref="userAddPhoneInput"
                :options="{initialCountry: 'it'}"
                :value="userInAdd.mpop_phone || ''"
                :class="savingUserAddErrors.includes('mpop_phone') ? 'bad-input' : ''"
                @change-number="()=>userInAdd.mpop_phone = parsePhone(userAddPhoneInput)"
                @change-country="()=>userInAdd.mpop_phone = parsePhone(userAddPhoneInput)"
            ></v-intl-phone>
        </td>
    </tr>
</table>
<button class="mpop-button btn-success" @click="addUser" :disabled="!validUserAddForm || saving">Aggiungi</button>