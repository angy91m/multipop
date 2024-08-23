import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
import * as Vue from '/wp-content/plugins/multipop/js/vue.esm-browser.js';
//import * as s from '/wp-content/plugins/multipop/js/vue-select.js';
const { createApp, ref, computed, reactive, onMounted, onUnmounted, defineAsyncComponent } = Vue,
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
mailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/s;
createApp({
    components: {
        'v-select': defineAsyncComponent(() => vSel)
    },
    setup() {
        const selectedTab = ref('summary'),
        displayNav = ref(false),
        user = reactive({}),
        userInEditing = reactive({}),
        profileEditing = ref(false),
        birthplaceOpen = ref(false),
        billingCityOpen = ref(false),
        saving = ref(false),
        savingProfileErrors = reactive([]),
        userNotices = reactive([]),
        helloName = computed(()=> user.first_name ? user.first_name : user.login),
        birthCities = reactive([]),
        billingCities = reactive([]),
        validProfileForm = computed(()=>
            mailRegex.test(userInEditing.email.trim())
            && userInEditing.first_name.trim()
            && userInEditing.last_name.trim()
            && userInEditing.mpop_birthdate
            && userInEditing.mpop_birthplace
            && userInEditing.mpop_billing_city
            && userInEditing.mpop_billing_state
            && userInEditing.mpop_billing_address.trim()
            && userInEditing.mpop_billing_zip
        ),
        maxBirthDate = new Date();
        maxBirthDate.setFullYear(maxBirthDate.getFullYear() - 18);
        async function birthCitiesSearch(searchText) {
            savingProfileErrors.length = 0;
            if (searchText.trim().length > 1) {
                const res = await serverReq({
                    'action': 'get_birth_cities',
                    'mpop_birthplace': searchText.trim(),
                    'mpop_birthdate': userInEditing.mpop_birthdate
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
        async function billingCitiesSearch(searchText) {
            savingProfileErrors.length = 0;
            if (searchText.trim().length > 1) {
                const res = await serverReq({
                    'action': 'get_billing_cities',
                    'mpop_billing_city': searchText.trim()
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
        async function updateProfile() {
            saving.value = true;
            savingProfileErrors.length = 0;
            const res = await serverReq({
                'action': 'update_profile',
                'email': userInEditing.email.trim(),
                'first_name': userInEditing.first_name.trim(),
                'last_name': userInEditing.last_name.trim(),
                'mpop_birthdate': userInEditing.mpop_birthdate,
                'mpop_birthplace': userInEditing.mpop_birthplace.codiceCatastale,
                'mpop_billing_city': userInEditing.mpop_billing_city.codiceCatastale,
                'mpop_billing_address': userInEditing.mpop_billing_address.trim(),
                'mpop_billing_zip': userInEditing.mpop_billing_zip
            });
            if (res.ok) {
                const newUser = await res.json();
                if (newUser.data && newUser.data.user) {
                    Object.assign(user, newUser.data.user);
                    profileEditing.value = false;
                    generateNotices();
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
            saving.value = false;
        }
        function generateNotices() {
            userNotices.length = 0;
            const missingFields = [];
            if (user._new_email) {
                userNotices.push({
                    type: 'warning',
                    msg: 'La tua nuova e-mail non è confermata. Fai clic sul link che ti è stato inviato all\'indirizzo che hai indicato.'
                });
            }
            if (!user.first_name) {
                missingFields.push('Nome');
            }
            if (!user.last_name) {
                missingFields.push('Cognome');
            }
            if (!user.mpop_birthdate) {
                missingFields.push('Data di nascita');
            }
            if (!user.mpop_birthplace) {
                missingFields.push('Luogo di nascita');
            }
            if (!user.mpop_billing_address) {
                missingFields.push('Indirizzo di residenza');
            }
            if (!user.mpop_billing_city) {
                missingFields.push('Comune di residenza');
            }
            if (!user.mpop_billing_zip) {
                missingFields.push('CAP');
            }
            if (!user.mpop_billing_state) {
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
            Object.assign(userInEditing, user);
            if (userInEditing.birthplace) {
                birthCities.push(userInEditing.birthplace);
            }
            if (userInEditing.billing_city) {
                billingCities.push(userInEditing.billing_city);
            }
        }
        function cancelEditProfile() {
            profileEditing.value = false;
            for(const key in userInEditing) {
                delete userInEditing[key];
            }
        }
        function selectTab(tabName) {
            if (selectedTab.value != tabName) {
                cancelEditProfile();
                selectedTab.value = tabName;
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
                body: JSON.stringify(obj)
            });
        }
        onMounted(() => {
            const {user: parsedUser} = JSON.parse(document.getElementById('__MULTIPOP_DATA__').innerText);
            Object.assign(user, parsedUser);
            generateNotices();
        });
        function searchOpen(tag) {
            const openVar = eval(tag + 'Open');
            openVar.value = true;
            setTimeout(()=> document.querySelector('#'+tag+'-select .vs__search').select(),300);
        }
        return {
            selectedTab,
            user,
            displayNav,
            helloName,
            userNotices,
            dismissNotice,
            profileEditing,
            userInEditing,
            editProfile,
            cancelEditProfile,
            selectTab,
            displayLocalDate,
            birthCities,
            birthCitiesSearch,
            birthplaceOpen,
            billingCityOpen,
            billingCities,
            billingCitiesSearch,
            updateProfile,
            searchOpen,
            saving,
            savingProfileErrors,
            validProfileForm,
            maxBirthDate: maxBirthDate.getFullYear() + '-' + ('0' + (maxBirthDate.getMonth() + 1)).slice(-2) + '-' + ('0' + maxBirthDate.getDate()).slice(-2)
        };
    }
})
.mount('#app');