<?php
defined( 'ABSPATH' ) || exit;
switch( $post_data['action'] ) {
    case 'resp_search_users':
        [$users, $total, $limit, $sort_by] = $this->resp_user_search($post_data, $current_user);
        $res_data['data'] = ['users' => $users, 'total' => $total, 'limit' => $limit, 'sortBy' => $sort_by];
        break;
    case 'resp_view_user':
        if (isset($post_data['ID']) && is_numeric($post_data['ID']) && $post_data['ID'] > 0) {
            if ($current_user->ID == intval($post_data['ID'])) {
                $res_data['error'] = ['ID'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Impossibile modificare i tuoi dati']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_user = $this->myaccount_get_profile($post_data['ID'], true, true);
            if (!$res_user) {
                $res_data['error'] = ['ID'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Utente non trovato']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_user['is_editable'] = $this->is_resp_of_user($post_data['ID']);
            $res_data['data'] = ['user' => $res_user];
        }
        break;
    case 'resp_update_user':
        $user = false;
        if (isset($post_data['ID']) && is_int($post_data['ID']) && $post_data['ID'] > 0 ) {
            $user = get_user_by('ID', $post_data['ID']);
        }
        if (!$user || !$this->is_resp_of_user($user)) {
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type'=>'error', 'msg' => 'ID non valido'];
            $res_data['error'] = ['ID'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $comuni = false;
        $found_caps = [];
        if (!isset($post_data['email']) || !is_string($post_data['email']) || !$this->is_valid_email(trim($post_data['email']), true)) {
            $res_data['error'] = ['email'];
        } else {
            $post_data['email'] = mb_strtolower( trim($post_data['email']), 'UTF-8' );
        }
        if (!isset($post_data['mpop_mail_confirmed']) || !is_bool($post_data['mpop_mail_confirmed'])) {
            $res_data['error'] = ['email'];
        }
        if (!isset($post_data['mpop_old_card_number']) || !is_string($post_data['mpop_old_card_number']) || mb_strlen(trim($post_data['mpop_old_card_number']), 'UTF-8') > 64) {
            $res_data['error'] = ['mpop_old_card_number'];
        } else {
            $post_data['mpop_old_card_number'] = mb_strtoupper(trim($post_data['mpop_old_card_number']), 'UTF-8');
        }
        if (!isset($post_data['first_name']) || !is_string($post_data['first_name']) || mb_strlen(trim($post_data['first_name']), 'UTF-8') < 2) {
            if ($user->first_name) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'first_name';
            } else {
                $post_data['first_name'] = '';
            }
        } else {
            $post_data['first_name'] = mb_strtoupper( trim($post_data['first_name']), 'UTF-8' );
            if (!$this::is_valid_name($post_data['first_name'])) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'first_name';
            }
        }
        if (!isset($post_data['last_name']) || !is_string($post_data['last_name']) || mb_strlen(trim($post_data['last_name']), 'UTF-8') < 2) {
            if ($user->last_name) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'last_name';
            } else {
                $post_data['last_name'] = '';
            }
        } else {
            $post_data['last_name'] = mb_strtoupper( trim($post_data['last_name']), 'UTF-8' );
            if (!$this::is_valid_name($post_data['last_name'])) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'last_name';
            }
        }
        if (!isset($post_data['mpop_billing_country']) || !$this->get_country_by_code($post_data['mpop_billing_country'])) {
            if ($user->mpop_billing_country) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_country';
            } else {
                $post_data['mpop_billing_country']= '';
            }
        } else if ($post_data['mpop_billing_country'] != 'ita') {
            $post_data['mpop_billing_city'] = '';
            $post_data['mpop_billing_state'] = '';
            $post_data['mpop_billing_zip'] = '';
        } else {
            if (!isset($post_data['mpop_billing_city']) || !preg_match('/^[A-Z]\d{3}$/', $post_data['mpop_billing_city'])) {
                if ($user->mpop_billing_city) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_billing_city';
                } else {
                    $post_data['mpop_billing_city']= '';
                }
            } else {
                $comuni = $this->get_comuni_all();
                $found_bc = array_values(array_filter($comuni, function($c) use ($post_data) {return (!isset($c['soppresso']) || !$c['soppresso']) && $c['codiceCatastale'] == $post_data['mpop_billing_city'];}));
                if (!count($found_bc)) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_billing_city';
                } else {
                    $post_data['mpop_billing_state'] = $found_bc[0]['provincia']['sigla'];
                    $found_caps = $found_bc[0]['cap'];
                }
            }
            if (!isset($post_data['mpop_billing_zip']) || !in_array($post_data['mpop_billing_zip'], $found_caps)) {
                if ($user->mpop_billing_zip) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_billing_zip';
                } else {
                    $post_data['mpop_billing_zip'] = '';
                }
            }
        }
        if (!isset($post_data['mpop_billing_address']) || !is_string($post_data['mpop_billing_address']) || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') < 2 || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') > 200) {
            if ($user->mpop_billing_address) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_address';
            } else {
                $post_data['mpop_billing_address'] = '';
            }
        }
        if (!isset($post_data['mpop_birthdate'])) {
            if ($user->mpop_birthdate) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
            } else {
                $post_data['mpop_birthdate'] = '';
            }
        } else {
            try {
                $post_data['mpop_birthdate'] = $this::validate_birthdate($post_data['mpop_birthdate']);
            } catch (Exception $e) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
                $post_data['mpop_birthdate'] = '';
            }
        }
        if (!isset($post_data['mpop_birthplace_country']) || !$this->get_country_by_code($post_data['mpop_birthplace_country'])) {
            if ($user->mpop_birthplace_country) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthplace_country';
            } else {
                $post_data['mpop_birthplace_country'] = '';
                $post_data['mpop_birthplace'] = '';
            }
        } else if ($post_data['mpop_birthplace_country'] != 'ita') {
            $post_data['mpop_birthplace'] = '';
        } else {
            if (!isset($post_data['mpop_birthplace']) || !$post_data['mpop_birthplace']) {
                if ($user->mpop_birthplace) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_birthplace';
                } else {
                    $post_data['mpop_birthplace'] = '';
                }
            } else if ($post_data['mpop_birthdate']) {
                try {
                    if (!$comuni) {
                        $comuni = $this->get_comuni_all();
                    }
                    $post_data['mpop_birthdate'] = $this->validate_birthplace($post_data['mpop_birthdate'],$post_data['mpop_birthplace'], $comuni);
                } catch (Exception $e) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $errors = explode(',',$e->getMessage());
                    $res_data['error'] = $errors;
                }
            }
        }
        if (is_object($post_data['mpop_birthdate'])) {
            $post_data['mpop_birthdate'] = $post_data['mpop_birthdate']->format('Y-m-d');
        }
        if (!isset($post_data['mpop_phone']) || !$this::is_valid_phone($post_data['mpop_phone']) ) {
            if ($user->mpop_phone) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_phone';
            } else {
                $post_data['mpop_phone'] = '';
            }
        } else if (!empty(get_users([
            'meta_key' => 'mpop_phone',
            'meta_value' => $post_data['mpop_phone'],
            'meta_compare' => '=',
            'login__not_in' => [$user->user_login]
        ]))) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_phone';
        }
        if (isset($res_data['error'])) {
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $user_edits = [];
        $duplicated = get_user_by('email', $post_data['email']);
        if ($duplicated && $duplicated->ID != $user->ID) {
            $res_data['error'] = ['email'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $duplicated = get_users([
            'meta_key' => '_new_email',
            'meta_value' => $post_data['email'],
            'meta_compare' => '=',
            'login__not_in' => [$user->user_login],
            'number' => 1
        ]);
        if (!empty($duplicated)) {
            $res_data['error'] = ['email'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $empty_user = new stdClass();
        $empty_user->roles = ['multipopolano'];
        $empty_user->mpop_billing_country = $post_data['mpop_billing_country'];
        $empty_user->mpop_billing_city = $post_data['mpop_billing_city'];
        if (!$this->is_resp_of_user($empty_user)) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_billing_country';
            $res_data['error'][] = 'mpop_billing_city';
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type' =>'error', 'msg' => 'Non puoi spostare un/a tesserato/a in una zona che non gestisci'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if ($post_data['mpop_mail_confirmed']) {
            $user_edits['user_email'] = $post_data['email'];
            $user_edits['meta_input'] = ['mpop_mail_to_confirm' => false,'_new_email' => false];
        } else {
            if (
                ($user->_new_email && $user->_new_email != $post_data['email'])
                || (!$user->_new_email && $user->user_email != $post_data['email'] )
            ) {
                $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                if(!$this->send_confirmation_mail($token, $post_data['email'])) {
                    $this->delete_temp_token( $token );
                    $res_data['error'] = ['email'];
                    http_response_code( 400 );
                    echo json_encode( $res_data );
                    exit;
                }
                if ($user->mpop_mail_to_confirm) {
                    $user_edits['user_email'] = $post_data['email'];
                }
                $user_edits['meta_input'] = [
                    '_new_email' => $post_data['email']
                ];
                if (!isset($res_data['notices'])) {
                    $res_data['notices'] = [];
                }
                $res_data['notices'][] = ['type'=>'info', 'msg' => 'È stata inviata un\'e-mail di conferma all\'indirizzo indicato'];
            } else {
                $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                if(!$this->send_confirmation_mail($token, $user->user_email)) {
                    $this->delete_temp_token( $token );
                    $res_data['error'] = ['email'];
                    http_response_code( 400 );
                    echo json_encode( $res_data );
                    exit;
                }
                $user_edits['meta_input'] = [
                    'mpop_mail_to_confirm' => true,
                    '_new_email' => false
                ];
                if (!isset($res_data['notices'])) {
                    $res_data['notices'] = [];
                }
                $res_data['notices'][] = ['type'=>'info', 'msg' => 'È stata inviata un\'e-mail di conferma all\'indirizzo indicato'];
            }
        }
        if (!isset($user_edits['meta_input'])) {
            $user_edits['meta_input'] = [];
        }
        foreach([
            'first_name',
            'last_name',
            'mpop_birthdate',
            'mpop_birthplace_country',
            'mpop_birthplace',
            'mpop_billing_address',
            'mpop_billing_country',
            'mpop_billing_city',
            'mpop_billing_zip',
            'mpop_billing_state',
            'mpop_phone',
            'mpop_old_card_number'
        ] as $prop) {
            $user_edits['meta_input'][$prop] = $post_data[$prop];
        }
        if (count($user_edits)) {
            $user_edits['ID'] = $user->ID;
            wp_update_user( $user_edits );
            $this->log_data('USER UPDATED', $user_edits, $user->ID);
            delete_user_meta( $user->ID, 'mpop_profile_pending_edits' );
            if ($user->discourse_sso_user_id && isset($user->roles[0]) && in_array($user->roles[0], ['administrator', 'multipopolano', 'multipopolare_resp', 'multipopolare_friend'])) {
                $this->sync_discourse_record($user);
            }
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type'=>'success', 'msg' => 'Dati salvati correttamente'];
        }
        $res_data['data'] = ['user' => $this->myaccount_get_profile($user->ID, true, true)];
        break;
    case 'resp_resend_invitation_mail':
        if (!isset($post_data['ID']) || !is_int($post_data['ID'])) {
            $res_data['error'] = ['ID'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessun utente selezionato']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $user = get_user_by('ID', $post_data['ID']);
        if (!$user || !$user->mpop_invited || !$this->is_resp_of_user($user)) {
            $res_data['error'] = ['ID'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Utente non valido']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $this->delete_temp_token_by_user_id($user->ID, 'invite_link');
        $token = $this->create_temp_token($user->ID,'invite_link',3600*24*30);
        if(!$this->send_invitation_mail($token, $user->user_email)) {
            $res_data['error'] = ['server'];
            $res_data['notices'] = [['type'=>'error', 'msg' => "Errore durante l'invio dell'invito" . ($this->last_mail_error ? ': ' . $this->last_mail_error : '')]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $res_data['data'] = 'ok';
        $res_data['notices'] = [['type'=>'success', 'msg' => 'Invito inviato con successo']];
        break;
    case 'resp_view_sub':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id'], 0, ['completer_ip', 'pp_capture_id']);
        if (!$sub) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub_files = [];
        $sub['is_editable'] = $this->is_resp_of_user($sub['user_id']);
        if ($sub['status'] =='tosee') {
            $sub_user = get_user_by('ID', $sub['user_id']);
            if ($sub_user) {
                if ($this->user_has_valid_id_card($sub_user)) {
                    unlink($this->get_filename_by_sub($sub, true));
                    unlink($this->get_filename_by_sub($sub, true, false));
                    $sub['user_id_card_confirmed'] = true;
                } else {
                    $sub['user_id_card_confirmed'] = false;
                    if (file_exists($this->get_filename_by_sub($sub, true)) || file_exists($this->get_filename_by_sub($sub, true, false))) {
                        $sub_files[] = 'idCard';
                        $sub['user_id_card_number'] = $sub_user->mpop_id_card_number;
                        $sub['user_id_card_type'] = intval($sub_user->mpop_id_card_type);
                        $sub['user_id_card_expiration'] = $sub_user->mpop_id_card_expiration;
                    }
                }
            }
        }
        if (file_exists($this->get_filename_by_sub($sub)) || file_exists($this->get_filename_by_sub($sub, false, false))) {
            $sub_files[] = 'signedModule';
        }
        unset($sub['filename']);
        $res_data['data'] = $sub + ['files' => $sub_files];
        break;
    case 'resp_documents_decrypt':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id'], 0, ['completer_ip', 'pp_order_id', 'pp_capture_id']);
        if (!$sub || !$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!isset($post_data['password']) || !is_string($post_data['password']) || !$post_data['password']) {
            $res_data['error'] = ['password'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Password non valida']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            $docs = $this->decrypt_module_documents($sub, $post_data['password']);
            if (!$docs || !is_array($docs)) {
                $res_data['error'] = ['password'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Password non valida']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_data['data'] = $docs;
        } catch(Exception $err) {
            $res_data['error'] = ['password'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Password non valida']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_documents_confirm':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id'], 0, ['']);
        if (!$sub || $sub['status'] != 'tosee' || !$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $force_id_card = isset($post_data['forceIdCard']) ? boolval($post_data['forceIdCard']) : false;
        try {
            if (!$this->confirm_module_documents($sub, $force_id_card)) {
                $res_data['error'] = ['unknown_error'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore sconosciuto']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_data['data'] = true;
        } catch (Exception $err) {
            $res_data['error'] = [$err->getMessage()];
            $res_data['notices'] = [['type'=>'error', 'msg'=>$err->getMessage()]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_subscription_refuse':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id'], 0, ['completer_ip', 'pp_order_id', 'pp_capture_id']);
        if (!$sub || in_array( $sub['status'], ['refused', 'canceled', 'completed']) || !$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            if (!$this->refuse_subscription($sub)) {
                $res_data['error'] = ['unknown_error'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore sconosciuto']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_data['data'] = true;
        } catch (Exception $err) {
            $res_data['error'] = [$err->getMessage()];
            $res_data['notices'] = [['type'=>'error', 'msg'=>$err->getMessage()]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_payment_confirm':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id']);
        if (!$sub || $sub['status'] != 'seen' || !$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!isset($post_data['signed_at']) || !is_string($post_data['signed_at'])) {
            $res_data['error'] = ['signed_at'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Data di iscrizione/rinnovo non valida']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            $d = $this::validate_date($post_data['signed_at']);
            if ($d->getTimestamp() > time()) {
                $res_data['error'] = ['signed_at'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Data di iscrizione/rinnovo non valida']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $this->complete_subscription($sub['id'], $d->getTimestamp());
            $res_data['data'] = true;
        } catch (Exception $err) {
            $res_data['error'] = [$err->getMessage()];
            $res_data['notices'] = [['type'=>'error', 'msg'=>$err->getMessage()]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_cancel_subscription':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id']);
        if (!$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            if(!$this->cancel_subscription($sub)) {
                $res_data['error'] = ['unknown_error'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore sconosciuto']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_data['data'] = true;
        } catch (Exception $err) {
            $res_data['error'] = [$err->getMessage()];
            $res_data['notices'] = [['type'=>'error', 'msg'=>$err->getMessage()]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'admin_save_sub_notes':
        if (!isset($post_data['id']) || !is_int($post_data['id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!isset($post_data['notes']) || !is_string($post_data['notes'])) {
            $res_data['error'] = ['notes'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Dati non validi']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $sub = $this->get_subscription_by('id', $post_data['id']);
        if (!$sub || !$this->is_resp_of_user($sub['user_id'])) {
            $res_data['error'] = ['id'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $post_data['notes'] = trim($post_data['notes']);
        if (!$post_data['notes']) $post_data['notes'] = null;
        $date_now = date_create('now', new DateTimeZone(current_time('e')));
        global $wpdb;
        if (!$wpdb->update(
            $wpdb->prefix . 'mpop_subscriptions',
            [
                'notes' => $post_data['notes'],
                'updated_at' => $date_now->getTimestamp()
            ],
            [
                'id' => $post_data['id']
            ]
        )) {
            $res_data['error'] = ['notes'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessuna sottoscrizione selezionata']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $res_data['data'] = true;
        break;
    case 'resp_confirm_profile_pending_edits':
        if (!isset($post_data['ID']) || !is_int($post_data['ID'])) {
            $res_data['error'] = ['ID'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessun utente selezionato']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $user = get_user_by('ID', $post_data['ID']);
        if (!$user || !$user->mpop_profile_pending_edits || !$this->is_resp_of_user($user)) {
            $res_data['error'] = ['ID'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessun utente selezionato']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $pending_edits = json_decode($user->mpop_profile_pending_edits, true);
        foreach ($pending_edits as $k => &$v) {
            if ( in_array($k, ['mpop_birthplace', 'mpop_billing_city']) && $v) {
                $v = $v['codiceCatastale'];
            }
        }
        wp_update_user([
            'ID' => $user->ID,
            'meta_input' => $pending_edits
        ]);
        $this->log_data('USER UPDATED', ['meta_input' => $pending_edits], $user->ID);
        delete_user_meta($user->ID, 'mpop_profile_pending_edits');
        delete_user_meta($user->ID, 'mpop_id_card_confirmed');
        $res_data['data'] = true;
        break;
    case 'resp_refuse_profile_pending_edits':
        if (!isset($post_data['ID']) || !is_int($post_data['ID']) || !$this->is_resp_of_user($post_data['ID'])) {
            $res_data['error'] = ['ID'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessun utente selezionato']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        delete_user_meta($post_data['ID'], 'mpop_profile_pending_edits');
        $res_data['data'] = true;
        break;
    case 'resp_add_user':
        try {
            $this->add_user($post_data, $res_data, true);
        } catch (Exception $err) {
            $res_data['error'] = ['unknown'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore sconosciuto']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_add_subscription':
        try {
            if (!isset($post_data['user_id']) || !is_int($post_data['user_id']) || !$this->is_resp_of_user($post_data['user_id'])) {
                $res_data['error'] = ['user_id'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Nessun utente selezionato']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $signed_at_date = false;
            if ( $post_data['status'] == 'completed' ) {
                $signed_at_date = $this::validate_date($post_data['signed_at']);
                $max_signed_date = date_create_from_format('Y-m-d H:i:s e', $post_data['year'] . '-12-31 00:00:00 '. current_time('e'));
                if (!$max_signed_date || $max_signed_date->getTimestamp() < $signed_at_date->getTimestamp()) {
                    $res_data['error'] = ['year'];
                    $res_data['notices'] = [['type'=>'error', 'msg' => 'Anno o data di iscrizione/rinnovo non validi']];
                    http_response_code( 400 );
                    echo json_encode( $res_data );
                    exit;
                }
            }
            $sub_id = $this->create_subscription(
                $post_data['user_id'],
                $post_data['year'],
                $post_data['quote'],
                $post_data['marketing_agree'],
                $post_data['newsletter_agree'],
                $post_data['publish_agree'],
                $post_data['notes'],
                false,
                true
            );
            if (!$sub_id) {
                $res_data['error'] = ['unknown'];
                $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore sconosciuto']];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            }
            $res_data['data'] = $sub_id;
            if ($signed_at_date) {
                $this->complete_subscription($sub_id, $signed_at_date->getTimestamp());
            }
        } catch (Exception $err) {
            $res_data['error'] = ['unknown'];
            $res_data['notices'] = [['type'=>'error', 'msg' => $err->getMessage()]];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'resp_subscription_upload_files':
        $this->subscription_upload_files($post_data, $res_data, true);
        break;
    case 'resp_masterkey_change':
        $this->change_master_key($post_data, $res_data);
        break;
    default:
        $res_data['error'] = ['action'];
        $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
        http_response_code( 400 );
        echo json_encode( $res_data );
        exit;
}
if (!isset($res_data['data'])) {
    $res_data['error'] = ['action'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (isset($res_data['data'])) {
    echo json_encode( $res_data );
    exit;
}