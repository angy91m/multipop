import { createApp, ref, computed, reactive } from '/wp-content/plugins/multipop/js/vue.esm-browser.js';
const passwordRegex = {
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
        const password = ref(''),
        passwordConfirm = ref(''),
        requesting = ref(false),
        isValidForm = computed( () => isValidPassword() && isValidPasswordConfirm() ),
        startedFields = reactive(new Set([])),
        errorFields = reactive(new Set([])),
        acceptedSymbols = passwordRegex.acceptedSymbols;
        function startField(field) {
            errorFields.clear();
            startedFields.add(field);
        }
        function isValidPassword() {
            return !errorFields.has('password') && passwordRegex.test(password.value);
        }
        function isValidPasswordConfirm() {
            return password.value === passwordConfirm.value;
        }
        async function send(e) {
            e.preventDefault();
            requesting.value = true;
            const res = await fetch(location.href, {
                method: 'POST',
                body: JSON.stringify({
                    password: password.value,
                    nonce: document.getElementById('mpop-password-change-nonce').value
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            try {
                const json = await res.json();
                if (res.ok && json.data == 'ok') console.log('ok');
                if (json.error) json.error.forEach(e => errorFields.add(e));
            } catch {
                errorFields.add('server');
            }
            requesting.value = false;
        }
        return {
            password,
            passwordConfirm,
            isValidForm,
            send,
            startedFields,
            isValidPassword,
            isValidPasswordConfirm,
            errorFields,
            startField,
            acceptedSymbols,
            requesting
        };
    }
}).mount('#app');