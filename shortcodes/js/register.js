import { createApp, ref, computed, reactive, onMounted, onUnmounted } from '/wp-content/plugins/multipop/js/vue.esm-browser.js';
const mailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/s,
usernameRegex = {
    rr: [
        /^[a-z0-9._-]{3,24}$/s,
        /[a-z0-9]/s,
        {
            test(username) {
                return username.charAt(0) !== '.'
                    && username.charAt(0) !== '-'
                    && username.charAt(username.length-1) !== '.'
                    && username.charAt(username.length-1) !== '-';
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
        /[ |\\!"£$%&/()=?'^,.;:_@°#*+[\]{}_-]+/s
    ],
    test(password) {
        if (password.length < 8 || password.length > 64) return false;
        let validRegex = 0;
        passwordRegex.rr.forEach(r => validRegex += r.test(password) ? 1 : 0);
        return validRegex >= 3;
    },
    acceptedSymbols: "SPACE | \\ ! \" £ $ % & / ( ) = ? ' ^ , . ; : _ @ ° # * + [ ] { } _ -"
};
createApp({
    setup() {
        const username = ref(''),
        email = ref(''),
        password = ref(''),
        passwordConfirm = ref(''),
        registered = ref(false),
        requesting = ref(false),
        hcaptchaRes = ref(''),
        isValidForm = computed( () => isValidCaptcha() && isValidUsername() && isValidEmail() && isValidPassword() && isValidPasswordConfirm() ),
        startedFields = reactive(new Set([])),
        errorFields = reactive(new Set([])),
        acceptedSymbols = passwordRegex.acceptedSymbols;
        let observer = null;
        onMounted(()=> {
            observer = new MutationObserver(() => {
                hcaptchaRes.value = document.querySelector('input[name="hcaptcha-response"]').value.trim();
            });
            observer.observe(document.querySelector('input[name="hcaptcha-response"]'), {attributes: true, attributeFilter: ['value']});
        });
        onUnmounted(()=> observer && observer.disconnect());
        function startField(field) {
            errorFields.clear();
            startedFields.add(field);
        }
        function isValidEmail() {
            return !errorFields.has('email') && mailRegex.test(email.value.trim());
        }
        function isValidUsername() {
            return !errorFields.has('username') && usernameRegex.test(username.value.trim());
        }
        function isValidPassword() {
            return !errorFields.has('password') && passwordRegex.test(password.value);
        }
        function isValidPasswordConfirm() {
            return password.value === passwordConfirm.value;
        }
        function isValidCaptcha() {
            return hcaptchaRes.value;
        }
        async function register(e) {
            e.preventDefault();
            requesting.value = true;
            const res = await fetch(location.href, {
                method: 'POST',
                body: JSON.stringify({
                    username: username.value.trim(),
                    email: email.value.trim(),
                    password: password.value,
                    nonce: document.getElementById('mpop-register-nonce').value,
                    'hcaptcha-response': hcaptchaRes.value
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            try {
                const json = await res.json();
                if (res.ok && json.data == 'ok') registered.value = true;
                if (json.error) {
                    json.error.forEach(e => {
                        if (e == 'captcha') {
                            document.querySelector('.h-captcha').classList.add('bad-captcha');
                        } else {
                            errorFields.add(e);
                        }
                    });
                };
            } catch {
                errorFields.add('server');
            }
            requesting.value = false;
        }
        return {
            username,
            email,
            password,
            passwordConfirm,
            isValidForm,
            register,
            startedFields,
            isValidEmail,
            isValidUsername,
            isValidPassword,
            isValidPasswordConfirm,
            isValidCaptcha,
            registered,
            errorFields,
            startField,
            acceptedSymbols,
            requesting
        };
    }
}).mount('#app');