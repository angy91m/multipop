<?php
defined( 'ABSPATH' ) || exit;
?>
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
            <mpop-sig-pad ref="moduleSigPad" :from-data-url="moduleUploadData.signature"></mpop-sig-pad>
            <br>
            <button :disabled="!isValidSignature" @click="moduleSigPadUndo">Annulla</button>&nbsp;&nbsp;<button @click="moduleSigPadClear">Ricomincia</button>
            <br>
            <br>
            <button @click="()=>previewModule(moduleUploadData.sub.id)">Anteprima documento</button>
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
                    <iframe v-if="f.type == 'application/pdf'" :src="f.url" height="500" style="width:100%; max-height:500px;"></iframe>
                    <image v-if="f.type != 'application/pdf'" :src="f.url" style="max-height:250px;" />
                </div>
            </template>
            <div v-if="!moduleUploadData.signedModuleFiles.length">Nessun file selezionato</div>
            <mpop-uploader 
                v-model="moduleUploadData.signedModuleFiles"
                :accepted-mime="['application/pdf', 'image/jpeg', 'image/png']"
                :formatter="v => {const f = {content: v.content, name: v.meta.name, type: v.meta.type, url: createObjectURL(base64ToBlob(v.content,v.meta.type)) }; return f;}"
                @invalid-mime="onInvalidMime"
                :disabled="moduleUploadData.signedModuleFiles.length == 2"
            >Seleziona file da caricare</mpop-uploader>
        </template>
        <br><br><button :disabled="moduleUploadData.withSignature ? !isValidSignature : !moduleUploadData.signedModuleFiles.length" @click="nextStep1">Avanti</button>
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
                <iframe v-if="f.type == 'application/pdf'" :src="f.url" height="500" style="width:100%; max-height:500px;"></iframe>
                <image v-if="f.type != 'application/pdf'" :src="f.url" style="max-height:250px;" />
            </div>
        </template>
        <div v-if="!moduleUploadData.idCardFiles.length">Nessun file selezionato</div>
        <mpop-uploader 
            v-model="moduleUploadData.idCardFiles"
            :accepted-mime="['application/pdf', 'image/jpeg', 'image/png']"
            :formatter="v => {const f = {content: v.content, name: v.meta.name, type: v.meta.type, url: createObjectURL(base64ToBlob(v.content,v.meta.type)) }; return f;}"
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
        <button @click="()=>moduleUploadData.step--">Indietro</button>&nbsp;&nbsp;<button :disabled="!moduleUploadData.idCardFiles.length || moduleUploadData.idCardType === null || !moduleUploadData.idCardNumber || !moduleUploadData.idCardExpiration" @click="()=>moduleUploadData.step++">Avanti</button>
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