window.onload = () => {
    const {mailConfirmed, currentUserHasMasterKey} = JSON.parse(document.getElementById('__MULTIPOP_DATA__').innerText);
    let emailEl = document.getElementById('email');
    const emailOriginal = emailEl.value,
    emailRow = emailEl.parentElement;
    let roleSelect = document.querySelector('select[name="role"]');
    const roleMultipop = ['customer', 'multipopolare_resp'].includes(roleSelect.value),
    options = Array.from(roleSelect.querySelectorAll('option'));
    options.unshift( options.splice(options.findIndex(o => o.value == 'multipopolare_resp'), 1).pop() );
    options.unshift( options.splice(options.findIndex(o => o.value == 'customer'), 1).pop() );
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
    roleSelect = document.querySelector('select[name="role"]');
    emailEl = document.getElementById('email');
    emailEl.addEventListener('input', () => {
        if (emailEl.value == emailOriginal) {
            sendMailConfirmationButton.style.display = mailConfirmed ? 'none' : 'unset';
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
        if (['customer', 'multipopolare_resp'].includes(roleSelect.value)) {
            if (confirmedEl.disabled) {
                customContainer.style.display = 'unset';
                confirmedEl.disabled = mailConfirmed;
                confirmedEl.checked = mailConfirmed;
                confirmedEl.dispatchEvent(new Event('change'));
            }
            if (mailConfirmed) {
                revokeMailConfirmationButton.style.display = 'unset';
            } else {
                revokeMailConfirmationButton.style.display = 'none';
            }
        } else {
            confirmedEl.disabled = true;
            confirmedEl.checked = true;
            sendMailConfirmationEl.disabled = true;
            sendMailConfirmationEl.checked = false;
            customContainer.style.display = 'none';
            confirmedEl.dispatchEvent(new Event('change'));
            revokeMailConfirmationButton.style.display = 'none';
        }
    });
};
