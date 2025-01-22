<?php
defined( 'ABSPATH' ) || exit;
$post_data = json_decode( file_get_contents('php://input'), true );
$res_data = [];
if (!isset($post_data['action']) || !is_string($post_data['action']) || !trim($post_data['action'])) {
    $res_data['error'] = ['action'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (!isset($post_data['mpop-logged-myaccount-nonce']) || !is_string($post_data['mpop-logged-myaccount-nonce'])) {
    $res_data['error'] = ['nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (!wp_verify_nonce($post_data['mpop-logged-myaccount-nonce'], 'mpop-logged-myaccount')) {
    $res_data['error'] = ['nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Pagina scaduta. Ricarica la pagina e riprova']];
    http_response_code( 401 );
    echo json_encode( $res_data );
    exit;
}
if (str_starts_with($post_data['action'], 'admin_')) {
    if ($this->current_user_is_admin()) {
        require('admin-actions.php');
    } else {
        http_response_code( 401 );
    }
    exit;
}
if (str_starts_with($post_data['action'], 'resp_')) {
    $current_user = wp_get_current_user();
    if (isset($current_user->roles[0]) && in_array($current_user->roles[0], ['administrator', 'multipopolare_resp']) ) {
        require('resp-actions.php');
    } else {
        http_response_code( 401 );
    }
    exit;
}
switch ($post_data['action']) {
    case 'get_countries':
        $res_data['data'] = ['countries' => $this->get_countries_all()];
        break;
    case 'get_authorized_subscription_years':
        $years = [];
        if ( isset($this->settings['master_doc_key']) && $this->settings['master_doc_key'] && isset($this->settings['authorized_subscription_years'])) {
            $years = $this->settings['authorized_subscription_years'];
        }
        $res_data['data'] = ['years' => $years];
        break;
    case 'get_birth_cities':
        if (!isset($post_data['mpop_birthplace'])) {
            $res_data['error'] = ['mpop_birthplace'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        if (!isset($post_data['mpop_birthdate'])) {
            $res_data['error'] = ['mpop_birthdate'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $post_birthdate = '';
        try {
            $filtered_comuni = $this->get_birth_cities($post_data['mpop_birthplace'], $post_data['mpop_birthdate']);
            $res_data['data'] = ['comuni' => $filtered_comuni];
        } catch (Exception $err) {
            $res_data['error'] = explode(',',$err->getMessage());
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'get_billing_cities':
        if (!isset($post_data['mpop_billing_city'])) {
            $res_data['error'] = ['mpop_billing_city'];
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        try {
            $filtered_comuni = $this->get_billing_cities($post_data['mpop_billing_city']);
            $res_data['data'] = ['comuni' => $filtered_comuni];
        } catch(Exception $e) {
            $res_data['error'] = explode(',',$err->getMessage());
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        break;
    case 'update_profile':
        $comuni = false;
        $found_caps = [];
        $has_subs = !empty($this->get_subscriptions(['user_id' => [$current_user->ID], 'status' => ['completed', 'seen', 'tosee']], 1));
        if (!isset($post_data['email']) || !is_string($post_data['email']) || !$this->is_valid_email(trim($post_data['email']), true)) {
            $res_data['error'] = ['email'];
        } else {
            $post_data['email'] = mb_strtolower( trim($post_data['email']), 'UTF-8' );
        }
        if (!isset($post_data['mpop_billing_country']) || !$this->get_country_by_code($post_data['mpop_billing_country'])) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_billing_country';
        } elseif ($post_data['mpop_billing_country'] != 'ita') {
            $post_data['mpop_billing_city'] = '';
            $post_data['mpop_billing_state'] = '';
            $post_data['mpop_billing_zip'] = '';
        } else {
            if (!isset($post_data['mpop_billing_city']) || !preg_match('/^[A-Z]\d{3}$/', $post_data['mpop_billing_city'])) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_city';
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
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_zip';
            }
            if (!isset($post_data['mpop_billing_zip']) || !in_array($post_data['mpop_billing_zip'], $found_caps)) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_billing_zip';
            }
        }
        if (!isset($post_data['mpop_billing_address']) || !is_string($post_data['mpop_billing_address']) || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') < 2 || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') > 200) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_billing_address';
        }
        if (!isset($post_data['mpop_phone']) || !$this::is_valid_phone($post_data['mpop_phone']) ) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_phone';
        } else if (!empty(get_users([
                'meta_key' => 'mpop_phone',
                'meta_value' => $post_data['mpop_phone'],
                'meta_compare' => '=',
                'login__not_in' => [$current_user->user_login]
        ]))) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'mpop_phone';
        }
        if (!$has_subs) {
            if (!isset($post_data['first_name']) || !is_string($post_data['first_name']) || mb_strlen(trim($post_data['first_name']), 'UTF-8') < 2) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'first_name';
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
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'last_name';
            } else {
                $post_data['last_name'] = mb_strtoupper( trim($post_data['last_name']), 'UTF-8' );
                if (!$this::is_valid_name($post_data['last_name'])) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'last_name';
                }
            }
            if (!isset($post_data['mpop_birthdate'])) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthdate';
            } else {
                try {
                    $this::validate_birthdate($post_data['mpop_birthdate']);
                } catch(Exception $e) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_birthdate';
                }
            }
            if (!isset($post_data['mpop_birthplace_country']) || !$this->get_country_by_code($post_data['mpop_birthplace_country'])) {
                if (!isset($res_data['error'])) {
                    $res_data['error'] = [];
                }
                $res_data['error'][] = 'mpop_birthplace_country';
            } elseif ($post_data['mpop_birthplace_country'] != 'ita') {
                $post_data['mpop_birthplace'] = '';
            } else {
                if (!isset($post_data['mpop_birthplace'])) {
                    if (!isset($res_data['error'])) {
                        $res_data['error'] = [];
                    }
                    $res_data['error'][] = 'mpop_birthplace';
                }
                if (!isset($res_data['error'])) {
                    if (!$comuni) {
                        $comuni = $this->get_comuni_all();
                    }
                    try {
                        $post_data['mpop_birthdate'] = $this->validate_birthplace($post_data['mpop_birthdate'], $post_data['mpop_birthplace'], $comuni);
                    } catch(Exception $e) {
                        $res_data['error'] = explode(',',$e->getMessage());
                    }
                }
            }
            if (is_object($post_data['mpop_birthdate'])) {
                $post_data['mpop_birthdate'] = $post_data['mpop_birthdate']->format('Y-m-d');
            }
        }
        if (isset($res_data['error'])) {
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        $user_edits = [];
        if (
            (!$current_user->_new_email && $current_user->user_email != $post_data['email'])
            || ($current_user->_new_email && $current_user->_new_email != $post_data['email'])
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
                'login__not_in' => [$current_user->user_login]
            ]);
            if (count($duplicated)) {
                $res_data['error'] = ['email'];
                http_response_code( 400 );
                echo json_encode( $res_data );
                exit;
            } else {
                $this->delete_temp_token_by_user_id($current_user->ID, 'email_confirmation_link');
                $token = $this->create_temp_token( $current_user->ID, 'email_confirmation_link');
                if(!$this->send_confirmation_mail($token, $post_data['email'])) {
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
        } else if ($current_user->user_email == $post_data['email']) {
            if (isset($user_edits['meta_input'])) {
                $user_edits['meta_input'] = [];
            }
            $user_edits['meta_input']['_new_email'] = false;
        }
        if ($has_subs) {
            $pending_edits = [];
            foreach([
                'mpop_billing_address',
                'mpop_billing_country',
                'mpop_billing_city',
                'mpop_billing_zip',
                'mpop_billing_state',
                'mpop_phone'
            ] as $prop) {
                if ($current_user->$prop != $post_data[$prop]) {
                    $pending_edits[$prop] = $post_data[$prop];
                }
            }
            if (count($pending_edits)) {
                if (!isset($user_edits['meta_input'])) {
                    $user_edits['meta_input'] = [];
                }
                $user_edits['meta_input']['mpop_profile_pending_edits'] = json_encode($pending_edits);
                if (!isset($res_data['notices'])) {
                    $res_data['notices'] = [];
                }
                $res_data['notices'][] = ['type'=>'info', 'msg' => 'I dati modificati sono in attesa di revisione'];
            }
        } else {
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
                'mpop_phone'
            ] as $prop) {
                $user_edits['meta_input'][$prop] = $post_data[$prop];
            }
            if (count($user_edits)) {
                delete_user_meta( $current_user->ID, 'mpop_profile_pending_edits' );
                if (!isset($res_data['notices'])) {
                    $res_data['notices'] = [];
                }
                $res_data['notices'][] = ['type'=>'success', 'msg' => 'Dati salvati correttamente'];
            }
        }
        if (count($user_edits)) {
            $user_edits['ID'] = $current_user->ID;
            wp_update_user( $user_edits );
        }
        $res_data['data'] = ['user' => $this->myaccount_get_profile($current_user->ID, true, true)];
        break;
    case 'get_profile':
        $res_data['data'] = ['user' => $this->myaccount_get_profile($current_user->ID, true, true)];
        break;
    case 'password_change':
        if (
            !isset($post_data['current'])
            || !is_string($post_data['current'])
            || !$post_data['current']
        ) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'current';
        }
        if (
            !isset($post_data['new'])
            || !is_string($post_data['new'])
            || !$post_data['new']
            || !$this->is_valid_password($post_data['new'])
        ) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'new';
        }
        if (!wp_check_password($post_data['current'], $current_user->user_pass, $current_user->ID)) {
            if (!isset($res_data['error'])) {
                $res_data['error'] = [];
            }
            $res_data['error'][] = 'current';
            if (!isset($res_data['notices'])) {
                $res_data['notices'] = [];
            }
            $res_data['notices'][] = ['type'=>'error', 'msg' => 'Password attuale non corretta'];
        }
        if (isset($res_data['error'])) {
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        }
        wp_set_password($post_data['new'], $current_user->ID);
        wp_set_auth_cookie($current_user->ID);
        wp_set_current_user($current_user->ID);
        do_action('wp_login', $current_user->user_login, $current_user);
        $res_data['data']['pwdRes'] = 'ok';
        if (!isset($res_data['notices'])) {
            $res_data['notices'] = [];
        }
        $res_data['notices'][] = ['type'=>'success', 'msg' => 'Password modificata correttamente'];
        break;
    default:
        $res_data['error'] = ['action'];
        $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
        http_response_code( 400 );
        echo json_encode( $res_data );
        exit;
}
echo json_encode( $res_data );
exit;