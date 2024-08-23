<?php
defined( 'ABSPATH' ) || exit;

// YOU CAN DISPOSE OF
// &$errors as WP_Error Instance
// $update as true if edit is an update
// &$user as WP_User Instance with new fields

$user->user_email = mb_strtolower(trim($user->user_email), 'UTF-8');
$user_meta = [];
$error_head = '<strong>Multipopolare:</strong>&nbsp;';
$master_key_len = 16;
if (!$errors->has_errors()) {
    if (defined('MPOP_PERSONAL_UPDATE') && MPOP_PERSONAL_UPDATE) {
        if (isset($_POST['master_key']) && $_POST['master_key']) {
            if (!$this::is_strong_password($_POST['master_key'], $master_key_len)) {
                $errors->add(400, $error_head . "La nuova master key deve essere composta almeno da almeno $master_key_len carattari e deve contenere maiscole, minuscole, numeri e simboli");
                return;
            }
            if (!isset($_POST['current_user_master_key']) || !$_POST['current_user_master_key']) {
                $errors->add(400, $error_head . "La master key attuale non è valida");
                return;
            }
            $enc_curr_user_mk = get_user_meta($user->ID, 'mpop_personal_master_key', true);
            if (!$enc_curr_user_mk) {
                $errors->add(400, $error_head . "Non sei in possesso di una master key");
                return;
            }
            $master_key = base64_decode(
                $this->decrypt_with_password(
                    base64_decode($enc_curr_user_mk, true),
                    $_POST['current_user_master_key']
                ),
                true
            );
            if (!$master_key || strlen($master_key) <= 32) {
                $errors->add(400, $error_head . "La master key attuale non è valida");
                return;
            }
            $user_meta['mpop_personal_master_key'] = base64_encode(
                $this->encrypt_with_password(
                    base64_encode($master_key),
                    $_POST['master_key']
                )
            );
        }
        if (!$this->current_user_is_admin()) {
            $old_user = get_user_by('ID', $user->ID);
            $old_user->description = $user->description;
            if (isset($user->user_pass) && $user->user_pass) {
                $old_user->user_pass = $user->user_pass;
            }
            $user = $old_user;
        }
    } else {
        do {
            if (in_array($user->role, ['multipopolano', 'multipopolare_resp'])) {
                if ($update) {
                    $old_user = get_user_by('ID', $user->ID);
                    $old_user_meta = get_user_meta($user->ID);
                    if (
                        isset($_POST['resend_mail_confirmation']) && $_POST['resend_mail_confirmation']
                        && (
                            (isset($old_user_meta['mpop_mail_to_confirm']) && $old_user_meta['mpop_mail_to_confirm'][0] )
                            || (isset($old_user_meta['_new_email']) && $old_user_meta['_new_email'][0])
                        )
                    ) {
                        $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                        $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                        $mail_res = $this->send_confirmation_mail($token, $old_user->user_email);
                        if ( $mail_res !== true ) {
                            $errors->add(500, $error_head . $mail_res);
                            return;
                        }
                        $user = $old_user;
                        return;
                    } elseif (isset($_POST['revoke_mail_confirmation']) && $_POST['revoke_mail_confirmation']) {
                        if (
                            (isset($old_user_meta['mpop_mail_to_confirm']) && $old_user_meta['mpop_mail_to_confirm'][0])
                            || (isset($old_user_meta['_new_email']) && $old_user_meta['_new_email'][0])
                        ) {
                            $errors->add(400, $error_head . "L'utente non ha confermato l'indirizzo e-mail");
                            return;
                        }
                        $user_meta['mpop_mail_to_confirm'] = true;
                        $user_meta['_new_email'] = false;
                        $user = $old_user;
                        break;
                    }
                    if ($old_user->user_email !== $user->user_email) {
                        $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                    }
                    if (isset($_POST['email_confirmed']) && $_POST['email_confirmed']) {
                        $user_meta['mpop_mail_to_confirm'] = false;
                        $user_meta['_new_email'] = false;
                    } else {
                        if ($old_user->user_email !== $user->user_email) {
                            if ( (!isset($old_user_meta['mpop_mail_to_confirm']) || !$old_user_meta['mpop_mail_to_confirm'][0] ) && (!isset($old_user_meta['_new_email']) || !$old_user_meta['_new_email'][0])) {
                                $user_meta['_new_email'] = $old_user->user_email;
                            }
                            if (isset($_POST['send_mail_confirmation']) && $_POST['send_mail_confirmation']) {
                                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                                $mail_res = $this->send_confirmation_mail($token, $user->user_email);
                                if ( $mail_res !== true ) {
                                    $errors->add(500, $error_head . $mail_res);
                                    return;
                                }
                            }
                        }
                    }
                } else {
                    if (isset($_POST['email_confirmed']) && $_POST['email_confirmed']) {
                        $user_meta['mpop_mail_to_confirm'] = false;
                        $user_meta['_new_email'] = false;
                    } else {
                        $user_meta['mpop_mail_to_confirm'] = true;
                        if (isset($_POST['send_mail_confirmation']) && $_POST['send_mail_confirmation']) {
                            $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                            $mail_res = $this->send_confirmation_mail($token, $user->user_email);
                            if ( $mail_res !== true ) {
                                $errors->add(500, $error_head . $mail_res);
                                return;
                            }
                        }
                    }
                }
            } else {
                delete_user_meta($user->ID, 'mpop_mail_to_confirm');
            }
            if ($update && in_array($user->role, ['administrator', 'multipopolare_resp'])) {
                if (isset($_POST['revoke_master_key']) && $_POST['revoke_master_key']) {
                    if ( $this->count_valid_master_keys() < 2 ) {
                        $errors->add(400, "Non puoi revocare l'ultima master key assegnata");
                        return;
                    }
                    delete_user_meta($user->ID, 'mpop_personal_master_key');
                    break;
                } else if (isset($_POST['master_key']) && $_POST['master_key']) {
                    $old_user_meta = get_user_meta($user->ID);
                    if (isset($user_meta['mpop_mail_to_confirm'])) {
                        if ($user_meta['mpop_mail_to_confirm']) {
                            $errors->add(400, "L'utente non ha confermato l'indirizzo e-mail");
                        }
                    } else if (isset($old_user_meta['mpop_mail_to_confirm']) && isset($old_user_meta['mpop_mail_to_confirm'][0])) {
                        if ($old_user_meta['mpop_mail_to_confirm'][0]) {
                            $errors->add(400, "L'utente non ha confermato l'indirizzo e-mail");
                        }
                    }
                    if (isset($user_meta['_new_email'])) {
                        if ($user_meta['_new_email']) {
                            $errors->add(400, "L'utente non ha confermato l'indirizzo e-mail");
                        }
                    } else if (isset($old_user_meta['_new_email']) && isset($old_user_meta['_new_email'][0])) {
                        if ($old_user_meta['_new_email'][0]) {
                            $errors->add(400, "L'utente non ha confermato l'indirizzo e-mail");
                        }
                    }
                    if ($errors->has_errors()) {
                        return;
                    }
                    if (!$this::is_strong_password($_POST['master_key'], $master_key_len)) {
                        $errors->add(400, $error_head . "La nuova master key deve essere composta almeno da almeno $master_key_len carattari e deve contenere maiscole, minuscole, numeri e simboli");
                        return;
                    }
                    if (!isset($_POST['current_user_master_key']) || !$_POST['current_user_master_key']) {
                        $errors->add(400, $error_head . "La tua master key non è valida");
                        return;
                    }
                    $enc_curr_user_mk = get_user_meta(get_current_user_id(), 'mpop_personal_master_key', true);
                    if (!$enc_curr_user_mk) {
                        $errors->add(400, $error_head . "Non sei in possesso di una master key");
                        return;
                    }
                    $master_key = base64_decode(
                        $this->decrypt_with_password(
                            base64_decode($enc_curr_user_mk, true),
                            $_POST['current_user_master_key']
                        ),
                        true
                    );
                    if (!$master_key || strlen($master_key) <= 32) {
                        $errors->add(400, $error_head . "La tua master key non è valida");
                        return;
                    }
                    $user_meta['mpop_personal_master_key'] = base64_encode(
                        $this->encrypt_with_password(
                            base64_encode($master_key),
                            $_POST['master_key']
                        )
                    );
                }
            } else {
                delete_user_meta($user->ID, 'mpop_personal_master_key');
            }
        } while(false);
    }
    
    foreach($user_meta as $k => $v) {
        update_user_meta($user->ID, $k, $v);
    }
}