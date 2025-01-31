import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
import Fuse from '/wp-content/plugins/multipop/js/fuse.mjs';
import IntlTelInput from '/wp-content/plugins/multipop/js/vue-tel-input.js';
const { createApp, ref, computed, reactive, onUnmounted, onBeforeMount, defineAsyncComponent, nextTick } = Vue,
{ loadModule } = window['vue3-sfc-loader'],
loadVueModule = (...modules) => {
    const loaded = [];
    modules.forEach(path => loaded.push(loadModule('/wp-content/plugins/multipop/js/'+ path, {
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
    })));
    return loaded;
},
[vSel] = loadVueModule('vue-select.js'),
[mpopUploader] = loadVueModule('MpopUploader.vue'),
mailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/s,
passwordRegex = {
    rr: [
        /[a-z]+/s,
        /[A-Z]+/s,
        /[0-9]+/s,
        /[|\\!"£$%&/()=?'^,.;:_@°#*+[\]{}_-]+/s
    ],
    test(password) {
        if (password.length < 8 || password.length > 64) return false;
        let validRegex = 0;
        passwordRegex.rr.forEach(r => validRegex += r.test(password) ? 1 : 0);
        return validRegex >= 3;
    },
    acceptedSymbols: "| \\ ! \" £ $ % & / ( ) = ? ' ^ , . ; : _ @ ° # * + [ ] { } _ -"
},
boolVal = v => {
    if (typeof v == 'string' && v) {
        v = v.toLowerCase();
        if (['1','true','si','sì'].includes(v)) {
            return true;
        }
    }
    return false;
},
displayLocalDate = (dt) => {
    if (!dt) return '';
    if (typeof dt === 'string') dt = new Date(dt);
    return ('0' + dt.getDate()).slice(-2) + '/' + ('0' + (dt.getMonth() + 1)).slice(-2) + '/' + dt.getFullYear();
},
userRoles = [
    'multipopolano',
    'multipopolare_resp',
    'multipopolare_friend',
    'administrator',
    'others'
],
historyTabs = [],
cachedProps = {},
foundUsersColumns = [
    {name: 'ID', label: 'ID', sortable: true},
    {name: 'login', label: 'Login', sortable: true},
    {name: 'email', label: 'E-mail', sortable: true},
    {name: 'mpop_mail_to_confirm', label: 'E-mail da confermare', sortable: true, format: val => val ? 'Sì': 'No'},
    {name: 'mpop_card_active', label: 'Tessera attiva', sortable: true, format: val => val ? 'Sì': 'No'},
    {name: 'first_name', label: 'Nome', sortable: true},
    {name: 'last_name', label: 'Cognome', sortable: true},
    {name: 'mpop_billing_state', label: 'Provincia', sortable: true},
    {name: 'mpop_billing_city', label: 'Comune', sortable: true},
    {name: 'mpop_resp_zones', label: 'Zone'},
].map(col => {
    col.align = 'left';
    col.field = col.name;
    return col;
}),
userCsvFields = [
    'email',
    'first_name',
    'last_name',
    'mpop_birthplace_country',
    'mpop_birthplace',
    'mpop_birthdate',
    'mpop_billing_country',
    'mpop_billing_city',
    'mpop_billing_address',
    'mpop_billing_zip',
    'mpop_phone',
    'mpop_subscription_quote',
    'mpop_subscription_date',
    'mpop_subscription_marketing_agree',
    'mpop_subscription_newsletter_agree',
    'mpop_subscription_publish_agree',
    'mpop_renew',
    'mpop_org_role',
    'mpop_friend',
    'mpop_subscription_notes',
    'esito'
].map(col => ({name: col, label: col, align: 'left', field: col})),
loggedMyAccountNonce = document.getElementById('mpop-logged-myaccount-nonce').value,
userSearchSelectableSubYears = [],
thisYear  = new Date().getFullYear(),
userSearchSelectableSubStatuses = [{
    label: 'In attesa di approvazione',
    value: 'tosee'
}, {
    label: 'In attesa di pagamento',
    value: 'seen'
}, {
    label: 'Completata',
    value: 'completed'
}, {
    label: 'Rifiutata',
    value: 'refused'
}, {
    label: 'Annullata',
    value: 'canceled'
}, {
    label: 'Rimborsata',
    value: 'refunded'
}, {
    label: 'Aperta',
    value: 'open'
}],
currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    currencyDisplay: 'symbol',
});
currencyFormatter.custFormat = function(v) {
    return this.format(v).replaceAll(',', '').replace('.', ',').replace('€', '€ ');
};
const subscriptionColumns = [{
    name: 'id',
    label: 'ID',
    field: 'id',
    sortable: true
}, {
    name: 'year',
    label: 'Anno',
    field: 'year',
    sortable: true
}, {
    name: 'status',
    label: 'Stato',
    field: 'status',
    format: v => userSearchSelectableSubStatuses.find(s => s.value == v).label,
    sortable: true
}, {
    name: 'quote',
    label: 'Quota annuale',
    field: 'quote',
    format: v => v ? currencyFormatter.custFormat(v) : '-',
    sortable: true
}, {
    name: 'signed_at',
    label: 'Data firma',
    field: 'signed_at',
    format: v => v ? displayLocalDate(new Date(v*1000)) : '-',
    sortable: true
}, {
    name: 'pp_order_id',
    label: 'PayPal',
    field: 'pp_order_id',
    format: v => v ? 'Sì' : 'No',
    sortable: true
}],
openExternalUrl = url => window.open(url, '_blank');
userSearchSelectableSubYears.push(thisYear+1, thisYear);
for (let i = thisYear-1; i >= 2020; i--) userSearchSelectableSubYears.push(i);
let searchUsersTimeout, triggerSearchTimeout;

