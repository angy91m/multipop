const mailConfirmed = !!document.getElementById('mail_confirmed').value;
let emailEl = document.getElementById('email');
const emailOriginal = emailEl.value,
emailRow = emailEl.parentElement;
emailRow.innerHTML += `<br><br>
<input type="checkbox" id="email_confirmed" name="email_confirmed" value="1" ${mailConfirmed ? 'disabled checked' : ''}>&nbsp;E-mail confermata<br><br>
<button id="send_mail_confirmation_button" name="resend_mail_confirmation" value="1" style="display:${mailConfirmed ? 'none' : 'unset'}">Invia messaggio per confermare l'e-mail</button>
<span id="send_mail_confirmation_container" style="display:none;"><input type="checkbox" id="send_mail_confirmation" name="send_mail_confirmation" value="1" disabled />&nbsp;Invia messaggio per confermare l'e-mail</span>`;
const confirmedEl = document.getElementById('email_confirmed'),
sendMailConfirmationButton = document.getElementById('send_mail_confirmation_button'),
sendMailConfirmationContEl = document.getElementById('send_mail_confirmation_container'),
sendMailConfirmationEl = document.getElementById('send_mail_confirmation');
emailEl = document.getElementById('email');
emailEl.addEventListener('input', () => {
    if (emailEl.value == emailOriginal) {
        if (sendMailConfirmationContEl.style.display != 'none') {
            sendMailConfirmationEl.checked = false;
            sendMailConfirmationEl.disabled = true;
            sendMailConfirmationContEl.style.display = 'none';
            confirmedEl.disabled = mailConfirmed;
            confirmedEl.checked = mailConfirmed;
        } else if (confirmedEl.disabled) {
            confirmedEl.disabled = mailConfirmed;
            confirmedEl.checked = mailConfirmed;
        }
        sendMailConfirmationButton.style.display = mailConfirmed ? 'none' : 'unset';
    } else {
        sendMailConfirmationButton.style.display = 'none';
        if (confirmedEl.disabled) {
            confirmedEl.disabled = false;
            confirmedEl.checked = false;
        }
        if (sendMailConfirmationEl.disabled) {
            sendMailConfirmationContEl.style.display = 'unset';
            sendMailConfirmationEl.disabled = false;
            sendMailConfirmationEl.checked = true;
        }
    }
});