<?php
defined( 'ABSPATH' ) || exit;
if ( !wp_verify_nonce( $_REQUEST['mpop-admin-settings-nonce'], 'mpop-admin-settings' ) ) {
    $this->add_admin_notice("Invalid request");
} else {
    if ($_REQUEST['force_tempmail_update'] == '1') {
        $this->update_tempmail(true);
        $this->add_admin_notice("Aggiornamento completato", 'success');
    } else if ($_REQUEST['force_comuni_update'] == '1') {
        $this->update_comuni(true);
        $this->add_admin_notice("Aggiornamento in corso", 'success');
    } else if ($_REQUEST['force_discourse_groups_reload'] == '1') {
        $disc_utils = $this->discourse_utilities();
        if (!$disc_utils) {
            $this->add_admin_notice("Plugin per Discourse non presente");
        } else {
            $disc_utils->get_discourse_groups(true);
            $this->add_admin_notice("Cache gruppi Discourse ricaricata", 'success');
        }
    } else if (!empty(trim($_REQUEST['send_test_mail']))) {
        if (!$this->is_valid_email(trim($_REQUEST['send_test_mail']), false, true)) {
            $this->add_admin_notice("Indirizzo e-mail non valido");
        } else {
            $res = $this->send_mail([
                'to' => trim($_REQUEST['send_test_mail']),
                'subject' => 'TEST Message',
                'body' => 'This is a test message'
            ]);
            if ($res === true) {
                $this->add_admin_notice("Inviato", 'success');
            } else {
                $this->add_admin_notice("Errore durante l'invio del messaggio: " . $res);
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
            if ( $this->is_valid_email(trim($_REQUEST['mail_from']), false, true) ) {
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
                if (!$this->is_valid_email($email, false, true)) {
                    $invalid = true;
                    break;
                }
            }
            if (!$invalid) {
                $edits['mail_general_notifications'] = implode(',', $emails);
            }
        }
        if (is_string($_REQUEST['authorized_subscription_years'])) {
            $_REQUEST['authorized_subscription_years'] = trim($_REQUEST['authorized_subscription_years']);
            if (empty($_REQUEST['authorized_subscription_years'])) {
                $edits['authorized_subscription_years'] = '';
            } else {
                $_REQUEST['authorized_subscription_years'] = array_unique( array_map( function($v) {return intval(trim($v));}, explode(',', $_REQUEST['authorized_subscription_years'])));
                $valid_years = [];
                $this_year = intval(current_time('Y'));
                foreach($_REQUEST['authorized_subscription_years'] as $y) {
                    if (is_int($y) && $y >= $this_year) {
                        $valid_years[] = $y;
                    } else {
                        $this->add_admin_notice( 'Anno ' . $y . ' non valido' );
                    }
                }
                sort($valid_years);
                $edits['authorized_subscription_years'] = implode(',', $valid_years);
            }
        }
        if (is_string($_REQUEST['min_subscription_payment']) && is_numeric($_REQUEST['min_subscription_payment'])) {
            $min_v = (double) $_REQUEST['min_subscription_payment'];
            $min_v = round($min_v * 100) / 100;
            if ($min_v > 0) {
                $edits['min_subscription_payment'] = "$min_v";
            }
        }
        if (is_string($_REQUEST['hcaptcha_site_key'])) {
            $edits['hcaptcha_site_key'] = trim($_REQUEST['hcaptcha_site_key']);
        }
        if (is_string($_REQUEST['hcaptcha_secret'])) {
            $edits['hcaptcha_secret'] = trim($_REQUEST['hcaptcha_secret']);
        }
        $edits['pp_sandbox'] = '';
        if (isset($_REQUEST['pp_sandbox']) && is_string($_REQUEST['pp_sandbox'])) {
            if ($_REQUEST['pp_sandbox'] == '1') {
                $edits['pp_sandbox'] = '1';
            }
        }
        if (is_string($_REQUEST['pp_client_id'])) {
            $edits['pp_client_id'] = trim($_REQUEST['pp_client_id']);
        }
        if (is_string($_REQUEST['pp_client_secret'])) {
            $edits['pp_client_secret'] = trim($_REQUEST['pp_client_secret']);
        }
        $policies = ['marketing', 'newsletter', 'publish'];
        foreach ($policies as $p) {
            if (is_string($_REQUEST[$p.'_policy']) ) {
                $policy = trim($_REQUEST[$p.'_policy']);
                if ($policy) {
                    $edits[$p.'_policy'] = $policy;
                }
            }
        }
        $first_master_key = false;
        if (trim($_REQUEST['master_doc_key'])) {
            if (!$this::is_strong_password($_REQUEST['master_doc_key'])) {
                $this->add_admin_notice( 'La master key deve essere composta almeno da almeno 24 carattari e deve contenere maiscole, minuscole, numeri e simboli' );
            } else if ($_REQUEST['master_doc_key'] !== $_REQUEST['master_doc_key_confirm']) {
                $this->add_admin_notice( 'I campi per la nuova password non combaciano' );
            } else {
                if (!$this->settings['master_doc_key']) {
                    $asym_keys = $this->generate_asym_keys();
                    $edits['master_doc_pubkey'] = base64_encode($asym_keys['pub']);
                    $edits['master_doc_key'] = base64_encode(
                        $this->encrypt_with_password(
                            base64_encode( openssl_random_pseudo_bytes(32, true) . $asym_keys['priv'] ),
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
                        if (!$master_key || strlen($master_key) <= 32) {
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
            } else if (is_bool($v)) {
                $q_arr[] = " $k = %d";
                $q_values[] = $v ? 1 : 0;
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
            $this->add_admin_notice("Salvato", 'success');
            if ($first_master_key) {
                update_user_meta(get_current_user_id(), 'mpop_personal_master_key', $first_master_key);
            }
        } else {
            $this->add_admin_notice("Errore del server durante il salvataggio");
        }
    }
}