<?php
defined( 'ABSPATH' ) || exit;
?>
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