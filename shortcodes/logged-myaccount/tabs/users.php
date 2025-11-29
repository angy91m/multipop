<?php
defined( 'ABSPATH' ) || exit;
?>
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