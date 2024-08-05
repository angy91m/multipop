<?php
defined( 'ABSPATH' ) || exit;
if ( !in_array('administrator', wp_get_current_user()->roles) ) {
    echo '<p>Accesso non consentito</p>';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mpop-admin-settings-nonce'])) {
    if ( !wp_verify_nonce( $_REQUEST['mpop-admin-settings-nonce'], 'mpop-admin-settings' ) ) {
        $this->add_admin_notice("Invalid request");
    } else {
        if ($_REQUEST['force_tempmail_update'] == '1') {
            $this->update_tempmail(true);
            $this->add_admin_notice("Updated", 'success');
        } else if (!empty(trim($_REQUEST['send_test_mail']))) {
            if (!$this::is_valid_email(trim($_REQUEST['send_test_mail']))) {
                $this->add_admin_notice("Invalid e-mail address");
            } else {
                $res = $this->send_mail([
                    'to' => trim($_REQUEST['send_test_mail']),
                    'subject' => 'TEST Message',
                    'body' => 'This is a test message'
                ]);
                if ($res === true) {
                    $this->add_admin_notice("Sent", 'success');
                } else {
                    $this->add_admin_notice("Error during message send: " . $res);
                }
            }
        } else {
            $edits['tempmail_urls'] = $this->settings['tempmail_urls'];
            if (is_string($_REQUEST['tempmail_urls_block']) && !empty(trim($_REQUEST['tempmail_urls_block'])))  {
                $edits['tempmail_urls']['block'] = preg_split('/\r\n|\r|\n/', trim($_REQUEST['tempmail_urls_block']));
                $edits['tempmail_urls']['block'] = array_values(array_filter($edits['tempmail_urls']['block'], function($url){ return !empty(trim($url)); }));
            }
            if (is_string($_REQUEST['tempmail_urls_allow']) && !empty(trim($_REQUEST['tempmail_urls_allow'])))  {
                $edits['tempmail_urls']['allow'] = preg_split('/\r\n|\r|\n/', trim($_REQUEST['tempmail_urls_allow']));
                $edits['tempmail_urls']['allow'] = array_values(array_filter($edits['tempmail_urls']['allow'], function($url){ return !empty(trim($url)); }));
            }
            if (is_string($_REQUEST['mail_host'])) {
                $edits['mail_host'] = trim($_REQUEST['mail_host']);
            }
            if (is_string($_REQUEST['mail_port'])) {
                $mail_port = intval($_REQUEST['mail_port']);
                if ($mail_port > 0 && $mail_port < 65536) {
                    $edits['mail_port'] = $mail_port;
                }
                
            }
            if (is_string($_REQUEST['mail_encryption'])) {
                if (in_array($_REQUEST['mail_encryption'], [
                    'SMTPS',
                    'STARTTLS'
                ])) {
                    $edits['mail_encryption'] = $_REQUEST['mail_encryption'];
                }
            }
            if (is_string($_REQUEST['mail_username'])) {
                $edits['mail_username'] = trim($_REQUEST['mail_username']);
            }
            if (is_string($_REQUEST['mail_password'])) {
                $edits['mail_password'] = $_REQUEST['mail_password'];
            }
            if (is_string($_REQUEST['mail_from'])) {
                if ( $this::is_valid_email(trim($_REQUEST['mail_from'])) ) {
                    $edits['mail_from'] = trim($_REQUEST['mail_from']);
                } else {
                    $this->add_admin_notice( 'mail_from non valido' );
                }
            }
            if (is_string($_REQUEST['mail_from_name'])) {
                $edits['mail_from_name'] = trim($_REQUEST['mail_from_name']);
            }
            if (is_string($_REQUEST['mail_general_notifications'])) {
                $emails = array_map(
                    function ($e) {
                        return mb_strtolower(trim($e), 'UTF-8');
                    },
                    array_filter(
                        preg_split( '/\r\n|\r|\n/',preg_replace('/,|;/', "\n", $_REQUEST['mail_general_notifications'])),
                        function ($e) {
                            return trim($e);
                        }
                    )
                );
                $invalid = false;
                foreach ($emails as $email) {
                    if (!$this::is_valid_email($email)) {
                        $invalid = true;
                        break;
                    }
                }
                if (!$invalid) {
                    $edits['mail_general_notifications'] = implode(',', $emails);
                }
            }
            $first_master_key = false;
            if (trim($_REQUEST['master_doc_key'])) {
                if (!$this::is_strong_password($_REQUEST['master_doc_key'])) {
                    $this->add_admin_notice( 'La master key deve essere composta almeno da 24 carattari, contenere maiscole, minuscole, numeri e simboli' );
                } else if ($_REQUEST['master_doc_key'] !== $_REQUEST['master_doc_key_confirm']) {
                    $this->add_admin_notice( 'I campi per la nuova password non combaciano' );
                } else {
                    if (!$this->settings['master_doc_key']) {
                        $edits['master_doc_key'] = base64_encode(
                            $this->encrypt_with_password(
                                base64_encode( openssl_random_pseudo_bytes(32) ),
                                $_REQUEST['master_doc_key']
                            )
                        );
                        $first_master_key = $edits['master_doc_key'];
                    } else {
                        if (!is_string($_REQUEST['master_doc_key_old']) || !trim($_REQUEST['master_doc_key_old'])) {
                            $this->add_admin_notice( 'Master key attuale non valida' );
                        } else {
                            $master_key = base64_decode(
                                $this->decrypt_with_password(
                                    base64_decode($this->get_master_key(), true),
                                    $_REQUEST['master_doc_key_old']
                                ),
                                true
                            );
                            if (!$master_key || strlen($master_key) != 32) {
                                $this->add_admin_notice( 'Master key attuale non valida' );
                            } else {
                                $edits['master_doc_key'] = base64_encode(
                                    $this->encrypt_with_password(
                                        base64_encode( $master_key ),
                                        $_REQUEST['master_doc_key']
                                    )
                                );
                            }
                        }
                    }
                }
            }
            global $wpdb;
            $q = "UPDATE " . $this::db_prefix('plugin_settings') . " SET ";
            $q_arr = [];
            $q_values = [];
            foreach($edits as $k=>$v) {
                if (is_string($v)) {
                    $q_arr[] = " $k = %s";
                    $q_values[] = $v;
                } else if (is_int($v)) {
                    $q_arr[] = " $k = %d";
                    $q_values[] = $v;
                } else if (is_array($v)) {
                    $q_arr[] = " $k = %s";
                    $q_values[] = json_encode( $v );
                }
            }
            $q .= implode(', ', $q_arr) . " WHERE id = 1 ;";
            if ($wpdb->query( $wpdb->prepare( $q, $q_values ) ) !== false) {
                $this->add_admin_notice("Saved", 'success');
                if ($first_master_key) {
                    update_user_meta(get_current_user_id(), 'mpop_personal_master_key', $first_master_key);
                }
            } else {
                $this->add_admin_notice("Server error during save");
            }
        }
    }
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
    <button id="force_tempmail_update_button">Force update</button>
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
    <button id="send_test_mail_button">Send test mail</button>
    <hr>
    <h3>Master key</h3>
    <button id="master_doc_key_button"><?=!$this->settings['master_doc_key'] ? 'Imposta' : 'Aggiorna'?> master key</button>
    <span id="master_doc_key_field" style="display:none">
        <h4><?=!$this->settings['master_doc_key'] ? 'Imposta' : 'Nuova'?> master key</h4>
        <input type="password" name="master_doc_key" />
        <h4>Conferma master key</h4>
        <input type="password" name="master_doc_key_confirm" />
        <?php if ($this->settings['master_doc_key']) { ?>
            <h4>Master key attuale</h4>
            <input type="password" name="master_doc_key_old" />
        <?php } else {?>
            <p>Al primo settaggio della master key sarà impostata anche la tua personale</p>
        <?php }
        ?>
    </span>
    <hr>
    <?php wp_nonce_field( 'mpop-admin-settings', 'mpop-admin-settings-nonce' ); ?>
    <button id="mpop_settings_save">Save</button>
</form>
<script type="text/javascript" src="<?=plugins_url()?>/multipop/js/settings.js"></script>
<?php