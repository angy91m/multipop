function sendTestMail(e) {
    e.preventDefault();
    document.getElementById('send_test_mail_button').disabled = true;
    if ( !document.getElementById('send_test_mail').value.trim() ) {
        document.getElementById('send_test_mail_button').disabled = false;
    } else {
        document.getElementById('mpop_settings_form').submit();
    }
}
function forceUpdateTempmail(e) {
    e.preventDefault();
    document.getElementById('force_tempmail_update_button').disabled = true;
    document.getElementById('force_tempmail_update').value = '1';
    document.getElementById('mpop_settings_form').submit();
}
function forceUpdateComuni(e) {
    e.preventDefault();
    document.getElementById('force_comuni_update_button').disabled = true;
    document.getElementById('force_comuni_update').value = '1';
    document.getElementById('mpop_settings_form').submit();
}
function forceReloadDiscourseGroups(e) {
    e.preventDefault();
    document.getElementById('force_discourse_groups_reload_button').disabled = true;
    document.getElementById('force_discourse_groups_reload').value = '1';
    document.getElementById('mpop_settings_form').submit();
}
function forceUpdateComuni(e) {
    e.preventDefault();
    document.getElementById('force_comuni_update_button').disabled = true;
    document.getElementById('force_comuni_update').value = '1';
    document.getElementById('mpop_settings_form').submit();
}
function saveSettings(e) {
    document.getElementById('mpop_settings_save').disabled = true;
    e.preventDefault();
    document.getElementById('send_test_mail').value = '';
    document.getElementById('mpop_settings_form').submit();
}
function setMasterKey(e) {
    e.preventDefault();
    document.getElementById('master_doc_key_button').style.display = 'none';
    document.getElementById('master_doc_key_field').style.display = 'unset';
}
document.getElementById('send_test_mail_button').addEventListener('click', sendTestMail);
document.getElementById('force_tempmail_update_button').addEventListener('click', forceUpdateTempmail);
document.getElementById('force_comuni_update_button').addEventListener('click', forceUpdateComuni);
document.getElementById('force_discourse_groups_reload_button').addEventListener('click', forceReloadDiscourseGroups);
document.getElementById('mpop_settings_save').addEventListener('click', saveSettings);
document.getElementById('master_doc_key_button').addEventListener('click', setMasterKey);