createApp({
    components: {
        'v-select': defineAsyncComponent(() => vSel),
        'v-intl-phone': IntlTelInput,
        'mpop-uploader': defineAsyncComponent(() => mpopUploader)
    },
    setup() {
        function activeCardForYear(cards = [], year) {
            return cards.filter(c => c.year == year && ['completed', 'tosee', 'seen', 'open'].includes(c.status)).sort((a,b) => a.status == b.status ? (a.status == 'completed' ? b.completed_at-a.completed_at : b.updated_at_at-a.updated_at_at) : (a.status == 'completed' ? -1 : (b.status == 'completed' ? 1 : b.updated_at_at-a.updated_at_at) ) ).shift();
        }
        const selectedTab = ref({
            name: 'summary',
            label: 'Riepilogo'
        }),
        menuItems = reactive([{
            name: 'summary',
            label: 'Riepilogo'
        }, {
            name: 'passwordChange',
            label: 'Cambio password'
        }, {
            name: 'card',
            label: 'Tessera'
        }, {
            name: 'users',
            label: 'Utenti',
            admin: true
        },
        // {
        //     name: 'subscriptions',
        //     label: 'Tessere',
        //     admin: true
        // },
        {
            name: 'uploadUserCsv',
            label: 'Carica CSV Utenti',
            admin: true
        }]),
        displayNav = ref(true),
        profile = reactive({}),
        profileInEditing = reactive({}),
        userInEditing = reactive({}),
        userInView = reactive({}),
        subInView = reactive({}),
        profileEditing = ref(false),
        userEditing = ref(false),
        birthplaceCountryOpen = ref(false),
        birthplaceOpen = ref(false),
        billingCountryOpen = ref(false),
        billingCityOpen = ref(false),
        userSearchZoneOpen = ref(false),
        userSearchRespZoneOpen = ref(false),
        userEditingRespZoneOpen = ref(false),
        mainOptions = reactive({
            authorizedSubscriptionYears: [],
            authorizedSubscriptionQuote: 0,
            idCardTypes: [],
            policies: {},
            privacyPolicyUrl: ''
        }),
        newSubscription = reactive({
            year: mainOptions.authorizedSubscriptionYears.length ? mainOptions.authorizedSubscriptionYears[0] : null,
            quote: mainOptions.authorizedSubscriptionQuote,
            mpop_marketing_agree: false,
            mpop_newsletter_agree: false,
            mpop_publish_agree: false
        }),
        requestingNewSubscription = ref(false),
        generatingSubscriptionPdf = reactive([]),
        marketingAgreeShow = ref(false),
        newsletterAgreeShow = ref(false),
        publishAgreeShow = ref(false),
        countries = reactive([]),
        saving = ref(false),
        savingProfileErrors = reactive([]),
        savingUserErrors = reactive([]),
        userNotices = reactive([]),
        helloName = computed(()=> profile.first_name || profile.login),
        birthCities = reactive([]),
        billingCities = reactive([]),
        pwdChangeFields = reactive({}),
        pwdChanging = ref(false),
        csvUsers = reactive([]),
        csvImportOptions = reactive({
            forceQuote: false,
            forceYear: false,
            delayedSend: true
        }),
        intPhoneInstance = ref('intPhoneInstance'),
        profilePhoneInput = ref('profilePhoneInput'),
        userEditPhoneInput = ref('userEditPhoneInput'),
        userSearchTablePagination = ref({
            sortBy: 'ID',
            descending: false,
            rowsPerPage: 0,
            rowsNumber: 0,
            page: 1,
            secondSortBy: null
        }),
        userSearch = reactive({
            txt: '',
            roles: [
                'multipopolano',
                'multipopolare_resp',
                'multipopolare_friend',
                'administrator'
            ],
            page: 1,
            zones: [],
            resp_zones: [],
            mpop_card_active: null,
            mpop_mail_to_confirm: null,
            subs_years: [],
            subs_statuses: []
        }),
        userSearching = ref(false),
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
        isValidProfileBirthPlace = computed(()=>profileInEditing.mpop_birthplace_country && (profileInEditing.mpop_birthplace_country != 'ita' || profileInEditing.mpop_birthplace)),
        isValidProfileBillingPlace = computed(()=>profileInEditing.mpop_billing_country && (profileInEditing.mpop_billing_country != 'ita' || (profileInEditing.mpop_billing_city && profileInEditing.mpop_billing_state && profileInEditing.mpop_billing_zip))),
        validProfileForm = computed(()=>
            mailRegex.test(profileInEditing.email.trim())
            && profileInEditing.first_name.trim()
            && profileInEditing.last_name.trim()
            && profileInEditing.mpop_birthdate
            && isValidProfileBirthPlace.value
            && isValidProfileBillingPlace.value
            && profileInEditing.mpop_billing_address.trim()
            && profileInEditing.mpop_phone
        ),
        isValidUserBirthPlace = computed(()=> (!userInView.mpop_birthplace_country || userInEditing.mpop_birthplace_country) && (!userInView.mpop_birthplace || (userInEditing.mpop_birthplace_country && (userInEditing.mpop_birthplace_country != 'ita' || userInEditing.mpop_birthplace )))),
        isValidUserBillingPlace = computed(()=>
            (!userInView.mpop_billing_country || userInEditing.mpop_billing_country)
            && (
                !userInView.mpop_billing_city
                || (
                    userInEditing.mpop_birthplace_country
                    && (
                        userInEditing.mpop_birthplace_country != 'ita'
                        || userInEditing.mpop_billing_city
                    )
                )
            )
            && (
                !userInView.mpop_billing_state
                || (
                    userInEditing.mpop_birthplace_country
                    && (
                        userInEditing.mpop_birthplace_country != 'ita'
                        || userInEditing.mpop_billing_state
                    )
                )
            )
            && (
                !userInView.mpop_billing_zip
                || (
                    userInEditing.mpop_birthplace_country
                    && (
                        userInEditing.mpop_birthplace_country != 'ita'
                        || userInEditing.mpop_billing_zip
                    )
                )
            )
        ),
        validUserForm = computed(()=>
            mailRegex.test(userInEditing.email.trim())
            && ( !userInView.first_name || userInEditing.first_name.trim() )
            && ( !userInView.last_name || userInEditing.last_name.trim() )
            && ( !userInView.mpop_birthdate || userInEditing.mpop_birthdate )
            && ( !userInView.mpop_birthplace || userInEditing.mpop_birthplace )
            && isValidUserBirthPlace.value
            && isValidUserBillingPlace.value
            && ( !userInView.mpop_billing_address || userInEditing.mpop_billing_address.trim() )
            && ( !userInView.mpop_phone || userInEditing.mpop_phone )
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
        nearActiveSub = computed(() => {
            let res, i = 0;
            const thisYear = (new Date()).getFullYear();
            while(!res && i < mainOptions.authorizedSubscriptionYears.length) {
                if (mainOptions.authorizedSubscriptionYears[i] >= thisYear) res = activeCardForYear(profile.mpop_my_subscriptions || [], mainOptions.authorizedSubscriptionYears[i]);
                i++;
            }
            return res;
        }),
        isProfileCompleted = computed(() => profile.first_name
            && profile.last_name
            && profile.mpop_birthdate
            && profile.mpop_birthplace
            && profile.mpop_billing_city
            && profile.mpop_billing_state
            && profile.mpop_billing_zip
            && profile.mpop_billing_address
            && profile.mpop_phone
        ? true : false ),
        moduleUploadData = reactive({
            sub: null,
            signedModuleFiles: [],
            idCardFiles: [],
            idCardType: null,
            idCardNumber: null,
            idCardExpiration: null,
            generalPolicyAccept: false,
            step: 1
        }),
        isValidIdCard = computed(()=> {
            if (!profile.mpop_id_card_expiration) return false;
            const d = new Date(profile.mpop_id_card_expiration);
            if (isNaN(d)) return false;
            if ((d.getFullYear() + '-' + ('0'+ (d.getMonth()+1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2)) != profile.mpop_id_card_expiration ) return false;
            if(Date.now() > d.getTime()) return false;
            return true;
        }),
        moduleUploadDataSending = ref(false),
        availableYearsToOrder = computed(() => {
            if (!mainOptions.authorizedSubscriptionQuote) return [];
            const thisYear = (new Date()).getFullYear();
            return profile.mpop_card_active && (!nearActiveSub.value || nearActiveSub.value.year != thisYear) ? [] : mainOptions.authorizedSubscriptionYears.filter(y => y >= thisYear && !activeCardForYear(profile.mpop_my_subscriptions || [], y));
        }),
        otherSubscriptions = computed(() => (profile.mpop_my_subscriptions || []).filter(c => nearActiveSub.value ? nearActiveSub.value.id !== c.id : true)),
        goodSubscriptions = computed(() => (profile.mpop_my_subscriptions || []).filter(s => ['completed', 'tosee', 'seen', 'open'].includes( s.status ))),
        maxBirthDate = new Date(),
        maxIdCardDate = new Date();
        maxBirthDate.setFullYear(maxBirthDate.getFullYear() - 18);
        maxIdCardDate.setDate(maxIdCardDate.getDate()+1);
        function cancelModuleUploadData() {
            moduleUploadData.step = 1;
            moduleUploadData.sub = null;
            moduleUploadData.signedModuleFiles.length = 0;
            moduleUploadData.idCardFiles.length = 0;
            moduleUploadData.idCardType = null;
            moduleUploadData.idCardNumber = null;
            moduleUploadData.idCardExpiration = null;
            moduleUploadData.generalPolicyAccept = false;
        }
        function fuseSearch(options, search) {
            const fuse = new Fuse(options, {
                keys: ['label'],
                shouldSort: true
            });
            return search.trim().length ? fuse.search(search).map(({item}) => item) : fuse.list;
        }
        function saveCachedProp(propName = '', expiry = 3 * 60 * 1000) {
            if (!propName) throw new Error('Empty property');
            cachedProps[propName] = (new Date()).getTime() + expiry;
        }
        function isCachedProp(propName) {
            return cachedProps[propName] ? cachedProps[propName] > (new Date()).getTime() : false;
        }
        async function loadUsersFromCsv(e) {
            csvUsers.length = 0;
            if (e.target.files.length) {
                const csvFile = e.target.files[0];
                if (csvFile.type == 'text/csv') {
                    const reader = new FileReader();
                    const csvContent = await new Promise(r => {
                        reader.onload = () => {
                            r(reader.result);
                        }
                        reader.readAsText(csvFile);
                    });
                    const workbook = XLSX.read(csvContent, {raw: true, type: 'string'}),
                    sheet = workbook.Sheets[workbook.SheetNames[0]];
                    csvUsers.push(...XLSX.utils.sheet_to_json(sheet));
                    csvUsers.forEach(u => {
                        for (const k in u) {
                            if (typeof u[k] == 'string') {
                                if (k == 'mpop_phone') {
                                    u[k] = validatePhone(u[k].trim());
                                    continue;
                                }
                                u[k] = u[k].trim();
                                u['esito'] = '';
                            }
                        }
                    });
                } else {
                    nextTick(()=> e.target.value = '');
                }
            }
        }
        async function uploadCsvRows() {
            saving.value = true;
            try {
                if (csvUsers.length) {
                    const response = await serverReq({
                        action: 'admin_import_rows',
                        rows: csvUsers.map(row => ({
                            ...row,
                            mpop_subscription_quote: parseFloat(row.mpop_subscription_quote.replace('€', '').replaceAll(/ |\./g, '').replaceAll(',', '.')),
                            mpop_subscription_marketing_agree: boolVal(row.mpop_subscription_marketing_agree),
                            mpop_subscription_newsletter_agree: boolVal(row.mpop_subscription_newsletter_agree),
                            mpop_subscription_publish_agree: boolVal(row.mpop_subscription_publish_agree),
                            mpop_renew: boolVal(row.mpop_renew)
                        })),
                        ...csvImportOptions
                    });
                    if (response.ok) {
                        const {data: rowResps} = await response.json();
                        rowResps.forEach((res, i) => csvUsers[i].esito = res.error || 'OK');
                    } else {
                        const {error} = await response.json();
                        console.error(error);
                    }
                }
            } catch (e) {
                console.error(e);
            } finally {
                saving.value = false;
            }
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
        function showSubscriptionStatus(card) {
            let status = '';
            const thisYear = (new Date()).getFullYear();
            switch(card) {
                case 'completed':
                    status = 'Attiva';
                    if (card.year < thisYear) status = 'Scaduta';
                    if (card.year > thisYear) status = 'In attivazione';
                    break;
                case 'tosee':
                    status = 'In attesa di approvazione';
                    break;
                case 'seen':
                    status = 'In attesa di pagamento';
                    break;
                case 'refused':
                    status = 'Rifiutata';
                    break;
                case 'canceled':
                    status = 'Annullata';
                    break;
                case 'refunded':
                    status = 'Rimborsata';
                    break;
                case 'open':
                    status = 'Aperta';
                    break;
            }
            return status;
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
        function parsePhone(input) {
            return input.instance.isValidNumber() ? input.instance.getNumber(1).replace(' ', '-').replaceAll(' ', '').replace('-', ' ') : '';
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
                mpop_birthplace_country: profileInEditing.mpop_birthplace_country,
                mpop_birthplace: profileInEditing.mpop_birthplace_country == 'ita' ? profileInEditing.mpop_birthplace.codiceCatastale : '',
                mpop_billing_country: profileInEditing.mpop_billing_country,
                mpop_billing_city: profileInEditing.mpop_billing_country == 'ita' ? profileInEditing.mpop_billing_city.codiceCatastale : '',
                mpop_billing_address: profileInEditing.mpop_billing_address.trim(),
                mpop_billing_zip: profileInEditing.mpop_billing_zip,
                mpop_phone: profileInEditing.mpop_phone
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
                mpop_birthplace_country: userInEditing.mpop_birthplace_country,
                mpop_birthplace: userInEditing.mpop_birthplace_country == 'ita' ? userInEditing.mpop_birthplace?.codiceCatastale : '',
                mpop_billing_country: userInEditing.mpop_billing_country,
                mpop_billing_city: userInEditing.mpop_billing_country == 'ita' ? userInEditing.mpop_billing_city?.codiceCatastale: '',
                mpop_billing_address: userInEditing.mpop_billing_address?.trim(),
                mpop_billing_zip: userInEditing.mpop_billing_zip,
                mpop_phone: userInEditing.mpop_phone,
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
                return history.replaceState(JSON.parse(JSON.stringify(historyTabs)), '', url.href);
            }
            historyTabs.unshift(selectedTab.value);
            const res = history.pushState(JSON.parse(JSON.stringify(historyTabs)), '', url.href);
            console.log(history.state);
            return res;
        }
        async function viewUser(ID, popstate = false) {
            if (ID == profile.ID) {
                return selectTab();
            }
            const res = await serverReq({
               action: 'admin_view_user',
               ID
            });
            if (res.ok) {
                const user = await res.json();
                if (user.data && user.data.user) {
                    for (const k in userInView) {
                        delete userInView[k];
                    }
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
                selectTab({name:'userView', label: 'Modifica utente'});
                pushQueryParams({'view-user': ID});
            }
        }
        async function viewSub(id, popstate = false) {
            const res = await serverReq({
               action: 'admin_view_sub',
               id
            });
            if (res.ok) {
                const sub = await res.json();
                if (sub.data) {
                    for (const k in subInView) {
                        delete subInView[k];
                    }
                    Object.assign(subInView, sub.data);
                    console.log(subInView);
                } else {
                    console.error('Unknown error');
                }
                generateNotices(sub.notices || []);
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
                selectTab({name:'subView', label: 'Visualizza sottoscrizione'});
                pushQueryParams({'view-sub': id});
            }
        }
        function moduleUploadBegin(sub) {
            moduleUploadData.sub = sub;
            selectTab({name: 'moduleUpload', label: 'Carica modulo'});
        }
        async function moduleUploadDataSend() {
            if (
                moduleUploadData.sub
                && moduleUploadData.sub.id
                && moduleUploadData.signedModuleFiles.length
                && (
                    isValidIdCard.value
                    || (
                        moduleUploadData.idCardFiles.length
                        && moduleUploadData.idCardType !== null
                        && moduleUploadData.idCardNumber
                        && moduleUploadData.idCardExpiration
                    )
                )
                && moduleUploadData.generalPolicyAccept
            ) {
                moduleUploadDataSending.value = true;
                console.log({
                    action: 'module_upload',
                    id: moduleUploadData.sub.id,
                    signedModuleFiles: moduleUploadData.signedModuleFiles.map(v => {const a = {...v}; delete a['name']; return a;}),
                    idCardFiles: moduleUploadData.idCardFiles.map(v => {const a = {...v}; delete a['name']; return a;}),
                    idCardType: moduleUploadData.idCardType,
                    idCardNumber: moduleUploadData.idCardNumber ? moduleUploadData.idCardNumber.toUpperCase().trim() : null,
                    idCardExpiration: moduleUploadData.idCardExpiration
                });
                const res = await serverReq({
                    action: 'module_upload',
                    id: moduleUploadData.sub.id,
                    signedModuleFiles: moduleUploadData.signedModuleFiles.map(v => {const a = {...v}; delete a['name']; return a;}),
                    idCardFiles: moduleUploadData.idCardFiles.map(v => {const a = {...v}; delete a['name']; return a;}),
                    idCardType: moduleUploadData.idCardType,
                    idCardNumber: moduleUploadData.idCardNumber ? moduleUploadData.idCardNumber.toUpperCase().trim() : null,
                    idCardExpiration: moduleUploadData.idCardExpiration
                });
                if (res.ok) {
                    const resJson = await res.json();
                    if (resJson.data) {
                        moduleUploadData.sub.status = 'tosee';
                        setTimeout(() => location.reload(), 3000);
                    }
                    generateNotices(resJson.notices || []);
                } else {
                    try {
                        const {error, notices} = await res.json();
                        if (error) {
                            console.error(error);
                            generateNotices(notices || []);
                        } else {
                            console.error('Unknown error');
                        }
                    } catch {
                        console.error('Unknown error');
                    }
                }
                moduleUploadDataSending.value = false;
            }
        }
        async function resendInvitationMail() {
            saving.value = true;
            try {
                const res = await serverReq({
                    ID: userInView.ID,
                    action: 'admin_resend_invitation_mail'
                });
                if (res.ok) {
                    const {notices = []} = await res.json();
                    generateNotices(notices);
                } else {
                    try {
                        const {notices = []} = await res.json();
                        if (error) {
                            generateNotices(notices);
                        } else {
                            console.error('Unknown error');
                        }
                    } catch {
                        console.error('Unknown error');
                    }
    
                }
            } finally {
                saving.value = false;
            }
        }
        async function getMainOptions() {
            if ( !isCachedProp('mainOptions') ) {
                const res = await serverReq({
                    action: 'get_main_options'
                });
                if (res.ok) {
                    const resData = await res.json();
                    if (resData.data) {
                        Object.assign(mainOptions, resData.data);
                        if (availableYearsToOrder.value.length) {
                            newSubscription.year = availableYearsToOrder.value[0];
                        }
                        newSubscription.quote = mainOptions.authorizedSubscriptionQuote;
                        saveCachedProp('mainOptions');
                    } else {
                        console.error('Unknown error');
                    }
                    generateNotices(resData.notices || []);
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
            return mainOptions;
        }
        async function requestNewSubscription() {
            requestingNewSubscription.value = !requestingNewSubscription.value;
            try {
                const res = await serverReq({
                    action: 'new_subscription',
                    ...newSubscription
                });
                if (res.ok) {
                    const resData = await res.json();
                    if (resData.data) {
                        await getProfile();
                    } else {
                        console.error('Unknown error');
                    }
                    generateNotices(resData.notices || []);
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
            } finally {
                requestingNewSubscription.value = !requestingNewSubscription.value;
            }
        }
        async function generateSubscriptionPdf(id) {
            generatingSubscriptionPdf.push(id);
            try {
                const res = await serverReq({
                    action: 'generate_subscription_pdf',
                    id
                });
                if (res.ok) {
                    const resData = await res.json();
                    if (resData.data && resData.data.pdf) {
                        Object.assign(document.createElement('a'), {
                            href: resData.data.pdf,
                            download: 'Modulo Multipopolare.pdf'
                        }).click();
                    } else {
                        console.error('Unknown error');
                    }
                    generateNotices(resData.notices || []);
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
            } finally {
                generatingSubscriptionPdf.splice( generatingSubscriptionPdf.findIndex(v => v == id), 1);
            }
        }
        async function searchUsers(options) {
            const newPagination = options ? options.pagination : userSearchTablePagination.value;
            try {
                userSearching.value = true;
                foundUsers.length = 0;
                const reqObj = {
                    action: 'admin_search_users',
                    ...userSearch,
                    mpop_billing_country: [],
                    mpop_billing_state: [],
                    mpop_billing_city: [],
                    mpop_resp_zones: [],
                    sortBy: {
                        [newPagination.sortBy]: !newPagination.descending
                    }
                };
                reqObj.subs_statuses = reqObj.subs_statuses.map(v => v.value);
                if (userSearchTablePagination.value.sortBy != newPagination.sortBy) {
                    userSearchTablePagination.value.secondSortBy = {[userSearchTablePagination.value.sortBy]: !userSearchTablePagination.value.descending};
                }
                if (userSearchTablePagination.value.secondSortBy) {
                    reqObj.sortBy = {...reqObj.sortBy, ...userSearchTablePagination.value.secondSortBy};
                }
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
                        userSearchTablePagination.value.rowsNumber = users.data.total;
                        userSearchTablePagination.value.sortBy = Object.keys(users.data.sortBy[0])[0];
                        userSearchTablePagination.value.descending = !Object.values(users.data.sortBy[0])[0];
                        if (users.data.sortBy[1]) {
                            userSearchTablePagination.value.secondSortBy = users.data.sortBy[1]
                        }
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
            } finally {
                userSearching.value = false;
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
            if (!profile.mpop_birthplace_country) {
                missingFields.push('Nazione di nascita');
            }
            if (!profile.mpop_birthplace) {
                missingFields.push('Comune di nascita');
            }
            if (!profile.mpop_billing_country) {
                missingFields.push('Nazione di residenza');
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
        function selectTab(tab, popstate = false) {
            if (selectedTab.value.name != tab?.name) {
                cancelEditProfile();
                cancelEditUser();
                const url = new URL(location);
                tab = tab || {name: 'summary', label: 'Riepilogo'};
                if (tab.name == 'moduleUpload') {
                    if (!moduleUploadData.sub) {
                        tab = {name: 'summary', label: 'Riepilogo'};
                    }
                } else {
                    cancelModuleUploadData();
                }
                selectedTab.value = tab;
                if (!popstate) {
                    const paramsToPush = [];
                    if ( !['userView','subView'].includes(tab.name) ) {
                        url.searchParams.delete('view-user');
                        url.searchParams.delete('view-sub');
                        pushQueryParams(paramsToPush.push({'view-user': null, 'view-sub': null}));
                    }
                } else if (url.searchParams.has('view-user')) {
                    viewUser(parseInt(url.searchParams.get('view-user'), 10), popstate);
                } else if (url.searchParams.has('view-sub')) {
                    viewSub(parseInt(url.searchParams.get('view-sub'), 10), popstate);
                }
                // FOR FAST DATA REFRESH UNCOMMENT FOLLOWING
                // if (tab.name == 'card') {
                //     getAuthorizedSubscriptionYears();
                //     getProfile();
                // } else if(tab.name == 'summary') {
                //     getProfile();
                // } else
                if (tab.name == 'uploadUserCsv') {
                    if(!document.getElementById('xlsx-loader')){
                        const xlsxLoader = document.createElement('script'),
                        loadedScripts = document.getElementById('loaded-scripts');
                        xlsxLoader.id = 'xlsx-loader';
                        xlsxLoader.src = '/wp-content/plugins/multipop/js/xlsx.full.min.js';
                        loadedScripts.appendChild(xlsxLoader);
                    }
                }
                if (!popstate && tab.name == 'users') {
                    searchUsers();
                }
            }
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
            const {user: parsedUser, discourseUrl, privacyPolicyUrl} = JSON.parse(document.getElementById('__MULTIPOP_DATA__').innerText);
            if (discourseUrl) {
                menuItems.push({name: 'discourseUrl', url: discourseUrl, label: 'Accedi a Discourse'});
            }
            mainOptions.privacyPolicyUrl = privacyPolicyUrl;
            Object.assign(profile, parsedUser);
            getMainOptions();
            generateNotices();
            if (['administrator', 'multipopolare_resp'].includes(profile.role)) {
                searchUsers();
            }
            window.addEventListener('popstate', onPopState);
            const url = new URL(location);
            if (url.searchParams.has('view-user') && profile.role == 'administrator') {
                selectedTab.value.name = 'userView';
                viewUser(parseInt(url.searchParams.get('view-user'), 10), true);
            }
            if (url.searchParams.has('view-sub') && profile.role == 'administrator') {
                selectedTab.value.name = 'subView';
                viewSub(parseInt(url.searchParams.get('view-sub'), 10), true);
            }
            if (!historyTabs.length) {
                historyTabs.unshift(selectedTab.value);
                history.replaceState(JSON.parse(JSON.stringify(historyTabs)), '', location.href);
            }
            serverReq({action: 'get_countries'}).then(r => r.json()).then(({data: {countries: cs}}) => countries.push(...cs));
        });
        function onPopState(e) {
            if (Array.isArray(e.state)){
                console.log(e.state);
                selectTab(e.state[0], true);
                historyTabs.length = 0;
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
                case 'multipopolare_friend':
                    role = 'Amica/o di Multipopolare';
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
        function showCountryName(code) {
            return code && countries.length ? countries.find(c => c.code == code).name : '';
        }
        function validatePhone(p = '') {
            intPhoneInstance.value.instance.setCountry('it');
            intPhoneInstance.value.instance.setNumber(p);
            return parsePhone(intPhoneInstance.value);
        }
        function onInvalidMime() {
            window.alert('Formato file non valido');
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
            birthplaceCountryOpen,
            birthplaceOpen,
            billingCountryOpen,
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
            showSubscriptionStatus,
            otherSubscriptions,
            goodSubscriptions,
            nearActiveSub,
            availableYearsToOrder,
            isProfileCompleted,
            maxBirthDate: maxBirthDate.getFullYear() + '-' + ('0' + (maxBirthDate.getMonth() + 1)).slice(-2) + '-' + ('0' + maxBirthDate.getDate()).slice(-2),
            maxIdCardDate: maxIdCardDate.getFullYear() + '-' + ('0' + (maxIdCardDate.getMonth() + 1)).slice(-2) + '-' + ('0' + maxIdCardDate.getDate()).slice(-2),
            csvUsers,
            loadUsersFromCsv,
            userCsvFields,
            profilePhoneInput,
            userEditPhoneInput,
            parsePhone,
            foundUsersColumns,
            userSearching,
            userSearchTablePagination,
            menuItems,
            csvImportOptions,
            uploadCsvRows,
            intPhoneInstance,
            resendInvitationMail,
            countries,
            showCountryName,
            userSearchSelectableSubYears,
            userSearchSelectableSubStatuses,
            openExternalUrl,
            subscriptionColumns,
            mainOptions,
            newSubscription,
            marketingAgreeShow,
            newsletterAgreeShow,
            publishAgreeShow,
            requestNewSubscription,
            requestingNewSubscription,
            currencyFormatter,
            generateSubscriptionPdf,
            generatingSubscriptionPdf,
            moduleUploadData,
            moduleUploadBegin,
            onInvalidMime,
            moduleUploadDataSend,
            moduleUploadDataSending,
            isValidIdCard,
            viewSub,
            subInView,
            consoleLog: v => console.log(v)
        };
    }
})
.use(Quasar)
.mount('#app');
