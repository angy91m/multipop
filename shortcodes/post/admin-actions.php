<?php
defined( 'ABSPATH' ) || exit;
switch( $post_data['action'] ) {
    case 'admin_search_users':
        [$users, $total, $limit] = $this->user_search($post_data['txt'], $post_data['roles'], $post_data['mpop_billing_state'], $post_data['mpop_billing_city'], $post_data['mpop_resp_zones'], $post_data['mpop_card_active'], $post_data['mpop_mail_to_confirm'], $post_data['page'], $post_data['sortBy']);
        $res_data['data'] = ['users' => $users, 'total' => $total, 'limit' => $limit];
        break;
    case 'admin_view_user':
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
            $res_data['data'] = ['user' => $res_user];
        }
        break;
    case 'admin_update_user':
        $user = false;
        if (isset($post_data['ID']) && is_int($post_data['ID']) && $post_data['ID'] > 0 ) {
            $user = get_user_by('ID', $post_data['ID']);
        }
        if (!$user) {
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
        $card_active = $user->mpop_card_active;
        if (!isset($post_data['email']) || !is_string($post_data['email']) || !$this::is_valid_email(trim($post_data['email']), true)) {
            $res_data['error'] = ['email'];
        } else {
            $post_data['email'] = mb_strtolower( trim($post_data['email']), 'UTF-8' );
        }
        if (!isset($post_data['mpop_mail_confirmed']) || !is_bool($post_data['mpop_mail_confirmed'])) {
            $res_data['error'] = ['email'];
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
        }
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
        if (!isset($post_data['mpop_billing_address']) || !is_string($post_data['mpop_billing_address']) || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') < 2) {
            if ($user->mpop_billing_address) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_address';
            } else {
                $post_data['mpop_billing_address'] = '';
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
        if (!isset($post_data['mpop_birthdate']) || !is_string($post_data['mpop_birthdate']) || strlen(trim($post_data['mpop_birthdate'])) != 10) {
            if ($user->mpop_birthdate) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
            } else {
                $post_data['mpop_birthdate'] = '';
                $post_data['mpop_birthplace'] = '';
            }
        }
        $date_arr = array_map(function ($dt) {return intval($dt);}, explode('-', strval( $post_data['mpop_birthdate'] ) ) );
        if (
            count($date_arr) != 3
            || !checkdate($date_arr[1], $date_arr[2], $date_arr[0])
        ) {
            if ($user->mpop_birthdate) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
            }
        } else {
            $post_data['mpop_birthdate'] = date_create('now', new DateTimeZone(current_time('e')));
            $post_data['mpop_birthdate']->setDate($date_arr[0], $date_arr[1], $date_arr[2]);
            $post_data['mpop_birthdate']->setTime(0,0,0,0);
            if (
                $post_data['mpop_birthdate']->getTimestamp() < $min_birthdate->getTimestamp()
                || $post_data['mpop_birthdate']->getTimestamp() > $max_birthdate->getTimestamp()
            ) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
            }
        }
        if (!isset($post_data['mpop_birthplace']) || !preg_match('/^[A-Z]\d{3}$/', $post_data['mpop_birthplace'])) {
            if ($user->mpop_birthplace) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthplace';
            } else {
                $post_data['mpop_birthplace'] = '';
            }
        } else {
            if (!$comuni) {
                $comuni = $this->get_comuni_all();
            }
            $found_bp = array_values(array_filter($comuni, function($c) use ($post_data) {return $c['codiceCatastale'] == $post_data['mpop_birthplace'];}));
            if (!count($found_bp)) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthplace';
            } else {
                if (isset($found_bp[0]['soppresso']) && $found_bp[0]['soppresso']) {
                    if (!isset($found_bp[0]['dataSoppressione'])) {
                        if (!isset($res_data['error'])) {
                            $res_data['error'] = [];
                        }
                        $res_data['error'][] = 'mpop_birthdate';
                        $res_data['error'][] = 'mpop_birthplace';
                    } else {
                        $soppressione_dt = date_create('now', new DateTimeZone('UTC'));
                        $soppr_arr = explode('T', $found_bp[0]['dataSoppressione']);
                        $soppr_arr_dt = array_map( function($v) {return intval($v);}, explode('-', $soppr_arr[0]));
                        $soppr_arr_tm = array_map( function($v) {return intval(substr( $v, 0, 2));}, explode(':', $soppr_arr[1]));
                        $soppressione_dt->setDate($soppr_arr_dt[0], $soppr_arr_dt[1], $soppr_arr_dt[2]);
                        $soppressione_dt->setTime($soppr_arr_tm[0], $soppr_arr_tm[1], $soppr_arr_tm[2]);
                        if ($post_data['mpop_birthdate']->getTimestamp() >= $soppressione_dt->getTimestamp()) {
                            if (!isset($res_data['error'])) {
                                $res_data['error'] = [];
                            }
                            $res_data['error'][] = 'mpop_birthdate';
                            $res_data['error'][] = 'mpop_birthplace';
                        }
                    }
                }
            }
        }
        $parsed_resp_zones = false;
        if (isset($user->roles[0]) && $user->roles[0] == 'multipopolare_resp' && isset($post_data['mpop_resp_zones']) && is_array($post_data['mpop_resp_zones'])) {
            $parsed_resp_zones = [];
            $regioni = false;
            $province = false;
            $comuni = false;
            foreach ($post_data['mpop_resp_zones'] as $zone) {
                if (!is_string($zone)) continue;
                if (str_starts_with($zone, 'reg_')) {
                    if (!$regioni) {
                        $regioni = $this->get_regioni_all();
                    }
                    $found = $regioni[substr($zone, 4)];
                    if ($found) {
                        $parsed_resp_zones[] = ['nome' => substr($zone, 4), 'type' => 'regione', 'province' => $found];
                    }
                } else if (preg_match('/^[A-Z]{2}$/', $zone)) {
                    if (!$province) {
                        $province = $this->get_province_all();
                    }
                    $found = array_filter($province, function($p) use ($zone) { return $p['sigla'] == $zone; });
                    $found = array_pop($found);
                    if ($found) {
                        $parsed_resp_zones[] = $found + ['type' => 'provincia'];
                    }
                } else if (preg_match('/^[A-Z]\d{3}$/', $zone)) {
                    if (!$comuni) {
                        $comuni = $this->get_comuni_all();
                    }
                    $found = array_filter($comuni, function($c) use ($zone) { return $c['codiceCatastale'] == $zone; });
                    $found = array_pop($found);
                    if ($found) {
                        $parsed_resp_zones[] = $found + ['type' => 'comune'];
                    }
                }
            }
            if (count($parsed_resp_zones)) {
                $parsed_resp_zones = $this->reduce_zones($parsed_resp_zones);
            }
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
            'login__not_in' => [$user->user_login]
        ]);
        if (count($duplicated)) {
            $res_data['error'] = ['email'];
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
                $res_mail = $this->send_confirmation_mail($token, $post_data['email']);
                if (!$res_mail) {
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
                $res_data['notices'] = [['type'=>'info', 'msg' => 'È stata inviata un\'e-mail di conferma all\'indirizzo indicato']];
            } else {
                $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                $res_mail = $this->send_confirmation_mail($token, $user->user_email);
                if (!$res_mail) {
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
                $res_data['notices'] = [['type'=>'info', 'msg' => 'È stata inviata un\'e-mail di conferma all\'indirizzo indicato']];
            }
        }
        if (!isset($user_edits['meta_input'])) {
            $user_edits['meta_input'] = [];
        }
        if (is_array($parsed_resp_zones)) {
            $resp_zones_edits = [];
            foreach ($parsed_resp_zones as $zone) {
                switch($zone['type']) {
                    case 'regione':
                        $resp_zones_edits[] = 'reg_' . $zone['nome'];
                        break;
                    case 'provincia':
                        $resp_zones_edits[] = $zone['sigla'];
                        break;
                    case 'comune':
                        $resp_zones_edits[] = $zone['codiceCatastale'];
                        break;
                }
            }
            $user_edits['meta_input']['mpop_resp_zones'] = $resp_zones_edits;
        }
        foreach([
            'first_name',
            'last_name',
            'mpop_birthdate',
            'mpop_birthplace',
            'mpop_billing_address',
            'mpop_billing_city',
            'mpop_billing_zip',
            'mpop_billing_state'
        ] as $prop) {
            switch($prop) {
                case 'mpop_birthdate':
                    if (is_object($post_data[$prop])) {
                        $user_edits['meta_input'][$prop] = $post_data[$prop]->format('Y-m-d');
                    } else {
                        $user_edits['meta_input'][$prop] = $post_data[$prop];
                    }
                    break;
                default:
                    $user_edits['meta_input'][$prop] = $post_data[$prop];
            }
        }
        if (count($user_edits)) {
            $user_edits['ID'] = $user->ID;
            wp_update_user( $user_edits );
            delete_user_meta( $user->ID, 'mpop_profile_pending_edits' );
            if ($user->discourse_sso_user_id && isset($user->roles[0]) && in_array($user->roles[0], ['administrator', 'multipopolano', 'multipopolare_resp'])) {
                $this->update_discourse_groups_by_user($user);
            }
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type'=>'success', 'msg' => 'Dati salvati correttamente'];
        }
        $res_data['data'] = ['user' => $this->myaccount_get_profile($user->ID, true, true)];
        break;
    case 'admin_search_subscriptions':
        $res = $this->search_subscriptions($post_data);
        if (!$res) {
            $res_data['error'] = ['data'];
            $res_data['notices'] = [['type'=>'error', 'msg' => 'Errore durante la ricerca']];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $res_data['data'] = $res;
        break;
    case 'admin_search_zones':
        $res_data['data'] = $this->search_zones($post_data['search']);
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