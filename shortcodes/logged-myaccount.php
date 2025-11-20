<?php
defined( 'ABSPATH' ) || exit;
if (
    !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
    && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
    && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
    require('post/logged-myaccount.php');
    exit;
}
$parsed_user = $this->myaccount_get_profile($current_user, true, true);
$discourse_url = null;
if ($this->discourse_utilities()) {
    $discourse_connect_options = get_option('discourse_connect');
    if (is_array($discourse_connect_options) && isset($discourse_connect_options['url']) && $discourse_connect_options['url']) {
        $discourse_url = $discourse_connect_options['url'] . '/login';
    }
}
?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-tel-input.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/logged-myaccount.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/fonts.css">
<?php
if ($this->settings['pp_client_id']) {
    ?>
    <script src="<?=$this->settings['pp_url']?>/sdk/js?client-id=<?=$this->settings['pp_client_id']?>&currency=EUR"></script>
    <?php
}
?>
<div id="loaded-scripts" style="display:none"></div>
<div id="app">
    <div style="display:none">
        <v-intl-phone
            ref="intPhoneInstance"
            :options="{initialCountry: 'it'}"
        ></v-intl-phone>
    </div>
    <span v-for="(notice, noticeInd) in userNotices" :class="'mpop-app-notice' + ' notice-' + notice.type"><span @click="dismissNotice(noticeInd)"><?=$this::dashicon('no-alt')?></span><span style="font-size:13px" v-html="notice.msg"></span></span>
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
                        <q-item v-if="!menuItem.admin && !menuItem.resp" clickable @click="if(menuItem.url) {openExternalUrl(menuItem.url);} else {selectTab(menuItem);}" :active="menuItem.name === selectedTab.name" v-ripple>
                            <q-item-section avatar>
                            </q-item-section>
                            <q-item-section>
                            {{ menuItem.label }}
                            </q-item-section>
                        </q-item>
                    </template>
                    <template v-if="['administrator', 'multipopolare_resp'].includes(profile.role)">
                        <q-separator></q-separator>
                        <template v-for="(menuItem, index) in menuItems" :key="index">
                            <q-item v-if="menuItem.resp" clickable @click="selectTab(menuItem)" :active="menuItem.name === selectedTab.name" v-ripple>
                                <q-item-section avatar>
                                </q-item-section>
                                <q-item-section>
                                {{ menuItem.label }}
                                </q-item-section>
                            </q-item>
                        </template>
                    </template>
                    <template v-if="profile.role == 'administrator'">
                        <template v-for="(menuItem, index) in menuItems" :key="index">
                            <q-item v-if="menuItem.admin" clickable @click="selectTab(menuItem)" :active="menuItem.name === selectedTab.name" v-ripple>
                                <q-item-section avatar>
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
            </div>
            <div v-if="selectedTab.name == 'passwordChange'">
                <button class="mpop-button" :disabled="pwdChanging ||pwdChangeErrors.length || !pwdChangeFields.current" @click="changePassword">Cambia password</button>
                <div id="mpop-passwordChange">
                    <input v-model="pwdChangeFields.current" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('current') ? 'bad-input' : ''" type="password" placeholder="Password attuale"/>
                    <input v-model="pwdChangeFields.new" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('new') ? 'bad-input' : ''" type="password" placeholder="Nuova password"/>
                    <input v-model="pwdChangeFields.confirm" @input="staticPwdErrors.length = 0" :class="pwdChangeErrors.includes('confirm') ? 'bad-input' : ''" type="password" placeholder="Conferma"/>
                </div>
            </div>
            <div v-if="selectedTab.name == 'masterkeyChange'">
                <template v-if="['administrator', 'multipopolare_resp'].includes(profile.role) && profile.mpop_has_master_key">
                    <button class="mpop-button" :disabled="masterkeyChanging || mkChangeErrors.length || !mkChangeFields.current" @click="changeMk">Cambia master key</button>
                    <div id="mpop-mkChange">
                        <input v-model="mkChangeFields.current" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('current') ? 'bad-input' : ''" type="password" placeholder="Master key attuale"/>
                        <input v-model="mkChangeFields.new" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('new') ? 'bad-input' : ''" type="password" placeholder="Nuova master key"/>
                        <input v-model="mkChangeFields.confirm" @input="staticMkErrors.length = 0" :class="mkChangeErrors.includes('confirm') ? 'bad-input' : ''" type="password" placeholder="Conferma master key"/>
                    </div>
                </template>
                <template v-else>
                    <div>
                        Nessuna master key impostata
                    </div>
                </template>
            </div>
            <!--CARD-->
            <div v-if="selectedTab.name == 'card'">
                <template v-if="profile.mpop_my_subscriptions">
                    <h5 class="text-h5" v-if="profile.mpop_card_active">La tua tessera è attiva!</h5>
                    <div>
                        <template v-if="nearActiveSub">
                            <h6 class="text-h6">ID richiesta: {{nearActiveSub.id}}</h6>
                            <ul>
                                <li>ID tesserato: {{profile.ID}}</li>
                                <li>Stato attivazione: {{userSearchSelectableSubStatuses.find(s => s.value == nearActiveSub.status).label}}</li>
                                <li>Anno: {{nearActiveSub.year}}</li>
                                <li>Quota annuale: {{currencyFormatter.custFormat(nearActiveSub.quote)}}</li>
                                <!-- <button v-if="nearActiveSub.status == 'open'" class="mpop-button" @click="generateSubscriptionPdf(nearActiveSub.id)">Genera modulo iscrizione</button> -->
                                <button v-if="nearActiveSub.status == 'open'" class="mpop-button" @click="moduleUploadBegin(nearActiveSub)">Carica modulo</button>
                                <button v-if="!['canceled', 'completed', 'refused'].includes(nearActiveSub.status)" class="mpop-button btn-error" @click="profileSubCancel(nearActiveSub)">Annulla richiesta</button>
                                <template v-if="nearActiveSub.status == 'seen'">
                                    <li><strong>Pagamento con bonifico</strong><br>
                                        Per pagare con bonifico, dopo aver effettuato il pagamento, invia una e-mail dall'indirizzo registrato sul sito a <?=$this->settings['mail_from']?> con gli eventuali riferimenti e scansione della ricevuta.
                                    </li>
                                    <?php
                                    if ($this->settings['pp_client_id']) { ?>
                                        <li><strong>Pagamento con PayPal</strong><br>
                                            <mpop-pp-btn :subscription="nearActiveSub" :options="paypalOptions"></mpop-pp-btn>
                                        </li>
                                    <?php
                                    } ?>
                                </template>
                            </ul>
                        </template>
                        <div v-if="availableYearsToOrder.length" id="mpop-avail-years-to-order">
                            <hr v-if="nearActiveSub">
                            <template v-if="isProfileCompleted">
                                <h5 class="text-h5">Richiesta tessera</h5>
                                <q-expansion-item
                                    v-model="subInstructionOpen"
                                    icon="info"
                                    label="Istruzioni tesseramento"
                                    class="mpop-instructions"
                                    >
                                    <q-card>
                                        <q-card-section>
                                        Per proseguire nell'iscrizione, scarica il modulo tramite il pulsante GENERA MODULO ISCRIZIONE, firmalo (sono necessarie 4 firme) e torna qui per caricarlo cliccando su CARICA MODULO. Insieme al modulo potrebbe essere richiesto il caricamento di un documento di identità.
                                        </q-card-section>
                                    </q-card>
                                </q-expansion-item>
                                <p>
                                    Richiedi la tua tessera per l'anno:&nbsp;
                                    <select v-model="newSubscription.year">
                                        <option v-for="y in availableYearsToOrder" :key="y" :value="y">{{y}}</option>
                                    </select>
                                </p>
                                <p>
                                    Quota annuale:&nbsp;&nbsp;€&nbsp;
                                    <input type="number" :min="mainOptions.authorizedSubscriptionQuote" step=".01" v-model="newSubscription.quote" />
                                </p>
                                <p>
                                    <strong>Consensi facoltativi</strong>
                                </p>
                                <p>
                                    <label><u class="mpop-click" @click="e => {e.preventDefault(); marketingAgreeShow = !marketingAgreeShow}">Accetto le condizioni marketing</u>&nbsp;
                                        <input type="checkbox" v-model="newSubscription.mpop_marketing_agree"/>
                                    </label>
                                </p>
                                <p v-show="marketingAgreeShow">
                                    <button class="mpop-button" @click="marketingAgreeShow = false">Chiudi</button><br>
                                    <span v-html="mainOptions.policies.marketing || ''"></span>
                                </p>
                                <p>
                                    <label><u class="mpop-click" @click="e => {e.preventDefault(); newsletterAgreeShow = !newsletterAgreeShow}">Accetto le condizioni della newsletter</u>&nbsp;
                                        <input type="checkbox" v-model="newSubscription.mpop_newsletter_agree"/>
                                    </label>
                                </p>
                                <p v-show="newsletterAgreeShow">
                                    <button class="mpop-button" @click="newsletterAgreeShow = false">Chiudi</button><br>
                                    <span v-html="mainOptions.policies.newsletter || ''"></span>
                                </p>
                                <p>
                                    <label><u class="mpop-click" @click="e => {e.preventDefault(); publishAgreeShow = !publishAgreeShow}">Accetto le condizioni di pubblicazione</u>&nbsp;
                                        <input type="checkbox" v-model="newSubscription.mpop_publish_agree"/>
                                    </label>
                                </p>
                                <p v-show="publishAgreeShow">
                                    <button class="mpop-button" @click="publishAgreeShow = false">Chiudi</button><br>
                                    <span v-html="mainOptions.policies.publish || ''"></span>
                                </p>
                                <button class="mpop-button" :disabled="saving" @click="requestNewSubscription">Richiedi</button>
                            </template>
                            <p v-else>Per richiedere una nuova tessera è necessario completare i tuoi dati del profilo</p>
                        </div>
                        <div v-if="!nearActiveSub && !availableYearsToOrder.length" id="mpop-avail-years-to-order">
                            <p>Al momento non è possibile richiedere nuove tessere</p>
                        </div>
                        <template v-if="otherSubscriptions.length">
                            <hr>
                            <q-table
                                title="Altre richieste"
                                :rows="otherSubscriptions"
                                :columns="subscriptionColumns"
                                row-key="id"
                                :pagination="{page:1,rowsPerPage:0}"
                                hide-bottom
                            >
                                <template v-slot:body-cell="props">
                                    <q-td :props="props">
                                        {{props.value}}
                                        <template v-if="props.col.name == 'status' && props.row.status == 'open'">
                                            <br>
                                            <!-- <q-btn
                                                dense
                                                color="primary"
                                                size="sm"
                                                label="Genera modulo"
                                                @click="generateSubscriptionPdf(props.row.id)"
                                                style="margin-bottom: 2px;"
                                            ></q-btn><br> -->
                                            <q-btn
                                                dense
                                                color="primary"
                                                size="sm"
                                                label="Carica modulo"
                                                @click="moduleUploadBegin(props.row)"
                                                style="margin-bottom: 2px;"
                                            ></q-btn>
                                        </template>
                                        <template v-if="props.col.name == 'status' && !['canceled', 'refused', 'completed'].includes(props.row.status)">
                                            <br>
                                            <q-btn
                                                dense
                                                color="negative"
                                                size="sm"
                                                label="Annulla"
                                                @click="profileSubCancel(props.row)"
                                                style="margin-bottom: 2px;"
                                            ></q-btn>
                                        </template>
                                        <template v-if="props.col.name == 'status' && props.row.status == 'seen'">
                                            <br>
                                            <?php
                                            if ($this->settings['pp_client_id']) { ?>
                                                <div><mpop-pp-btn style="max-width: 150px; margin-right: 0; margin-left: auto;" :subscription="props.row" :options="paypalOptions"></mpop-pp-btn></div>
                                            <?php
                                            } ?>
                                        </template>
                                    </q-td>
                                </template>
                            </q-table>
                        </template>
                    </div>
                </template>
            </div>
            <!--MODULE_UPLOAD-->
            <div v-if="selectedTab.name == 'moduleUpload' && moduleUploadData.sub">
                <q-stepper
                    v-if="moduleUploadData.sub.status == 'open'"
                    v-model="moduleUploadData.step"
                    vertical
                    color="primary"
                    animated
                >
                    <q-step
                        :name="1"
                        title="Firma o Carica il modulo firmato"
                        icon="upload_file"
                        :done="moduleUploadData.step > 1"
                    >
                        <div>
                            <label><input type="radio" v-model="moduleUploadData.withSignature" :value="true"/>&nbsp;Firmo dal mio dispositivo</label>
                            <br>
                            <label><input type="radio" v-model="moduleUploadData.withSignature" :value="false"/>&nbsp;Scarico il modulo PDF e lo ricarico firmato</label>
                        </div>
                        <template v-if="moduleUploadData.withSignature">
                            <mpop-sig-pad ref="moduleSigPad" width="600" height="200"></mpop-sig-pad>
                        </template>
                        <template v-if="moduleUploadData.withSignature === false">
                            <q-btn
                                dense
                                color="primary"
                                label="Genera modulo"
                                @click="generateSubscriptionPdf(moduleUploadData.sub.id)"
                                style="margin-bottom: 2px;"
                            ></q-btn><br><br>
                            <template v-if="moduleUploadData.signedModuleFiles.length">
                                <div v-for="(f, k) in moduleUploadData.signedModuleFiles" :key="k">
                                    - {{f.name}}&nbsp;&nbsp;<button @click="() => moduleUploadData.signedModuleFiles.splice(k, 1)">Rimuovi</button>
                                    <br>
                                    <iframe v-if="f.type == 'application/pdf'" :src="f.content" style="width:100%; max-height:250px;"></iframe>
                                    <image v-if="f.type != 'application/pdf'" :src="f.content" style="max-height:250px;" />
                                </div>
                            </template>
                            <div v-if="!moduleUploadData.signedModuleFiles.length">Nessun file selezionato</div>
                            <mpop-uploader 
                                v-model="moduleUploadData.signedModuleFiles"
                                :accepted-mime="['application/pdf', 'image/jpeg', 'image/png']"
                                :formatter="v => {const f = {content: v.content, name: v.meta.name, type: v.meta.type }; return f;}"
                                @invalid-mime="onInvalidMime"
                                :disabled="moduleUploadData.signedModuleFiles.length == 2"
                            >Seleziona file da caricare</mpop-uploader>
                        </template>
                        <br><br><button :disabled="!moduleUploadData.signedModuleFiles.length" @click="()=>moduleUploadData.step+= (isValidIdCard ? 2 : 1)">Avanti</button>
                    </q-step>
                    <q-step
                        v-if="!isValidIdCard"
                        :name="2"
                        title="Carica il documento di identità"
                        icon="upload_file"
                        :done="moduleUploadData.step > 2"
                    >
                        <template v-if="moduleUploadData.idCardFiles.length">
                            <div v-for="(f, k) in moduleUploadData.idCardFiles" :key="k">
                                - {{f.name}}&nbsp;&nbsp;<button @click="() => moduleUploadData.idCardFiles.splice(k, 1)">Rimuovi</button>
                                <br>
                                <iframe v-if="f.type == 'application/pdf'" :src="f.content" style="width:100%; max-height:250px;"></iframe>
                                <image v-if="f.type != 'application/pdf'" :src="f.content" style="max-height:250px;" />
                            </div>
                        </template>
                        <div v-if="!moduleUploadData.idCardFiles.length">Nessun file selezionato</div>
                        <mpop-uploader 
                            v-model="moduleUploadData.idCardFiles"
                            :accepted-mime="['application/pdf', 'image/jpeg', 'image/png']"
                            :formatter="v => {const f = {content: v.content, name: v.meta.name, type: v.meta.type }; return f;}"
                            @invalid-mime="onInvalidMime"
                            :disabled="moduleUploadData.idCardFiles.length == 2"
                        >Seleziona file da caricare</mpop-uploader>
                        <br>
                        <br>
                        <select v-model="moduleUploadData.idCardType">
                            <option disabled :value="null">Seleziona il tipo di documento</option>
                            <option v-for="(t, k) in mainOptions.idCardTypes" :key="k" :value="k">{{t}}</option>
                        </select>
                        <br>
                        <br>
                        <label>Numero documento:&nbsp;&nbsp;<input type="text" style="text-transform: uppercase;" v-model="moduleUploadData.idCardNumber"/></label>
                        <br>
                        <br>
                        <label>Data di scadenza documento:&nbsp;&nbsp;<input type="date" :min="maxIdCardDate" v-model="moduleUploadData.idCardExpiration"/></label>
                        <br>
                        <br>
                        <button @click="()=>moduleUploadData.step--">Indietro</button>&nbsp;&nbsp;<button :disabled="!moduleUploadData.idCardFiles.length || mainOptions.idCardTypes === null || !moduleUploadData.idCardNumber || !moduleUploadData.idCardExpiration" @click="()=>moduleUploadData.step++">Avanti</button>
                    </q-step>
                    <q-step
                        :name="3"
                        title="Invia i documenti"
                        icon="send"
                        :done="moduleUploadData.step > 3"
                    >
                        <label>Dichiaro di aver accettato i <a class="mpop-click" @click="e => {e.preventDefault(); if (mainOptions.privacyPolicyUrl) openExternalUrl(mainOptions.privacyPolicyUrl);}"><u>termini e le condizioni sul trattamento dati</u></a>&nbsp;
                            <input type="checkbox" v-model="moduleUploadData.generalPolicyAccept"/>
                        </label>
                        <br>
                        <button :disabled="saving" @click="()=>moduleUploadData.step-= (isValidIdCard ? 2 : 1)">Indietro</button>&nbsp;&nbsp;<button :disabled="!moduleUploadData.generalPolicyAccept || saving" @click="moduleUploadDataSend">Invia</button>
                    </q-step>
                </q-stepper>
                <p v-if="moduleUploadData.sub.status != 'open'">
                    Modulo correttamente caricato. Stai per essere reindirizzato...
                </p>
            </div>
            <!--USER_SEARCH-->
            <div v-if="selectedTab.name == 'users'" id="mpop-user-search">
                <div class="mpop-user-search-field">
                    <input type="text" v-model="userSearch.txt" @input="triggerSearchUsers" placeholder="Nome, e-mail, username" />
                </div>
                <div v-if="profile.role == 'administrator'" class="mpop-user-search-field" v-for="(role, index) in userRoles" :key="index">
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
                <div class="mpop-user-search-field">
                    <q-select
                        style="width: 250px"
                        filled
                        v-model="userSearch.subs_years"
                        multiple
                        :options="userSearchSelectableSubYears"
                        label="Sottoscrizioni negli anni"
                        @update:model-value="triggerSearchUsers"
                    ></q-select>
                </div>
                <div class="mpop-user-search-field">
                    <q-select
                        style="width: 250px"
                        filled
                        v-model="userSearch.subs_statuses"
                        multiple
                        :options="userSearchSelectableSubStatuses"
                        label="Sottoscrizioni con stato"
                        @update:model-value="triggerSearchUsers"
                        map-options
                    ></q-select>
                </div>
                <div>
                    <div class="mpop-user-search-field mpop-50-wid">
                        <label for="userSearchZone-select">Residenza&nbsp;
                            <mpop-select
                                multiple
                                fuse-search
                                :minLen="2"
                                id="userSearchZone-select"
                                v-model="userSearch.zones"
                                :options="zoneSearch.users"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                @option:selected="zones => {
                                    const oldLen = zones.length;
                                    reduceZones(zones, userSearch);
                                    if (oldLen == zones.length) triggerSearchUsers();
                                }"
                                @option:deselected="triggerSearchUsers"
                                @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'searchZones', 'users', userSearch)"
                            >
                                <template v-slot:option="zone">
                                    {{zone.untouched_label + addSuppressToLabel(zone)}}
                                </template>
                            </mpop-select>
                        </label>
                    </div>
                    <div v-if="profile.role == 'administrator'" class="mpop-user-search-field mpop-50-wid">
                        <label for="userSearchRespZone-select">Zone gestite&nbsp;

                            <mpop-select
                                multiple
                                fuse-search
                                :minLen="2"
                                id="userSearchRespZone-select"
                                v-model="userSearch.resp_zones"
                                :options="zoneSearch.users_resp"
                                :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
                                @option:selected="zones => {
                                    const oldLen = zones.length;
                                    reduceZones(zones, userSearch, 'resp_zones');
                                    if (oldLen == zones.length) triggerSearchUsers();
                                }"
                                @option:deselected="triggerSearchUsers"
                                @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'searchZones', 'users_resp', userSearch, 'resp_zones')"
                            >
                                <template v-slot:option="zone">
                                    {{zone.untouched_label + addSuppressToLabel(zone)}}
                                </template>
                            </mpop-select>
                        </label>
                    </div>
                </div>
                <div>Totale: {{foundUsersTotal}}</div>
                <div id="mpop-page-buttons">
                    <button class="mpop-button" @click="changeUserSearchPage(1)" v-if="userSearch.page != 1 && !pageButtons.includes(1) && userSearch.page -2 > 0" style="width:auto">Inizio</button>
                    <button class="mpop-button" @click="changeUserSearchPage(userSearch.page -1)" v-if="userSearch.page != 1 && !pageButtons.includes(userSearch.page -1)" style="padding:1px"><?=$this->dashicon('arrow-left')?></button>
                    <button :class="'mpop-button' + (p == userSearch.page ? ' mpop-page-selected' : '')" v-for="p in pageButtons" :key="p" @click="changeUserSearchPage(p)">{{p}}</button>
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
            <!--USER_ADD-->
            <div v-if="selectedTab.name == 'userAdd'" id="mpop-sub-view">
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
            </div>
            <!--SUB_VIEW-->
            <div v-if="selectedTab.name == 'subView'" id="mpop-sub-view">
                <h3>ID: {{subInView.id}} - Utente: {{subInView.user_login || subInView.user_id}}</h3>
                <table id="mpop-sub-table">
                    <tr>
                        <td><strong>ID Tesserato:</strong></td>
                        <td>{{subInView.user_id}}</td>
                    </tr>
                    <tr v-if="subInView.first_name">
                        <td><strong>Nome:</strong></td>
                        <td>{{subInView.first_name}}</td>
                    </tr>
                    <tr v-if="subInView.last_name">
                        <td><strong>Cognome:</strong></td>
                        <td>{{subInView.last_name}}</td>
                    </tr>
                    <tr>
                        <td><strong>Anno riferimento:</strong></td>
                        <td>{{subInView.year}}</td>
                    </tr>
                    <tr>
                        <td><strong>Stato:</strong></td>
                        <td>{{userSearchSelectableSubStatuses.find(s => s.value == subInView.status)?.label}}</td>
                    </tr>
                    <tr>
                        <td><strong>Quota annuale:</strong></td>
                        <td>{{subInView.quote ? currencyFormatter.custFormat(subInView.quote) : '-'}}</td>
                    </tr>
                    <tr>
                        <td><strong>Creata da:</strong></td>
                        <td>{{subInView.author_login || subInView.author_id}}</td>
                    </tr>
                    <tr>
                        <td><strong>Data creazione:</strong></td>
                        <td>{{timestampToFullDatetimeString(subInView.created_at)}}</td>
                    </tr>
                    <template v-if="subInView.status == 'completed'">
                        <tr>
                            <td><strong>Completata da:</strong></td>
                            <td>{{subInView.completer_login || subInView.completer_id}}</td>
                        </tr>
                        <tr>
                            <td><strong>Data completamento:</strong></td>
                            <td>{{timestampToFullDatetimeString(subInView.completed_at)}}</td>
                        </tr>
                        <tr>
                            <td><strong>Data iscrizione/rinnovo:</strong></td>
                            <td>{{timestampToFullDatetimeString(subInView.signed_at)}}</td>
                        </tr>
                        <tr v-if="subInView.pp_order_id">
                            <td><strong>ID ordine PayPal:</strong></td>
                            <td>{{subInView.pp_order_id}}</td>
                        </tr>
                    </template>
                    <tr>
                        <td><strong>Consenso marketing:</strong></td>
                        <td>{{subInView.marketing_agree ? 'Sì' : 'No'}}</td>
                    </tr>
                    <tr>
                        <td><strong>Consenso newsletter:</strong></td>
                        <td>{{subInView.newsletter_agree ? 'Sì' : 'No'}}</td>
                    </tr>
                    <tr>
                        <td><strong>Consenso pubblicazione:</strong></td>
                        <td>{{subInView.publish_agree ? 'Sì' : 'No'}}</td>
                    </tr>
                    <tr>
                        <td><strong>Note:</strong></td>
                        <td>
                            <textarea v-model="subInView.notes"></textarea>
                            <br>
                            <button :disabled="!subInView.is_editable" @click="saveSubNotes">Salva note</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Ultima modifica:</strong></td>
                        <td>{{timestampToFullDatetimeString(subInView.updated_at)}}</td>
                    </tr>
                    <template v-if="subInView.user_id_card_number">
                        <tr>
                            <td><strong>Numero documento d'identità:</strong></td>
                            <td>{{subInView.user_id_card_number}}</td>
                        </tr>
                        <tr>
                            <td><strong>Tipo di documento d'identità:</strong></td>
                            <td>{{mainOptions.idCardTypes[subInView.user_id_card_type]}}</td>
                        </tr>
                        <tr>
                            <td><strong>Scadenza documento d'identità:</strong></td>
                            <td>{{displayLocalDate(subInView.user_id_card_expiration)}}</td>
                        </tr>
                    </template>
                    <tr v-if="subInView.files && subInView.files.length">
                        <td><strong>File sottoscrizione:</strong></td>
                        <td>
                            <ul>
                                <li v-for="(f, k) in formatSubFiles(subInView.files)" :key="k">
                                    <template v-if="typeof subInView.files[k] == 'string'">{{f}}</template>
                                    <template v-else><span class="mpop-click" style="text-decoration: underline" @click="subInView.documentToShow = subInView.files[k]">{{f}}</template>
                                </li>
                            </ul>
                            <br>
                            <template v-if="typeof subInView.files[0] == 'string'">
                                <input type="password" @input="decryptPasswordSave" placeholder="La tua chiave personale" v-model="documentsDecryptPassword" />&nbsp;&nbsp;
                                <button :disabled="!documentsDecryptPassword || saving" @click="documentsDecrypt">Sblocca documenti</button>
                            </template>
                            <template v-else-if="subInView.status == 'tosee'">
                                <label v-if="!subInView.user_id_card_confirmed && !subInView.user_id_card_number"><input type="checkbox" v-model="subInView.forceIdCard" />&nbsp;&nbsp;Forza documento d'identità<br><br></label>
                                <button :disabled="!subInView.is_editable || !subInView.user_id_card_confirmed || (!subInView.user_id_card_number && !subInView.forceIdCard)" @click="documentsConfirm">Conferma i documenti</button>
                            </template>
                        </td>
                    </tr>
                    <tr v-else-if="['seen', 'completed'].includes(subInView.status)">
                        <td><strong>Carica modulo di sottoscrizione:</strong></td>
                        <td>
                            <template v-if="subModuleUploadFiles.length">
                                <ul>
                                    <li
                                        v-for="(f, k) in subModuleUploadFiles"
                                        :key="k"
                                        class="mpop-click"
                                    >
                                        <span @click="subInView.documentToShow = f">File {{k+1}}</span>
                                        &nbsp;&nbsp;
                                        <q-icon
                                            @click="() => {
                                                if(subInView.documentToShow && f.content == subInView.documentToShow.content ) subInView.documentToShow = null;
                                                subModuleUploadFiles.splice(k,1);
                                            }"
                                            name="close"
                                        ></q-icon>
                                    </li>
                                </ul>
                                <br>
                            </template>
                            <mpop-uploader 
                                v-model="subModuleUploadFiles"
                                :accepted-mime="['application/pdf', 'image/jpeg', 'image/png']"
                                :formatter="v => {const f = {content: v.content, name: v.meta.name, type: v.meta.type }; return f;}"
                                @invalid-mime="onInvalidMime"
                                @change="f => subInView.documentToShow = f"
                                :disabled="!subInView.is_editable || subModuleUploadFiles.length == 2"
                            >Seleziona file da caricare</mpop-uploader>
                            <br><br>
                            <input type="password" @input="decryptPasswordSave" placeholder="La tua chiave personale" v-model="documentsDecryptPassword" />
                            <br><br>
                            <button :disabled="!subModuleUploadFiles.length || !documentsDecryptPassword || saving" @click="userSubModuleFilesUpload">Carica file</button>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <button v-if="subInView.status == 'completed'" @click="subCancel" style="margin-right:5px" :disabled="saving">Annulla sottoscrizione</button>
                            <template v-if="subInView.status == 'seen'">
                                Data iscrizione/pagamento:&nbsp;<input :max="todayString" type="date" v-model="paymentConfirmationDate"/>&nbsp;&nbsp;
                                <button @click="paymentConfirm" :disabled="!paymentConfirmationDate || saving" style="margin-right:5px">Conferma pagamento</button>
                                <br><br>
                            </template>
                            <button :disabled="saving" @click="subscriptionRefuse" v-if="!['canceled', 'refused', 'completed'].includes(subInView.status)" style="margin-right:5px">Rifiuta la richiesta</button>
                        </td>
                    </tr>
                </table>
                <template v-if="subInView.documentToShow">
                    <hr>
                    <button @click="subInView.documentToShow = null">Chiudi</button>
                    <br>
                    <iframe v-if="subInView.documentToShow.type == 'application/pdf'" :src="subInView.documentToShow.content" style="width:100%; min-height: 1000px;"></iframe>
                    <image v-if="subInView.documentToShow.type != 'application/pdf'" :src="subInView.documentToShow.content" />
                </template>
            </div>
            <!--USER_VIEW-->
            <div v-if="selectedTab.name == 'userView'" id="mpop-user-view"><template v-if="userInView">
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
            </template></div>
            <!--ADD_SUBSCRIPTION-->
            <div v-if="selectedTab.name == 'subAdd'"><template v-if="userInView">
                <table id="mpop-sub-table">
                    <tr>
                        <td><strong>ID Tesserato:</strong></td>
                        <td>{{userInView.ID}}</td>
                    </tr>
                    <tr>
                        <td><strong>E-mail:</strong></td>
                        <td>{{userInView.email}}</td>
                    </tr>
                    <tr>
                        <td><strong>Nome:</strong></td>
                        <td>{{userInView.first_name}}</td>
                    </tr>
                    <tr>
                        <td><strong>Cognome:</strong></td>
                        <td>{{userInView.last_name}}</td>
                    </tr>
                    <tr>
                        <td><strong>Anno riferimento:</strong></td>
                        <td>
                            <select v-model="subInAdd.year">
                                <option v-for="y in userAvailableYearsToOrder" :value="y">{{y}}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Stato:</strong></td>
                        <td>
                            <select v-model="subInAdd.status">
                                <option v-for="s in userSearchSelectableSubStatuses.filter(e => ['seen','completed'].includes(e.value))" :value="s.value">{{s.label}}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Quota annuale:</strong></td>
                        <td>€&nbsp;&nbsp;<input v-model="subInAdd.quote" type="number" :min="mainOptions.authorizedSubscriptionQuote" step=".01" /></td>
                    </tr>
                    <tr v-if="subInAdd.status == 'completed'">
                        <td><strong>Data iscrizione/rinnovo:</strong></td>
                        <td><input type="date" :max="todayString" v-model="subInAdd.signed_at"/></td>
                    </tr>
                    <tr>
                        <td><strong>Consenso marketing:</strong></td>
                        <td><input v-model="subInAdd.marketing_agree" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td><strong>Consenso newsletter:</strong></td>
                        <td><input v-model="subInAdd.newsletter_agree" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td><strong>Consenso pubblicazione:</strong></td>
                        <td><input v-model="subInAdd.publish_agree" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td><strong>Note:</strong></td>
                        <td><textarea v-model="subInAdd.notes"></textarea></td>
                    </tr>
                </table>
                <button class="mpop-button btn-success" :disabled="!validSubAdd" @click="addSubscription">Aggiungi sottoscrizione</button>
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
    "user": <?=json_encode($parsed_user)?>,
    "discourseUrl": <?=json_encode($discourse_url)?>,
    "privacyPolicyUrl": <?=json_encode(get_privacy_policy_url())?>
}</script>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/logged-myaccount.js"></script>
<?php