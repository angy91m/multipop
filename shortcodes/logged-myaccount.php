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
                <?php require __DIR__ . '/logged-myaccount/tabs.php' ?>
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