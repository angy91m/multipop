<?php
defined( 'ABSPATH' ) || exit;
?>
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