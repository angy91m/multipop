<?php
defined( 'ABSPATH' ) || exit;

// YOU CAN DISPOSE OF
// &$errors as WP_Error Instance
// $update as true if edit is an update
// &$user as WP_User Instance with new fields
$user->first_name = mb_strtoupper(trim($user->first_name), 'UTF-8');
$user->last_name = mb_strtoupper(trim($user->last_name), 'UTF-8');
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
            $user->role = !empty($old_user->roles) ? $old_user->roles[0] : '';
        }
    } else {
        $old_user = false;
        if ($update) {
            $old_user = get_user_by('ID', $user->ID);
        }
        do {
            if (in_array($user->role, ['multipopolano', 'multipopolare_resp'])) {
                // FLOW FOR ROLES multipopolano & multipopolare_resp
                if ($update) {
                    // FLOW FOR UPDATES
                    if (
                        isset($_POST['resend_mail_confirmation']) && $_POST['resend_mail_confirmation']
                        && (
                            $old_user->mpop_mail_to_confirm
                            || $old_user->_new_email
                        )
                    ) {
                        $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                        $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                        if(!$this->send_confirmation_mail($token, $old_user->_new_email ? $old_user->_new_email : $old_user->user_email)) {
                            $errors->add(500, $error_head . ("Error while sending mail" . ($this->last_mail_error ? ': ' . $this->last_mail_error : '')));
                            return;
                        }
                        $user = $old_user;
                        $user->role = !empty($old_user->roles) ? $old_user->roles[0] : '';
                        return;
                    } elseif (isset($_POST['revoke_mail_confirmation']) && $_POST['revoke_mail_confirmation']) {
                        $user_meta['mpop_mail_to_confirm'] = true;
                        $user_meta['_new_email'] = false;
                        $user = $old_user;
                        $user->role = !empty($old_user->roles) ? $old_user->roles[0] : '';
                        break;
                    }
                    if (
                        (
                            !$old_user->_new_email
                            && $old_user->user_email !== $user->user_email
                        )
                        || (
                            $old_user->_new_email
                            && $old_user->_new_email !== $user->user_email
                        )
                    ) {
                        $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                    }
                    if (
                        isset($_POST['email_confirmed']) && $_POST['email_confirmed']
                        || $old_user->_new_email && $old_user->user_email === $user->user_email
                    ) {
                        $user_meta['mpop_mail_to_confirm'] = false;
                        $user_meta['_new_email'] = false;
                    } else {
                        if ($old_user->user_email !== $user->user_email) {
                            $mail_changed = false;
                            if ($old_user->mpop_mail_to_confirm) {
                                $user_meta['_new_email'] = false;
                                $mail_changed = true;
                            } else {
                                if (
                                    !$old_user->_new_email
                                    || $old_user->_new_email !== $user->user_email
                                ) {
                                    $duplicated = get_users([
                                        'meta_key' => '_new_email',
                                        'meta_value' => $user->user_email,
                                        'meta_compare' => '='
                                    ]);
                                    if (count($duplicated)) {
                                        $errors->add(400, $error_head . "E-mail $user->user_email già registrata. Scegline un'altra.");
                                        return;
                                    }
                                    $user_meta['_new_email'] = $user->user_email;
                                    $mail_changed = true;
                                }
                                $user->user_email = $old_user->user_email;
                            }
                            if ($mail_changed && isset($_POST['send_mail_confirmation']) && $_POST['send_mail_confirmation']) {
                                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                                if(!$this->send_confirmation_mail($token, $user_meta['_new_email'] ? $user_meta['_new_email'] : $user->user_email)) {
                                    $errors->add(500, $error_head . ("Error while sending mail" . ($this->last_mail_error ? ': ' . $this->last_mail_error : '')));
                                    return;
                                }
                            }
                        }
                    }
                } else {
                    // FLOW FOR NEW USERS
                    if (isset($_POST['email_confirmed']) && $_POST['email_confirmed']) {
                        $user_meta['mpop_mail_to_confirm'] = false;
                        $user_meta['_new_email'] = false;
                    } else {
                        $user_meta['mpop_mail_to_confirm'] = true;
                    }
                }
            } else if ($update) {
                delete_user_meta($user->ID, '_new_email');
                // FLOW FOR OTHER ROLES UPDATE
                if ($user->role == 'administrator') {
                    delete_user_meta($user->ID, 'mpop_mail_to_confirm');
                } else {
                    $disc_utils = $this->discourse_utilities();
                    if ($disc_utils) {
                        $disc_utils->logout_user_from_discourse($user);
                    }
                }
            }
            if ($update && $user->role != 'multipopolare_resp') {
                if ($old_user->mpop_resp_zones) {
                    delete_user_meta($user->ID, 'mpop_resp_zones');
                }
            }
            if ($update && in_array($user->role, ['administrator', 'multipopolare_resp'])) {
                // EXTRAFLOW FOR ROLES administrator & multipopolare_resp UPDATE
                if (isset($_POST['revoke_master_key']) && $_POST['revoke_master_key']) {
                    if ( $this->count_valid_master_keys() < 2 ) {
                        $errors->add(400, "Non puoi revocare l'ultima master key assegnata");
                        return;
                    }
                    delete_user_meta($user->ID, 'mpop_personal_master_key');
                    break;
                } else if (isset($_POST['master_key']) && $_POST['master_key']) {
                    if (
                        $user->role == 'multipopolare_resp'
                        && (
                            (isset($user_meta['mpop_mail_to_confirm']) && $user_meta['mpop_mail_to_confirm'])
                            || (!isset($user_meta['mpop_mail_to_confirm']) && $old_user->mpop_mail_to_confirm)
                            || (isset($user_meta['_new_email']) && $user_meta['_new_email'])
                            || (!isset($user_meta['_new_email']) && $old_user->_new_email)
                        )
                    ) {
                        $errors->add(400, "L'utente non ha confermato l'indirizzo e-mail");
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
            } else if ($update) {
                // EXTRAFLOW FOR OTHER ROLES UPDATE
                delete_user_meta($user->ID, 'mpop_personal_master_key');
            }
        } while(false);
    }
    if (!$update) {
        $send_confirmation = false;
        if (isset($_POST['send_mail_confirmation']) && $_POST['send_mail_confirmation']) {
            $send_confirmation = $user->user_email;
        }
        add_action('user_register', function($user_id) use ($user_meta, $send_confirmation) {
            if ($send_confirmation) {
                $token = $this->create_temp_token( $user_id, 'email_confirmation_link' );
                $this->send_confirmation_mail($token, $send_confirmation);
            }
            foreach($user_meta as $k => $v) {
                update_user_meta($user_id, $k, $v);
            }
        });
        return;
    }
    foreach($user_meta as $k => $v) {
        update_user_meta($user->ID, $k, $v);
    }
    if (!isset($user->role) && isset($user->ID)) {
        $old_user = get_user_by('ID', $user->ID);
        $user->role = !empty($old_user->roles) ? $old_user->roles[0] : '';
    }
    if (in_array($user->role, ['administrator', 'multipopolano', 'multipopolare_resp'])) {
        $this->sync_discourse_record($user);
    }
}