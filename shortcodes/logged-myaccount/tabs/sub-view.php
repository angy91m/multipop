<?php
defined( 'ABSPATH' ) || exit;
?>
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
    <iframe v-if="subInView.documentToShow.type == 'application/pdf'" :src="subInView.documentToShow.content" height="1000" style="width:100%; min-height: 1000px;"></iframe>
    <image v-if="subInView.documentToShow.type != 'application/pdf'" :src="subInView.documentToShow.content" />
</template>