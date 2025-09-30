<?php
defined( 'ABSPATH' ) || exit;
if ( !$this->current_user_is_admin() ) {
    echo '<p>Accesso non consentito</p>';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mpop-admin-settings-nonce'])) {
    require('post/settings.php');
}
$comuni_update_errors = $this->check_update_comuni();
foreach($comuni_update_errors as $err) {
    $this->add_admin_notice($err);
}
do_action('mpop_settings_notices', $this->get_settings());
?>
<form method="POST" id="mpop_settings_form">
    <h3>URL delle liste domini con indirizzi e-mail temporanei</h3>
    <h4>Bloccati</h4>
    <i>Uno per riga</i>
    <textarea name="tempmail_urls_block" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",$this->settings['tempmail_urls']['block'])?></textarea>
    <h4>Permessi</h4>
    <i>Uno per riga</i>
    <textarea name="tempmail_urls_allow" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",$this->settings['tempmail_urls']['allow'])?></textarea>
    <h4>Ultimo aggiornamento</h4>
    <span><?=$this::show_date_time($this->settings['last_tempmail_update'])?></span>
    <input type="hidden" id="force_tempmail_update" name="force_tempmail_update" value="" />
    <br><br>
    <button class="button" id="force_tempmail_update_button">Forza aggiornamento</button>
    <hr>
    <h3>Comuni</h3>
    <h4>Ultimo aggiornamento</h4>
    <span><?=$this::show_date_time($this->last_comuni_update())?></span>
    <input type="hidden" id="force_comuni_update" name="force_comuni_update" value="" />
    <br><br>
    <button class="button" id="force_comuni_update_button">Forza aggiornamento</button>
    <hr>
    <h3>Discourse</h3>
    <input type="hidden" id="force_discourse_groups_reload" name="force_discourse_groups_reload" value="" />
    <br><br>
    <button class="button" id="force_discourse_groups_reload_button">Ricarica cache gruppi</button>
    <hr>
    <h3>Configurazione e-mail</h3>
    <h4>Host</h4>
    <input type="text" name="mail_host" value="<?=$this->settings['mail_host']?>" />
    <h4>Porta</h4>
    <input type="number" name="mail_port" min="1" max="65535" step="1" value="<?=$this->settings['mail_port']?>" />
    <h4>Protocollo crittografia</h4>
    <select name="mail_encryption">
        <option value="SMTPS" <?=$this->settings['mail_encryption'] == 'SMTPS' ? 'selected' : '' ?>>SMTPS</option>
        <option value="STARTTLS" <?=$this->settings['mail_encryption'] == 'STARTTLS' ? 'selected' : '' ?>>STARTTLS</option>
    </select>
    <h4>Username</h4>
    <input type="text" name="mail_username" value="<?=$this->settings['mail_username']?>" />
    <h4>Password</h4>
    <input type="password" name="mail_password" value="<?=$this->settings['mail_password']?>" />
    <h4>Indirizzo e-mail campo "Da:"</h4>
    <input type="text" name="mail_from" value="<?=$this->settings['mail_from']?>" />
    <h4>Nome campo "Da:"</h4>
    <input type="text" name="mail_from_name" value="<?=$this->settings['mail_from_name']?>" />
    <h4>Indirizzi per notifiche e-mail</h4>
    <i>Uno per riga</i>
    <textarea name="mail_general_notifications" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",explode( ',',$this->settings['mail_general_notifications']))?></textarea>
    <h4>Invia e-mail di test a:</h4>
    <input type="text" id="send_test_mail" name="send_test_mail" value="" />
    <br><br>
    <button class="button" id="send_test_mail_button">Invia e-mail di test</button>
    <hr>
    <h4>IP rilevato:</h4>
    <p><?=$this::get_client_ip()?></p>
    <h4>Numero massimo di login falliti</h4>
    <input type="number" min="-1" step="1" name="max_failed_login_attempts" value="<?=$this->settings['max_failed_login_attempts']?>" />
    <h4>Numero secondi tra i tentativi di login</h4>
    <input type="number" min="1" step="1" name="seconds_between_login_attempts" value="<?=$this->settings['seconds_between_login_attempts']?>" />
    <h4>Numero secondi in blacklist dopo il superamento tentativi</h4>
    <input type="number" min="1" step="1" name="seconds_in_blacklist" value="<?=$this->settings['seconds_in_blacklist']?>" />
    <hr>
    <h4>hCatpcha Site key</h4>
    <input type="text" name="hcaptcha_site_key" value="<?=$this->settings['hcaptcha_site_key']?>" />
    <h4>hCatpcha Secret</h4>
    <input type="password" name="hcaptcha_secret" value="<?=$this->settings['hcaptcha_secret']?>" />
    <hr>
    <p><strong>PayPal Sandbox</strong>&nbsp;&nbsp;&nbsp;<input type="checkbox" name="pp_sandbox" value="1" <?=$this->settings['pp_sandbox'] ? 'checked' : ''?>/></p>
    <h4>PayPal Client ID</h4>
    <input type="text" name="pp_client_id" value="<?=$this->settings['pp_client_id']?>" />
    <h4>PayPal Client Secret</h4>
    <input type="password" name="pp_client_secret" value="<?=$this->settings['pp_client_secret']?>" />
    <hr>
    <h4>Google Maps API key</h4>
    <input type="password" name="gmaps_api_key" value="<?=$this->settings['gmaps_api_key'] ?? '' ?>" />
    <hr>
    <h3>Master key</h3>
    <button class="button" id="master_doc_key_button"><?=!$this->settings['master_doc_key'] ? 'Imposta' : 'Aggiorna'?> master key</button>
    <span id="master_doc_key_field" style="display:none">
        <h4><?=!$this->settings['master_doc_key'] ? 'Imposta' : 'Nuova'?> master key</h4>
        <input type="password" name="master_doc_key" />
        <h4>Conferma master key</h4>
        <input type="password" name="master_doc_key_confirm" />
        <?php if ($this->settings['master_doc_key']) { ?>
            <h4>Master key attuale</h4>
            <input type="password" name="master_doc_key_old" />
            <p><strong>Master key db string:</strong> <?=$this->get_master_key()?></p>
        <?php } else {?>
            <p><strong>NOTA:</strong> Al primo settaggio della master key sarà impostata anche la tua personale</p>
        <?php }
        ?>
    </span>
    <hr>
    <h4>Anni disponibili per l'iscrizione (separati da virgola)</h4>
    <input type="text" name="authorized_subscription_years" value="<?=implode(',',$this->settings['authorized_subscription_years'])?>" />
    <h4>Quota minima d'iscrizione</h4>
    <input type="number" min="0.01" step="0.01" name="min_subscription_payment" value="<?=$this->settings['min_subscription_payment']?>" />
    <hr>
    <h3>Consensi</h3>
    <h4>Policy marketing</h4>
    <textarea name="marketing_policy" style="width:98%; height:fit-content; margin-right: 10px;"><?=$this->settings['marketing_policy']?></textarea>
    <h4>Policy newsletter</h4>
    <textarea name="newsletter_policy" style="width:98%; height:fit-content; margin-right: 10px;"><?=$this->settings['newsletter_policy']?></textarea>
    <h4>Policy pubblicazione</h4>
    <textarea name="publish_policy" style="width:98%; height:fit-content; margin-right: 10px;"><?=$this->settings['publish_policy']?></textarea>
    <hr>
    <input type="hidden" id="purge_deactivate" name="purge_deactivate" value="" />
    <button class="button" id="purge_deactivate_button">Disattiva plugin e pulisci</button>
    <br><br>
    <button class="button" id="save_plugin_button">Salva plugin</button>
    <hr>
    <?php wp_nonce_field( 'mpop-admin-settings', 'mpop-admin-settings-nonce' ); ?>
    <button class="button button-primary" id="mpop_settings_save">Salva</button>
</form>
<script type="text/javascript" src="<?=plugins_url()?>/multipop/js/settings.js"></script>
<?php