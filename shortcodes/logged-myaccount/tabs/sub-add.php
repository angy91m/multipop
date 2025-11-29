<?php
defined( 'ABSPATH' ) || exit;
?>
<template v-if="userInView">
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
</template>