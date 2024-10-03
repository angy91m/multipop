<?php
defined( 'ABSPATH' ) || exit;
switch( $post_data['action'] ) {
    case 'admin_search_users':
        [$users, $total, $limit] = $this->user_search($post_data['txt'], $post_data['roles'], $post_data['mpop_billing_state'], $post_data['mpop_billing_city'], $post_data['page'], $post_data['sortBy']);
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
            $res_user = $this->myaccount_get_profile($post_data['ID'], true);
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
        if (isset($res_data['error'])) {
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $user_edits = [];
        if (
            (!$user->_new_email && $user->user_email != $post_data['email'] )
            || ($user->_new_email ? $user->_new_email != $post_data['email'] : false)
        ) {
            $duplicated = get_user_by('email', $post_data['email']);
            if ($duplicated) {
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
            } else {
                $this->delete_temp_token_by_user_id($user->ID);
                $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                $res_mail = $this->send_confirmation_mail($token, $post_data['email']);
                if (!$res_mail) {
                    $this->delete_temp_token( $token );
                    $res_data['error'] = ['email'];
                    http_response_code( 400 );
                    echo json_encode( $res_data );
                    exit;
                }
                $user_edits['meta_input'] = [
                    '_new_email' => $post_data['email']
                ];
                $res_data['notices'] = [['type'=>'info', 'msg' => 'È stata inviata un\'e-mail di conferma all\'indirizzo indicato']];
            }
        } else {
            if ($user->user_email == $post_data['email']) {
                $user_edits['meta_input'] = ['_new_email' => false];
            }
        }

        if (!isset($user_edits['meta_input'])) {
            $user_edits['meta_input'] = [];
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
            if (
                $user->discourse_sso_user_id
                && in_array($user->roles[0],['multipopolano', 'multipopolare_resp'])
                && strval($user->mpop_billing_city) != strval($post_data['mpop_billing_city'])
            ) {
                $disc_utils = $this->discourse_utilities();
                if ($disc_utils) {
                    $current_user_groups = $disc_utils->get_mpop_discourse_groups_by_user($user);
                    if ($current_user_groups) {
                        $new_user_disc_groups = $this->generate_user_discourse_groups($user);
                        $remove_groups = [];
                        $add_groups = [];
                        foreach($current_user_groups as $group) {
                            if (!array_pop(array_filter($new_user_disc_groups, function($g) use ($group) {return $g['name'] == $group;}))) {
                                $remove_groups[] = $group;
                            }
                        }
                        foreach($new_user_disc_groups as $group) {
                            if (!in_array($group['name'], $current_user_groups)) {
                                $add_groups[] = $group;
                            }
                        }
                        if (!empty($remove_groups)) {
                            $disc_utils->remove_user_from_discourse_group($user->ID, implode(',',$remove_groups));
                        }
                        if (!empty($add_groups)) {
                            $current_groups = $disc_utils->get_mpop_discourse_groups();
                            foreach($add_groups as $group) {
                                if (!array_pop(array_filter($current_groups, function($g) use ($group) {return $g['name'] == $group['name'];}))) {
                                    $disc_utils->create_discourse_group($group['name'], $group['full_name']);
                                }
                            }
                            $disc_utils->add_user_to_discourse_group($user->ID, implode(',', array_map(function($g) {return $g['name'];}, $add_groups)));
                        }
                    }
                }
            }
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type'=>'success', 'msg' => 'Dati salvati correttamente'];
        }
        $res_data['data'] = ['user' => $this->myaccount_get_profile($user->ID, true)];
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