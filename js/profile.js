window.onload = () => {
    const toDisable = [
        '.user-first-name-wrap input',
        '.user-last-name-wrap input',
        '.user-nickname-wrap input',
        '.user-display-name-wrap select',
        '.user-url-wrap input',
        '#email'
    ];
    toDisable.forEach(e => document.querySelector(e).readOnly = true);
    const changeMasterKeyButton = document.getElementById('change_master_key_button');
    if (changeMasterKeyButton) {
        const cancelSetMasterKeyButton = document.getElementById('cancel_change_master_key_button'),
        changeMasterKeyContainer = document.getElementById('change_master_key_container'),
        masterKeyField = document.getElementById('master_key'),
        masterKeyFieldConfirmation = document.getElementById('master_key_confirmation'),
        currentUserMasterKey = document.getElementById('current_user_master_key'),
        masterKeyError = document.getElementById('master_key_error'),
        submitButton = document.querySelector('p.submit input'),
        checkMasterKeyConfirmation = () => {
            if (masterKeyField.value !== masterKeyFieldConfirmation.value) {
                submitButton.disabled = true;
                masterKeyField.classList.add('bad-input');
                masterKeyFieldConfirmation.classList.add('bad-input');
                masterKeyError.style.display = 'unset';
            } else {
                submitButton.disabled = false;
                masterKeyField.classList.remove('bad-input');
                masterKeyFieldConfirmation.classList.remove('bad-input');
                masterKeyError.style.display = 'none';
            }
        };
        changeMasterKeyButton.addEventListener('click', e => {
            e.preventDefault();
            submitButton.disabled = true;
            changeMasterKeyButton.style.display = 'none';
            changeMasterKeyContainer.style.display = 'unset';
            masterKeyField.disabled = false;
            currentUserMasterKey.disabled = false;
            masterKeyField.value = '';
            masterKeyFieldConfirmation.value = '';
            currentUserMasterKey.value = '';
            masterKeyField.addEventListener('input', checkMasterKeyConfirmation);
            masterKeyFieldConfirmation.addEventListener('input', checkMasterKeyConfirmation);
        });
        cancelSetMasterKeyButton.addEventListener('click', e => {
            e.preventDefault();
            changeMasterKeyButton.style.display = 'unset';
            changeMasterKeyContainer.style.display = 'none';
            masterKeyField.removeEventListener('input', checkMasterKeyConfirmation);
            masterKeyFieldConfirmation.removeEventListener('input', checkMasterKeyConfirmation);
            submitButton.disabled = false;
            masterKeyField.disabled = true;
            currentUserMasterKey.disabled = true;
        });
    }
};