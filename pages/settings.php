<?php
defined( 'ABSPATH' ) || exit;
if ( !$this->current_user_is_admin() ) {
    echo '<p>Accesso non consentito</p>';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mpop-admin-settings-nonce'])) {
    require('post/settings.php');
}
do_action('mpop_settings_notices', $this->get_settings());
?>
<form method="POST" id="mpop_settings_form">
    <h3>Temporary mail domain list URLs</h3>
    <h4>Block</h4>
    <i>One per line</i>
    <textarea name="tempmail_urls_block" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",$this->settings['tempmail_urls']['block'])?></textarea>
    <h4>Allow</h4>
    <i>One per line</i>
    <textarea name="tempmail_urls_allow" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",$this->settings['tempmail_urls']['allow'])?></textarea>
    <h4>Last update</h4>
    <span><?=$this::show_date_time($this->settings['last_tempmail_update'])?></span>
    <input type="hidden" id="force_tempmail_update" name="force_tempmail_update" value="" />
    <br><br>
    <button class="button" id="force_tempmail_update_button">Force update</button>
    <hr>
    <h3>Comuni</h3>
    <h4>Last update</h4>
    <span><?=$this::show_date_time($this->last_comuni_update())?></span>
    <input type="hidden" id="force_comuni_update" name="force_comuni_update" value="" />
    <br><br>
    <button class="button" id="force_comuni_update_button">Force update</button>
    <hr>
    <h3>Mail settings</h3>
    <h4>Mail host</h4>
    <input type="text" name="mail_host" value="<?=$this->settings['mail_host']?>" />
    <h4>Mail port</h4>
    <input type="number" name="mail_port" min="1" max="65535" step="1" value="<?=$this->settings['mail_port']?>" />
    <h4>Mail encription</h4>
    <select name="mail_encryption">
        <option value="SMTPS" <?=$this->settings['mail_encryption'] == 'SMTPS' ? 'selected' : '' ?>>SMTPS</option>
        <option value="STARTTLS" <?=$this->settings['mail_encryption'] == 'STARTTLS' ? 'selected' : '' ?>>STARTTLS</option>
    </select>
    <h4>Mail username</h4>
    <input type="text" name="mail_username" value="<?=$this->settings['mail_username']?>" />
    <h4>Mail password</h4>
    <input type="password" name="mail_password" value="<?=$this->settings['mail_password']?>" />
    <h4>Mail from</h4>
    <input type="text" name="mail_from" value="<?=$this->settings['mail_from']?>" />
    <h4>Mail from name</h4>
    <input type="text" name="mail_from_name" value="<?=$this->settings['mail_from_name']?>" />
    <h4>Mail general notifications</h4>
    <i>One per line</i>
    <textarea name="mail_general_notifications" style="width:98%; height:fit-content; margin-right: 10px;"><?=implode("\n",explode( ',',$this->settings['mail_general_notifications']))?></textarea>
    <h4>Send a test mail to:</h4>
    <input type="text" id="send_test_mail" name="send_test_mail" value="" />
    <br><br>
    <button class="button" id="send_test_mail_button">Send test mail</button>
    <hr>
    <h4>hCatpcha Site key</h4>
    <input type="text" name="hcaptcha_site_key" value="<?=$this->settings['hcaptcha_site_key']?>" />
    <h4>hCatpcha Secret</h4>
    <input type="password" name="hcaptcha_secret" value="<?=$this->settings['hcaptcha_secret']?>" />
    <hr>
    <h4>PayPal Client ID</h4>
    <input type="text" name="pp_client_id" value="<?=$this->settings['pp_client_id']?>" />
    <h4>PayPal Client Secret</h4>
    <input type="password" name="pp_client_secret" value="<?=$this->settings['pp_client_secret']?>" />
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
        <?php } else {?>
            <p><strong>NOTA:</strong> Al primo settaggio della master key sarà impostata anche la tua personale</p>
        <?php }
        ?>
    </span>
    <hr>
    <?php wp_nonce_field( 'mpop-admin-settings', 'mpop-admin-settings-nonce' ); ?>
    <button class="button button-primary" id="mpop_settings_save">Save</button>
</form>
<script type="text/javascript" src="<?=plugins_url()?>/multipop/js/settings.js"></script>
<?php