import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
import Fuse from '/wp-content/plugins/multipop/js/fuse.mjs';
import * as Vue from '/wp-content/plugins/multipop/js/vue.esm-browser.js';
//import * as s from '/wp-content/plugins/multipop/js/vue-select.js';
const { createApp, ref, computed, reactive, onUnmounted, onBeforeMount, defineAsyncComponent } = Vue,
{ loadModule } = window['vue3-sfc-loader'];

const vSel = loadModule(`/wp-content/plugins/multipop/js/vue-select.js`, {
    moduleCache: { vue: Vue },
    async getFile(url) {
        const response = await fetch(url);
        if ( !response.ok ){
            console.error({message:'Import failed ' + url, response})
            throw new Error('Import failed ' + url)
        }
        return { getContentData: asBinary => asBinary ? response.arrayBuffer() : response.text()};
    },
    addStyle() {}
}),
mailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/s,
passwordRegex = {
    rr: [
        /[a-z]+/s,
        /[A-Z]+/s,
        /[0-9]+/s,
        /[ |\\!"£$%&/()=?'^,.;:_@°#*+[\]{}_-]+/s
    ],
    test(password) {
        if (password.length < 8 || password.length > 64) return false;
        let validRegex = 0;
        passwordRegex.rr.forEach(r => validRegex += r.test(password) ? 1 : 0);
        return validRegex >= 3;
    },
    acceptedSymbols: "SPACE | \\ ! \" £ $ % & / ( ) = ? ' ^ , . ; : _ @ ° # * + [ ] { } _ -"
},
userRoles = [
    'multipopolano',
    'multipopolare_resp',
    'administrator',
    'others'
],
historyTabs = [],
loggedMyAccountNonce = document.getElementById('mpop-logged-myaccount-nonce').value;
let searchUsersTimeout, triggerSearchTimeout;
createApp({
    components: {
        'v-select': defineAsyncComponent(() => vSel)
    },
    setup() {
        const selectedTab = ref('summary'),
        displayNav = ref(false),
        profile = reactive({}),
        profileInEditing = reactive({}),
        userInEditing = reactive({}),
        userInView = reactive({}),
        profileEditing = ref(false),
        userEditing = ref(false),
        birthplaceOpen = ref(false),
        billingCityOpen = ref(false),
        userSearchZoneOpen = ref(false),
        userSearchRespZoneOpen = ref(false),
        userEditingRespZoneOpen = ref(false),
        saving = ref(false),
        savingProfileErrors = reactive([]),
        savingUserErrors = reactive([]),
        userNotices = reactive([]),
        helloName = computed(()=> profile.first_name ? profile.first_name : profile.login),
        birthCities = reactive([]),
        billingCities = reactive([]),
        pwdChangeFields = reactive({}),
        pwdChanging = ref(false),
        userSearch = reactive({
            txt: '',
            roles: [
                'multipopolano',
                'multipopolare_resp',
                'administrator'
            ],
            page: 1,
            sortBy: {
                ID: true
            },
            zones: [],
            resp_zones: [],
            mpop_card_active: null,
            mpop_mail_to_confirm: null
        }),
        zoneSearch = reactive({
            users: [],
            users_resp: [],
            subscriptions: [],
            mpop_resp: []
        }),
        userSearchLimit = ref(100),
        foundUsers = reactive([]),
        foundUsersTotal = ref(0),
        foundUsersPageTotal = computed(() => {
            return Math.ceil(foundUsersTotal.value / userSearchLimit.value) || 1;
        }),
        pageButtons = computed(()=> {
            const buttons = [userSearch.page],
            maxButtons = 7,
            halfButtons =((maxButtons % 2) ? maxButtons-1 : maxButtons) / 2;
            for (let i = userSearch.page - 1; i > 0 && i >= userSearch.page - halfButtons; i--) {
                buttons.unshift(i);
            }
            const missingButtons = 4 - userSearch.page;
            for (let i = userSearch.page + 1; i <= userSearch.page + halfButtons + missingButtons && i <= foundUsersPageTotal.value; i++) {
                buttons.push(i);
            }
            return buttons;
        }),
        validProfileForm = computed(()=>
            mailRegex.test(profileInEditing.email.trim())
            && profileInEditing.first_name.trim()
            && profileInEditing.last_name.trim()
            && profileInEditing.mpop_birthdate
            && profileInEditing.mpop_birthplace
            && profileInEditing.mpop_billing_city
            && profileInEditing.mpop_billing_state
            && profileInEditing.mpop_billing_address.trim()
            && profileInEditing.mpop_billing_zip
        ),
        validUserForm = computed(()=>
            mailRegex.test(userInEditing.email.trim())
            && ( !userInView.first_name || userInEditing.first_name.trim() )
            && ( !userInView.last_name || userInEditing.last_name.trim() )
            && ( !userInView.mpop_birthdate || userInEditing.mpop_birthdate )
            && ( !userInView.mpop_birthplace || userInEditing.mpop_birthplace )
            && ( !userInView.mpop_billing_city || userInEditing.mpop_billing_city )
            && ( !userInView.mpop_billing_state || userInEditing.mpop_billing_state )
            && ( !userInView.mpop_billing_address || userInEditing.mpop_billing_address.trim() )
            && ( !userInView.mpop_billing_zip || userInEditing.mpop_billing_zip )
        ),
        staticPwdErrors = reactive([]),
        pwdChangeErrors = computed(()=> {
            const errs = [];
            errs.push(...staticPwdErrors);
            if (!pwdChangeFields.current && !pwdChangeFields.new && !pwdChangeFields.confirm) return errs;
            if (!pwdChangeFields.current) errs.push('current');
            if (!pwdChangeFields.new || !passwordRegex.test(pwdChangeFields.new)) errs.push('new');
            if (!pwdChangeFields.confirm || pwdChangeFields.new !== pwdChangeFields.confirm) errs.push('confirm');
            return errs;
        }),
        maxBirthDate = new Date();
        maxBirthDate.setFullYear(maxBirthDate.getFullYear() - 18);

        function fuseSearch(options, search) {
            const fuse = new Fuse(options, {
                keys: ['label'],
                shouldSort: true
            });
            return search.trim().length ? fuse.search(search).map(({item}) => item) : fuse.list;
        }
        async function birthCitiesSearch(searchText, user = false) {
            if (user) {
                savingUserErrors.length = 0;
            } else {
                savingProfileErrors.length = 0;
            }
            if (searchText.trim().length > 1) {
                const res = await serverReq({
                    action: 'get_birth_cities',
                    mpop_birthplace: searchText.trim(),
                    mpop_birthdate: user ? userInEditing.mpop_birthdate : profileInEditing.mpop_birthdate
                });
                if (res.ok) {
                    const cities = await res.json();
                    if (cities.data && cities.data.comuni) {
                        birthCities.length = 0;
                        birthCities.push(...cities.data.comuni.sort((a,b) => {
                            if (a.nome < b.nome) return -1;
                            if (a.nome > b.nome) return 1;
                            return 0;
                        } ));
                    } else {
                        console.error('Unknown error');
                    }
                } else {
                    try {
                        const {error} = await res.json();
                        if (error) {
                            if (user) {
                                savingUserErrors.push(...error);
                            } else {
                                savingProfileErrors.push(...error);
                            }
                        } else {
                            console.error('Unknown error');
                        }
                    } catch {
                        console.error('Unknown error');
                    }
                }
            }
        }
        async function billingCitiesSearch(searchText) {
            savingProfileErrors.length = 0;
            if (searchText.trim().length > 1) {
                const res = await serverReq({
                    action: 'get_billing_cities',
                    mpop_billing_city: searchText.trim()
                });
                if (res.ok) {
                    const cities = await res.json();
                    if (cities.data && cities.data.comuni) {
                        billingCities.length = 0;
                        billingCities.push(...cities.data.comuni.sort((a,b) => {
                            if (a.nome < b.nome) return -1;
                            if (a.nome > b.nome) return 1;
                            return 0;
                        } ));
                    } else {
                        console.error('Unknown error');
                    }
                } else {
                    try {
                        const {error} = await res.json();
                        if (error) {
                            savingProfileErrors.push(...error);
                        } else {
                            console.error('Unknown error');
                        }
                    } catch {
                        console.error('Unknown error');
                    }
                }
            }

        }
        async function searchZones(txt, ctx, target, zonesKey = 'zones') {
            if (zoneSearch[ctx]) {
                const res = await serverReq({
                    action: 'admin_search_zones',
                    search: txt.trim()
                });
                if (res.ok) {
                    const zones = await res.json();
                    if (zones.data) {
                        zoneSearch[ctx].length = 0;
                        zoneSearch[ctx].push(...target[zonesKey]);
                        zoneSearch[ctx].push(...zones.data.filter(z => !zoneSearch[ctx].find(zz => z.type == zz.type && (z.type == 'regione' ? z.nome == zz.nome : z.codice == zz.codice))));
                    } else {
                        console.error('Unknown error');
                    }
                } else {
                    console.error('Unknown error');
                }
            }
        }
        function triggerSearch(txt, loading, callable, ...args) {
            clearTimeout(triggerSearchTimeout);
            loading(true);
            const func = eval(callable);
            triggerSearchTimeout = setTimeout( () => func(txt, ...args).then(() => loading(false)), 500);
        }
        function showZones(zones) {
            const regioni = zones.filter(z => z.type == 'regione'),
            province = zones.filter(z => z.type == 'provincia'),
            comuni = zones.filter(z => z.type == 'comune');
            let res = '';
            res += '<ul class="mpop-search-results-zone">';
            if (regioni.length) {
                res += '<li class="mpop-nowrap"><strong>Reg:</strong> ' + regioni.map(r => r.nome + addSuppressToLabel(r)).join(', ') + '</li>';
            }
            if (province.length) {
                res += '<li class="mpop-nowrap"><strong>Prov:</strong> ' + province.map(p => p.sigla + addSuppressToLabel(p)).join(', ') + '</li>';
            }
            if (comuni.length) {
                res += '<li class="mpop-nowrap"><strong>Com:</strong> ' + comuni.map(c => c.nome + addSuppressToLabel(c)).join(', ') + '</li>';
            }
            res += '</ul>';
            return res;
        }
        function reduceZones(zones, target, zonesKey = 'zones') {
            const added = zones[zones.length - 1];
            if (added.type == 'comune') {
                if (target[zonesKey].find(z => (z.type == 'provincia' && z.codice == added.provincia.codice) || (z.type == 'regione' && z.nome == added.provincia.regione) ) ) {
                    target[zonesKey].pop();
                }
            }
            if (added.type == 'provincia') {
                if (target[zonesKey].find(z => (z.type == 'regione' && z.nome == added.regione) ) ) {
                    target[zonesKey].pop();
                } else {
                    target[zonesKey] = target[zonesKey].filter(z => z.type != 'comune' || z.provincia.codice != added.codice);
                }
            }
            if (added.type == 'regione') {
                target[zonesKey] = target[zonesKey].filter(z => z.type == 'regione' || (z.type == 'provincia' && z.regione != added.nome) || (z.type == 'comune' && z.provincia.regione != added.nome));
            }
        }
        async function getProfile() {
            const res = await serverReq({action: 'get_profile'});
            if (res.ok) {
                const newUser = await res.json();
                if (newUser.data) {
                    if (newUser.data.user) {
                        for (const k in profile) {
                            delete profile[k];
                        }
                        Object.assign(profile, newUser.data.user);
                    } else {
                        console.error('Unknown error');
                    }
                } else {
                    console.error('Unknown error');
                }
                generateNotices(newUser.notices || []);
            } else {
                try {
                    const {error, notices} = await res.json();
                    if (error) {
                        savingProfileErrors.push(...error);
                        generateNotices(notices || []);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }
            }
        }
        function addSuppressToLabel(option) {
            let res = '';
            if (option.type == 'provincia' || option.type == 'regione') {
                if (option.soppressa) res += ' (soppressa)';
            } else {
                if (option.soppresso) res += ' (soppresso)';
            }
            return res;
        }
        async function updateProfile() {
            saving.value = true;
            savingProfileErrors.length = 0;
            const res = await serverReq({
                action: 'update_profile',
                email: profileInEditing.email.trim(),
                first_name: profileInEditing.first_name.trim(),
                last_name: profileInEditing.last_name.trim(),
                mpop_birthdate: profileInEditing.mpop_birthdate,
                mpop_birthplace: profileInEditing.mpop_birthplace.codiceCatastale,
                mpop_billing_city: profileInEditing.mpop_billing_city.codiceCatastale,
                mpop_billing_address: profileInEditing.mpop_billing_address.trim(),
                mpop_billing_zip: profileInEditing.mpop_billing_zip
            });
            if (res.ok) {
                const newUser = await res.json();
                if (newUser.data) {
                    if (newUser.data.user) {
                        for (const k in profile) {
                            delete profile[k];
                        }
                        Object.assign(profile, newUser.data.user);
                        profileEditing.value = false;
                    } else {
                        console.error('Unknown error');
                    }
                }
                generateNotices(newUser.notices || []);
            } else {
                try {
                    const {error, notices} = await res.json();
                    if (error) {
                        savingProfileErrors.push(...error);
                        generateNotices(notices || []);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }
            }
            saving.value = false;
        }
        async function updateUser() {
            saving.value = true;
            savingUserErrors.length = 0;
            userInEditing.email = userInEditing.email.trim().toLowerCase();
            if (!userInEditing.mpop_mail_confirmed &&!userInView.mpop_mail_to_confirm && userInView.email == userInEditing.email) {
                if (!confirm(`Stai settando l'e-mail principale dell'utente (${userInView.email}) come non confermata. Questo gli impedirà di effettuare un login fino a che non la confermerà nuovamente.\nSei sicuro di continuare?`)) {
                    saving.value = false;
                    return;
                }
            }
            const respZones = [];
            if (userInEditing.role == 'multipopolare_resp') {
                userInEditing.mpop_resp_zones.forEach(z => {
                    let zs;
                    switch (z.type) {
                        case 'regione':
                            zs = 'reg_' + z.nome;
                            break;
                        case 'provincia':
                            zs = z.sigla;
                            break;
                        case 'comune':
                            zs = z.codiceCatastale;
                            break;
                    }
                    respZones.push(zs);
                });
            }
            const res = await serverReq({
                action: 'admin_update_user',
                ID: userInEditing.ID,
                email: userInEditing.email,
                mpop_mail_confirmed: userInEditing.mpop_mail_confirmed,
                first_name: userInEditing.first_name?.trim(),
                last_name: userInEditing.last_name?.trim(),
                mpop_birthdate: userInEditing.mpop_birthdate,
                mpop_birthplace: userInEditing.mpop_birthplace?.codiceCatastale,
                mpop_billing_city: userInEditing.mpop_billing_city?.codiceCatastale,
                mpop_billing_address: userInEditing.mpop_billing_address?.trim(),
                mpop_billing_zip: userInEditing.mpop_billing_zip,
                mpop_resp_zones: respZones
            });
            if (res.ok) {
                const newUser = await res.json();
                if (newUser.data) {
                    if (newUser.data.user) {
                        for (const k in userInView) {
                            delete userInView[k];
                        }
                        Object.assign(userInView, newUser.data.user);
                        userEditing.value = false;
                    } else {
                        console.error('Unknown error');
                    }
                }
                generateNotices(newUser.notices || []);
            } else {
                try {
                    const {error, notices} = await res.json();
                    if (error) {
                        savingUserErrors.push(...error);
                        generateNotices(notices || []);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }
            }
            saving.value = false;
        }
        async function changePassword() {
            pwdChanging.value = true;
            const {current, new: newPassword} = pwdChangeFields;
            const res = await serverReq({
                action: 'password_change',
                current: current,
                new: newPassword
            });
            if (res.ok) {
                const pwdRes = await res.json();
                if (pwdRes.data && pwdRes.data.pwdRes) {
                    pwdChangeFields.current = '';
                    pwdChangeFields.new = '';
                    pwdChangeFields.confirm = '';
                } else {
                    console.error('Unknown error');
                }
                generateNotices(pwdRes.notices || []);
            } else {
                try {
                    const {error, notices} = await res.json();
                    if (error) {
                        staticPwdErrors.push(...error);
                        generateNotices(notices || []);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }
            }
            pwdChanging.value = false;
        }
        function pushQueryParams(params = {}, replace = false) {
            const url = new URL(location);
            for (const k in params) {
                if (params[k] === null) {
                    url.searchParams.delete(k);
                    continue;
                }
                url.searchParams.set(k, params[k]);
            }
            if (replace) {
                return history.replaceState(historyTabs, '', url.href);
            }
            historyTabs.unshift(selectedTab.value);
            return history.pushState(historyTabs, '', url.href);
        }
        async function viewUser(ID, popstate = false) {
            if (ID == profile.ID) {
                return selectTab('summary');
            }
            const res = await serverReq({
               action: 'admin_view_user',
               ID
            });
            if (res.ok) {
                const user = await res.json();
                if (user.data && user.data.user) {
                    Object.assign(userInView, user.data.user);
                } else {
                    console.error('Unknown error');
                }
                generateNotices(user.notices || []);
            } else {
                try {
                    const {error, notices} = await res.json();
                    if (error) {
                        staticPwdErrors.push(...error);
                        generateNotices(notices || []);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }

            }
            if (!popstate) {
                selectTab('userView');
                pushQueryParams({'view-user': ID});
            }
        }
        async function searchUsers() {
            foundUsers.length = 0;
            const reqObj = {
                action: 'admin_search_users',
                ...userSearch,
                mpop_billing_state: [],
                mpop_billing_city: [],
                mpop_resp_zones: []
            };
            delete reqObj.zones;
            delete reqObj.resp_zones;
            userSearch.zones.forEach(z => {
                if (z.type == 'regione') reqObj.mpop_billing_state.push(...Object.keys(z.province));
                if (z.type == 'provincia') reqObj.mpop_billing_state.push(z.sigla);
                if (z.type == 'comune') reqObj.mpop_billing_city.push(z.codiceCatastale);
            });
            userSearch.resp_zones.forEach(z => {
                if (z.type == 'regione') {
                    reqObj.mpop_resp_zones.push('reg_' + z.nome, ...Object.keys(z.province));
                    for (const k in z.province) {
                        reqObj.mpop_resp_zones.push(...z.province[k]);
                    }
                }
                if (z.type == 'provincia') reqObj.mpop_resp_zones.push(z.sigla, ...z.comuni);
                if (z.type == 'comune') reqObj.mpop_resp_zones.push(z.codiceCatastale);
            });
            reqObj.mpop_resp_zones = Array.from(new Set(reqObj.mpop_resp_zones));
            const res = await serverReq(reqObj);
            if (res.ok) {
                const users = await res.json();
                if (users.data && users.data.users) {
                    foundUsers.push(...users.data.users);
                    foundUsersTotal.value = users.data.total;
                    userSearchLimit.value = users.data.limit;
                } else {
                    console.error('Unknown error');
                }
                generateNotices(users.notices || []);
            } else {
                try {
                    const {error} = await res.json();
                    if (error) {
                        console.error(error);
                    } else {
                        console.error('Unknown error');
                    }
                } catch {
                    console.error('Unknown error');
                }
            }
        }
        function triggerSearchUsers() {
            clearTimeout(searchUsersTimeout);
            searchUsersTimeout = setTimeout(searchUsers, 500);
        }
        function generateNotices(srvNotices = []) {
            userNotices.length = 0;
            userNotices.push(...srvNotices);
            const missingFields = [];
            if (profile._new_email) {
                userNotices.push({
                    type: 'warning',
                    msg: 'La tua nuova e-mail non è confermata. Fai clic sul link che ti è stato inviato all\'indirizzo che hai indicato.'
                });
            }
            if (!profile.first_name) {
                missingFields.push('Nome');
            }
            if (!profile.last_name) {
                missingFields.push('Cognome');
            }
            if (!profile.mpop_birthdate) {
                missingFields.push('Data di nascita');
            }
            if (!profile.mpop_birthplace) {
                missingFields.push('Luogo di nascita');
            }
            if (!profile.mpop_billing_address) {
                missingFields.push('Indirizzo di residenza');
            }
            if (!profile.mpop_billing_city) {
                missingFields.push('Comune di residenza');
            }
            if (!profile.mpop_billing_zip) {
                missingFields.push('CAP');
            }
            if (!profile.mpop_billing_state) {
                missingFields.push('Provincia di residenza');
            }
            if (missingFields.length) {
                userNotices.push({
                    type: 'warning',
                    msg: 'Per poter procedere con il tessaramento è necessario compilare i seguenti campi:<br>' + missingFields.join('<br>')
                });
            }
        }
        function dismissNotice(noticeInd) {
            userNotices.splice(noticeInd, 1);
        }
        function editProfile() {
            savingProfileErrors.length = 0;
            profileEditing.value = true;
            birthCities.length = 0;
            billingCities.length = 0;
            for (const key in profileInEditing) {
                delete profileInEditing[key];
            }
            Object.assign(profileInEditing, profile);
            if (profileInEditing._new_email) {
                profileInEditing.email = profileInEditing._new_email;
            }
            if (profileInEditing.birthplace) {
                birthCities.push(profileInEditing.birthplace);
            }
            if (profileInEditing.billing_city) {
                billingCities.push(profileInEditing.billing_city);
            }
        }
        function cancelEditProfile() {
            profileEditing.value = false;
            for(const key in profileInEditing) {
                delete profileInEditing[key];
            }
        }
        function editUser() {
            savingUserErrors.length = 0;
            userEditing.value = true;
            birthCities.length = 0;
            billingCities.length = 0;
            for (const key in userInEditing) {
                delete userInEditing[key];
            }
            Object.assign(userInEditing, userInView);
            if (userInEditing._new_email) {
                userInEditing.email = userInEditing._new_email;
            }
            userInEditing.emailOldValue = userInEditing.email;
            if (userInEditing.birthplace) {
                birthCities.push(userInEditing.birthplace);
            }
            if (userInEditing.billing_city) {
                billingCities.push(userInEditing.billing_city);
            }
            userInEditing.mpop_mail_confirmed = !userInEditing._new_email && !userInEditing.mpop_mail_to_confirm;
            userInEditing.mail_edited = false;
            userInEditing.mpop_resp_zones = JSON.parse(JSON.stringify(userInEditing.mpop_resp_zones));
            zoneSearch.mpop_resp = JSON.parse(JSON.stringify(userInEditing.mpop_resp_zones));
        }
        function cancelEditUser() {
            userEditing.value = false;
            for(const key in userInEditing) {
                delete userInEditing[key];
            }
        }
        function selectTab(tabName, popstate = false) {
            if (selectedTab.value != tabName) {
                cancelEditProfile();
                cancelEditUser();
                const url = new URL(location);
                selectedTab.value = tabName || 'summary';
                if (!popstate) {
                    if (tabName != 'userView') {
                        url.searchParams.delete('view-user');
                        pushQueryParams({'view-user': null});
                    }
                } else if (url.searchParams.has('view-user')) {
                    viewUser(url.searchParams.get('view-user'), popstate);
                }
                if (tabName == 'summary' || tabName == 'card') {
                    getProfile();
                }
            }
        }
        function displayLocalDate(dt) {
            if (!dt) return '';
            if (typeof dt === 'string') dt = new Date(dt);
            return ('0' + dt.getDate()).slice(-2) + '/' + ('0' + (dt.getMonth() + 1)).slice(-2) + '/' + dt.getFullYear();
        }
        function serverReq(obj) {
            return fetch(location.origin + location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ...obj,
                    'mpop-logged-myaccount-nonce': loggedMyAccountNonce
                })
            });
        }
        onBeforeMount(()=> {
            const {user: parsedUser} = JSON.parse(document.getElementById('__MULTIPOP_DATA__').innerText);
            Object.assign(profile, parsedUser);
            generateNotices();
            searchUsers();
            window.addEventListener('popstate', onPopState);
            const url = new URL(location);
            if (url.searchParams.has('view-user') && profile.role == 'administrator') {
                selectedTab.value = 'userView';
                viewUser(url.searchParams.get('view-user'), false, true);
            }
            if (!historyTabs.length) {
                historyTabs.unshift(selectedTab.value);
                history.replaceState(historyTabs, '', location.href);
            }
        });
        function onPopState(e) {
            if (Array.isArray(e.state)){
                selectTab(e.state[0] || 'summary', true);
                historyTabs.length;
                historyTabs.push(...e.state);
            }
        }
        onUnmounted(()=> {
            clearTimeout(searchUsersTimeout);
            window.removeEventListener('popstate', onPopState);
        });
        function changeUserSearchPage(page) {
            userSearch.page = page;
            searchUsers();
        }
        function userSearchSortBy(k) {
            
            if (Object.keys(userSearch.sortBy)[0] === k) {
                userSearch.sortBy[k] = !userSearch.sortBy[k];
            } else {
                userSearch.sortBy = {
                    [k]: true,
                    [Object.keys(userSearch.sortBy)[0]]: userSearch.sortBy[Object.keys(userSearch.sortBy)[0]]
                };
            }
            searchUsers();
        }
        function searchOpen(tag) {
            const openVar = eval(tag + 'Open');
            openVar.value = true;
            setTimeout(()=> document.querySelector('#'+tag+'-select .vs__search').select(),300);
        }
        function showRole(role = '') {
            if (!role) return 'Nessuno';
            switch(role) {
                case 'multipopolare_resp':
                    role = 'Responsabile';
                    break;
                case 'administrator':
                    role = "Amministratore";
                    break;
                case 'others':
                    role = 'Altri';
                    break;
                default:
                    role = role.charAt(0).toUpperCase() + role.slice(1);
            }
            return role;
        }
        return {
            selectedTab,
            profile,
            displayNav,
            helloName,
            userNotices,
            dismissNotice,
            profileEditing,
            profileInEditing,
            editProfile,
            cancelEditProfile,
            selectTab,
            displayLocalDate,
            birthCities,
            birthplaceOpen,
            billingCityOpen,
            billingCities,
            updateProfile,
            searchOpen,
            saving,
            savingProfileErrors,
            savingUserErrors,
            validProfileForm,
            validUserForm,
            pwdChangeFields,
            pwdChangeErrors,
            pwdChanging,
            changePassword,
            staticPwdErrors,
            userRoles,
            userSearch,
            showRole,
            foundUsers,
            searchUsers,
            triggerSearchUsers,
            foundUsersTotal,
            userSearchSortBy,
            foundUsersPageTotal,
            pageButtons,
            changeUserSearchPage,
            viewUser,
            userInView,
            userInEditing,
            userEditing,
            editUser,
            cancelEditUser,
            updateUser,
            fuseSearch,
            userSearchZoneOpen,
            userSearchRespZoneOpen,
            userEditingRespZoneOpen,
            zoneSearch,
            triggerSearch,
            reduceZones,
            showZones,
            addSuppressToLabel,
            maxBirthDate: maxBirthDate.getFullYear() + '-' + ('0' + (maxBirthDate.getMonth() + 1)).slice(-2) + '-' + ('0' + maxBirthDate.getDate()).slice(-2)
        };
    }
})
.mount('#app');