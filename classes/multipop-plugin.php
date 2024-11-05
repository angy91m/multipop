<?php

defined( 'ABSPATH' ) || exit;

// GENERAL FUNCTIONS

// DEBUG VARS
function html_dump($obj) {
    ob_start();
    echo '<pre>';
    var_dump($obj);
    echo '</pre>';
    return ob_get_clean();
}
function save_test($obj, $id=0, $append = false) {
    $txt = '';
    if ($append && file_exists(MULTIPOP_PLUGIN_PATH ."/test-$id.txt")) {
        $txt = file_get_contents(MULTIPOP_PLUGIN_PATH ."/test-$id.txt");
    }
    file_put_contents(MULTIPOP_PLUGIN_PATH ."/test-$id.txt", $txt . html_dump($obj));
}

class MultipopPlugin {

    private ?array $settings;
    private bool $wp_yet_loaded = false;
    private string $req_url = '';
    private string $req_path = '';
    private array $user_notices = [];
    private array $mime2ext = [
        'application/pdf' => '.pdf',
        'image/jpeg' => '.jpg',
        'image/png' => '.png'
    ];
    private array $id_card_types = [
        "Carta d'identità",
        "Patente di guida",
        "Passaporto"
    ];
    private ?array $comuni_all;
    private ?array $province_all;
    private ?array $regioni_all;
    private ?array $countries_all;
    public string $last_mail_error = '';
    private ?object $invited_user;
    private $discourse_system_user;
    public const SUBS_STATUSES = [
        'tosee',
        'seen',
        'refused',
        'canceled',
        'completed',
        'refunded'
    ];
    public const SINGLE_ORG_ROLES = [
        'Presidente',
        'Vicepresidente'
    ];
    public ?object $disc_utils;
    public array $delayed_scripts = [];
    public bool $delayed_action = false;

    // FORMAT DATETIME TO LOCAL STRING YYYY-MM-DD HH:MM:SS TZ
    private static function show_date_time($date) {
        $ts = 0;
        if (is_int($date)) {
            $ts = $date;
        } else {
            if (is_string($date)) {
                $date = date_create($date);
            }
            $ts = $date->getTimestamp();
        }
        return wp_date('Y-m-d H:i:s T', $ts);
    }

