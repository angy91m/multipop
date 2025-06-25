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
if (!isset($post_data['mpop-invite-nonce']) || !is_string($post_data['mpop-invite-nonce'])) {
    $res_data['error'] = ['mpop-invite-nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Richiesta non valida']];
    http_response_code( 400 );
    echo json_encode( $res_data );
    exit;
}
if (!wp_verify_nonce($post_data['mpop-invite-nonce'], 'mpop-invite')) {
    $res_data['error'] = ['nonce'];
    $res_data['notices'] = [['type'=>'error', 'msg' => 'Pagina scaduta. Ricarica la pagina e riprova']];
    http_response_code( 401 );
    echo json_encode( $res_data );
    exit;
}
switch( $post_data['action'] ) {
    case 'get_countries':
        $res_data['data'] = ['countries' => $this->get_countries_all()];
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
    case 'get_profile':
        $res_data['data'] = ['profile' => $this->myaccount_get_profile($this->invited_user, true)];
        break;
    case 'activate_account':
        $user = $this->invited_user;
        $res_data['error'] = [];
        $residenza = false;
        if (!isset($post_data['password']) || !$this::is_valid_password($post_data['password'])) {
            $res_data['error'][] = 'password';
        }
        if ($user->roles[0] == 'multipopolano') {
            if(!isset($post_data['username']) || !$this::is_valid_username($post_data['username']) || get_user_by('login', $post_data['username'])) {
                $res_data['error'][] = 'username';
            }
            if(!isset($post_data['first_name']) || !$this::is_valid_name($post_data['first_name'])) {
                $res_data['error'][] = 'first_name';
            }
            if(!isset($post_data['last_name']) || !$this::is_valid_name($post_data['last_name'])) {
                $res_data['error'][] = 'last_name';
            }
            $comuni = [];
            if(!isset($post_data['mpop_birthdate'])) {
                $res_data['error'][] = 'mpop_birthdate';
                $post_data['mpop_birthdate'] = false;
            } else {
                try {
                    $post_data['mpop_birthdate'] = $this::validate_birthdate($post_data['mpop_birthdate']);
                } catch(Exception $e) {
                    $res_data['error'][] = 'mpop_birthdate';
                    $post_data['mpop_birthdate'] = false;
                }
            }
            if (!isset($post_data['mpop_birthplace_country']) || !$this->get_country_by_code($post_data['mpop_birthplace_country'])) {
                $res_data['error'][] = 'mpop_birthplace_country';
            } else if ($post_data['mpop_birthplace_country'] == 'ita' && $post_data['mpop_birthdate']) {
                if(!isset($post_data['mpop_birthplace']) ) {
                    $res_data['error'][] = 'mpop_birthplace';
                } else {
                    if (empty($comuni)) {
                        $comuni = $this->get_comuni_all();
                    }
                    try {
                        $post_data['mpop_birthdate'] = $this->validate_birthplace($post_data['mpop_birthdate'], $post_data['mpop_birthplace'], $comuni);
                    } catch(Exception $e) {
                        array_push($res_data['error'], ...explode(',',$e->getMessage()));
                    }
                }
            } else if ($post_data['mpop_birthdate']) {
                $post_data['mpop_birthdate'] = $post_data['mpop_birthdate']->format('Y-m-d');
            }
            if (!isset($post_data['mpop_billing_country']) || !$this->get_country_by_code($post_data['mpop_billing_country'])) {
                $res_data['error'][] = 'mpop_billing_country';
            } else if ($post_data['mpop_billing_country'] == 'ita') {
                if (!isset($post_data['mpop_billing_city']) || !is_string($post_data['mpop_billing_city']) || !preg_match('/^[A-Z]\d{3}$/', $post_data['mpop_billing_city'])) {
                    $res_data['error'][] = 'mpop_billing_city';
                } else {
                    if (empty($comuni)) {
                        $comuni = $this->get_comuni_all();
                    }
                    $residenza = $this->get_comune_by_catasto($post_data['mpop_billing_city'], false, $comuni);
                    if (!$residenza) {
                        $res_data['error'][] = 'mpop_billing_city';
                    } else {
                        if(!isset($post_data['mpop_billing_zip']) || !in_array($post_data['mpop_billing_zip'], $residenza['cap'])) {
                            $res_data['error'][] = 'mpop_billing_zip';
                        }
                    }
                }
            }
            if (!isset($post_data['mpop_billing_address']) || !is_string($post_data['mpop_billing_address']) || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') < 2 || mb_strlen(trim($post_data['mpop_billing_address']), 'UTF-8') > 200) {
                $res_data['error'][] = 'mpop_billing_address';
            }
            if (!isset($post_data['mpop_phone']) || !$this::is_valid_phone($post_data['mpop_phone']) || !empty(get_users(['meta_key' => 'mpop_phone', 'meta_value' => $post_data['mpop_phone'], 'meta_compare' => '=', 'login__not_in' => [$user->user_login]]))) {
                $res_data['error'][] = 'mpop_phone';
            }
        }
        if (!empty($res_data['error'])) {
            http_response_code( 400 );
            echo json_encode( $res_data );
            exit;
        } else {
            unset($res_data['error']);
        }
        $meta_input = [
            'mpop_invited' => false
        ] + ($user->roles[0] == 'multipopolano'? [
            'first_name' => mb_strtoupper($post_data['first_name'], 'UTF-8'),
            'last_name' =>  mb_strtoupper($post_data['last_name'], 'UTF-8'),
            'mpop_birthdate' => $post_data['mpop_birthdate'],
            'mpop_birthplace_country' => $post_data['mpop_birthplace_country'],
            'mpop_birthplace' => $post_data['mpop_birthplace_country'] == 'ita' ? $post_data['mpop_birthplace'] : '',
            'mpop_billing_country' => $post_data['mpop_billing_country'],
            'mpop_billing_city' => $post_data['mpop_billing_country'] == 'ita' ? $post_data['mpop_billing_city'] : '',
            'mpop_billing_state' => $post_data['mpop_billing_country'] == 'ita' ? $residenza['provincia']['sigla'] : '',
            'mpop_billing_zip' => $post_data['mpop_billing_country'] == 'ita' ? $post_data['mpop_billing_zip'] : '',
            'mpop_billing_address' => $post_data['mpop_billing_address'],
            'mpop_phone' => $post_data['mpop_phone'],
            'mpop_marketing_agree' => isset($post_data['mpop_subscription_marketing_agree']) ? boolval($post_data['mpop_subscription_marketing_agree']) : false,
            'mpop_newsletter_agree' => isset($post_data['mpop_subscription_newsletter_agree']) ? boolval($post_data['mpop_subscription_newsletter_agree']) : false,
            'mpop_publish_agree' => isset($post_data['mpop_subscription_publish_agree']) ? boolval($post_data['mpop_subscription_publish_agree']) : false
        ]:[]);
        $user_edits = [
            'ID' => $user->ID,
            'user_pass' => $post_data['password'],
            'meta_input' => $meta_input
        ];
        add_filter('send_password_change_email', function() {return false;}, 10, 0);
        wp_update_user($user_edits);
        $this->log_data('USER ENABLED', null, ['role' => $user->roles[0],'meta_input' => $meta_input], $user->ID);
        if ($user->roles[0] == 'multipopolano') {
            $this->change_user_login($user->ID, $post_data['username'], mb_strtoupper($post_data['first_name'], 'UTF-8') . ' ' . mb_strtoupper($post_data['last_name'], 'UTF-8'));
            $user = get_user_by('ID',$user->ID);
        } else {
            clean_user_cache($user->ID);
            update_user_caches(get_user_by('ID', $user->ID));
            $user = get_user_by('ID',$user->ID);
        }
        $this->sync_discourse_record($user, true);
        $this->delete_temp_token_by_user_id($user->ID, 'invite_link');
        wp_set_auth_cookie( $user->ID, true );
        $res_data['data'] = get_permalink($this->settings['myaccount_page']);
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