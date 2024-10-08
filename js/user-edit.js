window.onload = () => {
    document.querySelector('form').removeAttribute('action');
    const {mailConfirmed, _new_email} = JSON.parse(document.getElementById('__MULTIPOP_DATA__').innerText);
    let emailEl = document.getElementById('email'), _new_email_edit = _new_email;
    const primaryEmail =  emailEl.value,
    emailOriginal = _new_email || emailEl.value,
    emailRow = emailEl.parentElement,
    roleSelect = document.querySelector('select[name="role"]'),
    roleMultipop = ['multipopolano', 'multipopolare_resp'].includes(roleSelect.value),
    options = Array.from(roleSelect.querySelectorAll('option'));
    options.unshift( options.splice(options.findIndex(o => o.value == 'multipopolare_resp'), 1).pop() );
    options.unshift( options.splice(options.findIndex(o => o.value == 'multipopolano'), 1).pop() );
    const optContainer = document.createElement('div');
    options.forEach(o => optContainer.append(o));
    roleSelect.innerHTML = optContainer.innerHTML;
    emailRow.innerHTML += `<br><br>
    <span id="multipop_custom_container" style="display:${ roleMultipop ? 'unset': 'none'}">
    <input type="checkbox" id="email_confirmed" name="email_confirmed" value="1" ${!roleMultipop || mailConfirmed ? 'disabled checked' : ''} />&nbsp;E-mail confermata<br><br>
    <button class="button" id="send_mail_confirmation_button" name="resend_mail_confirmation" value="1" style="display:${!roleMultipop || mailConfirmed ? 'none' : 'unset'}" />Invia messaggio per confermare l'e-mail</button>
    <button class="button" id="revoke_mail_confirmation_button" name="revoke_mail_confirmation" value="1" style="display:${roleMultipop && mailConfirmed ? 'unset': 'none'}">Revoca conferma e-mail</button>
    <span id="send_mail_confirmation_container" style="display:none;"><input type="checkbox" id="send_mail_confirmation" name="send_mail_confirmation" value="1" disabled />&nbsp;Invia messaggio per confermare l'e-mail</span></span>`;
    const confirmedEl = document.getElementById('email_confirmed'),
    sendMailConfirmationButton = document.getElementById('send_mail_confirmation_button'),
    sendMailConfirmationContEl = document.getElementById('send_mail_confirmation_container'),
    sendMailConfirmationEl = document.getElementById('send_mail_confirmation'),
    customContainer = document.getElementById('multipop_custom_container'),
    revokeMailConfirmationButton = document.getElementById('revoke_mail_confirmation_button');
    emailEl = document.getElementById('email');
    emailEl.addEventListener('input', () => {
        if (customContainer.style.display !== 'none') {
            if (emailEl.value == emailOriginal || (_new_email && emailEl.value == primaryEmail)) {
                sendMailConfirmationButton.style.display = mailConfirmed || (_new_email && emailEl.value == primaryEmail) ? 'none' : 'unset';
                revokeMailConfirmationButton.style.display = mailConfirmed || (_new_email && emailEl.value == primaryEmail) ? 'unset' : 'none';
                if (sendMailConfirmationContEl.style.display != 'none') {
                    sendMailConfirmationEl.checked = false;
                    sendMailConfirmationEl.disabled = true;
                    sendMailConfirmationContEl.style.display = 'none';
                    confirmedEl.disabled = mailConfirmed;
                    if (mailConfirmed) {
                        confirmedEl.checked = true;
                    }
                    sendMailConfirmationButton.disabled = mailConfirmed || confirmedEl.checked;
                } else if (confirmedEl.disabled) {
                    confirmedEl.disabled = mailConfirmed;
                    confirmedEl.checked = mailConfirmed;
                }
            } else {
                revokeMailConfirmationButton.style.display = 'none';
                sendMailConfirmationButton.style.display = 'none';
                if (sendMailConfirmationEl.disabled) {
                    sendMailConfirmationContEl.style.display = 'unset';
                    sendMailConfirmationEl.disabled = confirmedEl.disabled ? false : confirmedEl.checked;
                    sendMailConfirmationEl.checked = sendMailConfirmationEl.disabled ? false : true;
                }
                if (confirmedEl.disabled) {
                    confirmedEl.disabled = false;
                    confirmedEl.checked = false;
                }
            }
        }
    });
    confirmedEl.addEventListener('change', () => {
        const checked = confirmedEl.checked;
        if (sendMailConfirmationContEl.style.display == 'unset') {
            sendMailConfirmationEl.disabled = checked;
            sendMailConfirmationEl.checked = !checked;
        } else if (sendMailConfirmationButton.style.display == 'unset') {
            sendMailConfirmationButton.disabled = checked;
        }
    });
    roleSelect.addEventListener('change', () => {
        if (['multipopolano', 'multipopolare_resp'].includes(roleSelect.value)) {
            if (_new_email) {
                emailEl.value = _new_email_edit;
            }
            if (confirmedEl.disabled) {
                customContainer.style.display = 'unset';
                confirmedEl.disabled = mailConfirmed || (_new_email && emailEl.value == primaryEmail);
                confirmedEl.checked = mailConfirmed || (_new_email && emailEl.value == primaryEmail);
                confirmedEl.dispatchEvent(new Event('change'));
                emailEl.dispatchEvent(new Event('input'));
            }
            if (mailConfirmed || (_new_email && emailEl.value == primaryEmail)) {
                revokeMailConfirmationButton.style.display = 'unset';
            } else {
                revokeMailConfirmationButton.style.display = 'none';
            }
        } else {
            if (_new_email) {
                _new_email_edit = emailEl.value;
                emailEl.value = primaryEmail;
            }
            confirmedEl.disabled = true;
            confirmedEl.checked = true;
            sendMailConfirmationEl.disabled = true;
            sendMailConfirmationEl.checked = false;
            customContainer.style.display = 'none';
            confirmedEl.dispatchEvent(new Event('change'));
            revokeMailConfirmationButton.style.display = 'none';
        }
    });
    const setMasterKeyButton = document.getElementById('set_master_key_button');
    if (setMasterKeyButton) {
        const cancelSetMasterKeyButton = document.getElementById('cancel_set_master_key_button'),
        setMasterKeyContainer = document.getElementById('set_master_key_container'),
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
        setMasterKeyButton.addEventListener('click', e => {
            e.preventDefault();
            submitButton.disabled = true;
            setMasterKeyButton.style.display = 'none';
            setMasterKeyContainer.style.display = 'unset';
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
            setMasterKeyButton.style.display = 'unset';
            setMasterKeyContainer.style.display = 'none';
            masterKeyField.removeEventListener('input', checkMasterKeyConfirmation);
            masterKeyFieldConfirmation.removeEventListener('input', checkMasterKeyConfirmation);
            submitButton.disabled = false;
            masterKeyField.disabled = true;
            currentUserMasterKey.disabled = true;
        });
    }
    if (['multipopolano', 'multipopolare_resp'].includes(roleSelect.value) && _new_email ) {
        emailEl.value = _new_email;
    }
};