    // EMAIL VALIDATION
    private function is_valid_email( $email, $check_temp_mail = false, $bypass_discourse_system_user = false ) {
        $disc_system_user = false;
        if (!$bypass_discourse_system_user) {
            $disc_system_user = $this->get_discourse_system_user();
        }
        $res = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($res && $check_temp_mail && file_exists(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt')) {
            $res = !in_array(explode('@', $email)[1], preg_split('/\r\n|\r|\n/', file_get_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt')));
        }
        if ($res && $disc_system_user) {
            $res = mb_strtolower($email, 'UTF-8') != mb_strtolower($disc_system_user->email, 'UTF-8');
        }
        return $res;
    }

    // USERNAME VALIDATION
    private static function is_valid_username( $username, $login = false ) {
        $disc_system_username = false;
        if(!$login) {
            $disc_system_username = static::get_discourse_system_username();
        }
        if (
            !is_string($username)
            || ($disc_system_username && $disc_system_username == $username)
            || !preg_match('/^[a-z0-9._-]{3,20}$/', $username)
            || !preg_match('/[a-z0-9]/', $username)
            || str_starts_with( $username, '.' )
            || str_starts_with( $username, '-' )
            || str_starts_with( $username, 'mp_' )
            || str_ends_with( $username, '.' )
            || str_ends_with( $username, '-' )
        ) {return false;}
        return true;
    }

    // PASSWORD VALIDATION
    private static function is_valid_password( $password ) {
        if ( !is_string($password) || mb_strlen( $password, 'UTF-8' ) < 8 ) {return false;}
        $rr = [
            '/[a-z]+/',
            '/[A-Z]+/',
            '/[0-9]+/',
            '/[|\\!"£$%&\/()=?\'^,.;:_@°#*+[\]{}_-]+/'
        ];
        $res = 0;
        foreach ( $rr as $r ) {
            if ( preg_match( $r, $password ) ) {
                $res++;
            }
        }
        return $res >= 3;
    }
    private static function is_strong_password( $password, $len = 24 ) {
        if (!$len) {
            return false;
        }
        if ( mb_strlen( $password, 'UTF-8' ) < $len ) {return false;}
        $rr = [
            '/[a-z]+/',
            '/[A-Z]+/',
            '/[0-9]+/',
            '/[ |\\!"£$%&\/()=?\'^,.;:_@°#*+[\]{}_-]+/'
        ];
        foreach( $rr as $r ) {
            if (!preg_match($r, $password)) {
                return false;
            }
        }
        return true;
    }
    private static function sanitize_name(string $name) {
        $substitutions = [
            '/\s+,/' => ',',
            "/'+/" => "'",
            '/\'\s+/' => "' ",
            '/\s+\'+/' => ' ',
            '/,\'+/' => ',',
            '/,+/' => ', ',
            '/\s+/' => ' ',
            '/^[, \']+|[, ]+$/' => ''

        ];
        return mb_strtoupper(
            preg_replace(
                array_keys($substitutions),
                array_values($substitutions),
                $name
            ),
            'UTF-8'
        );
    }
    private static function translit_name(string $name) {
        $name = mb_strtolower($name, 'UTF-8');
        $others_chars = [
            'ä' => 'ae',
            'æ' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
        ];
        return mb_strtoupper(iconv('UTF-8','ASCII//TRANSLIT',str_replace(array_keys($others_chars), array_values($others_chars), $name)), 'UTF-8');
    }
    private static function is_valid_name($name = '') {
        if(!is_string($name) || !$name) return false;
        $name = trim(mb_strtolower($name, 'UTF-8'));
        $allowed_chars = "a-zàáâäæçčèéêëìíîïòóôöœùúûüšžß";
        if (
            !preg_match("/^[$allowed_chars][$allowed_chars\', ]*[$allowed_chars\']$/", $name)
            || mb_strlen(preg_replace("/^[^$allowed_chars]$/", '', $name),'UTF-8') < 2
            || static::sanitize_name($name) != mb_strtoupper($name, 'UTF-8')
        ) {
            return false;
        }
        return true;
    }
    private static function is_valid_phone($phone) {
        if (is_string($phone) && strlen($phone) >= 13 && strlen($phone) <= 16 && preg_match('/^\+\d+ \d+$/', $phone)) {
            return true;
        }
        return false;
    }

    private static function dashicon(string $icon = '', string $ba = 'before') {
        return '<span class="dashicons-'.$ba.' dashicons-'.$icon.'">&nbsp;</span>';
    }
    
    private static function is_plugin_active($plugin) {
        if (!function_exists('is_plugin_active')) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        return is_plugin_active($plugin);
    }

    private static function zerofill($num, $len = 8) {
        return str_pad($num,8,0,STR_PAD_LEFT);
    }

    public static function get_client_ip() {
        if (file_exists(MULTIPOP_PLUGIN_PATH . '/private/proxy')) {
            $fcontent = file_get_contents(MULTIPOP_PLUGIN_PATH . '/private/proxy');
            if ($fcontent) {
                require_once(MULTIPOP_PLUGIN_PATH . '/classes/client-ip-getter.php');
                $ip_getter = new ClientIPGetter($fcontent);
                return $ip_getter->get_client_ip();
            }
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private static function delay_script(string $script, ...$argv) {
        $test = false;
        return exec('php ' .MULTIPOP_PLUGIN_PATH . 'delayed_scripts/delayed.php ' . $script . ' ' . implode(' ', $argv) . ' '.($test? '>>'.MULTIPOP_PLUGIN_PATH . '/delay_test.log' : '>/dev/null').' 2>&1 &');
    }

    // DB PREFIX FOR PLUGIN TABLES
    private static function db_prefix( $table ) {
        global $wpdb;
        $prefix = '`' . DB_NAME . '`.`' . $wpdb->prefix . 'mpop_' . $table . '`';
        return $prefix;
    }

    private static function export_GET() {
        $res = [];
        foreach($_GET as $k => $v) {
            $res[] = "$k=" . urlencode($v);
        }
        return implode('&', $res);
    }

    function __construct() {

        $req_url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https': 'http') . "://" . ( isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '' ) . ( isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '' );
        $this->req_url = $req_url;
        $this->req_path = preg_replace('/^https?:\/\/[^\/]+/', '', $req_url);
        
        // INIT HOOK
        add_action('init', [$this, 'init']);
        // ACTIVATION HOOK
        register_activation_hook(MULTIPOP_PLUGIN_PATH . '/multipop.php', [$this, 'activate']);
        // DEACTIVATION HOOK
        register_deactivation_hook(MULTIPOP_PLUGIN_PATH . '/multipop.php', [$this, 'deactivate']);
        // TEMPLATE REDIRECT HOOK
        // add_action('template_redirect', [$this, 'template_redirect'] );
        // CONNECT wp_mail to $this->send_mail()
        add_filter('pre_wp_mail', function ($res, $atts) {
            if ($res) return $res;
            $plugin_setts = $this->get_settings();
            if (!$plugin_setts['mail_host'] || !$plugin_setts['mail_from']) {
                return false;
            }
            return $this->send_mail(['to' => $atts['to'], 'subject' => $atts['subject'], 'body' => $atts['message'], 'attachments' => $atts['attachments']]);
        }, 10, 2 );
        add_action('wp_head', [$this, 'wp_head'] );
        // `wp_loaded` HOOK
        add_action( 'wp_loaded', [$this, 'wp_loaded'] );
        // `admin_init` HOOK
        add_action('admin_init', [$this, 'admin_init']);
        // `admin_head` HOOK
        add_action('admin_head', [$this, 'admin_head']);


        // LOGIN
        // ADD ELEMENTS TO LOGIN PAGE
        // CHECK AFTER LOGIN
        //add_action('wp_login', [$this,'filter_login'], 10, 2);
        add_filter('authenticate', [$this, 'filter_login'], 21, 1);

        // SHOW USER NOTICES
        add_filter('the_content', [$this, 'show_user_notices'], 10);
        // REGISTRATION PAGE SHORTCODE
        add_shortcode('mpop_register_form', [$this, 'register_sc'] );
        // MYACCOUNT PAGE SHORTCODE
        add_shortcode('mpop_myaccount', [$this, 'myaccount_sc'] );
        // FILTER NAV MENU ITEMS AND ADD LOGOFF BUTTON
        add_filter( 'block_core_navigation_render_inner_blocks', [$this, 'filter_menu_items'], 10 );
    
        // ADD MULTIPOPOLARE DASHBOARD
        add_action('admin_menu', function() {
            add_menu_page('Multipop Plugin', 'Multipop', 'edit_private_posts', 'multipop_settings', [$this, 'menu_page'], 'dashicons-fullscreen-exit-alt', 61);
        });
        add_action('user_new_form', [$this, 'user_new_form']);

        add_action('show_user_profile', [$this, 'add_profile_meta']);
        // ADD USER META IN ADMIN EDIT USER PAGE
        add_action('edit_user_profile', [$this, 'add_user_meta']);
        // SAVE USER META IN ADMIN EDIT USER PAGE
        add_action('user_profile_update_errors', [$this, 'user_profile_update_errors'], 10, 3);
        add_action('personal_options_update', [$this, 'personal_options_update']);
        add_filter('run_wptexturize', [$this, 'run_wptexturize']);
        add_filter('https_ssl_verify', [$this,'discourse_req_ca'], 10, 2 );
        add_filter('discourse_email_verification', function() {return false;} );
        add_action('wpdc_sso_provider_before_sso_redirect', [$this, 'discourse_filter_login'], 10, 2 );
        add_filter('wpdc_sso_params', [$this, 'discourse_user_params'], 10, 2);
        add_filter('wpdc_bypass_sync_sso', [$this, 'discourse_bypass_invited_users'], 10, 2);
        $this->delayed_action = isset($GLOBALS['mpop_delayed_action']) ? $GLOBALS['mpop_delayed_action'] : false;
        $this->delayed_scripts = [
            'updateDiscourseGroupsByUser' => function($user_id) {
                sleep(10);
                $this->update_discourse_groups_by_user($user_id);
            },
            'flushSubscriptions' => function() {
                $this->flush_subscriptions();
            },
            'sendMultipleInvitation' => function($file_name) {
                $file_path = MULTIPOP_PLUGIN_PATH . '/private';
                if (file_exists($file_path . '/' . $file_name)) {
                    $file_name = $file_path . '/' . $file_name;
                    $mails = json_decode(file_get_contents($file_name), true);
                    $errors = [];
                    if ($mails && is_array($mails)) {
                        $this->get_settings();
                        foreach($mails as $m) {
                            if(!$this->send_invitation_mail($m['token'], $m['to'])) {
                                $errors[] = ['to' => $m['to'], 'error' => $this->last_mail_error];
                            }
                            sleep(5);
                        }
                    }
                    if (!empty($errors)) {
                        file_put_contents($file_name . '.error.log', json_encode($errors, JSON_PRETTY_PRINT));
                    } else {
                        unlink($file_name);
                    }
                }
            }
        ];
    }

    // INITIALIZE PLUGIN
    public function init() {
        $this->get_settings();
        $this->flush_db();
        $this->update_tempmail();
        $this->update_comuni();

        if ($this->current_user_is_admin()) {
            return;
        }

        $current_user = wp_get_current_user();

        // HIDE ADMIN BAR
        if (count($current_user->roles) == 1 && in_array($current_user->roles[0], ['multipopolano','multipopolare_resp','multipopolare_friend'])) {
            show_admin_bar(false);
        }

        // REDIRECT AFTER EMAIL CONFIRMATION
        // (IF USER CLICK ON A CONFIRMATION LINK BUT HE'S LOGGED IN WITH ANOTHER USER)
        $this->mail_token_redirect($current_user);

        // REDIRECT AFTER EMAIL INVITATION
        // (IF USER CLICK ON A INVITATION LINK BUT HE'S LOGGED IN WITH ANOTHER USER)
        $this->invite_token_redirect($current_user);
    }

    private function mail_token_redirect($current_user) {
        if (
            str_starts_with($this->req_url, get_permalink($this->settings['myaccount_page']))
            && isset($_REQUEST['mpop_mail_token'])
        ) {
            if ( !preg_match('/^[a-f0-9]{96}$/', $_REQUEST['mpop_mail_token']) ) {
                $this->location_not_found();
            }
            $user_verified = $this->verify_temp_token($_REQUEST['mpop_mail_token'], 'email_confirmation_link');
            if (!$user_verified) {
                $this->location_not_found();
            }
            $user_id = $current_user->ID;
            if ($user_id == $user_verified) {
                if ($current_user->_new_email) {
                    $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                    $duplicated = get_users([
                        'search' => $current_user->_new_email,
                        'search_columns' => ['user_email']
                    ]);
                    if (!count($duplicated)) {
                        $duplicated = get_users([
                            'meta_key' => '_new_email',
                            'meta_value' => $current_user->_new_email,
                            'meta_compare' => '=',
                            'login__not_in' => [$current_user->user_login]
                        ]);
                    }
                    if (count($duplicated)) {
                        wp_update_user([
                            'ID' => $user_id,
                            'meta_input' => [
                                '_new_email' => false
                            ]
                        ]);
                    } else {
                        wp_update_user([
                            'ID' => $user_id,
                             'user_email' => $current_user->_new_email,
                             'meta_input' => [
                                 '_new_email' => false
                             ]
                         ]);
                    }
                    wp_redirect(get_permalink($this->settings['myaccount_page']));
                    exit;
                } else {
                    $this->logout_redirect($this->req_url);
                }
            } elseif ($user_id) {
                $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                $this->location_not_found();
            }
        }
    }
    private function invite_token_redirect($current_user) {
        if (
            str_starts_with($this->req_url, get_permalink($this->settings['myaccount_page']))
            && isset($_REQUEST['mpop_invite_token'])
        ) {
            if ( !preg_match('/^[a-f0-9]{96}$/', $_REQUEST['mpop_invite_token']) ) {
                $this->location_not_found();
            }
            $invited_user_id = $this->verify_temp_token($_REQUEST['mpop_invite_token'], 'invite_link');
            if ($invited_user_id && $current_user->ID) {
                $this->delete_temp_token($_REQUEST['mpop_invite_token']);
            }
            if (!$invited_user_id || $current_user->ID) {
                $this->location_not_found();
            }
            $invited_user = get_user_by('ID', $invited_user_id);
            if (!$invited_user) {
                $this->location_not_found();
            }
            $this->invited_user = $invited_user;
            add_filter('the_title', function($title, $post_id) {
                if ($post_id == $this->settings['myaccount_page']) {
                    return "Attiva il tuo account";
                }
                return $title;
            }, 10, 2);
        }
    }

    public function admin_head() {
        // ADD CUSTOM STYLE TO profile.php
        if (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) {
            require(MULTIPOP_PLUGIN_PATH .'/pages/profile-head.php');
        }
    }

    // PLUGIN ACTIVATION TRIGGER
    public function activate() {
        $register_page = get_posts([
            'numberposts' => 1,
            'post_type' => 'page',
            'meta_key' => 'mpop_page',
            'meta_value' => 'register'
        ]);
        if (!count($register_page)) {
            $register_page = wp_insert_post([
                'post_title' => 'Registrati',
                'post_type' => 'page',
                'post_content' => '[mpop_register_form]',
                'post_status' => 'publish',
                'meta_input' => [
                    'mpop_page' => 'register'
                ]
            ]);
        } else {
            $register_page = $register_page[0]->ID;
        }

        $myaccount_page = get_posts([
            'numberposts' => 1,
            'post_type' => 'page',
            'meta_key' => 'mpop_page',
            'meta_value' => 'myaccount'
        ]);
        if (!count($myaccount_page)) {
            $myaccount_page = wp_insert_post([
                'post_title' => 'Il mio account',
                'post_type' => 'page',
                'post_content' => '[mpop_myaccount]',
                'post_status' => 'publish',
                'meta_input' => [
                    'mpop_page' => 'myaccount'
                ]
            ]);
        } else {
            $myaccount_page = $myaccount_page[0]->ID;
        }

        $this->set_db_tables($register_page, $myaccount_page);
    
        // remove_role('multipopolare_resp');
        // ADD MULTIPOPOLANO ROLE
        add_role('multipopolano', 'Multipopolano', [
            'read' => true
        ]);
        // ADD RESPONSABILE ROLE
        add_role('multipopolare_resp', 'Responsabile Multipopolare', [
            'read' => true
        ]);
        // ADD RESPONSABILE ROLE
        add_role('multipopolare_friend', 'Amico di Multipopolare', [
            'read' => true
        ]);

        // ADD DYNAMIC PAGES
        // add_rewrite_endpoint( 'card', EP_ROOT | EP_PAGES );
        // flush_rewrite_rules();
    }

    // PLUGIN DEACTIVATION HOOK
    public static function deactivate() {

    }

    public function wp_head() {?>
        <link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/main.css">
        <?php
        wp_enqueue_style('dashicons');
    }

    // `wp_loaded` TRIGGER
    public function wp_loaded() {
        if ($this->wp_yet_loaded) {
            return;
        }
        $this->wp_yet_loaded = true;
        $user_id = get_current_user_id();
        if (!$user_id) {
            // SHOW USER NOTICES
            if (isset($_REQUEST['invalid_mpop_mail_token'])) {
                $this->add_user_notice('Token di conferma non valido');
            }
            if (isset($_REQUEST['invalid_mpop_login'])) {
                $this->add_user_notice('Credenziali non valide');
            }
            if (isset($_REQUEST['mpop_mail_not_confirmed'])) {
                $this->add_user_notice("L'indirizzo e-mail non è ancora confermato. Controlla nella tua casella di posta per il link di conferma.");
            }
            if (isset($_REQUEST['mpop_sent_reset_mail'])) {
                $this->add_user_notice("Dovresti aver ricevuto un'e-mail contenente un link per resettare la password", 'info');
            }
        } else {
            if (get_the_ID() != $this->settings['myaccount_page']) {
                $mail_changing = get_user_meta($user_id, '_new_email', true);
                if ($mail_changing) {
                    $this->add_user_notice("L'indirizzo e-mail non è ancora confermato. Controlla nella tua casella di posta per il link di conferma.");
                }
                if (isset($_REQUEST['mpop_mail_not_confirmed'])) {
                    $this->add_user_notice("L'indirizzo e-mail non è ancora confermato. Controlla nella tua casella di posta per il link di conferma.");
                }
                if (isset($_REQUEST['mpop_mail_confirmed'])) {
                    $this->add_user_notice("Indirizzo e-mail confermato correttamente", 'success');
                }
            }
        }
    }
    
    // `admin_init` TRIGGER
    public function admin_init() {
        $current_user = wp_get_current_user();

        if (count($current_user->roles) == 1 && in_array($current_user->roles[0], ['multipopolano','multipopolare_resp','multipopolare_friend'])) {
            wp_redirect(site_url('/'));
            exit();
        }
        if ( $this->current_user_is_admin() && !$this->settings['master_doc_key'] ) {
            if (isset($_GET['page']) && $_GET['page'] == 'multipop_settings') {
                return add_action('mpop_settings_notices', function() {
                    if (!$this->settings['master_doc_key']) {
                        $this->add_admin_notice( 'La master key per la crittografia dei documenti non è impostata. <a href="' . $this->get_admin_url() . 'admin.php?page=multipop_settings#master_doc_key_button">Impostala</a> per ricevere proposte di iscrizione.', 'warning', false );
                    }
                });
            }
            $this->add_admin_notice( 'La master key per la crittografia dei documenti non è impostata. <a href="' . $this->get_admin_url() . 'admin.php?page=multipop_settings#master_doc_key_button">Impostala</a> per ricevere proposte di iscrizione.', 'warning', false );
        }
    }

    // CREATE DB TABLES ON INIT
    private function set_db_tables($register_page, $myaccount_page) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        global $wpdb;
        
        // SETTINGS TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('plugin_settings') . " (
            `id` INT NOT NULL,
            `tempmail_urls` TEXT NOT NULL,
            `last_tempmail_update` BIGINT UNSIGNED NOT NULL,
            `mail_host` VARCHAR(255) NOT NULL,
            `mail_port` INT UNSIGNED NOT NULL,
            `mail_encryption` VARCHAR(127) NOT NULL,
            `mail_username` VARCHAR(255) NOT NULL,
            `mail_password` VARCHAR(255) NOT NULL,
            `mail_from` VARCHAR(255) NOT NULL,
            `mail_from_name` VARCHAR(255) NOT NULL,
            `mail_general_notifications` TEXT NOT NULL,
            `authorized_subscription_years` VARCHAR(255) NOT NULL,
            `last_year_checked` INT NOT NULL,
            `min_subscription_payment` DOUBLE NOT NULL,
            `temp_token_key` VARCHAR(255) NOT NULL,
            `master_doc_key` TEXT NULL,
            `master_doc_pubkey` TEXT NULL,
            `hcaptcha_site_key` VARCHAR(255) NULL,
            `hcaptcha_secret` VARCHAR(255) NULL,
            `pp_client_id` VARCHAR(255) NULL,
            `pp_client_secret` VARCHAR(255) NULL,
            `pp_access_token` VARCHAR(255) NULL,
            `pp_token_expiration` BIGINT UNSIGNED NOT NULL,
            `pp_sandbox` TINYINT(1) NOT NULL,
            `register_page` BIGINT NOT NULL,
            `myaccount_page` BIGINT NOT NULL,
            PRIMARY KEY (`id`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";
        dbDelta( $q );

        // WEBCARD NUMBERS TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('webcard_numbers') . " (
            `year` SMALLINT UNSIGNED NOT NULL,
            `last` BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (`year`)
        )";
        dbDelta( $q );

        // TOKEN TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('temp_tokens') . " (
            `id` CHAR(32) NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `expiration` BIGINT UNSIGNED NOT NULL,
            `scope` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";
        dbDelta( $q );
    
        // SUBSCRIPTIONS TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('subscriptions') . " (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `card_number` VARCHAR(255) NULL,
            `filename` VARCHAR(255) NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `year` SMALLINT UNSIGNED NOT NULL,
            `quote` DOUBLE UNSIGNED NOT NULL,
            `marketing_agree` TINYINT UNSIGNED NOT NULL,
            `newsletter_agree` TINYINT UNSIGNED NOT NULL,
            `publish_agree` TINYINT UNSIGNED NOT NULL,
            `status` VARCHAR(255) NOT NULL,
            `created_at` BIGINT UNSIGNED NOT NULL,
            `updated_at` BIGINT UNSIGNED NOT NULL,
            `signed_at` BIGINT UNSIGNED NULL,
            `completed_at` BIGINT UNSIGNED NULL,
            `author_id` BIGINT UNSIGNED NOT NULL,
            `pp_order_id` VARCHAR(255) NULL,
            `completer_id` BIGINT UNSIGNED NULL,
            `completer_ip` VARCHAR(255) NULL,
            `notes` TEXT NULL,
            PRIMARY KEY (`id`),
            UNIQUE (`card_number`, `year`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";
        dbDelta( $q );
    
        // ADD SETTINGS ROW IF NOT EXISTS
        if (!$this->get_settings()) {
            $q = "INSERT INTO " . $this::db_prefix('plugin_settings')
                . " (
                    `id`,
                    `tempmail_urls`,
                    `last_tempmail_update`,
                    `mail_host`,
                    `mail_port`,
                    `mail_encryption`,
                    `mail_username`,
                    `mail_password`,
                    `mail_from`,
                    `mail_from_name`,
                    `mail_general_notifications`,
                    `authorized_subscription_years`,
                    `last_year_checked`,
                    `min_subscription_payment`,
                    `pp_token_expiration`,
                    `pp_sandbox`,
                    `temp_token_key`
                )"
                . " VALUES (
                    1,
                    '" . json_encode(['block' => [], 'allow' => []]) . "',
                    0,
                    '',
                    465,
                    'SMTPS',
                    '',
                    '',
                    '" . get_bloginfo('admin_email') . "',
                    '" . get_bloginfo('name') . "',
                    '" . get_bloginfo('admin_email') . "',
                    '',
                    0,
                    15,
                    0,
                    1,
                    '".base64_encode(openssl_random_pseudo_bytes(64))."'
                ) ;";
            $wpdb->query($q);
        }
        $q = "UPDATE " . $this::db_prefix('plugin_settings') . " SET `register_page` = $register_page, `myaccount_page` = $myaccount_page WHERE `id` = 1;";
        $wpdb->query($q);
        $this->get_settings();
    }

    // RETURN ECHO FROM FUNCTION NAME TO STRING
    private function html_to_string( $html_func, ...$args ) {
        ob_start();
        $html_func(...$args);
        return ob_get_clean();
    }

    // DOWLOAD FILE
    private function curl_exec($url, $settings = []) {
        $settings = $settings + [
            CURLOPT_AUTOREFERER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_TIMEOUT => 5
        ];
        $ch = curl_init($url);
        foreach($settings as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
        $data = curl_exec($ch);
        if(curl_error($ch) || empty(trim($data))) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $data;
    }

    /*
        SEND AN EMAIL
        MAIL MUST BE AN ARRAY WITH THE FOLLOWING KEYS:
            'to' or 'cc' or 'bcc' => 'email1,email2' or ['email1','email2']
            'subject' => EMAIL SUBJECT as a string
            'body' => EMAIL BODY as a string
        MAIL CAN HAVE THE FOLLOWING KEYS:
            'style' => EMAIL STYLE as a string
            'reply_to' => EMAIL REPLY TO as a string
            'from' => EMAIL FROM as a string
            'from_name' => EMAIL FROM NAME as a string
    */
    private function send_mail( $mail ) {
        if ( !is_array($mail) ) {
            return false;
        }
        ob_start();
        require( MULTIPOP_PLUGIN_PATH . '/email.php' );
        $body = ob_get_clean();
        $from_name = isset( $mail['from_name'] ) ? $mail['from_name'] : '';
        $from = isset( $mail['from'] ) ? $mail['from'] : '';
        $to = isset( $mail['to'] ) ? $mail['to'] : [];
        if ( ! is_array( $to ) ) {
            $to = explode( ',', $to );
        }
        $cc = isset( $mail['cc'] ) ? $mail['cc'] : [];
        if ( ! is_array( $cc ) ) {
            $cc = explode( ',', $cc );
        }
        $bcc = isset( $mail['bcc'] ) ? $mail['bcc'] : [];
        if ( ! is_array( $bcc ) ) {
            $bcc = explode( ',', $bcc );
        }
        $reply_to = isset( $mail['reply_to'] ) ? $mail['reply_to'] : [];
        if ( ! is_array( $reply_to ) ) {
            $reply_to = explode( ',', $reply_to );
        }
        $attachments = isset( $mail['attachments'] ) ? $mail['attachments'] : [];
        if ( ! is_array( $attachments ) ) {
            $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
        }
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        $phpmail = new PHPMailer\PHPMailer\PHPMailer( true );
        if ( empty( $from_name ) ) {
            $from_name = $this->settings['mail_from_name'];
        }
        if ( empty( $from ) ) {
            $from = $this->settings['mail_from'];
        }
        try {
            $phpmail->isSMTP();
            $phpmail->CharSet = 'UTF-8';
            $phpmail->Encoding = 'base64';
            $phpmail->Host = $this->settings['mail_host'];
            $phpmail->SMTPAuth = true;
            $phpmail->SMTPSecure = $this->settings['mail_encryption'] === 'SMTPS' ? 'ssl' : 'tls';
            $phpmail->Port = $this->settings['mail_port'];
            $phpmail->Username = $this->settings['mail_username'];
            $phpmail->Password = $this->settings['mail_password'];
            $phpmail->setFrom( $from, $from_name );
            foreach( $to as $m ) {
                $phpmail->addAddress( $m );
            }
            foreach( $cc as $m ) {
                $phpmail->addCC( $m );
            }
            foreach( $bcc as $m ) {
                $phpmail->addBCC( $m );
            }
            foreach( $reply_to as $m ) {
                $phpmail->addReplyTo( $m );
            }
            foreach( $attachments as $m ) {
                $phpmail->addAttachment( $m );
            }
            $phpmail->isHTML( true );
            $phpmail->Subject = $mail['subject'];
            $phpmail->Body = $body;
            $phpmail->send();
            return true;
        } catch (Exception $e) {
            $this->last_mail_error = $phpmail->ErrorInfo;
            return false;
        }
    }

    private function send_confirmation_mail($token, $to) {
        $confirmation_link = get_permalink($this->settings['myaccount_page']);
        return wp_mail($to,'Multipopolare - Conferma e-mail','Clicca sul link per confermare la tua e-mail: <a href="'. $confirmation_link . '?mpop_mail_token=' . $token . '" target="_blank">'. $confirmation_link . '?mpop_mail_token=' . $token . '</a>');
    }

    private function send_invitation_mail($token, $to) {
        $confirmation_link = get_permalink($this->settings['myaccount_page']);
        return wp_mail($to,'Multipopolare - Invito iscrizione','Sei stato invitato a iscriverti su Multipopolare.it. Clicca sul link per completare l\'iscrizione: <a href="'. $confirmation_link . '?mpop_invite_token=' . $token . '" target="_blank">'. $confirmation_link . '?mpop_invite_token=' . $token . '</a>');
    }

    private function get_last_webcard_number(int $year) {
        global $wpdb;
        return intval($wpdb->get_var("SELECT `last` FROM " . $this::db_prefix('webcard_numbers') . " WHERE `year` = $year LIMIT 1;"));
    }

    private function increment_last_webcard_number(int $year) {
        global $wpdb;
        $wpdb->query("UPDATE " . $this::db_prefix('webcard_numbers') . " SET `last` = `last` + 1 WHERE `year` = $year;");
    }

    // CREATE PDF
    private function pdf_create(array $pdf_config = []) {
        require_once(MULTIPOP_PLUGIN_PATH . '/classes/multipopdf.php');
        $pdf = new MultipoPDF($pdf_config+['logo'=> MULTIPOP_PLUGIN_PATH . '/logo-pdf.png']);
        require_once(MULTIPOP_PLUGIN_PATH . '/modulo-iscrizione.php');
        return $pdf->export_file();
    }
    
    // IMPORT PDF
    private function pdf_import(string $pdf_file_string = '') {
        require_once(MULTIPOP_PLUGIN_PATH . '/classes/multipopdf.php');
        $pdf = new MultipoPDF(['mpop_import' => true]);
        $fd = fopen('data://application/pdf;base64,'. base64_encode($pdf_file_string), 'r');
        $pages_count = $pdf->setSourceFile($fd);
        for ($i=1; $i<=$pages_count; $i++) {
            if ($i > 1) {
                $pdf->AddPage();
            }
            $tpl = $pdf->importPage($i);
            $pdf->useTemplate($tpl);
        }
        return $pdf;
    }
    private function pdf_compile($pdf, $options = []) {
        if (isset($options['quote']) && (is_int($options['quote']) || is_float($options['quote'])) && $options['quote'] > 0) {
            $pdf->setPage(1);
            $pdf->setY(10);
            ob_start(); ?>
            <span style="font-family: 'helveticamedium'; font-size: 12pt; line-height: 15px;">Ciao</span>
            <?php
            $pdf->writeHTML(ob_get_clean(),true, false, false, false);
        }
        return $pdf;
    }
    // private function pdf_import(string $file = '', array $options = [], string $key = '') {
    //     require_once(MULTIPOP_PLUGIN_PATH . '/classes/multipopdf.php');
    //     $pdf = new MultipoPDF(['mpop_import' => true]);
    //     $fd = $file;
    //     if (isset($options['key'])) {
    //         if (isset($options['key'], )) {
    //             if (strlen($options['key'], ) != 32) {
    //                 throw new Exception('Invalid key');
    //             }
    //             $fd = fopen('data://application/pdf;base64,'. base64_encode( $this->decrypt(file_get_contents($file), $options['key'], isset($options['mac_key']) ? $options['mac_key'] : '')), 'r');
    //         }
    //     } else if (isset($options['password'])) {
    //         $fd = fopen('data://application/pdf;base64,'. base64_encode( $this->decrypt_with_password(file_get_contents($file), $options['password'], isset($options['mac_key']) ? $options['mac_key'] : '')), 'r');
    //     }
    //     $pages_count = $pdf->setSourceFile( $fd );
    //     for ($i=1; $i<=$pages_count; $i++) {
    //         if ($i > 1) {
    //             $pdf->AddPage();
    //         }
    //         $tpl = $pdf->importPage($i);
    //         $pdf->useTemplate($tpl);
    //     }
    //     return $pdf;
    // }

    // CREATE EMAIL CONFIRMATION LINK
    private function create_temp_token( int $user_id, string $scope, int $validity_seconds = 3600 ) {
        if ($user_id <= 0) {
            throw new Exception('Invalid $user_id');
        }
        if (!$scope) {
            throw new Exception('Invalid $scope');
        }
        global $wpdb;
        $row = true;
        $token = '';
        while( $row ) {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            $q = "SELECT * FROM " . $this::db_prefix('temp_tokens') . " WHERE `id` = '$token' LIMIT 1;";
            $row = $wpdb->get_row( $q, ARRAY_A );
        }
        $q = "INSERT INTO " . $this::db_prefix('temp_tokens') . " (`id`, `user_id`, `expiration`, `scope`) VALUES ('$token', $user_id, " . (time() + $validity_seconds) . ", '$scope');";
        $wpdb->query( $q );
        return $token . hash_hmac('sha3-256', $token, $this->settings['temp_token_key']);
    }

    // DELETE EMAIL CONFIRMATION LINK
    private function delete_temp_token( $token ) {
        $token = substr($token, 0, 32);
        global $wpdb;
        $q = "DELETE FROM " . $this::db_prefix('temp_tokens') . " WHERE `id` = '$token';";
        $wpdb->query($q);
    }
    private function delete_temp_token_by_user_id($user_id, $scope = '') {
        global $wpdb;
        $q = "DELETE FROM " . $this::db_prefix('temp_tokens') . " WHERE `user_id` = $user_id" . ($scope ? " AND `scope` = '$scope'" : "") . ";";
        $wpdb->query($q);
    }

    // VERIFY EMAIL CONFIRMATION LINK AND RETURN USER ID
    private function verify_temp_token( $token, $scope, $delete = false ) {
        global $wpdb;
        if ( !preg_match( '/^[a-f0-9]{96}$/', $token ) ) {
            return false;
        }
        $signature = substr($token, 32);
        $token = substr($token, 0, 32);
        if (!hash_equals(hash_hmac('sha3-256', $token, $this->settings['temp_token_key']), $signature)) {
            return false;
        }
        $q = "SELECT * FROM " . $this::db_prefix('temp_tokens') . " WHERE `id` = '$token' AND `expiration` > " . time() . " AND `scope` = '$scope' LIMIT 1;";
        $row = $wpdb->get_row( $q, ARRAY_A );
        if ( !$row ) {
            return false;
        }
        if ( $delete ) {
           $this->delete_temp_token( $token );
        }
        return intval($row['user_id']);
    }

    // LOAD SETTINGS FROM DB AND SET THEM IN GLOBALS
    private function get_settings() {
        global $wpdb;
        $q = "SHOW COLUMNS FROM " . $this::db_prefix('plugin_settings') . ";";
        $column_names = implode(',', array_map( function($c) {return "`$c`";}, array_filter( $wpdb->get_col($q), function($c) {return $c !== 'master_doc_key';} ) ));
        $columns = $column_names . ',`master_doc_key` IS NOT NULL as `master_doc_key`';
        $q = "SELECT $columns FROM " . $this::db_prefix('plugin_settings') . " WHERE `id` = 1;";
        $settings = $wpdb->get_row($q, ARRAY_A);
        if (!$settings) {
            return;
        }
        if ($settings['authorized_subscription_years']) {
            $this_year = intval(current_time('Y'));
            $settings['authorized_subscription_years'] = array_values(array_filter(array_map(function($v) {return intval($v);},explode(',', $settings['authorized_subscription_years'])), function($v) use ($this_year) {return $v >= $this_year;}));
        } else {
            $settings['authorized_subscription_years'] = [];
        }
        $settings['min_subscription_payment'] = (double) $settings['min_subscription_payment'];
        $settings['pp_token_expiration'] = intval($settings['pp_token_expiration']);
        $settings['pp_sandbox'] = intval($settings['pp_sandbox']);
        $settings['pp_url'] = 'https://api-m.'. ($settings['pp_sandbox'] ? 'sandbox.' : '') .'paypal.com';
        $settings['tempmail_urls'] = json_decode($settings['tempmail_urls'], true);
        $settings['last_year_checked'] = intval($settings['last_year_checked']);
        $settings['last_tempmail_update'] = intval($settings['last_tempmail_update']);
        $settings['mail_port'] = intval($settings['mail_port']);
        $settings['master_doc_key'] = boolval($settings['master_doc_key']);
        $settings['register_page'] = intval($settings['register_page']);
        $settings['myaccount_page'] = intval($settings['myaccount_page']);
        $settings['temp_token_key'] = base64_decode($settings['temp_token_key'], true);
        $this->settings = $settings;
        return $this->settings;
    }

    // GET MASTER KEY FROM DB
    private function get_master_key() {
        global $wpdb;
        return $wpdb->get_var("SELECT `master_doc_key` FROM " .  $this::db_prefix('plugin_settings') . " WHERE `id` = 1;");
    }

    // FLUSH DB EMAIL CONFIRMATION LINK
    private function flush_db() {
        global $wpdb;
        $q = "DELETE FROM " . $this::db_prefix('temp_tokens') . " WHERE expiration <= " . time() . ";";
        $wpdb->query($q);
        if (!$this->delayed_action) {
            $this->delay_script('flushSubscriptions');
        }
    }

    // UPDATE TEMPMAIL LIST
    private function update_tempmail($force = false) {
        if (isset( $this->settings ) && is_array( $this->settings )) {
            $last_update = date_create();
            $last_update->setTimestamp($this->settings['last_tempmail_update']);
            $last_update->add(new DateInterval('P30D'));
            if ($force || $last_update->getTimestamp() <= time()) {
                $old_list = file_exists(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt') ? file_get_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt') : "";
                $block_list = $old_list !== false ? preg_split('/\r\n|\r|\n/', trim($old_list)) : [];
                foreach($this->settings['tempmail_urls']['block'] as $l ) {
                    $res = $this->curl_exec($l);
                    if (!$res) continue;
                    $res = preg_split('/\r\n|\r|\n/', trim($res));
                    foreach($res as $i=>$b) {
                        $res[$i] = mb_strtolower(trim($b), 'UTF-8');
                    }
                    $block_list = array_unique(array_merge($block_list, array_filter($res, function($e) {return $e && !str_starts_with($e, '#');})));
                }
                $block_list = array_values( $block_list );
                foreach($this->settings['tempmail_urls']['allow'] as $l) {
                    $res = $this->curl_exec($l);
                    if (!$res) continue;
                    $res = preg_split('/\r\n|\r|\n/', trim($res));
                    foreach($res as $a) {
                        if (!trim($a) || str_starts_with(trim($a), '#')) {
                            continue;
                        }
                        $i = array_search( mb_strtolower(trim($a), 'UTF-8'), $block_list );
                        if ($i !== false) {
                            unset($block_list[$i]);
                        }
                    }
                }
                $block_list = array_values( $block_list );
                file_put_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt', implode( "\n", $block_list) );
                global $wpdb;
                $q = "UPDATE " . $this::db_prefix('plugin_settings') . " SET `last_tempmail_update` = " . time() . " WHERE `id` = 1 ;";
                $wpdb->query($q);
            }
        }
    }

    private function flush_subscriptions() {
        if ( isset( $this->settings ) && is_array( $this->settings )) {
            $this_year = intval(current_time('Y'));
            if ( $this_year > $this->settings['last_year_checked'] ) {
                global $wpdb;
                $users_to_enable = $wpdb->get_results("SELECT `user_id`, `marketing_agree`, `newsletter_agree`, `publish_agree` FROM " . $this::db_prefix('subscriptions') . " WHERE `status` = 'completed' AND `year` = $this_year;", 'ARRAY_A');
                $users_enabled = [];
                foreach($users_to_enable as $u) {
                    $this->enable_user_card($u['user_id'], $u['marketing_agree'], $u['newsletter_agree'], $u['publish_agree']);
                    $users_enabled[] = $u['user_id'];
                }
                $users_to_disable = $wpdb->get_col(
                    "SELECT `user_id` FROM " . $this::db_prefix('subscriptions') . " WHERE `status` = 'completed' AND `year` < $this_year" . (!empty($users_enabled) ? " AND `user_id` NOT IN ( " . implode(',' ,$users_enabled) . " )" : '') . ";"
                );
                foreach ($users_to_disable as $u) {
                    $this->disable_user_card($u);
                }
                $wpdb->query("UPDATE " . $this::db_prefix('subscriptions') . " SET `status` = 'canceled' WHERE `year` < $this_year AND `status` IN ('tosee','seen');");

                // SET NEW YEAR
                $wpdb->query("UPDATE " . $this::db_prefix('plugin_settings') . " SET `last_year_checked` = $this_year WHERE `id` = 1 ;");

                // CHECK FOR CURRENT USER
                $cu = wp_get_current_user();
                if ($cu && $cu->ID && in_array(strval($cu->ID), $users_to_disable)) {
                    $id = $cu->ID;
                    wp_set_current_user(0);
                    wp_set_current_user($id);
                }
            }
        }
    }

    private function enable_user_card($user, $marketing_agree = false, $newsletter_agree = false, $publish_agree = false) {
        if (is_string($user) || is_int($user)) {
            $user = get_user_by('ID', intval($user));
        }
        if (!$user) {
            return false;
        }
        $marketing_agree = boolval($marketing_agree);
        $newsletter_agree = boolval($newsletter_agree);
        $publish_agree = boolval($publish_agree);
        if (
            !$user->mpop_card_active
            || boolval($user->mpop_marketing_agree) != $marketing_agree
            || boolval($user->mpop_newsletter_agree) != $newsletter_agree
            || boolval($user->mpop_publish_agree) != $publish_agree
        ) {
            wp_update_user([
                'ID' => $user->ID,
                'meta_input' => [
                    'mpop_card_active' => true,
                    'mpop_marketing_agree' => $marketing_agree,
                    'mpop_newsletter_agree' => $newsletter_agree,
                    'mpop_publish_agree' => $publish_agree
                ]
            ]);
            if ($user->discourse_sso_user_id && isset($user->roles[0]) && in_array($user->roles[0], ['multipopolano', 'multipopolare_resp'])) {
                $this->sync_discourse_record($user);
            }
        }
    }

    private function disable_user_card($user) {
        if (is_string($user) || is_int($user)) {
            $user = get_user_by('ID', intval($user));
        }
        if (!$user) {
            return false;
        }
        if ($user->mpop_card_active) {
            update_user_meta($user->ID, 'mpop_card_active', false);
            if ($user->discourse_sso_user_id && isset($user->roles[0]) && in_array($user->roles[0], ['multipopolano', 'multipopolare_resp'])) {
                $this->sync_discourse_record($user);
            }
        }
    }

    private function last_comuni_update() {
        $last_file_name = explode(',', file_get_contents(MULTIPOP_PLUGIN_PATH . 'comuni/bk/last-cycle.txt'))[0];
        return date_create_from_format('YmdHis', explode('.',explode('-', $last_file_name)[2])[0], wp_timezone() );
    }

    private function update_comuni($force = false) {
        if (!$force) {
            $last_update = $this->last_comuni_update();
            $last_update->add(new DateInterval('P30D'));
            if (time() < $last_update->getTimestamp()) {
                return;
            }
        }
        exec(MULTIPOP_PLUGIN_PATH . 'comuni/comuni-update.sh --skip-on-error=attivi,soppressi,multicap --flush=2 > /dev/null &');
    }

    // LOGOUT AND REDIRECT TO URL OR HOME
    private function logout_redirect($url = null) {
        if (!isset($url)) {
            $url = home_url();
        }
        if (get_current_user_id()) {
            wp_logout();
        }
        wp_redirect( $url );
        exit;
    }

    // REDIRECT TO 404
    private function location_not_found() {
        header('Location: /404');
        exit();
    }

    // ADD ADMIN NOTICE IN DASHBOARD
    private function add_admin_notice( $msg, $type='error', $dimissible = true ) {
        if (!$this->current_user_is_admin() ) {
            return;
        }
        $cb = function() use ($msg, $type, $dimissible) { ?>
                <div class="notice notice-<?=$type . ($dimissible ? ' is-dismissible' : '')?>">
                    <p><strong>Multipop:</strong>&nbsp;<?=$msg?></p>
                </div>
            <?php
        };
        if (!did_action('all_admin_notices') ) {
            add_action('all_admin_notices', $cb);
        } else {
            ($cb)();
        }
    }

    // ADD USER NOTICE
    private function add_user_notice($msg, $type = 'error') {
        $this->user_notices[] = '<div class="mpop-notice notice-'.$type.'"><p>'.$msg.'</p></div>';
    }

    public function show_user_notices($content) {
        if (in_the_loop()) {
            $content = implode('',$this->user_notices) . $content;
        }
        return $content;
    }

    // RETUTN MY ACCOUNT PAGE SLUG
    private function my_account_page_slug() {
        $my_account_addr_arr = explode('/',preg_replace('/https?:\/\//', '', get_permalink($this->settings['myaccount_page'])));
        $my_account_addr_arr = array_values(array_filter($my_account_addr_arr, function($e) {return !empty(trim($e));}));
        unset($my_account_addr_arr[0]);
        return implode('/', $my_account_addr_arr);
    }


    // LOGIN

    // ADD ELEMENTS TO LOGIN PAGE
    public function html_added() {
        if (isset($_REQUEST['mpop_mail_token']) && preg_match('/^[a-f0-9]{96}$/', $_REQUEST['mpop_mail_token'])) { ?>
            <p>Inserisci le credenziali per confermare l'indirizzo e-mail</p>
            <?php
        } else if (isset($_REQUEST['mpop_invite_token']) && preg_match('/^[a-f0-9]{96}$/', $_REQUEST['mpop_invite_token'])) { ?>
            <p>Inserisci le informazioni richieste per completare l'attivazione</p>
            <?php
        }
    }

    // CHECK AFTER LOGIN
    public function filter_login( $user ) {
        if (is_null($user) || is_wp_error($user)) {
            return $user;
        }
        if ($user->mpop_invited) return new WP_Error(401, "Inactive user's invitation");
        $roles = $user->roles;
        if (count( $roles ) == 0) {
            return new WP_Error(401, "No roles found");
        } else if (count( $roles ) == 1 && in_array($roles[0], ['multipopolano','multipopolare_resp','multipopolare_friend'])) {
            if ($user->mpop_mail_to_confirm || $user->_new_email) {
                if (
                    isset($_REQUEST['mpop_mail_token'])
                    && preg_match('/^[a-f0-9]{32}$/', $_REQUEST['mpop_mail_token'])
                ) {
                    $user_id = $this->verify_temp_token($_REQUEST['mpop_mail_token'], 'email_confirmation_link');
                    if ($user_id == $user->ID) {
                        $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                        if ($user->_new_email) {
                            $duplicated = get_users([
                                'search' => $user->_new_email,
                                'search_columns' => ['user_email']
                            ]);
                            if (!count($duplicated)) {
                                $duplicated = get_users([
                                    'meta_key' => '_new_email',
                                    'meta_value' => $user->_new_email,
                                    'meta_compare' => '=',
                                    'login__not_in' => [$user->user_login]
                                ]);
                            }
                            if (count($duplicated)) {
                                wp_update_user([
                                    'ID' => $user->ID,
                                    'meta_input' => [
                                        '_new_email' => false
                                    ]
                                ]);
                            } else {
                                wp_update_user([
                                    'ID' => $user->ID,
                                    'user_email' => $user->_new_email,
                                    'meta_input' => [
                                        '_new_email' => false
                                    ]
                                ]);
                            }
                        } else {
                            wp_update_user([
                                'ID' => $user->ID,
                                'meta_input' => [
                                    'mpop_mail_to_confirm' => false
                                ]
                            ]);
                        }
                        return $user;
                    } else {
                        if ($user_id) {
                            $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                        }
                        $this->logout_redirect(get_permalink($this->settings['myaccount_page']) . '?invalid_mpop_mail_token=1');
                    }
                } else if ($user->mpop_mail_to_confirm) {
                    $this->logout_redirect(get_permalink($this->settings['myaccount_page']) . '?mpop_mail_not_confirmed=1');
                }
            }
        }
        return $user;
    }


    private function render_blocks($html_blocks) {
        $html = '';
        $blocks = parse_blocks($html_blocks);
        foreach($blocks as $b) {
            $html .= render_block($b);
        }
        return $html;
    }

    // REGISTRATION
    
    // REGISTRATION PAGE HTML
    private function register_form() {
        require( MULTIPOP_PLUGIN_PATH . '/shortcodes/register.php' );
    }
    
    // REGISTRATION PAGE SHORTCODE
    public function register_sc() {
        return $this->html_to_string( [$this, 'register_form'] );
    }

    // MYACCOUNT PAGE HTML
    private function myaccount_page() {
        require( MULTIPOP_PLUGIN_PATH . '/shortcodes/myaccount.php' );
    }

    // MYACCOUNT PAGE SHORTCODE
    public function myaccount_sc() {
        return $this->html_to_string( [$this, 'myaccount_page'] );
    }



    // DASHBOARD
    
    // PLUGIN SETTINGS PAGE
    public function menu_page() { 
        require(MULTIPOP_PLUGIN_PATH . '/pages/settings.php');
    }

    //
    public function user_new_form($ctx) {
        if ($ctx != 'add-new-user') {
            return;
        } ?>
        <style type="text/css">
            #first_name,
            #last_name {
                text-transform: uppercase
            }
        </style>
        <script type="text/javascript" src="<?=plugins_url()?>/multipop/js/user-new.js"></script>
        <?php
    }

    public function add_profile_meta( $user ) {
        require(MULTIPOP_PLUGIN_PATH . '/pages/profile.php');
    }
    // ADD USER META IN ADMIN EDIT USER PAGE
    public function add_user_meta( $user ) {
        require(MULTIPOP_PLUGIN_PATH . '/pages/user-edit.php');
    }

    // CHECK FIELDS WHEN USER IS EDITED BY DASHBOARD
    public function user_profile_update_errors(&$errors, $update, &$user) {
        require(MULTIPOP_PLUGIN_PATH . '/pages/post/user-edit.php');
    }

    public function personal_options_update() {
        define('MPOP_PERSONAL_UPDATE', true);
    }

    // PERSONAL CARD PAGE CONTENT
    public function my_account_card_html() {
        ?>
        <?php
    }
    
    // CHECK IF USER CARD IS ACTIVE
    private function is_card_active($user_id) {
        if (!isset($user_id)) {
            $user_id = get_current_user_id();
        } else if (is_string($user_id)) {
            $user_id = intval($user_id);
        } else if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        if (!is_int($user_id) || $user_id <= 0) {
            return false;
        }
        return (bool) get_user_meta($user_id, 'mpop_card_active', true);
    }

    private function generate_asym_keys($priv_key_len = 8192) {
        $keys = openssl_pkey_new(['private_key_bits'=> $priv_key_len, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $public_key_pem = base64_decode(str_replace(array("-----BEGIN PUBLIC KEY-----","-----END PUBLIC KEY-----","\r\n", "\n", "\r"), '', openssl_pkey_get_details($keys)['key']));
        openssl_pkey_export($keys, $private_key_pem);
        $private_key_pem = base64_decode( str_replace(array("-----BEGIN PRIVATE KEY-----","-----END PRIVATE KEY-----","\r\n", "\n", "\r"), '', $private_key_pem) );
        return ['priv' => $private_key_pem, 'pub' => $public_key_pem];
    }

    // ENCRYPTION

    // ENCRYPT DATA WITH 32 BYTE KEY AND ADDITIONAL MAC SIGNATURE
    private function encrypt(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $key = '',
        #[\SensitiveParameter]
            string $mac_key = '',
        #[\SensitiveParameter]
            string $iv = ''
    ) {
        if (empty($iv)) {
            $iv = openssl_random_pseudo_bytes(16);
        } else if (strlen($iv) != 16) {
            throw new Exception('Invalid IV');
        }
        $enc_data = $iv . openssl_encrypt(
            $data,
            'aes-256-ctr',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if (!empty($mac_key)) {
            $enc_data .= hash_hmac('sha3-512', $enc_data, $mac_key, true);
        }
        return $enc_data;
    }
    
    // ENCRYPT DATA WITH SALTED PASSWORD AND ADDITIONAL MAC SIGNATURE
    private function encrypt_with_password(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $password = '',
        #[\SensitiveParameter]
            string $mac_key = '',
        #[\SensitiveParameter]
            string $iv = '',
        int $iterations = 900000,
        int $salt_length = 32
    ) {
        $salt = openssl_random_pseudo_bytes($salt_length);
        return $iterations . '#' . $salt . $this->encrypt(
            $data,
            hash_pbkdf2( 'sha3-512', $password, $salt, $iterations, 32, true ),
            $mac_key,
            $iv
        );
    }

    // ENCRYPT DATA WITH ASYMMETRIC PUBLIC KEY
    private function encrypt_asym(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $pub_key = ''
    ) {
        $symkey = openssl_random_pseudo_bytes(32);
        $pub_key = openssl_pkey_get_public( "-----BEGIN PUBLIC KEY-----\n". base64_encode($pub_key) . "\n-----END PUBLIC KEY-----");
        openssl_public_encrypt($symkey, $enc_key, $pub_key, OPENSSL_PKCS1_OAEP_PADDING);
        return strlen($enc_key) . '#' . $enc_key . $this->encrypt($data,$symkey);
    }
    
    
    // DECRYPTION

    // DECRYPT DATA WITH 32 BYTE KEY AND ADDITIONAL MAC VERIFICATION
    private function decrypt(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $key = '',
        #[\SensitiveParameter]
            string $mac_key = '',
    ) {
        $mac_length = empty($mac_key) ? 0 : 64;
        if (strlen($data) < 16 + $mac_length) {
            throw new Exception('Invalid data');
        }
        if ($mac_length) {
            $mac = substr($data, -64);
            $data = substr($data, 0, -64);
            if ($mac !== hash_hmac('sha3-512', $data, $mac_key, true)) {
                throw new Exception('Invalid MAC');
            }
        }
        return openssl_decrypt(
            substr($data, 16),
            'aes-256-ctr',
            $key,
            OPENSSL_RAW_DATA,
            substr($data, 0, 16)
        );
    }
    
    // DECRYPT DATA WITH SALTED PASSWORD AND ADDITIONAL MAC VERIFICATION
    private function decrypt_with_password(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $password = '',
        #[\SensitiveParameter]
            string $mac_key = '',
        int $salt_length = 32
    ) {
        $splitter_pos = strpos($data, '#');
        if ($splitter_pos === false) {
            throw new Exception('Invalid data');
        }
        $iterations = intval(substr($data, 0, $splitter_pos));
        if ($iterations <=0 ) {
            throw new Exception('Invalid data');
        }
        $data = substr($data, $splitter_pos+1);
        $mac_length = empty($mac_key) ? 0 : 64;
        if (strlen($data) < 16 + $salt_length + $mac_length) {
            throw new Exception('Invalid data');
        }
        $salt = substr($data, 0, $salt_length);
        $data = substr($data, $salt_length);
        return $this->decrypt(
            $data,
            hash_pbkdf2('sha3-512', $password, $salt, $iterations, 32, true),
            $mac_key
        );
    }

    // DECRYPT DATA WITH ASYMMETRIC PRIVATE KEY
    private function decrypt_asym(
        #[\SensitiveParameter]
            string $data = '',
        #[\SensitiveParameter]
            string $priv_key = ''
    ) {
        $priv_key = openssl_pkey_get_private( "-----BEGIN PRIVATE KEY-----\n". base64_encode($priv_key) . "\n-----END PRIVATE KEY-----");
        $splitter_pos = strpos($data, '#');
        $symkey_len = intval(substr($data,0,$splitter_pos));
        $enc_symkey = substr($data, $splitter_pos +1, $symkey_len);
        openssl_private_decrypt($enc_symkey, $symkey, $priv_key, OPENSSL_PKCS1_OAEP_PADDING);
        $data = substr($data, $splitter_pos +1 + $symkey_len);
        return $this->decrypt($data, $symkey);
    }


    // CHECK IF CURRENT USER IS ADMIN
    private function current_user_is_admin() {
        $current_user = wp_get_current_user();
        if ( in_array('administrator', $current_user->roles) ){
            return $current_user->ID;
        }
        return false;
    }

    private function user_has_master_key($user_id = null) {
        $user_id = is_int($user_id) ? $user_id : get_current_user_id();
        return $user_id && get_user_meta($user_id, 'mpop_personal_master_key', true);
    }

    // GET LOCAL ADMIN URL (ex: /wp-admin/)
    private function get_admin_url() {
        return preg_replace('/^https?:\/\/[^\/]+/', '', get_admin_url());
    }

    private function count_valid_master_keys() {
        return count(get_users([
            'role__in' => ['administrator', 'multipopolare_resp'],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => '_new_email',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_new_email',
                        'value' => '',
                        'compare' => '='
                    ]
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'mpop_mail_to_confirm',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => 'mpop_mail_to_confirm',
                        'value' => '',
                        'compare' => '='
                    ]
                ],
                [
                    'key' => 'mpop_personal_master_key',
                    'compare' => 'EXISTS'
                ],
                [
                    'key' => 'mpop_personal_master_key',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]));
    }
    public function filter_menu_items($sorted_menu_items) {
        $new_items = [];
        foreach($sorted_menu_items as $item) {
            $new_items[] = $item;
        }
        if (get_current_user_id()) {
            $new_items[] = new WP_Block(parse_blocks('<!-- wp:loginout /-->')[0]);
        } else {
            $rp = get_post($this->settings['register_page']);
            $new_items[] = new WP_Block(parse_blocks('<!-- wp:navigation-link {"label":"'.$rp->post_title.'","type":"page","id":'.$rp->ID.',"url":"/'.$rp->post_name.'","kind":"post-type"} /-->')[0]);
        }
        return new WP_Block_List( $new_items );
    }
    private function show_hcaptcha_script() { ?>
        <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
        <script type="text/javascript">
            function hcaptchaCallback(token) {
                document.querySelector("input[name=hcaptcha-response]").value = token;
            }
        </script>
        <?php
    }
    private function create_hcaptcha() {
        if ($this->settings['hcaptcha_site_key'] ) {
            return '<div class="h-captcha" data-sitekey="'.$this->settings['hcaptcha_site_key'].'" data-callback="hcaptchaCallback"></div><input type="hidden" name="hcaptcha-response" />';
        }
        return '';
    }
    private function verify_hcaptcha($response) {
        if (!$this->settings['hcaptcha_site_key'] ) {return true;}
        if (!$this->settings['hcaptcha_secret'] ) {return false;}
        $res = $this->curl_exec('https://hcaptcha.com/siteverify', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'secret='.$this->settings['hcaptcha_secret'] . '&site_key=' . $this->settings['hcaptcha_site_key']. '&response=' . $response
        ]);
        if (!$res) {return false;}
        $res = json_decode($res, true);
        return $res['success'];
    }
    public function run_wptexturize($run_texturize) {
        if (get_the_ID() == $this->settings['myaccount_page']) {
            return false;
        }
        return $run_texturize;
    }
    private function get_comuni_all() {
        if (!isset($this->comuni_all)) {
            $comuni = json_decode(file_get_contents(MULTIPOP_PLUGIN_PATH . '/comuni/comuni.json'), true);
            if (!is_array($comuni)) {
                $comuni = [];
            }
            $this->comuni_all = $comuni;
        } 
        return $this->comuni_all;
    }
    private function get_province_all() {
        if (!isset($this->province_all)) {
            $province = json_decode(file_get_contents(MULTIPOP_PLUGIN_PATH . '/comuni/province.json'), true);
            if (!is_array($province)) {
                $province = [];
            }
            $this->province_all = $province;
        } 
        return $this->province_all;
    }
    private function get_regioni_all() {
        if (!isset($this->regioni_all)) {
            $regioni = [];
            $province = $this->get_province_all();
            foreach($province as $p) {
                if (!$p['soppressa']) {
                    if (!isset($regioni[$p['regione']])) {
                        $regioni[$p['regione']] = [];
                    }
                    $regioni[$p['regione']][] = $p;
                }
            }
            $this->regioni_all = $regioni;
        }
        return $this->regioni_all;
    }
    private function get_countries_all() {
        if (!isset($this->countries_all)) {
            $countries = json_decode(file_get_contents(MULTIPOP_PLUGIN_PATH . '/comuni/countries.json'), true);
            if (!is_array($countries)) {
                $countries = [];
            }
            $this->countries_all = $countries;
        } 
        return $this->countries_all;
    }
    private function add_birthplace_labels(...$comuni) {
        foreach($comuni as $i=>$c) {
            $comuni[$i]['untouched_label'] = mb_strtoupper($c['nome'], 'UTF-8') . ' (' . $c['provincia']['sigla'] . ') - ' . $c['codiceCatastale'];
            $comuni[$i]['label'] = iconv('UTF-8','ASCII//TRANSLIT', $comuni[$i]['untouched_label']);
        }
        return $comuni;
    }

    private function add_billing_city_labels(...$comuni) {
        foreach($comuni as $i=>$c) {
            $comuni[$i]['label'] = iconv('UTF-8','ASCII//TRANSLIT',  mb_strtoupper($c['nome'], 'UTF-8') . ' (' . $c['provincia']['sigla'] . ')');
        }
        return $comuni;
    }
    private function search_zones($search = '', $soppressi = false) {
        $zones = [];
        if (!is_string($search) || mb_strlen(trim($search), 'UTF-8') < 2) {
            return $zones;
        } else {
            $search = trim(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $search, 'UTF-8' )));
        }
        $regioni = [];
        $province_all = $this->get_province_all();
        foreach($province_all as $p) {
            if (!$soppressi && $p['soppressa']) {
                continue;
            }
            $pp = $p + [
                'type' => 'provincia',
                'untouched_label' => 'Provincia: ' . mb_strtoupper($p['nome'], 'UTF-8')
            ];
            $pp['label'] = iconv('UTF-8','ASCII//TRANSLIT', $pp['untouched_label']);
            $pp['comuni'] = [];
            if (
                strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $p['nome'], 'UTF-8' )),$search) !== false
                || strpos($p['sigla'], $search) !== false
            ) {
                $zones[$p['sigla']] = $pp;
            }
            if (strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($p['regione'], 'UTF-8')), $search) !== false) {
                if (!isset($regioni[$p['regione']])) {
                    $regioni[$p['regione']] = [];
                }
                $regioni[$p['regione']][$pp['sigla']] = [];
            }
        }
        foreach($regioni as $n =>$r) {
            $zone = [
              'nome' => $n,
              'type' => 'regione',
              'untouched_label' => 'Regione: ' . mb_strtoupper($n, 'UTF-8'),
              'province' => $r
            ];
            $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT', $zone['untouched_label']);
            $zones[$n] = $zone;
        }
        $comuni_all = $this->get_comuni_all();
        foreach($comuni_all as $c) {
            if (!$soppressi && $c['soppresso']) {
                continue;
            }
            $zone = $c;
            $zone['type'] = 'comune';
            $zone['untouched_label'] = 'Comune: ' . mb_strtoupper($c['nome'], 'UTF-8');
            $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT', $zone['untouched_label']);
            $zone['untouched_label'] .= ' (' . $c['provincia']['sigla'] . ')';
            if (isset($zones[$c['provincia']['sigla']])) {
                $zones[$c['provincia']['sigla']]['comuni'][] = $c['codiceCatastale'];
            }
            if ( isset($zones[$c['provincia']['regione']])) {
                $zones[$c['provincia']['regione']]['province'][$c['provincia']['sigla']][] = $c['codiceCatastale'];
            }
            if (
                strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($c['nome'], 'UTF-8')), $search) !== false
            ) {
                $zones[] = $zone;
            }
        }
        $zones = array_values($zones);
        $cmp_comuni = function ($a, $b) {
            if ($b['type'] == 'comune') {
                if ($a['provincia']['regione'] == $b['provincia']['regione']) {
                    if ($a['provincia']['nome'] == $b['provincia']['nome']) {
                        if ($a['nome'] == $b['nome']) {
                            return 0;
                        }
                        return $a['nome'] < $b['nome'] ? -1 : 1;
                    }
                    return (mb_strtoupper( $a['provincia']['nome'], 'UTF-8') < mb_strtoupper( $b['provincia']['nome'], 'UTF-8') ) ? -1 : 1;
                }
                return mb_strtoupper( $a['provincia']['regione'], 'UTF-8') < mb_strtoupper( $b['provincia']['regione'], 'UTF-8') ? -1 : 1;
            }
            if ($b['type'] == 'provincia')  {
                if ($a['provincia']['nome'] == $b['nome']) {
                    return 1;
                }
                return mb_strtoupper( $a['provincia']['nome'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
            }
            if ($a['provincia']['regione'] == $b['nome']) {
                return 1;
            }
            return mb_strtoupper( $a['provincia']['regione'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
        };
        $cmp_province = function ($a, $b) {
            if ($b['type'] == 'provincia') {
                return mb_strtoupper( $a['nome'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
            }
            if ($a['regione'] == $b['nome']) {
                return 1;
            }
            return mb_strtoupper( $a['regione'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
        };
        usort($zones, function($a, $b) use ($cmp_comuni, $cmp_province) {
            if ($a['type'] == 'comune') {
                return $cmp_comuni($a, $b);
            } else if ($b['type'] == 'comune') {
                return -($cmp_comuni($b, $a));
            } else if ($a['type'] == 'provincia') {
                return $cmp_province($a, $b);
            } else if ($b['type'] == 'provincia') {
                return -($cmp_province($b, $a));
            } else {
                return mb_strtoupper( $a['nome'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
            }
        });
        return $zones;
    }
    private function retrieve_zones_from_resp_zones($resp_zones) {
        $zones = [];
        $regioni_all = false;
        $province_all = false;
        $comuni_all = false;
        foreach($resp_zones as $resp_zone) {
            if(str_starts_with($resp_zone, 'reg_')) {
                if (!$regioni_all) {
                    $regioni_all = $this->get_regioni_all();
                }
                $reg_fullname = substr($resp_zone, 4);
                if (isset($regioni_all[$reg_fullname])) {
                    $province = $regioni_all[$reg_fullname];
                    $zone = [
                        'nome' => $reg_fullname,
                        'type' => 'regione',
                        'untouched_label' => 'Regione: ' . mb_strtoupper($reg_fullname, 'UTF-8'),
                        'province' => $province
                    ];
                    $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT', $zone['untouched_label']);
                    $zones[] = $zone;
                }
                
            } else if (preg_match('/^[A-Z]{2}$/', $resp_zone)) {
                if (!$province_all) {
                    $province_all = $this->get_province_all();
                }
                $found = array_filter($province_all, function($p) use ($resp_zone) { return $p['sigla'] == $resp_zone; });
                $found = array_pop($found);
                if ($found) {
                    $zone = $found +[
                        'type' => 'provincia',
                        'untouched_label' => 'Provincia: ' . mb_strtoupper($found['nome'], 'UTF-8')
                    ];
                    $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT', $zone['untouched_label']);
                    $zones[] = $zone;
                }
            } else if (preg_match('/^[A-Z]\d{3}$/', $resp_zone)) {
                if (!$comuni_all) {
                    $comuni_all = $this->get_comuni_all();
                }
                $found = array_filter($comuni_all, function($c) use ($resp_zone) { return $c['codiceCatastale'] == $resp_zone; });
                $found = array_pop($found);
                if ($found) {
                    $zone = $found +[
                        'type' => 'comune',
                        'untouched_label' => 'Comune: ' . mb_strtoupper($found['nome'], 'UTF-8')
                    ];
                    $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT', $zone['untouched_label']);
                    $zone['untouched_label'] .= ' (' . $found['provincia']['sigla'] . ')';
                    $zones[] = $zone;
                }
            }
        }
        return $zones;
    }
    private function reduce_zones(array $zones = []) {
        $res = [];
        usort($zones, function($a, $b) {
            if ($a['type'] == $b['type']) {
                return 0;
            }
            if ($a['type'] == 'regione') {
                return -1;
            }
            if ($b['type'] == 'regione') {
                return 1;
            }
            if ($a['type'] == 'provincia') {
                return -1;
            }
            return 1;
        });
        foreach($zones as $zone) {
            if ($zone['type'] == 'regione') {
                if (!isset($res[$zone['nome']])) {
                    $res[$zone['nome']] = $zone;
                }
            }
            if ($zone['type'] == 'provincia') {
                $found = array_filter($res, function($z) use ($zone) {
                    if (
                        ($z['type'] == 'regione' && $z['nome'] == $zone['regione'])
                        || ($z['type'] == 'provincia' && $z['sigla'] == $zone['sigla'])
                    ) { return true;}
                    return false;
                });
                if (!array_pop($found)) {
                    $res[] = $zone;
                }
            }
            if ($zone['type'] == 'comune') {
                $found = array_filter($res, function($z) use ($zone) {
                    if (
                        ($z['type'] == 'regione' && $z['nome'] == $zone['provincia']['regione'])
                        || ($z['type'] == 'provincia' && $z['sigla'] == $zone['provincia']['sigla'])
                        || ($z['type'] == 'comune' && $z['codiceCatastale'] == $zone['codiceCatastale'])
                    ) {return true;}
                    return false;
                });
                if (!array_pop($found)) {
                    $res[] = $zone;
                }
            }
        }
        return $res;
    }
    private function myaccount_get_profile($user, $add_labels = false, $retrieve_resp_zones = false) {
        if (!$user) {
            return false;
        }
        if (is_int($user) || is_string($user)) {
            $user = get_user_by('ID', intval($user));
        }
        if (!$user) {
            return false;
        }
        $parsed_user = [
            'ID' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'registered' => $user->user_registered,
            'role' => isset($user->roles[0]) ? $user->roles[0] : '',
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            '_new_email' => $user->_new_email ? $user->_new_email : false,
            'mpop_mail_to_confirm' => boolval( $user->mpop_mail_to_confirm ),
            'mpop_card_active' => boolval($user->mpop_card_active ),
            'mpop_birthdate' => $user->mpop_birthdate,
            'mpop_birthplace_country' => $user->mpop_birthplace_country,
            'mpop_birthplace' => $user->mpop_birthplace,
            'mpop_billing_address' => $user->mpop_billing_address,
            'mpop_billing_city' => $user->mpop_billing_city,
            'mpop_billing_zip' => $user->mpop_billing_zip,
            'mpop_billing_state' => $user->mpop_billing_state,
            'mpop_billing_country' => $user->mpop_billing_country,
            'mpop_phone' => $user->mpop_phone,
            'mpop_marketing_agree' => boolval($user->mpop_marketing_agree),
            'mpop_newsletter_agree' => boolval($user->mpop_newsletter_agree),
            'mpop_publish_agree' => boolval($user->mpop_publish_agree),
            'mpop_org_role' => $user->mpop_org_role,
            'mpop_invited' => boolval($user->mpop_invited),
            'mpop_resp_zones' => [],
            'mpop_my_subscriptions' => $this->get_my_subscriptions($user->ID)
        ];
        if (in_array($parsed_user['role'], ['administrator', 'multipopolare_resp'])) {
            $parsed_user['mpop_has_master_key'] = false;
            if (isset($this->settings['master_doc_key']) && $this->settings['master_doc_key']) {
                $parsed_user['mpop_has_master_key'] = !!$user->mpop_personal_master_key;
            }
        }
        if ($user->mpop_profile_pending_edits) {
            $parsed_user['mpop_profile_pending_edits'] = json_decode($user->mpop_profile_pending_edits, true);
        }
        if ($add_labels) {
            $comuni = false;
            if ($parsed_user['mpop_birthplace']) {
                $comuni = $this->get_comuni_all();
                $fc = $this->get_comune_by_catasto($parsed_user['mpop_birthplace'], true);
                if ($fc) {
                    $parsed_user['mpop_birthplace'] = $this->add_birthplace_labels($fc)[0];
                }
            }
            if ($parsed_user['mpop_billing_city']) {
                if (!$comuni) {
                    $comuni = $this->get_comuni_all();
                }
                $fc = $this->get_comune_by_catasto($parsed_user['mpop_billing_city'], true);
                if ($fc) {
                    $parsed_user['mpop_billing_city'] = $this->add_billing_city_labels($fc)[0];
                }
            }
        }
        if ($retrieve_resp_zones && isset($user->roles[0]) && $user->roles[0] == 'multipopolare_resp' && !empty($user->mpop_resp_zones)) {
            $parsed_user['mpop_resp_zones'] = $this->retrieve_zones_from_resp_zones($user->mpop_resp_zones);
        }
        return $parsed_user;
    }

    private function pp_auth() {
        if (
            !isset($this->settings['pp_client_id'])
            || !$this->settings['pp_client_id']
            || !isset($this->settings['pp_client_secret'])
            || !$this->settings['pp_client_secret']
        ) {
            return false;
        }
        $res = $this->curl_exec($this->settings['pp_url'] . '/v1/oauth2/token', [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_USERPWD => $this->settings['pp_client_id'] . ':' . $this->settings['pp_client_secret']
        ]);
        if ($res) {
            $res = json_decode($res, true);
            if ($res['access_token']) {
                global $wpdb;
                $wpdb->query( "UPDATE " . $this::db_prefix('plugin_settings') . " SET `pp_access_token` = '$res[access_token]', `pp_token_expiration` = " . ($res['expires_in'] + time()) . ";" );
                $this->get_settings();
                $res = true;
            }
        }
        return $res;
    }
    private function pp_req($url, $curl_settings = []) {
        if (!isset($this->settings['pp_access_token'])) {
            $auth_res = $this->pp_auth();
            if ($auth_res !== true) {
                return false;
            }
        }
        if ($this->settings['pp_token_expiration'] < (time()+30)) {
            $auth_res = $this->pp_auth();
            if ($auth_res !== true) {
                return false;
            }
        }
        if (isset($curl_settings[CURLOPT_HTTPHEADER])) {
            $curl_settings[CURLOPT_HTTPHEADER] = $curl_settings[CURLOPT_HTTPHEADER] + [
                'Authorization: Bearer ' . $this->settings['pp_access_token']
            ];
        }
        $curl_settings = $curl_settings + [
            CURLOPT_POST => true
        ];
        $res = $this->curl_exec($this->settings['pp_url'] . $url, $curl_settings);
        if ($res) {
            $res = json_decode($res, true);
        }
        return $res;
    }
    private function pp_create_order($args = []) {
        $site_url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https': 'http') . "://$_SERVER[HTTP_HOST]";
        $brand_name = 'Multipopolare';
        if (!isset($args['brand_name'])) {
            $brand_name = $args['brand_name'];
            unset($args['brand_name']);
        }
        $args = $args + [
            'req_id' => null,
            'intent' => 'AUTHORIZE',
            'purchase_units' => [],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'UNRESTRICTED',
                        'brand_name' => $brand_name,
                        'locale' => 'it-IT',
                        'landing_page' => 'LOGIN',
                        'shipping_preference' => 'NO_SHIPPING',
                        'user_action' => 'CONTINUE',
                        'return_url' => $site_url . '/mpop-pp-success',
                        'cancel_url' => $site_url . '/mpop-pp-cancel'
                    ]
                ]
            ]
        ];
        $headers = [
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        if ($args['req_id']) {
            $headers[] = 'PayPal-Request-Id: '. $args['req_id'];
        }
        unset($args['req_id']);
        return $this->pp_req('/v2/checkout/orders', [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($args)
        ]);
    }
    private function pp_get_order($order_id) {
        return $this->pp_req('/v2/checkout/orders/' . $order_id, [
            CURLOPT_POST => false
        ]);
    }
    private function create_subscription_pp_order($args, $req_id = true) {
        if (!isset($args['subs_id']) || !is_int($args['subs_id'])) {
            return false;
        }
        if (!isset($args['payment']) || !is_numeric($args['payment'])) {
            return false;
        }
        $args['payment'] = round(((double) $args['payment']) * 100) /100;
        if ($args['payment'] < $this->settings['min_subscription_payment']) {
            return false;
        }
        return $this->pp_create_order([
            'req_id' => $req_id ? 'subs-' . $args['subs_id'] : null,
            'purchase_units' => [[
                'reference_id' => "$args[subs_id]",
                'items' => [[
                    'quantity' => '1',
                    'name' => 'Iscrizione',
                    'category' => 'DIGITAL_GOODS',
                    'unit_amount' => [
                        'currency_code' => 'EUR',
                        'value' => "$args[payment]"
                    ]
                ]],
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => "$args[payment]",
                    'breakdown' => [
                        'item_total' => [
                            'currency_code' => 'EUR',
                            'value' => "$args[payment]"
                        ]
                    ]
                ]
            ]]
        ]);
    }
    private function check_mime_type(string $file_content, $accepted = true) {
        $f_info = new finfo(FILEINFO_MIME_TYPE);
        $mime = $f_info->buffer($file_content);
        if (is_string($accepted)) {
            if ($mime != $accepted) {
                return false;
            }
        } else if (is_array($accepted)) {
            if (!in_array($mime, $accepted)) {
                return false;
            }
        }
        return $mime;
    }
    private function create_subscription(
        int $user_id,
        int $year,
        float $quote,
        $marketing_agree = null,
        $newsletter_agree = null,
        $publish_agree = null,
        string $pdf_b64 = '',
        string $id_card_b64 = '',
        int $id_card_type = 0,
        string $id_card_number = '',
        string $id_card_expiration = '',
        string $notes = '',
        $force_year = false,
        $force_quote = false,
        $ignore_others = false
    ) {
        if (
            is_null($marketing_agree)
            || is_null($newsletter_agree)
            || is_null($publish_agree)
        ) {
            throw new Exception('Empty agrees');
        }
        if ($quote < 0 || (!$force_quote && $quote < $this->settings['min_subscription_payment'])) {
            throw new Exception('Invalid year');
        }
        $quote = round($quote, 2);
        if (
            $year < 1
            || (
                !$force_year
                && !in_array($year, $this->settings['authorized_subscription_years'])
            )
        ) {
            throw new Exception('Invalid year');
        }
        if (!$ignore_others) {
            $others = $this->search_subscriptions([
                'user_id' => [$user_id],
                'year' => [$year],
                'status' => [
                    'tosee',
                    'seen',
                    'completed'
                ],
                'pagination' => false
            ], -1);
            if (count($others)) {
                throw new Exception('User has got open subscription(s) yet: ' . implode( ',', array_map(function($o) {return $o['id'];}, $others)));
            }
        }
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            throw new Exception('Invalid user');
        }
        $date_now = date_create('now', new DateTimeZone(current_time('e')));
        $rand_file_name = $date_now->format('YmdHis');
        $from_user_web_form = false;
        if ($pdf_b64) {
            $from_user_web_form = true;
            if (!$this->settings['master_doc_pubkey']) {
                throw new Exception('Server not ready to get subscriptions');
            }
            if (!trim($id_card_b64)) {
                throw new Exception('Empty ID card content');
            }
            if ($id_card_type < 0 || !isset($this->id_card_types[$id_card_type])) {
                throw new Exception('Invalid ID card type');
            }
            if (!preg_match('/^[A-Z0-9]{7,}$/', $id_card_number)) {
                throw new Exception('Invalid ID card number');
            }
            if (count(get_users([
                'meta_key' => 'mpop_id_card_number',
                'meta_value' => $id_card_number,
                'meta_compare' => '=',
                'login__not_in' => [$user->user_login]
            ]))) {
                throw new Exception('Duplicated ID card number');
            }
            if (
                !preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $id_card_expiration, $expiration_capture)
                || !checkdate(intval($expiration_capture[2]), intval($expiration_capture[3]), intval($expiration_capture[1]))
            ) {
                throw new Exception('Invalid ID card number');
            }
            $expiration_date = clone $date_now;
            $expiration_date->setDate(intval($expiration_capture[1]),intval($expiration_capture[2]), intval($expiration_capture[3]));
            $expiration_date->setTime(0,0);
            if ($expiration_date->getTimestamp() < $date_now->getTimestamp()) {
                throw new Exception('ID card expired');
            }
            $pdf_content = base64_decode($pdf_b64, true);
            if (!$pdf_content || !$this->check_mime_type($pdf_content, 'application/pdf')) {
                throw new Exception('Invalid pdf content');
            }
            $id_card_content = base64_decode($id_card_b64, true);
            $id_card_mime = $this->check_mime_type($id_card_content, ['application/pdf', 'image/jpeg', 'image/png']);
            if (!$id_card_content || !$id_card_mime) {
                throw new Exception('Invalid ID card content');
            }
            $pdf_content = $this->encrypt_asym($pdf_content, base64_decode( $this->settings['master_doc_pubkey'], true ) );
            $id_card_content = $this->encrypt_asym($id_card_content, base64_decode( $this->settings['master_doc_pubkey'], true ) );
            $pdf_file_name = $rand_file_name . '-sub-' . $user_id . '.pdf.enc';
            $id_card_file_name = $rand_file_name . '-idcard-' . $user_id . $this->mime2ext[$id_card_mime] . '.enc';
            file_put_contents(MULTIPOP_PLUGIN_PATH . "/privatedocs/$pdf_file_name", $pdf_content);
            file_put_contents(MULTIPOP_PLUGIN_PATH . "/privatedocs/$id_card_file_name", $id_card_content);
            update_user([
                'ID' => $user_id,
                'meta_input' => [
                    'mpop_id_card_type' => $id_card_type,
                    'mpop_id_card_number' => $id_card_number,
                    'mpop_id_card_expiration' => $expiration_date->getTimestamp()
                ]
            ]);
        }
        $insert_data = [
            ['user_id', $user_id, '%d'],
            ['year', $year, '%d'],
            ['quote', $quote, '%f'],
            ['marketing_agree', intval(!!$marketing_agree), '%d'],
            ['newsletter_agree', intval(!!$newsletter_agree), '%d'],
            ['publish_agree', intval(!!$publish_agree), '%d'],
            ['status', $from_user_web_form ? 'tosee' : 'seen','%s'],
            ['created_at', $date_now->getTimestamp(), '%d'],
            ['updated_at', $date_now->getTimestamp(), '%d'],
            ['author_id', get_current_user_id(), '%d']
        ];
        $notes = trim($notes);
        if ($notes) {
            $insert_data[] = ['notes', $notes, '%s'];
        }
        if ($from_user_web_form) {
            $insert_data[] = ['filename', $rand_file_name, '%s'];
        }
        global $wpdb;
        if ($wpdb->insert(
            $wpdb->prefix . 'mpop_subscriptions',
            array_reduce($insert_data, function($arr, $v){$arr[$v[0]] = $v[1]; return $arr;}, []),
            array_map(function($v) {return $v[2];}, $insert_data)
        )) {
            return $wpdb->insert_id;
        }
    }
    private function get_subscription_by($getby, $sub_id, $year = 0) {
        $search_format = '%d';
        if ($getby == 'id') {
            if (is_array($sub_id) && isset($sub_id['id'])) {
                $sub_id = $sub_id['id'];
            }
            $sub_id = intval($sub_id);
            if (!$sub_id) {
                return false;
            }
        } else if ($getby == 'card_number') {
            $sub_id = trim($sub_id);
            if (!$sub_id || !$year) {
                return false;
            }
            $search_format = '%s';
        } else {
            return false;
        }
        global $wpdb;
        $q_from = "FROM "
            . $this->db_prefix('subscriptions') . " s
            LEFT JOIN $wpdb->users users
            ON s.user_id = users.ID
            LEFT JOIN $wpdb->users authors
            ON s.author_id = authors.ID
            LEFT JOIN $wpdb->users completers
            ON s.completer_id = completers.ID
            LEFT JOIN $wpdb->usermeta fn 
            ON s.user_id = fn.user_id 
            AND fn.meta_key = 'first_name'
            LEFT JOIN $wpdb->usermeta ln 
            ON s.user_id = ln.user_id 
            AND ln.meta_key = 'last_name' "
        ;
        $res = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT s.*,
                users.user_login AS user_login,
                users.user_email AS user_email,
                authors.user_login AS author_login,
                completers.user_login AS completer_login,
                fn.meta_value AS first_name, 
                ln.meta_value AS last_name
                $q_from WHERE s.$getby = $search_format ". ($getby == 'card_number' ? "AND s.year = $year" : '') ." LIMIT 1;",
                [$sub_id]
            ),
            'ARRAY_A'
        );
        if (!$res) {
            return false;
        }
        $res['id'] = intval($res['id']);
        $res['user_id'] = intval($res['user_id']);
        $res['year'] = intval($res['year']);
        $res['quote'] = (double) $res['quote'];
        $res['marketing_agree'] = boolval($res['marketing_agree']);
        $res['newsletter_agree'] = boolval($res['newsletter_agree']);
        $res['publish_agree'] = boolval($res['publish_agree']);
        $res['created_at'] = intval($res['created_at']);
        $res['updated_at'] = intval($res['updated_at']);
        $res['signed_at'] = intval($res['signed_at']);
        $res['completed_at'] = intval($res['completed_at']);
        $res['author_id'] = intval($res['author_id']);
        $res['completer_id'] = intval($res['completer_id']);
        unset($res['completer_ip']);
        return $res;
    }


    private function complete_subscription(
        $sub_id,
        string $card_number = '',
        $signed_at = 0,
        $paypal = false
    ) {
        $webcard = false;
        $sub = $this->get_subscription_by('id', $sub_id);
        if (!$sub) {
            throw new Exception('Invalid subscription');
        }
        if ($sub['status'] != 'seen') {
            throw new Exception("Invalid subscription status: $sub[status]");
        }
        if ($card_number) {
            $card_number = trim($card_number);
            if ($this->get_subscription_by('card_number', $card_number, $sub['year'])) {
                throw new Exception("Duplicated card_number");
            }
        } else {
            $webcard = true;
            $card_number = 'W'. $this->zerofill($this->get_last_webcard_number($sub['year'])+1);
        }
        $date_now = date_create('now', new DateTimeZone( current_time('e')));
        $now_ts = $date_now->getTimestamp();
        if ($signed_at) {
            if (is_string($signed_at)) {
                if (
                    !preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $signed_at, $signed_at_capture)
                    || !checkdate(intval($signed_at_capture[2]), intval($signed_at_capture[3]), intval($signed_at_capture[1]))
                ) {
                    throw new Exception('Invalid ID card number');
                }
                $signed_at = $date_now;
                $signed_at->setDate(intval($signed_at_capture[1]),intval($signed_at_capture[2]), intval($signed_at_capture[3]));
                $signed_at->setTime(0,0);
                $signed_at = $signed_at->getTimestamp();
            } else if (!is_int($signed_at) || $signed_at < 0) {
                throw new Exception("Invalid signed_at");
            }
        } else {
            $signed_at = $now_ts;
        }
        global $wpdb;
        if(!$wpdb->query($wpdb->prepare(
            "UPDATE " . $this::db_prefix('subscriptions') . " SET 
                card_number = %s,
                status = 'completed',
                updated_at = $now_ts,
                completed_at = $now_ts,
                signed_at = $signed_at,
                completer_id = %d,
                completer_ip = %s
                ".($paypal ? '' : ", pp_order_id = NULL")."
            WHERE id = $sub_id;",
            [
                $card_number,
                get_current_user_id(),
                $this::get_client_ip()
            ]
        ))) {
            throw new Exception("Error while saving on DB");
        }
        if ($webcard) {
            $this->increment_last_webcard_number();
        }
        if (intval(current_time('Y')) <= $sub['year']){
            $meta_input = [
                'mpop_marketing_agree' => $sub['marketing_agree'],
                'mpop_newsletter_agree' => $sub['newsletter_agree'],
                'mpop_publish_agree' => $sub['publish_agree']
            ];
            if (current_time('Y') == $sub['year']) {
                $meta_input['mpop_card_active'] = true;
            }
            wp_update_user([
                'ID' => $sub['user_id'],
                'meta_input' => $meta_input
            ]);
            $this->sync_discourse_record($sub['user_id']);
        }
    }
    private static function validate_date($date_string = '') {
        if ( !is_string($date_string) || strlen(trim($date_string)) != 10) {
            throw new Exception('Invalid date');
        }
        $date_arr = array_map(function ($dt) {return intval($dt);}, explode('-', strval($date_string) ) );
        if (
            count($date_arr) != 3
            || !checkdate($date_arr[1], $date_arr[2], $date_arr[0])
        ) {
            throw new Exception('Invalid date');
        }
        $new_date = date_create('now', new DateTimeZone(current_time('e')));
        $new_date->setDate($date_arr[0], $date_arr[1], $date_arr[2]);
        $new_date->setTime(0,0,0,0);
        return $new_date;
    }
    private static function validate_birthdate($birthdate = '') {
        try {
            $birthdate = static::validate_date($birthdate);
            $min_birthdate = date_create('1910-10-13', new DateTimeZone('Europe/Rome'));
            $min_birthdate->setTime(0,0,0,0);
            $max_birthdate = date_create('now', new DateTimeZone('Europe/Rome'));
            $max_birthdate->setTime(0,0,0,0);
            $max_birthdate->sub(new DateInterval('P18Y'));
            if (
                $birthdate->getTimestamp() < $min_birthdate->getTimestamp()
                || $birthdate->getTimestamp() > $max_birthdate->getTimestamp()
            ) {
                throw new Exception('mpop_birthdate');
            }
            return $birthdate;
        } catch(Exception $e) {
            throw new Exception('mpop_birthdate');
        }
    }
    private function validate_birthplace($birthdate = '', $birthplace = '', &$comuni = [], $return_birthplace = false) {
        if (!is_string($birthplace) || !preg_match('/^[A-Z]\d{3}$/', $birthplace)) {
            throw new Exception('mpop_birthplace');
        }
        if (is_string($birthdate)) {
            $birthdate = $this::validate_birthdate($birthdate);
        } else if (is_int($birthdate)) {
            $birth_d = date_create('now', new DateTimeZone( current_time('e')));
            $birth_d->setTimestamp($birth_d);
            $birthdate = $birth_d;
        }
        if (empty($comuni)) {
            $comuni = $this->get_comuni_all();
        }
        $found_bp = $this->get_comune_by_catasto($birthplace, true, $comuni);
        if (!count($found_bp)) {
            throw new Exception('mpop_birthplace');
        }
        if (isset($found_bp['soppresso']) && $found_bp['soppresso']) {
            if (!isset($found_bp['dataSoppressione'])) {
                throw new Exception('mpop_birthplace,mpop_birthplace');
            } else {
                $soppressione_dt = date_create('now', new DateTimeZone('UTC'));
                $soppr_arr = explode('T', $found_bp['dataSoppressione']);
                $soppr_arr_dt = array_map( function($v) {return intval($v);}, explode('-', $soppr_arr[0]));
                $soppr_arr_tm = array_map( function($v) {return intval(substr( $v, 0, 2));}, explode(':', $soppr_arr[1]));
                $soppressione_dt->setDate($soppr_arr_dt[0], $soppr_arr_dt[1], $soppr_arr_dt[2]);
                $soppressione_dt->setTime($soppr_arr_tm[0], $soppr_arr_tm[1], $soppr_arr_tm[2]);
                if ($birthdate->getTimestamp() >= $soppressione_dt->getTimestamp()) {
                    throw new Exception('mpop_birthplace,mpop_birthplace');
                }
            }
        }
        if ($return_birthplace) {
            return [$birthdate->format('Y-m-d'), $found_bp];
        }
        return $birthdate->format('Y-m-d');
    }
    private function get_birth_cities($birthplace, $birthdate, &$comuni = []) {
        if (!is_string($birthplace) || mb_strlen(trim($birthplace), 'UTF-8') < 2) {
            throw new Exception('mpop_birthplace');
        }
        if (is_string($birthdate)) {
            $birthdate = $this::validate_birthdate($birthdate);
        } else if (is_int($birthdate)) {
            $birth_d = date_create('now', new DateTimeZone( current_time('e')));
            $birth_d->setTimestamp($birth_d);
            $birthdate = $birth_d;
        }
        $birthplace = trim(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $birthplace, 'UTF-8' )));
        if (empty($comuni)) {
            $comuni = $this->get_comuni_all();
        }
        $filtered_comuni = [];
        foreach($comuni as $c) {
            if (isset($c['soppresso']) && $c['soppresso']) {
                if (!isset($c['dataSoppressione']) || !$c['dataSoppressione']) {
                    continue;
                } else {
                    $soppressione_dt = date_create('now', new DateTimeZone('UTC'));
                    $soppr_arr = explode('T', $c['dataSoppressione']);
                    $soppr_arr_dt = array_map( function($v) {return intval($v);}, explode('-', $soppr_arr[0]));
                    $soppr_arr_tm = array_map( function($v) {return intval(substr( $v, 0, 2));}, explode(':', $soppr_arr[1]));
                    $soppressione_dt->setDate($soppr_arr_dt[0], $soppr_arr_dt[1], $soppr_arr_dt[2]);
                    $soppressione_dt->setTime($soppr_arr_tm[0], $soppr_arr_tm[1], $soppr_arr_tm[2]);
                    $c['dataSoppressione'] = $soppressione_dt;
                }
            }
            if (
                (
                    !isset($c['soppresso'])
                    || !$c['soppresso']
                    || $birthdate->getTimestamp() < $c['dataSoppressione']->getTimestamp()
                )
                && (
                    strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($c['nome'], 'UTF-8')), $birthplace) !== false
                    || strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($c['provincia']['nome'], 'UTF-8')), $birthplace) !== false
                    || strpos($c['provincia']['sigla'], $birthplace) !== false
                    || ( isset($c['codiceCatastale']) && strpos($c['codiceCatastale'], $birthplace) !== false)
                )
            ) {
                $c = $this->add_birthplace_labels($c)[0];
                $filtered_comuni[] = $c;
            }
        }
        return $filtered_comuni;
    }
    private function get_billing_cities($billing_city, &$comuni = []) {
        if (!is_string($billing_city) || mb_strlen(trim($billing_city), 'UTF-8') < 2) {
            throw new Exception('mpop_billing_city');
        }
        $billing_city = trim(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $billing_city, 'UTF-8' )));
        if (empty($comuni)) {
            $comuni = $this->get_comuni_all();
        }
        $filtered_comuni = [];
        foreach($comuni as $c) {
            if (isset($c['soppresso']) && $c['soppresso']) {
                continue;
            }
            if (
                strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($c['nome'], 'UTF-8')), $billing_city) !== false
            ) {
                $c = $this->add_billing_city_labels($c)[0];
                $filtered_comuni[] = $c;
            }
        }
        return $filtered_comuni;
    }
    private function get_country_by_code($code, &$countries = []) {
        if (!is_string($code) || empty($code)) {return false;}
        if (empty($countries)) {
            $countries = $this->get_countries_all();
        }
        $found = false;
        foreach($countries as $c) {
            if ($c['code'] == $code) {
                $found = $c;
                break;
            }
        }
        return $found;
    }
    private function get_comune_by_catasto(string $catasto, $soppressi = false, &$comuni= []) {
        if (empty($catasto)) {return false;}
        if (empty($comuni)) {
            $comuni = $this->get_comuni_all();
        }
        $found = false;
        foreach($comuni as $c) {
            if (!$soppressi && $c['soppresso']) {
                continue;
            }
            if ($catasto == $c['codiceCatastale']) {
                $found = $c;
                break;
            }
        }
        return $found;
    }
    private function row_import(array $row, $force_year = false, $force_quote = false, &$comuni = [], &$mails = []) {
        if (!isset($row['email']) || !$this->is_valid_email($row['email'])) {
            throw new Exception('Invalid email');
        }
        $row['email'] = strtolower($row['email']);
        if (
            get_user_by('email', $row['email'])
            || !empty(get_users([
                'meta_key' => '_new_email',
                'meta_value' => $row['email'],
                'meta_compare' => '='
            ]))
        ) {
            throw new Exception('Duplicated email');
        }
        if (!isset($row['mpop_subscription_quote']) || (!is_float($row['mpop_subscription_quote']) && !is_int($row['mpop_subscription_quote'])) ) {
            throw new Exception('Invalid mpop_subscription_quote');
        }
        if (!$force_quote) {
            if (!$this->settings['min_subscription_payment']) {
                throw new Exception('min_subscription_payment not setted in settings');
            }
            if ($row['mpop_subscription_quote'] < $this->settigs['min_subscription_payment']) {
                throw new Exception('mpop_subscription_quote too little. Min: ' . $this->settigs['min_subscription_payment']);
            }
        } else {
            if ($row['mpop_subscription_quote'] <= 0) {
                throw new Exception('mpop_subscription_quote too little');
            }
        }
        if (!isset($row['mpop_subscription_date'])) {
            throw new Exception('Invalid mpop_subscription_quote');
        }
        $subscription_date = '';
        try {
            $subscription_date = $this::validate_date($row['mpop_subscription_date']);
        } catch (Exception $e) {
            throw new Exception('Invalid mpop_subscription_date');
        }
        if (!$force_year) {
            if (!in_array(intval($subscription_date->format('Y')),$this->settings['authorized_subscription_years'])) {
                throw new Exception('Invalid year in mpop_subscription_date');
            }
        }
        if (
            !isset($row['mpop_subscription_card_number'])
            || !is_string($row['mpop_subscription_card_number'])
            || !trim($row['mpop_subscription_card_number'])
        ) {
            throw new Exception('Invalid mpop_subscription_card_number');
        } else {
            $row['mpop_subscription_card_number'] = trim( mb_strtoupper( $row['mpop_subscription_card_number'], 'UTF-8'));
            if (str_starts_with($row['mpop_subscription_card_number'], 'W')) {
                throw new Exception('mpop_subscription_card_number cannot start with W');
            }
            if (!empty($this::get_subscription_by('card_number',$row['mpop_subscription_card_number'], $subscription_date->format('Y')))) {
                throw new Exception('Duplicated mpop_subscription_card_number');
            }
        }
        $mpop_friend = isset($row['mpop_friend']) ? boolval($row['mpop_friend']) : false;
        $user_input = [
            'user_pass' => bin2hex(openssl_random_pseudo_bytes(16)),
            'user_email' => $row['email'],
            'role' => $mpop_friend ? 'multipopolare_friend' : 'multipopolano',
            'locate' => 'it_IT',
            'meta_input' => [
                'mpop_invited' => true
            ]
        ];
        if ($mpop_friend) {
            if (!isset($row['login']) || !is_string($row['login']) || !strlen($row['login'])) {
                throw new Exception('Invalid login');
            }
            $row['login'] = strtolower($row['login']);
            if (!$this::is_valid_username($row['login'])) {
                throw new Exception('Invalid login');
            }
            if (get_user_by('login', $row['login'])) {
                throw new Exception('Duplicated login');
            }
            $user_input['user_login'] = $row['login'];
        } else {
            if (isset($row['mpop_org_role'])) {
                if (in_array($row['mpop_org_role'],self::SINGLE_ORG_ROLES)) {
                    if (!empty(get_users([
                        'meta_key' => 'mpop_org_role',
                        'meta_value' => $row['mpop_org_role'],
                        'meta_compare' => '='
                    ]))) {
                        throw new Exception($row['mpop_org_role'] . ' mpop_org_role yet assigned');
                    }
                } else {
                    throw new Exception('Invalid mpop_org_role');
                }
                $user_input['meta_input']['mpop_org_role'] = $row['mpop_org_role'];
            }
            $sub_notes = isset($row['mpop_subscription_notes']) ? trim(strval($row['mpop_subscription_notes'])) : '';
            $marketing_agree = isset($row['mpop_subscription_marketing_agree']) ? boolval($row['mpop_subscription_marketing_agree']) : false;
            $newsletter_agree = isset($row['mpop_subscription_newsletter_agree']) ? boolval($row['mpop_subscription_newsletter_agree']) : false;
            $publish_agree = isset($row['mpop_subscription_publish_agree']) ? boolval($row['mpop_subscription_publish_agree']) : false;
            $user_input['meta_input']['mpop_marketing_agree'] = $marketing_agree;
            $user_input['meta_input']['mpop_newsletter_agree'] = $newsletter_agree;
            $user_input['meta_input']['mpop_publish_agree'] = $publish_agree;
            if (isset($row['login']) && is_string($row['login']) && strlen($row['login'])) {
                $row['login'] = strtolower($row['login']);
                if (!$this::is_valid_username($row['login'])) {
                    throw new Exception('Invalid login');
                }
                if (get_user_by('login', $row['login'])) {
                    throw new Exception('Duplicated login');
                }
                $user_input['user_login'] = $row['login'];
                if (!isset($row['first_name']) || !$this::is_valid_name($row['first_name'])) {
                    throw new Exception('Invalid first_name');
                }
                $user_input['meta_input']['first_name'] = mb_strtoupper($row['first_name'], 'UTF-8');
                if (!isset($row['last_name']) || !$this::is_valid_name($row['last_name'])) {
                    throw new Exception('Invalid last_name');
                }
                $user_input['meta_input']['last_name'] = mb_strtoupper($row['last_name'], 'UTF-8');
                if (!isset($row['mpop_birthdate'])) {
                    throw new Exception('Invalid mpop_birthdate');
                }
                try {
                    $row['mpop_birthdate'] = $this::validate_birthdate($row['mpop_birthdate']);
                } catch (Exception $e) {
                    throw new Exception('Invalid mpop_birthdate');
                }
                if (!isset($row['mpop_birthplace_country']) || !$this->get_country_by_code($row['mpop_birthplace_country'])) {
                    throw new Exception('Invalid mpop_birthplace_country');
                }
                $user_input['meta_input']['mpop_birthplace_country'] = $row['mpop_birthplace_country'];
                if ($row['mpop_birthplace_country'] == 'ita') {
                    if (!isset($row['mpop_birthplace'])) {
                        throw new Exception('Invalid mpop_birthplace');
                    }
                    try {
                        $birthdate = $this->validate_birthplace($row['mpop_birthdate'], $row['mpop_birthplace']);
                        $user_input['meta_input']['mpop_birthdate'] = $birthdate;
                        $user_input['meta_input']['mpop_birthplace'] = $row['mpop_birthplace'];
                    } catch (Exception $e) {
                        throw new Exception('Invalid ' . $e->getMessage());
                    }
                } else {
                    $user_input['meta_input']['mpop_birthdate'] = $row['mpop_birthdate']->format('Y-m-d');
                }
                if (!isset($row['mpop_billing_address']) || !is_string($row['mpop_billing_address']) || mb_strlen(trim($row['mpop_billing_address']), 'UTF-8') < 2) {
                    throw new Exception('Invalid mpop_billing_address');
                }
                $user_input['meta_input']['mpop_billing_address'] = $row['mpop_billing_address'];
                if (!isset($row['mpop_billing_country']) || !$this->get_country_by_code($row['mpop_billing_country']) ) {
                    throw new Exception('Invalid mpop_billing_country');
                } elseif ($row['mpop_billing_country'] == 'ita') {
                    if (!isset($row['mpop_billing_city']) || !is_string($row['mpop_billing_city']) ) {
                        throw new Exception('Invalid mpop_billing_city');
                    }
                    if (!isset($row['mpop_billing_zip']) || !is_string($row['mpop_billing_zip']) ) {
                        throw new Exception('Invalid mpop_billing_zip');
                    }
                    if (empty($comuni)) {
                        $comuni = $this->get_comuni_all();
                    }
                    $comune = $this->get_comune_by_catasto($row['mpop_billing_city'], false, $comuni);
                    if (!$comune) {
                        throw new Exception('Invalid mpop_billing_city');
                    }
                    if (!in_array($row['mpop_billing_zip'], $comune['cap'])) {
                        throw new Exception('Invalid mpop_billing_zip');
                    }
                    $user_input['meta_input']['mpop_billing_state'] = $comune['provincia']['sigla'];
                    $user_input['meta_input']['mpop_billing_city'] = $row['mpop_billing_city'];
                    $user_input['meta_input']['mpop_billing_zip'] = $row['mpop_billing_zip'];
                }
                $user_input['meta_input']['mpop_billing_country'] = $row['mpop_billing_country'];
                if (!isset($row['mpop_phone']) || !is_string($row['mpop_phone']) || !$this::is_valid_phone($row['mpop_phone'])) {
                    throw new Exception('Invalid mpop_phone');
                }
                $user_input['meta_input']['mpop_phone'] = $row['mpop_phone'];
            } else {
                do {
                    $row['login'] = 'mp_' . bin2hex(openssl_random_pseudo_bytes(16));
                } while(get_user_by('login', $row['login']));
                $user_input['user_login'] = $row['login'];
                if (isset($row['first_name']) && $row['first_name']) {
                    if (!$this::is_valid_name($row['first_name'])) {
                        throw new Exception('Invalid first_name');
                    }
                    $user_input['meta_input']['first_name'] = mb_strtoupper($row['first_name'], 'UTF-8');
                }
                if (isset($row['last_name']) && $row['last_name']) {
                    if (!$this::is_valid_name($row['last_name'])) {
                        throw new Exception('Invalid last_name');
                    }
                    $user_input['meta_input']['last_name'] = mb_strtoupper($row['last_name'], 'UTF-8');
                }
                $birthdate = '';
                if (isset($row['mpop_birthdate']) && $row['mpop_birthdate']) {
                    try {
                        $birthdate = $this::validate_birthdate($row['mpop_birthdate']);
                    } catch (Exception $e) {
                        throw new Exception('Invalid mpop_birthdate');
                    }
                    $user_input['meta_input']['mpop_birthdate'] = $birthdate->format('Y-m-d');
                }
                if (isset($row['mpop_birthplace_country']) && $row['mpop_birthplace_country']) {
                    if (!$this->get_country_by_code($row['mpop_birthplace_country'])) {
                        throw new Exception('Invalid mpop_birthplace_country');
                    }
                    $user_input['meta_input']['mpop_birthplace_country'] = $row['mpop_birthplace_country'];
                    if ($row['mpop_birthplace_country'] == 'ita') {
                        if (isset($row['mpop_birthplace']) && $row['mpop_birthplace']) {
                            try {
                                $this->validate_birthplace($birthdate, $row['mpop_birthplace']);
                                $user_input['meta_input']['mpop_birthplace'] = $row['mpop_birthplace'];
                            } catch (Exception $e) {
                                throw new Exception('Invalid ' . $e->getMessage());
                            }
                        }
                    }
                }
                if (isset($row['mpop_billing_address']) && is_string($row['mpop_billing_address'])) {
                    $user_input['meta_input']['mpop_billing_address'] = $row['mpop_billing_address'];
                }
                if (isset($row['mpop_billing_country']) && $row['mpop_billing_country']) {
                    if (!$this->get_country_by_code($row['mpop_billing_country'])) {
                        throw new Exception('Invalid mpop_billing_country');
                    }
                    $user_input['meta_input']['mpop_billing_country'] = $row['mpop_billing_country'];
                    if ($row['mpop_billing_country'] == 'ita') {
                        if (isset($row['mpop_billing_city']) && $row['mpop_billing_city'] ) {
                            if (empty($comuni)) {
                                $comuni = $this->get_comuni_all();
                            }
                            $comune = $this->get_comune_by_catasto($row['mpop_billing_city'], false, $comuni);
                            if (!$comune) {
                                throw new Exception('Invalid mpop_billing_city');
                            }
                            $user_input['meta_input']['mpop_billing_state'] = $comune['provincia']['sigla'];
                            $user_input['meta_input']['mpop_billing_city'] = $row['mpop_billing_city'];
                            if (isset($row['mpop_billing_zip']) && $row['mpop_billing_zip'] ) {
                                if (!in_array($row['mpop_billing_zip'], $comune['cap'])) {
                                    throw new Exception('Invalid mpop_billing_zip');
                                }
                                $user_input['meta_input']['mpop_billing_zip'] = $row['mpop_billing_zip'];
                            } else if (count($comune['cap']) == 1) {
                                $user_input['meta_input']['mpop_billing_zip'] = $comune['cap'][0];
                            }
                        }
                    }
                }
                if (isset($row['mpop_phone']) && is_string($row['mpop_phone']) && $row['mpop_phone']) {
                    if (!$this::is_valid_phone($row['mpop_phone'])) {
                        throw new Exception('Invalid mpop_phone');
                    }
                    $user_input['meta_input']['mpop_phone'] = $row['mpop_phone'];
                }
            }
        }
        $user_id = wp_insert_user($user_input);
        if (is_wp_error($user_id)) {
            throw new Exception('Error during user save: ' . $user_id->get_error_message());
        }
        if (!$mpop_friend) {
            $sub_id = false;
            try {
                $sub_id = $this->create_subscription(
                    $user_id,
                    intval($subscription_date->format('Y')),
                    $row['mpop_subscription_quote'],
                    $marketing_agree,
                    $newsletter_agree,
                    $publish_agree,
                    '',
                    '',
                    0,
                    '',
                    '',
                    $sub_notes,
                    $force_year,
                    $force_quote
                );
            } catch(Exception $e) {
                throw new Exception('Error while saving subscription: ' . $e->getMessage());
            }
            if (!$sub_id) {
                throw new Exception('Error while saving subscription');
            }
            try {
                $this->complete_subscription($sub_id,$row['mpop_subscription_card_number'],$subscription_date->getTimestamp());
            } catch(Exception $e) {
                throw new Exception('Error while completing subscription: ' . $e->getMessage());
            }
        }
        $token = $this->create_temp_token($user_id,'invite_link',3600*24*30);
        if (is_array($mails)) {
            $mails[] = ['token' => $token, 'to' => $row['email']];
        } else {
            if(!$this->send_invitation_mail($token, $row['email'])) {
                throw new Exception("Error while sending mail" . ($this->last_mail_error ? ': ' . $this->last_mail_error : ''));
            }
        }
        return ['user_id'=> $user_id, 'sub_id' => $sub_id];
    }
    private function change_user_login($user_id, string $new_login, string $display_name = '') {
        if (!$this::is_valid_username($new_login)) {
            throw new Exception('user_login');
        }
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        if (!$user_id) {
            throw new Exception('user_id');
        }
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            throw new Exception('user_id');
        }
        if ($user->discourse_sso_user_id) {
            throw new Exception('user');
        }
        if( get_user_by('login', $new_login) ) {
            throw new Exception('duplicated');
        }
        global $wpdb;
        $wpdb->update(
            $wpdb->users,
            [
                'user_login' => $new_login,
                'user_nicename' => sanitize_title($new_login),
                'display_name' => $display_name ? $display_name : $new_login
            ],
            ['ID' => $user->ID]
        );
        $wpdb->update(
            $wpdb->usermeta,
            ['meta_value' => $new_login],
            ['user_id'=> $user->ID, 'meta_key'=> 'nickname']
        );
        delete_user_meta($user->ID, 'mpop_invited');
        clean_user_cache($user->ID);
        update_user_caches(get_user_by('ID', $user->ID));
    }
    private function get_my_subscriptions($user_id) {
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        if (!is_int($user_id)) {
            return [];
        }
        global $wpdb;
        $res = $wpdb->get_results("SELECT * FROM " . $this->db_prefix('subscriptions') . " WHERE user_id = $user_id ORDER BY updated_at DESC;");
        foreach ($res as $k => &$v) {
            if (in_array($k, [
                'id',
                'user_id',
                'year',
                'created_at',
                'updated_at',
                'signed_at',
                'completed_at',
                'author_id',
                'completer_id'
            ])) {
                $v = intval($v);
            } else if (in_array($k, [
                'marketing_agree',
                'newsletter_agree',
                'publish_agree',
            ])) {
                $v = boolval($v);
            } else if ($k == 'quote') {
                $v = (double) $v;
            }
        }
        unset($res['completer_ip']);
        return $res;
    }
    private function search_subscriptions(array $options = [], $limit = 100, ) { 
        $options = $options + [
            'txt' => '',
            'user_id' => [],
            'year' => [], 
            'quote' => ',',
            'marketing_agree' => null,
            'newsletter_agree' => null,
            'publish_agree' => null,
            'status' => [],
            'created_at' => ',',
            'updated_at' => ',',
            'signed_at' => ',',
            'completed_at' => ',',
            'author_id' => [],
            'completer_id' => [],
            'mpop_billing_country' => [],
            'mpop_billing_state' => [],
            'mpop_billing_city' => [],
            'page' => 1,
            'pagination' => true,
            'order_by' => ['s.updated_at' => false]
        ];
        $time_interval_reg = '/^\d*,\d*$/';
        $quote_interval_reg = '/^(\d+(\.\d{1,2})?)?,(\d+(\.\d{1,2})?)?$/';
        $mpop_billing_country_reg = '/^[a-z]{3}$/';
        $mpop_billing_state_reg = '/^[A-Z]{2}$/';
        $mpop_billing_city_reg = '/^[A-Z]\d{3}$/';
        $allowed_sorts = [
            'id',
            'card_number',
            'user_login',
            'login',
            'user_email',
            'email',
            'first_name',
            'last_name',
            'year',
            'quote',
            'marketing_agree',
            'newsletter_agree',
            'publish_agree',
            'status',
            'created_at',
            'updated_at',
            'signed_at',
            'completed_at',
            'author',
            'completer_id',
            'mpop_billing_country',
            'mpop_billing_state',
            'mpop_billing_city'
        ];
        $res = false;
        if (
            !is_string($options['txt'])
            || !is_array($options['user_id'])
            || !is_array($options['year'])
            || !is_string($options['quote'])
            || !preg_match($quote_interval_reg, $options['quote'])
            || !is_array($options['status'])
            || !is_string($options['created_at'])
            || !preg_match($time_interval_reg, $options['created_at'])
            || !is_string($options['updated_at'])
            || !preg_match($time_interval_reg, $options['updated_at'])
            || !is_string($options['signed_at'])
            || !preg_match($time_interval_reg, $options['signed_at'])
            || !is_string($options['completed_at'])
            || !preg_match($time_interval_reg, $options['completed_at'])
            || !is_array($options['author_id'])
            || !is_array($options['completer_id'])
            || !is_array($options['mpop_billing_country'])
            || !is_array($options['mpop_billing_state'])
            || !is_array($options['mpop_billing_city'])
            || !is_int($options['page'])
            || $options['page'] < 1
            || !is_array($options['order_by'])
        ) {
            return $res;
        }
        $options['quote'] = explode(',', $options['quote']);
        $options['created_at'] = explode(',', $options['created_at']);
        $options['updated_at'] = explode(',', $options['updated_at']);
        $options['signed_at'] = explode(',', $options['signed_at']);
        $options['completed_at'] = explode(',', $options['completed_at']);
        if (
            (
                strlen($options['quote'][0])
                && strlen($options['quote'][1])
                && ((double) $options['quote'][0]) > ((double) $options['quote'][1])
            )
            || (
                strlen($options['created_at'][0])
                && strlen($options['created_at'][1])
                && intval($options['created_at'][0]) > intval($options['created_at'][1])
            )
            || (
                strlen($options['updated_at'][0])
                && strlen( $options['updated_at'][1])
                && intval($options['updated_at'][0]) > intval($options['updated_at'][1])
            )
            || (
                strlen($options['signed_at'][0])
                && strlen( $options['signed_at'][1])
                && intval($options['signed_at'][0]) > intval($options['signed_at'][1])
            )
            || (
                strlen($options['completed_at'][0])
                && strlen($options['completed_at'][1])
                && intval($options['completed_at'][0]) > intval($options['completed_at'][1])
            )
        ) {
            return $res;
        }
        $options['user_id'] = array_values(array_unique(array_filter($options['user_id'], function($y) {return is_int($y) && $y > 0;})));
        $options['author_id'] = array_values(array_unique(array_filter($options['author_id'], function($id) {return is_int($id) && $id > 0;})));
        $options['completer_id'] = array_values(array_unique(array_filter($options['completer_id'], function($id) {return is_int($id) && $id > 0;})));
        $options['year'] = array_values(array_unique(array_filter($options['year'], function($y) {return is_int($y) && $y > 0;})));
        $options['status'] = array_values(array_unique(array_filter($options['status'], function($s) {return in_array($s, MultipopPlugin::SUBS_STATUSES);})));
        $options['mpop_billing_country'] = array_values(array_unique(array_filter($options['mpop_billing_country'], function($s) use ($mpop_billing_country_reg) {return preg_match($mpop_billing_country_reg, $s);})));
        $options['mpop_billing_state'] = array_values(array_unique(array_filter($options['mpop_billing_state'], function($s) use ($mpop_billing_state_reg) {return preg_match($mpop_billing_state_reg, $s);})));
        $options['mpop_billing_city'] = array_values(array_unique(array_filter($options['mpop_billing_city'], function($c) use ($mpop_billing_city_reg) {return preg_match($mpop_billing_city_reg, $c);})));
        $order_by = "";
        foreach ($options['order_by'] as $k => $v) {
            if (in_array($k, $allowed_sorts)) {
                switch ($k) {
                    case 'email':
                        $k = 'users.user_email';
                        break;
                    case 'user_email':
                        $k = 'users.user_email';
                        break;
                    case 'login':
                        $k = 'users.user_login';
                        break;
                    case 'user_login':
                        $k = 'users.user_login';
                        break;
                    case 'first_name':
                        $k = 'fn.meta_value';
                        break;
                    case 'last_name':
                        $k = 'ln.meta_value';
                        break;
                    case 'author':
                        $k = 'authors.user_login';
                        break;
                    case 'completer_id':
                        $k = 'completers.user_login';
                        break;
                    case 'mpop_billing_country':
                        $k = 'country.meta_value';
                        break;
                    case 'mpop_billing_state':
                        $k = 'prov.meta_value';
                        break;
                    case 'mpop_billing_city':
                        $k = 'comune.meta_value';
                        break;
                    default:
                        switch($k) {
                            case 'marketing_agree':
                                $v = !$v;
                                break;
                            case 'newsletter_agree':
                                $v = !$v;
                                break;
                            case 'publish_agree':
                                $v = !$v;
                                break;
                        }
                        $k = 's.' . $k;
                }
                $order_by .= ($order_by ? ", " : "") . $k . ($v ? " ASC" : " DESC");
            }
        }
        if (!$order_by) {
            $order_by = "s.updated_at DESC";
        }
        $options['txt'] = trim($options['txt']);
        global $wpdb;
        $q_where = "";
        $append_to_where = function($w, $or = false) use (&$q_where) {
            if ($q_where) {$q_where .= $or ? " OR " : " AND ";}
            $q_where .= $w;
        };
        $options['txt'] = trim($options['txt']);
        if ($options['txt']) {
            $sanitized_value = '%' . $wpdb->esc_like($options['txt']) . '%';
            $append_to_where($wpdb->prepare(
                "( s.card_number LIKE %s
                OR users.user_login LIKE %s
                OR users.user_email LIKE %s
                OR fn.meta_value LIKE %s
                OR ln.meta_value LIKE %s )",
                $sanitized_value,
                $sanitized_value,
                $sanitized_value,
                $sanitized_value,
                $sanitized_value
            ));
        }
        if (count($options['user_id'])) {
            $append_to_where("s.user_id IN ( " . implode(',',$options['user_id']) . " )");
        }
        if (count($options['year'])) {
            $append_to_where("s.year IN ( " . implode(',', $options['year']) . " )");
        }
        if ($options['quote'][0]) {
            $append_to_where("s.quote >= " . $options['quote'][0]);
        }
        if ($options['quote'][1]) {
            $append_to_where("s.quote <= " . $options['quote'][1]);
        }
        if (!is_null($options['marketing_agree'])) {
            $append_to_where("s.marketing_agree = " . ($options['marketing_agree'] ? '1' : '0'));
        }
        if (!is_null($options['newsletter_agree'])) {
            $append_to_where("s.newsletter_agree = " . ($options['newsletter_agree'] ? '1' : '0'));
        }
        if (!is_null($options['publish_agree'])) {
            $append_to_where("s.publish_agree = " . ($options['publish_agree'] ? '1' : '0'));
        }
        if (count($options['status'])) {
            $append_to_where("s.status IN ( " . implode(',', array_map(function($s) {return "'$s'";}, $options['status'])) . " )");
        }
        if ($options['created_at'][0]) {
            $append_to_where("s.created_at >= " . $options['created_at'][0]);
        }
        if ($options['created_at'][1]) {
            $append_to_where("s.created_at <= " . $options['created_at'][1]);
        }
        if ($options['updated_at'][0]) {
            $append_to_where("s.updated_at >= " . $options['updated_at'][0]);
        }
        if ($options['updated_at'][1]) {
            $append_to_where("s.updated_at <= " . $options['updated_at'][1]);
        }
        if ($options['signed_at'][0]) {
            $append_to_where("s.signed_at >= " . $options['signed_at'][0]);
        }
        if ($options['signed_at'][1]) {
            $append_to_where("s.signed_at <= " . $options['signed_at'][1]);
        }
        if ($options['completed_at'][0]) {
            $append_to_where("s.completed_at >= " . $options['completed_at'][0]);
        }
        if ($options['completed_at'][1]) {
            $append_to_where("s.completed_at <= " . $options['completed_at'][1]);
        }
        if (count($options['author_id'])) {
            $append_to_where("s.author_id IN ( " . implode(',',$options['author_id']) . " )");
        }
        if (count($options['completer_id'])) {
            $append_to_where("s.completer_id IN ( " . implode(',',$options['completer_id']) . " )");
        }
        if (count($options['mpop_billing_country'])) {
            $append_to_where("country.meta_value IN ( " . implode(',', array_map(function($s) {return "'$s'";},$options['mpop_billing_country'])) . " )");
        }
        if (count($options['mpop_billing_state'])) {
            $append_to_where("prov.meta_value IN ( " . implode(',', array_map(function($s) {return "'$s'";},$options['mpop_billing_state'])) . " )");
        }
        if (count($options['mpop_billing_city'])) {
            $append_to_where("comune.meta_value IN ( " . implode(',', array_map(function($s) {return "'$s'";}, $options['mpop_billing_city'])) . " )");
        }

        $q_from = "FROM "
            . $this->db_prefix('subscriptions') . " s
            LEFT JOIN $wpdb->users users
            ON s.user_id = users.ID
            LEFT JOIN $wpdb->users authors
            ON s.author_id = authors.ID
            LEFT JOIN $wpdb->users completers
            ON s.completer_id = completers.ID
            LEFT JOIN $wpdb->usermeta fn
            ON s.user_id = fn.user_id
            AND fn.meta_key = 'first_name'
            LEFT JOIN $wpdb->usermeta ln
            ON s.user_id = ln.user_id
            AND ln.meta_key = 'last_name'
            LEFT JOIN $wpdb->usermeta country
            ON s.user_id = country.user_id
            AND country.meta_key = 'mpop_billing_country'
            LEFT JOIN $wpdb->usermeta prov
            ON s.user_id = prov.user_id
            AND prov.meta_key = 'mpop_billing_state'
            LEFT JOIN $wpdb->usermeta comune
            ON s.user_id = comune.user_id
            AND comune.meta_key = 'mpop_billing_city' "
        ;
        $total = 0;
        if ($options['pagination']) {
            $q_count = "SELECT COUNT(DISTINCT s.id) as total_count $q_from " . ($q_where ? "WHERE $q_where" : '') . ";";
            $total = intval($wpdb->get_var($q_count));
        }
        $pages = 1;
        $q_limit = "";
        if ($limit >= 0) {
            $pages = ceil($total / $limit);
            $q_limit = " LIMIT $limit";
            if ($options['page'] > $pages) {
                $options['page'] = $pages;
            }
            if ($options['page'] > 1) {
                $q_limit .= " OFFSET " . ($options['page'] - 1) * $limit;
            }
        }
        $q = "SELECT DISTINCT s.*,
            users.user_login AS user_login,
            users.user_email AS user_email,
            authors.user_login AS author_login,
            completers.user_login AS completer_login,
            fn.meta_value AS first_name, 
            ln.meta_value AS last_name,
            country.meta_value AS mpop_billing_country,
            prov.meta_value AS mpop_billing_state,
            comune.meta_value AS mpop_billing_city
            $q_from " . ($q_where ? "WHERE $q_where" : '') . " $q_limit;";
        $res = [];
        $res['subscriptions'] = $wpdb->get_results($q, 'ARRAY_A');
        foreach($res['subscriptions'] as &$sub) {
            unset($sub['filename']);
            $sub['id'] = intval($sub['id']);
            $sub['user_id'] = intval($sub['user_id']);
            $sub['year'] = intval($sub['year']);
            $sub['quote'] = (double) $sub['quote'];
            $sub['marketing_agree'] = boolval($sub['marketing_agree']);
            $sub['newsletter_agree'] = boolval($sub['newsletter_agree']);
            $sub['publish_agree'] = boolval($sub['publish_agree']);
            $sub['created_at'] = intval($sub['created_at']);
            $sub['updated_at'] = intval($sub['updated_at']);
            $sub['signed_at'] = intval($sub['signed_at']);
            $sub['completed_at'] = intval($sub['completed_at']);
            $sub['author_id'] = intval($sub['author_id']);
            $sub['completer_id'] = intval($sub['completer_id']);
            unset($sub['completer_ip']);
        }
        if (!$options['pagination']) {
            return $res['subscriptions'];
        }
        $res['total'] = $total;
        $res['pages'] = $pages;
        $res['page'] = $options['page'];
        return $res;
    }
    public function user_search_pre_user_query($q) {
        global $wpdb;
        $extra_from = [];
        if (!empty($q->query_vars['mpop_extra_meta'])) {
            foreach($q->query_vars['mpop_extra_meta'] as $m_key => $m_type) {
                $extra_from[$m_key] = "LEFT JOIN $wpdb->usermeta mpop_exmt_$m_key ON (mp_users.ID = mpop_exmt_$m_key.user_id AND mpop_exmt_$m_key.meta_key = '$m_key')";
            }
        }
        if (!empty($extra_from)) {
            $q->query_from .= " " . implode(' ', $extra_from);
        }
        if (isset($q->query_vars['orderby']) && is_array($q->query_vars['orderby'])) {
            $order_by = [];
            $clauses = $q->meta_query->get_clauses();
            $i = 0;
            foreach($q->query_vars['orderby'] as $k=>$v ) {
                if (in_array($k, ['first_name', 'last_name'])) {
                    $alias = $clauses[$k]['alias'];
                    $order_by[$i] = "IF($alias.meta_value = '', 1, 0) $v, $alias.meta_value $v";
                } else if (isset($extra_from[$k])) {
                    $order_by[$i] = "IF(COALESCE(mpop_exmt_$k.meta_value, '') = '', 1, 0) $v, mpop_exmt_$k.meta_value $v";
                }
                $i++;
            }
            if (count($order_by)) {
                $qob_orig = substr($q->query_orderby, 9);
                $qob_n = [];
                for($i=0; $i<2; $i++) {
                    if (isset($order_by[$i])) {
                        $qob_n[] = $order_by[$i];
                    } else {
                        $qob_n[] = $qob_orig;
                    }
                }
                $q->query_orderby = "ORDER BY " . implode(', ', $qob_n);
            }
        }
        if (isset($q->query_vars['mpop_custom_search']) && is_string($q->query_vars['mpop_custom_search'])) {
            $sanitized_value = '%' . $wpdb->esc_like($q->query_vars['mpop_custom_search']) . '%';
            $q->query_where .= $wpdb->prepare(
                " AND (user_login LIKE %s OR user_email LIKE %s OR (search_first_name.meta_key = 'first_name' AND search_first_name.meta_value LIKE %s) OR (search_last_name.meta_key = 'last_name' AND search_last_name.meta_value LIKE %s))",
                $sanitized_value,
                $sanitized_value,
                $sanitized_value,
                $sanitized_value
            );
            $q->query_from .= " INNER JOIN $wpdb->usermeta AS search_first_name ON ( $wpdb->users.ID = search_first_name.user_id ) INNER JOIN $wpdb->usermeta AS search_last_name ON ( $wpdb->users.ID = search_last_name.user_id )";
        }
        remove_action('pre_user_query', [$this, 'user_search_pre_user_query']);
    }
    private function parse_requested_roles($roles = true) {
        $allowed_roles = ['administrator', 'multipopolano', 'multipopolare_resp', 'multipopolare_friend', 'others'];
        if ($roles === true) {
            $roles = $allowed_roles;
        }
        foreach ($roles as $role) {
            if (!is_string($role) || !trim($role) || !in_array($role, $allowed_roles)) {
                return false;
            }
        }
        $roles = array_unique($roles);
        if (in_array('others', $roles)) {
            array_slice($roles, array_search('others', $roles), 1);
            array_push($roles, ...array_filter(array_keys(wp_roles()->role_names), function($r) use ($allowed_roles) {return !in_array($r,$allowed_roles);}));
        }
        return $roles;
    }
    private function user_search(
        $txt= '',
        $roles = true,
        array $mpop_billing_country = [],
        array $mpop_billing_state = [],
        array $mpop_billing_city = [],
        array $mpop_resp_zones = [],
        $mpop_card_active = null,
        $mpop_mail_to_confirm = null,
        $page = 1,
        $sort_by = ['ID' => true],
        $limit = 100
    ) {
        $res = [];
        if (!is_array($roles) && $roles !== true) {
            return $res;
        }
        if (!is_int($page) || $page < 1) {
            $page = 1;
        }
        if (!is_int($limit) || $limit < 1 || $limit > 100) {
            $limit = 100;
        }
        $query = [
            'paged' => $page,
            'number' => $limit
        ];
        if(count($mpop_resp_zones)) {
            $mpop_resp_zones = array_filter($mpop_resp_zones, function($z) {return is_string($z) && (str_starts_with($z, 'reg_') || preg_match('/^([A-Z]{2})|([A-Z]\d{3})$/', $z));});
            $mpop_resp_zones = array_unique($mpop_resp_zones);
            $mpop_resp_zones = array_values($mpop_resp_zones);
        }
        if(count($mpop_resp_zones)) {
            if ($roles === true || in_array('multipopolare_resp', $roles)) {
                $roles = ['multipopolare_resp'];
            } else {
                return [[], 0, $limit];
            }
        } else {
            $roles = $this->parse_requested_roles($roles);
        }
        if (!is_array($roles)) {
            return $res;
        }
        add_action('pre_user_query', [$this, 'user_search_pre_user_query']);
        $meta_q = [
            'relation' => 'AND',
            'role' => [
                'relation' => 'OR'
            ]
        ];
        global $wpdb;
        if (count($roles)) {
            sort($roles);
            foreach($roles as $role) {
                $meta_q['role'][] = [
                    'key' => $wpdb->prefix . 'capabilities',
                    'value' => "\"$role\"",
                    'compare' => 'LIKE'
                ];
            }
        } else {
            $meta_q['role'][] = [
                'key' => $wpdb->prefix . 'capabilities',
                'value' => 'a:0:{}'
            ];
        }
        if (is_string($txt) && trim($txt) && !preg_match("\r|\n|\t",$txt)) {
            $query['mpop_custom_search'] = $txt;
        }
        $mpop_billing_country = array_values(array_unique(array_filter($mpop_billing_country, function($s) { return preg_match('/^[a-z]{3}$/', $s); })));
        $mpop_billing_state = array_values(array_unique(array_filter($mpop_billing_state, function($s) { return preg_match('/^[A-Z]{2}$/', $s); })));
        $mpop_billing_city = array_values(array_unique(array_filter($mpop_billing_city, function($s) { return preg_match('/^[A-Z]\d{3}$/', $s); })));
        if (count($mpop_billing_country)) {
            $meta_q['mpop_billing_country'] = [
                'key' => 'mpop_billing_country',
                'compare' => 'IN',
                'value' => $mpop_billing_country
            ];
        }
        if (count($mpop_billing_state)) {
            $meta_q['mpop_billing_state'] = [
                'key' => 'mpop_billing_state',
                'compare' => 'IN',
                'value' => $mpop_billing_state
            ];
        }
        if (count($mpop_billing_city)) {
            $meta_q['mpop_billing_city'] = [
                'key' => 'mpop_billing_city',
                'compare' => 'IN',
                'value' => $mpop_billing_city
            ];
        }
        if (count($mpop_resp_zones)) {
            $meta_q['mpop_resp_zones'] = [
                'relation' => 'OR'
            ];
            foreach($mpop_resp_zones as $zone) {
                $meta_q['mpop_resp_zones'][] = [
                    'key' => 'mpop_resp_zones',
                    'value' => "\"$zone\"",
                    'compare' => 'LIKE'
                ];
            }
        }
        if (is_bool($mpop_card_active)) {
            if ($mpop_card_active) {
                $meta_q['mpop_card_active'] = [
                    'key' => 'mpop_card_active',
                    'value' => '1',
                    'type' => 'NUMERIC'
                ];
            } else {
                $meta_q['mpop_card_active'] = [
                    'relation' => 'OR',
                    [
                        'key' => 'mpop_card_active',
                        'value' => '',
                    ],
                    [
                        'key' => 'mpop_card_active',
                        'compare' => 'NOT EXISTS'
                    ]
                ];
            }
        }
        if (is_bool($mpop_mail_to_confirm)) {
            if ($mpop_mail_to_confirm) {
                $meta_q['mpop_mail_to_confirm'] = [
                    'key' => 'mpop_mail_to_confirm',
                    'value' => '1',
                    'type' => 'NUMERIC'
                ];
            } else {
                $meta_q['mpop_mail_to_confirm'] = [
                    'relation' => 'OR',
                    [
                        'key' => 'mpop_mail_to_confirm',
                        'value' => '',
                    ],
                    [
                        'key' => 'mpop_mail_to_confirm',
                        'compare' => 'NOT EXISTS'
                    ]
                ];
            }
        }
        $allowed_field_sorts = [
            'ID',
            'login',
            'user_login',
            'email',
            'user_email',
            'registered',
            'user_registered',
            'role'
        ];
        $allowed_meta_sorts = [
            'mpop_mail_to_confirm',
            'first_name',
            'last_name',
            'mpop_billing_country',
            'mpop_billing_state',
            'mpop_billing_city',
            'mpop_card_active'
        ];
        $extra_meta = [];
        if (!is_array($sort_by)) {
            $sort_by = ['ID' => 'ASC'];
        } else {
            $sort_keys = array_keys($sort_by);
            $fsort_by = [];
            foreach ($sort_keys as $k) {
                if (in_array($k, $allowed_field_sorts)) {
                    if ($k == 'role') {
                        $fsort_by[$wpdb->usermeta] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                    } else {
                        $fsort_by[$k] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                    }
                } else if (in_array($k, $allowed_meta_sorts)) {
                    switch ($k) {
                        case 'first_name':
                            $meta_q[$k] = [
                                'key' => $k,
                                'compare' => 'EXISTS'
                            ];
                            $fsort_by[$k] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                            break;
                        case 'last_name':
                            $meta_q[$k] = [
                                'key' => $k,
                                'compare' => 'EXISTS'
                            ];
                            $fsort_by[$k] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                            break;
                        default:
                            if (isset($meta_q[$k])) {
                                $fsort_by[$k] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                                break;
                            }
                            switch ($k) {
                                case 'mpop_mail_to_confirm':
                                    $extra_meta[$k] = 'BOOL';
                                    break;
                                case 'mpop_card_active':
                                    $extra_meta[$k] = 'BOOL';
                                    break;
                                default:
                                    $extra_meta[$k] = 'CHAR';
                            }
                            $fsort_by[$k] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
                    }
                } else {
                    unset($sort_by[$k]);
                }
            }
            $sort_by = $fsort_by;
            if (empty($sort_by)) {
                $sort_by = ['ID' => 'ASC'];
            }
        }
        $query['meta_query'] = $meta_q;
        $query['orderby'] = $sort_by;
        $query['mpop_extra_meta'] = $extra_meta;
        $user_query = new WP_User_Query($query);
        $total = $user_query->get_total();
        $res = $user_query->get_results();
        $comuni_all = false;
        if (count($res)) {
            $comuni_all = $this->get_comuni_all();
        }
        $users = [];
        foreach($res as $u) {
            $billing_city = '';
            if ($u->mpop_billing_city) {
                $billing_city = $this->get_comune_by_catasto($u->mpop_billing_city, true);
            }
            $parsed_u = [
                'ID' => intval($u->ID),
                'login' => $u->user_login,
                'email' => $u->user_email,
                'role' => isset($u->roles[0]) ? $u->roles[0] : '',
                'registred' => $u->user_registered,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'mpop_card_active' => $u->mpop_card_active ? true : false,
                'mpop_mail_to_confirm' => boolval($u->mpop_mail_to_confirm ),
                'mpop_billing_country' => $u->mpop_billing_country,
                'mpop_billing_state' => $u->mpop_billing_state,
                'mpop_billing_city' => $billing_city ? $billing_city['nome'] : '',
                'mpop_phone' => $u->mpop_phone,
                'mpop_org_role' => $u->mpop_org_role,
                'mpop_invited' => $u->mpop_invited,
                'mpop_resp_zones' => []
            ];
            if (isset($u->roles[0]) && $u->roles[0] == 'multipopolare_resp' && !empty($u->mpop_resp_zones)) {
                $parsed_u['mpop_resp_zones'] = $this->retrieve_zones_from_resp_zones( $u->mpop_resp_zones );
            }
            $users[] = $parsed_u;
        }
        $arr_sort_by = [];
        foreach ($sort_by as $k =>$v) {
            $arr_sort_by[] = [$k=>$v == 'ASC' ? true : false];
        }
        return [$users, $total, $limit, $arr_sort_by];
    }

    public function discourse_req_ca($verify, $url) {
        $discourse_connect_options = get_option('discourse_connect');
        if (
            is_array($discourse_connect_options)
            && isset($discourse_connect_options['url'])
            && $discourse_connect_options['url']
            && str_starts_with($url, $discourse_connect_options['url'])
            && file_exists( MULTIPOP_PLUGIN_PATH . '/discourse.ca' )
        ) {
            return MULTIPOP_PLUGIN_PATH . '/discourse.ca';
        }
        return $verify;
    }
    public function discourse_filter_login($user_id, $user) {
        $allowed_roles = ['administrator', 'multipopolano', 'multipopolare_resp', 'multipopolare_friend'];
        if (
            !count($user->roles)
            || !in_array( $user->roles[0], $allowed_roles )
        ) {
            wp_redirect(get_permalink($this->settings['myaccount_page']));
            exit;
        }
        if (!in_array($user->roles[0], ['administrator', 'multipopolare_friend'])) {
            if (!$user->mpop_card_active) {
                wp_redirect(get_permalink($this->settings['myaccount_page']));
                exit;
            }
        }
    }
    private function discourse_utilities() {
        if (isset($this->disc_utils)) {return $this->disc_utils;}
        if (
            $this->is_plugin_active('wp-discourse/wp-discourse.php')
            && !empty(get_option( 'discourse_sso_provider' )['enable-sso'])
            && class_exists('WPDiscourse\Utilities\Utilities')
        ) {
            require_once(MULTIPOP_PLUGIN_PATH . '/classes/mpop-discourse-utilities.php');
            $this->disc_utils = new MpopDiscourseUtilities();
            return $this->disc_utils;
        }
        return false;
    }
    private function compact_regione_name(string $name = '') {
        $regione_name = $name;
        if ($regione_name !== "Valle d'Aosta") {
            $regione_name = explode(' ', $regione_name)[0];
        }
       return preg_replace("/ |'/", '', strtolower(iconv('UTF-8','ASCII//TRANSLIT', $regione_name)));
    }
    private function generate_user_discourse_groups($user_id) {
        $user = false;
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        $user = get_user_by('ID', intval($user_id));
        if (!$user) {
            return false;
        }
        $groups = [];
        $province_all = false;
        $regioni_all = false;
        if (isset($user->roles[0])) {
            if ($user->roles[0] == 'administrator') {
                $groups[] = ['name' => 'mp_wp_admins', 'full_name' => 'Amministratori Wordpress', 'owner' => false];
            } else if ($user->roles[0] == 'multipopolare_friend') {
                $groups[] = ['name' => 'mp_friends', 'full_name' => 'Amici di Multipopolare', 'owner' => false];
            } else if ($user->mpop_card_active && in_array($user->roles[0], ['multipopolano', 'multipopolare_resp'])) {
                if ($user->mpop_billing_state) {
                    if (!$province_all) {
                        $province_all = $this->get_province_all();
                    }
                    if ($province_all) {
                        $provincia = array_filter($province_all, function($p) use ($user) { return $p['sigla'] == $user->mpop_billing_state; });
                        $provincia = array_pop( $provincia );
                        if ($provincia) {
                            $groups[$user->mpop_billing_state] = ['name' => "mp_pr_$user->mpop_billing_state", 'full_name' => "Provincia di $provincia[nome]", 'owner' => false];
                            $regione_name = $this->compact_regione_name($provincia['regione']);
                            $groups[$provincia['regione']] = ['name' => "mp_rg_$regione_name", 'full_name' => "Regione $provincia[regione]", 'owner' => false];
                        }
                    }
                } else if ($user->mpop_billing_country && $user->mpop_billing_country != 'ita') {
                    $country = $this->get_country_by_code($user->mpop_billing_country);
                    if ($country) {
                        $groups[$country['code']] = ['name' => "mp_ct_$country[code]", 'full_name' => "Stato $country[name]", 'owner' => false];
                    }
                }
                if ($user->mpop_card_active && $user->roles[0] == 'multipopolare_resp' && !empty($user->mpop_resp_zones)) {
                    foreach($user->mpop_resp_zones as $zone) {
                        if (str_starts_with( $zone, 'reg_' )) {
                            $reg_fullname = substr($zone, 4);
                            $regione_name = $this->compact_regione_name($reg_fullname);
                            $groups[$reg_fullname] = ['name' => "mp_rg_$regione_name", 'full_name' => "Regione $reg_fullname", 'owner' => false];
                            $groups[] = ['name' => "mp_rgr_$regione_name", 'full_name' => "Responsabili regione $reg_fullname", 'owner' => false];
                            if (!$regioni_all) {
                                $regioni_all = $this->get_regioni_all();
                            }
                            foreach($regioni_all[$reg_fullname] as $p) {
                                $groups[$p['sigla']] = ['name' => "mp_pr_$p[sigla]", 'full_name' => "Provincia di $p[nome]", 'owner' => false];
                                $groups[$p['sigla'].'_r'] = ['name' => "mp_prr_$p[sigla]", 'full_name' => "Responsabili provincia di $p[nome]", 'owner' => false];
                            }
                        } else if (preg_match('/^[A-Z]{2}$/', $zone)) {
                            if (!$province_all) {
                                $province_all = $this->get_province_all();
                            }
                            $provincia = array_filter($province_all, function($p) use ($zone) { return $p['sigla'] == $zone; });
                            $provincia = array_pop( $provincia);
                            if ($provincia) {
                                $groups[$provincia['sigla']] = ['name' => "mp_pr_$provincia[sigla]", 'full_name' => "Provincia di $provincia[nome]", 'owner' => false];
                                $groups[$provincia['sigla'].'_r'] = ['name' => "mp_prr_$provincia[sigla]", 'full_name' => "Responsabili provincia di $provincia[nome]", 'owner' => false];
                            }
                        }
                    }
                }
            }
        }
        if(empty($groups)) {
            $groups[] = ['name' => 'mp_disabled_users', 'full_name' => 'Utenti Wordpress disabilitati', 'owner' => false];
        }
        return array_values($groups);
    }
    private function sync_discourse_record($user_id, $force = false) {
        if (!$user_id) {return;}
        if (is_object($user_id)) {
            $user_id = $user_id->ID;
        }
        $user = get_user_by('ID', $user_id);
        if (!$user) {return;}
        if (!$force && !$user->discourse_sso_user_id) {return;}
        $disc_utils = $this->discourse_utilities();
        if (!$disc_utils) {return;}
        $disc_utils::sync_sso_record($disc_utils::get_sso_params($user));
    }
    private function update_discourse_groups_by_user($user) {
        $disc_utils = $this->discourse_utilities();
        if (!$disc_utils) { return false; }
        $new_groups = $this->generate_user_discourse_groups($user);
        return $disc_utils->update_mpop_discourse_groups_by_user($user, $new_groups);
    }
    public function discourse_user_params($params, $user) {
        $groups = $this->generate_user_discourse_groups($user);
        if (count($groups)) {
            $disc_utils = $this->discourse_utilities();
            if ($disc_utils) {
                $disc_groups = $disc_utils->get_discourse_mpop_groups();
                foreach($groups as $g) {
                    $found = array_filter($disc_groups, function($dg) use ($g) {return $dg->name == $g['name'];});
                    if (!count($found)) {
                        $disc_utils->create_discourse_group($g['name'], $g['full_name']);
                    }
                }
            }
            $params['groups'] = implode( ',', array_map(function($g) {return $g['name'];}, $groups) );
        }
        $this->delay_script('updateDiscourseGroupsByUser', $user->ID);
        return $params;
    }
    private static function get_discourse_system_username() {
        $discourse_connect_options = get_option('discourse_connect');
        if (
            is_array($discourse_connect_options)
            && isset($discourse_connect_options['publish-username'])
            && $discourse_connect_options['publish-username']
        ) {
            return $discourse_connect_options['publish-username'];
        }
        return false;
    }
    private function get_discourse_system_user() {
        if (isset($this->discourse_system_user)) {return $this->discourse_system_user;}
        $username = $this::get_discourse_system_username();
        if ($username) {
            $disc_utils = $this->discourse_utilities();
            if ($disc_utils) {
                $res = $disc_utils::discourse_request("/u/$username.json");
                if (!is_wp_error($res)) {
                    $this->discourse_system_user = $res->user;
                    return $this->discourse_system_user;
                }
            }
        }
        return false;
    }
    public function discourse_bypass_invited_users($user_id, $user) {
        return $user->mpop_invited;
    }
}

?>