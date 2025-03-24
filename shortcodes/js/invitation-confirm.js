import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
import Fuse from '/wp-content/plugins/multipop/js/fuse.js';
import IntlTelInput from '/wp-content/plugins/multipop/js/vue-tel-input.js';
const { createApp, ref, computed, reactive, defineAsyncComponent, onMounted } = Vue,
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
usernameRegex = {
    rr: [
        /^[a-z0-9._-]{3,20}$/s,
        /[a-z0-9]/s,
        {
            test(username) {
                return username.charAt(0) !== '.'
                    && username.charAt(0) !== '-'
                    && username.charAt(username.length-1) !== '.'
                    && username.charAt(username.length-1) !== '-'
                    && !username.startsWith('mp_');
            }
        }
    ],
    test(username) {
        let res = true;
        for (let i = 0; i < usernameRegex.rr.length && res; i++) {
            res = usernameRegex.rr[i].test(username);
        }
        return res;
    }
},
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
{role} = JSON.parse(document.getElementById('invitation-props').innerText),
maxBirthDate = new Date();
maxBirthDate.setFullYear(maxBirthDate.getFullYear() - 18);
let triggerSearchTimeout;
createApp({
    components: {
        'v-select': defineAsyncComponent(() => vSel),
        'v-intl-phone': IntlTelInput
    },
    setup() {
        const user = reactive({
            username: '',
            password: '',
            password_confirm: '',
            first_name: '',
            last_name: '',
            mpop_birthdate: undefined,
            mpop_birthplace_country: '',
            mpop_birthplace: '',
            mpop_billing_country: '',
            mpop_billing_city: '',
            mpop_billing_state: '',
            mpop_billing_zip: '',
            mpop_billing_address: '',
            mpop_phone: '',
            mpop_subscription_marketing_agree: true,
            mpop_subscription_newsletter_agree: true,
            mpop_subscription_publish_agree: true
        }),
        countries = reactive([]),
        marketingAgreeShow = ref(false),
        newsletterAgreeShow = ref(false),
        publishAgreeShow = ref(false),
        birthplaceCountryOpen = ref(false),
        birthplaceOpen = ref(false),
        billingCountryOpen = ref(false),
        billingCityOpen = ref(false),
        userNotices = reactive([]),
        birthCities = reactive([]),
        billingCities = reactive([]),
        phoneInput = ref('phoneInput'),
        requesting = ref(false),
        isValidBirthPlace = computed(()=>user.mpop_birthplace_country && (user.mpop_birthplace_country != 'ita' || user.mpop_birthplace)),
        isValidBillingPlace = computed(()=>user.mpop_billing_country && (user.mpop_billing_country != 'ita' || (user.mpop_billing_city && user.mpop_billing_state && user.mpop_billing_zip))),
        isValidForm = computed( () => role == 'multipopolano' ?
                isValidUsername()
                && isValidPassword()
                && isValidPasswordConfirm()
                && user.first_name.trim()
                && user.last_name.trim()
                && user.mpop_birthdate
                && isValidBirthPlace.value
                && isValidBillingPlace.value
                && user.mpop_billing_address.trim()
                && user.mpop_phone
            :
                isValidPassword() && isValidPasswordConfirm()
        ),
        startedFields = reactive(new Set([])),
        errorFields = reactive(new Set([])),
        acceptedSymbols = passwordRegex.acceptedSymbols;
        function startField(field) {
            errorFields.clear();
            startedFields.add(field);
        }
        function isValidUsername() {
            return !errorFields.has('username') && usernameRegex.test(user.username.trim());
        }
        function isValidPassword() {
            return !errorFields.has('password') && passwordRegex.test(user.password);
        }
        function isValidPasswordConfirm() {
            return user.password === user.password_confirm;
        }
        async function activateAccount(e) {
            e.preventDefault();
            requesting.value = true;
            let reqObj;
            if (role == 'multipopolano') {
                reqObj = {
                    ...user,
                    username: user.username.trim(),
                    password: user.password.trim(),
                    action: 'activate_account'
                };
                if (user.mpop_birthplace) reqObj.mpop_birthplace = user.mpop_birthplace.codiceCatastale;
                if (user.mpop_billing_city) reqObj.mpop_billing_city = user.mpop_billing_city.codiceCatastale;
                delete reqObj['password_confirm'];
            } else {
                reqObj = {
                    password: user.password.trim(),
                    action: 'activate_account'
                };
            }
            const res = await serverReq(reqObj);
            try {
                const json = await res.json();
                if (res.ok && json.data) {
                    location.href = json.data;
                } else if (json.error) {
                    json.error.forEach(e => {
                        errorFields.add(e);
                    });
                };
            } catch {
                errorFields.add('server');
            }
            requesting.value = false;
        }
        function fuseSearch(options, search) {
            const fuse = new Fuse(options, {
                keys: ['label'],
                shouldSort: true
            });
            return search.trim().length ? fuse.search(search).map(({item}) => item) : fuse.list;
        }
        function searchOpen(tag) {
            const openVar = eval(tag + 'Open');
            openVar.value = true;
            setTimeout(()=> document.querySelector('#'+tag+'-select .vs__search').select(),300);
        }
        async function birthCitiesSearch(searchText) {
            if (user) {
                errorFields.clear();
            }
            if (searchText.trim().length > 1) {
                const res = await serverReq({
                    action: 'get_birth_cities',
                    mpop_birthplace: searchText.trim(),
                    mpop_birthdate: user.mpop_birthdate
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
                            console.error(error);
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
            errorFields.clear();
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
                            console.error(error);
                        } else {
                            console.error('Unknown error');
                        }
                    } catch {
                        console.error('Unknown error');
                    }
                }
            }

        }
        function parsePhone(input) {
            return input.instance.isValidNumber() ? input.instance.getNumber(1).replace(' ', '-').replaceAll(' ', '').replace('-', ' ') : '';
        }
        function triggerSearch(txt, loading, callable, ...args) {
            clearTimeout(triggerSearchTimeout);
            loading(true);
            const func = eval(callable);
            triggerSearchTimeout = setTimeout( () => func(txt, ...args).then(() => loading(false)), 500);
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
        function serverReq(obj) {
            return fetch(location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ...obj,
                    'mpop-invite-nonce': document.getElementById('mpop-invite-nonce').value,
                })
            });
        }
        onMounted(async () => {
            const countriesRes = await serverReq({action: 'get_countries'});
            countries.push(...(await countriesRes.json()).data.countries);
            if (role == 'multipopolano') {
                const profileRes = await serverReq({action: 'get_profile'}),
                {data: {profile}} = await profileRes.json();
                if (role == 'multipopolano'){
                    user.first_name = profile.first_name || '';
                    user.last_name = profile.last_name || '';
                    user.mpop_birthdate = profile.mpop_birthdate || undefined;
                    user.mpop_birthplace_country = profile.mpop_birthplace_country || '';
                    user.mpop_birthplace = profile.mpop_birthplace || '';
                    user.mpop_billing_country = profile.mpop_billing_country || '';
                    user.mpop_billing_city = profile.mpop_billing_city || '';
                    user.mpop_billing_state = profile.mpop_billing_state || '';
                    user.mpop_billing_zip = profile.mpop_billing_zip || '';
                    user.mpop_billing_address = profile.mpop_billing_address || '';
                    if (profile.mpop_phone) phoneInput.value.instance.setNumber(profile.mpop_phone);
                }
                user.mpop_subscription_marketing_agree = profile.mpop_marketing_agree || false;
                user.mpop_subscription_newsletter_agree = profile.mpop_newsletter_agree || false;
                user.mpop_subscription_publish_agree = profile.mpop_publish_agree || false;
            }
        });
        return {
            user,
            isValidForm,
            activateAccount,
            startedFields,
            isValidUsername,
            isValidPassword,
            isValidPasswordConfirm,
            errorFields,
            startField,
            acceptedSymbols,
            requesting,
            triggerSearch,
            fuseSearch,
            birthplaceCountryOpen,
            birthplaceOpen,
            billingCountryOpen,
            billingCityOpen,
            birthCities,
            billingCities,
            phoneInput,
            parsePhone,
            searchOpen,
            addSuppressToLabel,
            marketingAgreeShow,
            newsletterAgreeShow,
            publishAgreeShow,
            countries,
            role,
            maxBirthDate: maxBirthDate.getFullYear() + '-' + ('0' + (maxBirthDate.getMonth() + 1)).slice(-2) + '-' + ('0' + maxBirthDate.getDate()).slice(-2)
        };
    }
}).mount('#app');