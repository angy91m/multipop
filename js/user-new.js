window.onload = () => {
    let roleSelect = document.querySelector('select[name="role"]');
    const emailRow = document.getElementById('email').parentElement,
    options = Array.from(roleSelect.querySelectorAll('option'));
    options.unshift( options.splice(options.findIndex(o => o.value == 'multipopolare_resp'), 1).pop() );
    options.unshift( options.splice(options.findIndex(o => o.value == 'multipopolano'), 1).pop() );
    const optContainer = document.createElement('div');
    options.forEach(o => optContainer.append(o));
    roleSelect.innerHTML = optContainer.innerHTML;
    roleSelect.querySelector('option[value="multipopolano"]').selected = true;
    emailRow.innerHTML += `<br><br><span id="multipop_custom_container">
    <input type="checkbox" id="email_confirmed" name="email_confirmed" value="1" style="width:unset">&nbsp;E-mail confermata<br><br>
    <input type="checkbox" id="send_mail_confirmation" name="send_mail_confirmation" value="1" checked style="width:unset">&nbsp;Invia messaggio per confermare l'e-mail</span>`;
    const confirmedEl = document.getElementById('email_confirmed'),
    sendMailConfirmationEl = document.getElementById('send_mail_confirmation'),
    customContainer = document.getElementById('multipop_custom_container');
    confirmedEl.addEventListener('change', () => {
        if (!confirmedEl.disabled) {
            if (confirmedEl.checked) {
                sendMailConfirmationEl.checked = false;
                sendMailConfirmationEl.disabled = true;
            } else {
                sendMailConfirmationEl.disabled = false;
                sendMailConfirmationEl.checked = true;
            }
        }
    });
    roleSelect.addEventListener('change', () => {
        if (['multipopolano', 'multipopolare_resp'].includes(roleSelect.value)) {
            if (confirmedEl.disabled) {
                customContainer.style.display = 'unset';
                confirmedEl.disabled = false;
                confirmedEl.checked = false;
                confirmedEl.dispatchEvent(new Event('change'));
            }
        } else {
            confirmedEl.disabled = true;
            confirmedEl.checked = true;
            sendMailConfirmationEl.disabled = true;
            sendMailConfirmationEl.checked = false;
            customContainer.style.display = 'none';
        }
    });
};