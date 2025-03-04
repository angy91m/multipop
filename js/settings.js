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
function purgeDeactivate(e) {
    e.preventDefault();
    if (confirm('Vuoi davvero disattivare il plugin ed eliminare tutti i dati?')) {
        document.getElementById('purge_deactivate_button').disabled = true;
        document.getElementById('purge_deactivate').value = '1';
        document.getElementById('mpop_settings_form').submit();
    }
}
async function savePlugin(e) {
    e.preventDefault();
    const res = await fetch(location.href, {
        method: 'POST',
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: new URLSearchParams({save_plugin: '1', 'mpop-admin-settings-nonce': document.getElementById('mpop-admin-settings-nonce').value})
    });
    if (res.ok) {
        const resTxt = await res.text(),
        doc = document.createElement('div');
        doc.innerHTML = resTxt;
        const jsEl = doc.querySelector('#mpop_json_res');
        if (jsEl) {
            const jsRes = JSON.parse(jsEl.innerHTML),
            {data, error} = jsRes;
            if (error) console.error(error);
            if (data) {
                const linkSource = `data:application/zip;base64,${data}`,
                downloadLink = document.createElement('a');
                downloadLink.href = linkSource;
                downloadLink.target = '_blank';
                downloadLink.download = 'bak.zip';
                document.body.appendChild(downloadLink);
                downloadLink.click();
            }
        }
    }
}
document.getElementById('send_test_mail_button').addEventListener('click', sendTestMail);
document.getElementById('force_tempmail_update_button').addEventListener('click', forceUpdateTempmail);
document.getElementById('force_comuni_update_button').addEventListener('click', forceUpdateComuni);
document.getElementById('force_discourse_groups_reload_button').addEventListener('click', forceReloadDiscourseGroups);
document.getElementById('mpop_settings_save').addEventListener('click', saveSettings);
document.getElementById('master_doc_key_button').addEventListener('click', setMasterKey);
document.getElementById('purge_deactivate_button').addEventListener('click', purgeDeactivate);
document.getElementById('save_plugin_button').addEventListener('click', savePlugin);