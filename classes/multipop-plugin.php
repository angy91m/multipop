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
function save_test($obj, $id=0) {
    file_put_contents(MULTIPOP_PLUGIN_PATH ."/test-$id.txt", html_dump($obj));
}

class MultipopPlugin {

    private ?array $settings;
    private bool $wp_yet_loaded = false;
    private string $req_url = '';
    private string $req_path = '';
    private array $user_notices = [];
    private array $subs_statuses = [
        'tosee',
        'seen',
        'refused',
        'canceled',
        'completed',
        'refunded'
    ];

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
    private static function is_valid_email( $email, $check_temp_mail = false ) {
        $res = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($res && $check_temp_mail && file_exists(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt')) {
            $res = !in_array(explode('@', $email)[1], preg_split('/\r\n|\r|\n/', file_get_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt')));
        }
        return $res;
    }

    // USERNAME VALIDATION
    private static function is_valid_username( $username ) {
        if (
            !preg_match('/^[a-z0-9._-]{3,24}$/', $username)
            || !preg_match('/[a-z0-9]/', $username)
            || str_starts_with( $username, '.' )
            || str_starts_with( $username, '-' )
            || str_ends_with( $username, '.' )
            || str_ends_with( $username, '-' )
        ) {return false;}
        return true;
    }

    // PASSWORD VALIDATION
    private static function is_valid_password( $password ) {
        if ( mb_strlen( $password, 'UTF-8' ) < 8 ) {return false;}
        $rr = [
            '/[a-z]+/',
            '/[A-Z]+/',
            '/[0-9]+/',
            '/[ |\\!"£$%&\/()=?\'^,.;:_@°#*+[\]{}_-]+/'
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

    private static function dashicon(string $icon = '', string $ba = 'before') {
        return '<span class="dashicons-'.$ba.' dashicons-'.$icon.'">&nbsp;</span>';
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
            $res[] = "$k=$v";
        }
        return implode('&', $res);
    }

    function __construct() {

        $req_url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https': 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
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
        //add_action( 'woocommerce_login_form_start', [$this, 'html_login_mail_confirm'] );
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
        add_filter( 'https_ssl_verify', [$this, 'discourse_req_ca'], 10, 2 );
        add_filter( 'discourse_email_verification', function() {return false;} );
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
        if (count($current_user->roles) == 1 && ($current_user->roles[0] == 'multipopolano' || $current_user->roles[0] == 'multipopolare_resp')) {
            show_admin_bar(false);
        }

        // REDIRECT AFTER EMAIL CONFIRMATION
        // (IF USER CLICK ON A CONFIRMATION LINK BUT HE'S LOGGED IN WITH ANOTHER USER)
        if (
            str_starts_with($this->req_url, get_permalink($this->settings['myaccount_page']))
            && isset($_REQUEST['mpop_mail_token'])
        ) {
            if ( !preg_match('/^[a-f0-9]{32}$/', $_REQUEST['mpop_mail_token']) ) {
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
                $this->logout_redirect($this->req_url);
            }
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
        // HIDE ADMIN BAR
        $current_user = wp_get_current_user();

        if (count($current_user->roles) == 1 && ($current_user->roles[0] == 'multipopolano' || $current_user->roles[0] == 'multipopolare_resp')) {
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
            `subscription_storage_path` VARCHAR(255) NULL,
            `last_webcard_number` BIGINT,
            `authorized_subscription_years` VARCHAR(255) NOT NULL,
            `last_year_checked` INT NOT NULL,
            `min_subscription_payment` DOUBLE NOT NULL,
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
            `card_id` VARCHAR(255) NULL UNIQUE,
            `filename` VARCHAR(255) NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `year` SMALLINT UNSIGNED NOT NULL,
            `status` VARCHAR(255) NOT NULL,
            `created_at` BIGINT UNSIGNED NOT NULL,
            `signed_at` BIGINT UNSIGNED NULL,
            `completed_at` BIGINT UNSIGNED NULL,
            `author_id` BIGINT UNSIGNED NOT NULL,
            `pp_order_id` VARCHAR(255) NULL,
            PRIMARY KEY (`id`)
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
                    `last_webcard_number`,
                    `authorized_subscription_years`,
                    `last_year_checked`,
                    `min_subscription_payment`,
                    `pp_token_expiration`,
                    `pp_sandbox`
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
                    0,
                    '',
                    0,
                    15,
                    0,
                    1
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
            return $phpmail->ErrorInfo;
        }
    }

    private function send_confirmation_mail($token, $to) {
        $confirmation_link = get_permalink($this->settings['myaccount_page']);
        return $this->send_mail([
            'to' => $to,
            'subject' =>'Conferma email',
            'body' => 'Clicca sul link per confermare la tua email: <a href="'. $confirmation_link . '?mpop_mail_token=' . $token . '" target="_blank">'. $confirmation_link . '?mpop_mail_token=' . $token . '</a>'
        ]);
    }

    // CREATE PDF
    private function pdf_create(array $pdf_config = []) {
        require_once(MULTIPOP_PLUGIN_PATH . '/classes/multipopdf.php');
        $pdf = new MultipoPDF($pdf_config+['logo'=> MULTIPOP_PLUGIN_PATH . '/logo-pdf.png']);
        require_once(MULTIPOP_PLUGIN_PATH . '/modulo-iscrizione.php');
        return $pdf->export_file();
    }
    
    // IMPORT PDF
    private function pdf_import(string $file = '', array $options = [], string $key = '') {
        require_once(MULTIPOP_PLUGIN_PATH . '/classes/multipopdf.php');
        $pdf = new MultipoPDF(['mpop_import' => true]);
        $fd = $file;
        if (isset($options['key'])) {
            if (isset($options['key'], )) {
                if (strlen($options['key'], ) != 32) {
                    throw new Exception('Invalid key');
                }
                $fd = fopen('data://application/pdf;base64,'. base64_encode( $this->decrypt(file_get_contents($file), $options['key'], isset($options['mac_key']) ? $options['mac_key'] : '')), 'r');
            }
        } else if (isset($options['password'])) {
            $fd = fopen('data://application/pdf;base64,'. base64_encode( $this->decrypt_with_password(file_get_contents($file), $options['password'], isset($options['mac_key']) ? $options['mac_key'] : '')), 'r');
        }
        $pages_count = $pdf->setSourceFile( $fd );
        for ($i=1; $i<=$pages_count; $i++) {
            if ($i > 1) {
                $pdf->AddPage();
            }
            $tpl = $pdf->importPage($i);
            $pdf->useTemplate($tpl);
        }
        return $pdf;
    }

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
        return $token;
    }

    // DELETE EMAIL CONFIRMATION LINK
    private function delete_temp_token( $token ) {
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
        if ( !preg_match( '/^[a-f0-9]{32}$/', $token ) ) {
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
            $settings['authorized_subscription_years'] = array_values(array_filter(array_map(function($v) {return intval($v);},explode(',', $settings['authorized_subscription_years'])), function($v) {return $v >= $this_year;}));
        } else {
            $settings['authorized_subscription_years'] = [];
        }
        $settings['subscription_storage_path'] = $settings['subscription_storage_path'] ? explode( ',', $settings['subscription_storage_path'] ) : [];
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
        $this->flush_subscriptions();
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
        if (isset( $this->settings ) && is_array( $this->settings )) {
            $this_year = intval(current_time('Y'));
            if ( $this_year > $this->settings['last_year_checked'] ) {
                global $wpdb;
                $users_to_disable = $wpdb->get_col(
                    "SELECT `user_id` FROM " . $this::db_prefix('subscriptions') . " WHERE `status` = 'completed' AND `year` < $this_year AND `user_id` NOT IN (
                        SELECT `user_id` FROM " . $this::db_prefix('subscriptions') . " WHERE `status` = 'completed' AND `year` = $this_year
                    );"
                );
                foreach ($users_to_disable as $u) {
                    update_user_meta(intval($u), 'mpop_card_active', false);
                }

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
        exec(MULTIPOP_PLUGIN_PATH . 'comuni/comuni-update.sh --skip-on-error=attivi,soppressi,multicap > /dev/null &');
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
        header('location:/404');
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

    // MAIN TEMPLATE REDIRECT
    // (USED TO HIDE SOME MY ACCOUNT SUB-PAGES)
    // public function template_redirect() {
    //     global $wp;
    //     if (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename'] == $this->my_account_page_slug() && isset($wp->query_vars['edit-address'])) {
    //         $this->location_not_found();
    //     }
    // }

    // RETUTN MY ACCOUNT PAGE SLUG
    private function my_account_page_slug() {
        $my_account_addr_arr = explode('/',preg_replace('/https?:\/\//', '', get_permalink($this->settings['myaccount_page'])));
        $my_account_addr_arr = array_values(array_filter($my_account_addr_arr, function($e) {return !empty(trim($e));}));
        unset($my_account_addr_arr[0]);
        return implode('/', $my_account_addr_arr);
    }


    // LOGIN

    // ADD ELEMENTS TO LOGIN PAGE
    public function html_login_mail_confirm() {
        if (isset($_REQUEST['mpop_mail_token']) && preg_match('/^[a-f0-9]{32}$/', $_REQUEST['mpop_mail_token'])) { ?>
            <p>Inserisci le credenziali per confermare l'indirizzo e-mail</p>
            <?php
        }
    }

    // CHECK AFTER LOGIN
    public function filter_login( $user ) {
        if (is_null($user) || is_wp_error($user)) {
            return $user;
        }
        $roles = $user->roles;
        if (count( $roles ) == 0) {
            return new WP_Error(401, "No roles found");
        } else if (count( $roles ) == 1 && in_array($roles[0], ['multipopolano', 'multipopolare_resp'])) {
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
                        // unset($_GET['mpop_mail_token']);
                        // unset($_GET['invalid_mpop_login']);
                        // $_GET['mpop_mail_confirmed'] = '1';
                        // wp_redirect(explode('?',$this->req_url)[0] . '?' . $this->export_GET());
                        // exit;
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
        $pub_key = openssl_pkey_get_public( "-----BEGIN PUBLIC KEY-----\n". base64_encode($pub_key) . "\n-----END PUBLIC KEY-----");
        openssl_public_encrypt($data, $enc, $pub_key, OPENSSL_PKCS1_OAEP_PADDING);
        return $enc;
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
        openssl_private_decrypt($data, $dec, $priv_key, OPENSSL_PKCS1_OAEP_PADDING);
        return $dec;
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
        return json_decode(file_get_contents(MULTIPOP_PLUGIN_PATH . '/comuni/comuni.json'), true);
    }
    private function add_birthplace_labels(...$comuni) {
        foreach($comuni as $i=>$c) {
            $comuni[$i]['untouched_label'] = mb_strtoupper($c['nome'], 'UTF-8') . ' (' . $c['provincia']['sigla'] . ') - ' . $c['codiceCatastale'] . (isset($c['soppresso']) && $c['soppresso'] ? ' (soppresso)' : '');
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
    private function get_province_all() {
        return json_decode(file_get_contents(MULTIPOP_PLUGIN_PATH . '/comuni/province.json'), true);
    }
    private function search_zones($search = '') {
        $zones = [];
        if (!is_string($search) || mb_strlen(trim($search), 'UTF-8') < 2) {
            return $zones;
        } else {
            $search = trim(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $search, 'UTF-8' )));
        }
        $regioni = [];
        $province_all = $this->get_province_all();
        foreach($province_all as $p) {
            if (!$p['soppressa']) {
                if (
                    strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper( $p['nome'], 'UTF-8' )),$search) !== false
                    || strpos($p['sigla'], $search) !== false
                ) {
                    $pp = $p + [
                        'type' => 'provincia',
                        'untouched_label' => 'Provincia: ' . mb_strtoupper($p['nome'], 'UTF-8')
                    ];
                    $pp['label'] = iconv('UTF-8','ASCII//TRANSLIT',  $pp['untouched_label']);
                    $zones[] = $pp;
                }
                if (!in_array($p['regione'], $regioni) && strpos(iconv('UTF-8', 'ASCII//TRANSLIT',mb_strtoupper($p['regione'], 'UTF-8')), $search) !== false) {
                    $regioni[] = $p['regione'];
                }
            }
        }
        foreach($regioni as $r) {
            $zone = [
              'nome' => $r,
              'type' => 'regione',
              'untouched_label' => 'Regione: ' . mb_strtoupper($r, 'UTF-8'),
            ];
            $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT',  $zone['untouched_label']);
            $zones[] = $zone;
        }
        $comuni_all = $this->get_comuni_all();
        foreach($comuni_all as $c) {
            if (
                (!isset($c['soppresso']) || !$c['soppresso'])
                && strpos(iconv('UTF-8','ASCII//TRANSLIT',mb_strtoupper($c['nome'], 'UTF-8')), $search) !== false
            ) {
                $zone = $c;
                $zone['type'] = 'comune';
                $zone['untouched_label'] = 'Comune: ' . mb_strtoupper($c['nome'], 'UTF-8');
                $zone['label'] = iconv('UTF-8','ASCII//TRANSLIT',  $zone['untouched_label']);
                $zones[] = $zone;
            }
        }
        function cmp_comune($a, $b) {
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
        }
        function cmp_province($a, $b) {
            if ($b['type'] == 'provincia') {
                return mb_strtoupper( $a['nome'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
            }
            if ($a['regione'] == $b['nome']) {
                return 1;
            }
            return mb_strtoupper( $a['regione'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
        }
        usort($zones, function($a, $b) {
            if ($a['type'] == 'comune') {
                return cmp_comune($a, $b);
            } else if ($b['type'] == 'comune') {
                return -(cmp_comune($b, $a));
            } else if ($a['type'] == 'provincia') {
                return cmp_province($a, $b);
            } else if ($b['type'] == 'provincia') {
                return -(cmp_province($b, $a));
            } else {
                return mb_strtoupper( $a['nome'], 'UTF-8') < mb_strtoupper( $b['nome'], 'UTF-8') ? -1 : 1;
            }
        });
        return $zones;
    }
    private function myaccount_get_profile($user, $add_labels = false) {
        if (is_int($user)) {
            $user = get_user_by('ID', $user);
        }
        $parsed_user = [
            'ID' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'registered' => $user->user_registered,
            'role' => $user->roles[0],
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            '_new_email' => $user->_new_email ? $user->_new_email : false,
            'mpop_mail_to_confirm' => boolval( $user->mpop_mail_to_confirm ),
            'mpop_card_active' => boolval($user->mpop_card_active ),
            'mpop_birthdate' => $user->mpop_birthdate,
            'mpop_birthplace' => $user->mpop_birthplace,
            'mpop_billing_address' => $user->mpop_billing_address,
            'mpop_billing_city' => $user->mpop_billing_city,
            'mpop_billing_zip' => $user->mpop_billing_zip,
            'mpop_billing_state' => $user->mpop_billing_state
        ];
        if ($add_labels) {
            $comuni = false;
            if ($parsed_user['mpop_birthplace']) {
                $comuni = $this->get_comuni_all();
                $fc = array_values(array_filter($comuni, function($c) use ($parsed_user) {return $c['codiceCatastale'] == $parsed_user['mpop_birthplace'];}));
                if (count($fc)) {
                    $parsed_user['mpop_birthplace'] = $this->add_birthplace_labels(...$fc)[0];
                }
            }
            if ($parsed_user['mpop_billing_city']) {
                if (!isset($comuni)) {
                    $comuni = $this->get_comuni_all();
                }
                $fc = array_values(array_filter($comuni, function($c) use ($parsed_user) {return $c['codiceCatastale'] == $parsed_user['mpop_billing_city'];}));
                if (count($fc)) {
                    $parsed_user['mpop_billing_city'] = $this->add_billing_city_labels(...$fc)[0];
                }
            }
        }
        return $parsed_user;
    }
    private function discourse_group_names() {
        $sanitize_names = function($name) {
            return preg_replace(
                '/ |\'/',
                '-',
                str_replace(
                    " - ",
                    '-',
                    mb_strtolower(
                        iconv('UTF-8','ASCII//TRANSLIT', $name),
                        'UTF-8'
                    )
                )
            );
        };
        $group_names = ['wp_admins'];
        $province = $this->get_province_all();
        if ($province) {
            foreach($province as $p) {
                if ($p['soppressa']) {
                    continue;
                }
                $r = ($sanitize_names)($p['regione']);
                if (!in_array("wp_regione_$r", $group_names)) {
                    $group_names[] = "wp_regione_$r";
                }
                $group_names[] = "wp_provincia_" . (($sanitize_names)($p['sigla']));
            }
        }
        sort($group_names);
        return $group_names;
    }
    public function discourse_groups($user) {
        $sanitize_names = function($name) {
            return preg_replace(
                '/ |\'/',
                '-',
                str_replace(
                    " - ",
                    '-',
                    mb_strtolower(
                        iconv('UTF-8','ASCII//TRANSLIT', $name),
                        'UTF-8'
                    )
                )
            );
        };
        if (!isset($user)) {
            return '';
        }
        if (is_string($user) ) {
            $user = intval($user);
        }
        if (is_int($user) && $user) {
            $user = get_user_by('ID', $user);
        }
        if (!is_object($user) || !isset($user->ID) || !$user->ID) {
            return '';
        }
        
        $group_names = $this->discourse_group_names();
        foreach ($group_names as $gn) {
            
        }
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
    private function create_subscription() {

    }
    public function user_search_pre_user_query($q) {
        global $wpdb;
        $sanitized_value = '%' . $wpdb->esc_like($q->query_vars['mpop_custom_search']) . '%';
        $q->query_where .= $wpdb->prepare(
            " AND (user_login LIKE %s OR user_email LIKE %s OR (search_first_name.meta_key = 'first_name' AND search_first_name.meta_value LIKE %s) OR (search_last_name.meta_key = 'last_name' AND search_last_name.meta_value LIKE %s))",
            $sanitized_value,
            $sanitized_value,
            $sanitized_value,
            $sanitized_value
        );
        $q->query_from .= ' INNER JOIN ' . $wpdb->prefix . 'usermeta AS search_first_name ON ( ' . $wpdb->prefix . 'users.ID = search_first_name.user_id ) INNER JOIN ' . $wpdb->prefix . 'usermeta AS search_last_name ON ( ' . $wpdb->prefix . 'users.ID = search_last_name.user_id )';
        remove_action('pre_user_query', [$this, 'user_search_pre_user_query']);
    }
    private function user_search($txt= '', $roles = true, $page = 1, $sort_by = ['ID' => true], $limit = 100) {
        $res = [];
        if (!is_array($roles) && $roles !== true) {
            return $res;
        }
        $allowed_roles = ['administrator', 'multipopolano', 'multipopolare_resp', 'others'];
        if ($roles === true) {
            $roles = $allowed_roles;
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
        $meta_q = [
            'relation' => 'AND',
            'role' => [
                'relation' => 'OR'
            ]
        ];
        foreach ($roles as $role) {
            if (!is_string($role) || !trim($role) || !in_array($role, $allowed_roles)) {
                return $res;
            }
        }
        $roles = array_unique($roles);
        if (in_array('others', $roles)) {
            array_slice($roles, array_search('others', $roles), 1);
            array_push($roles, ...array_filter(array_keys(wp_roles()->role_names), function($r) {return !in_array($r,$allowed_roles);}));
        }
        if (count($roles)) {
            sort($roles);
            foreach($roles as $role) {
                $meta_q['role'][] = [
                    'key' => 'wp_capabilities',
                    'value' => "\"$role\"",
                    'compare' => 'LIKE'
                ];
            }
        } else {
            $meta_q['role'][] = [
                'key' => 'wp_capabilities',
                'compare' => 'NOT EXISTS'
            ];
        }
        if (is_string($txt) && trim($txt) && !preg_match("\r|\n|\t",$txt)) {
            $query['mpop_custom_search'] = $txt;
            add_action('pre_user_query', [$this, 'user_search_pre_user_query']);
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
            'mpop_billing_state',
            'mpop_billing_city'
        ];
        if (!is_array($sort_by)) {
            $sort_by = ['ID' => 'ASC'];
        } else {
            $sort_keys = array_keys($sort_by);
            $fsort_by = [];
            foreach ($sort_keys as $k) {
                if (in_array($k, $allowed_field_sorts)) {
                    if ($k == 'role') {
                        $fsort_by['wp_usermeta'] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
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
                            $meta_q[$k] = [
                                'relation' => 'OR',
                                $k.'_exists' => [
                                    'key' => $k,
                                    'compare' => 'EXISTS'
                                ],
                                $k.'_notexists' => [
                                    'key' => $k,
                                    'compare' => 'NOT EXISTS'
                                ],
                            ];
                            $fsort_by[$k.'_exists'] = boolval($sort_by[$k]) ? 'ASC' : 'DESC';
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
        $user_query = new WP_User_Query($query);
        $total = $user_query->get_total();
        $res = $user_query->get_results();
        $comuni_all = false;
        if (count($res)) {
            $comuni_all = $this->get_comuni_all();
        }
        $users = [];
        foreach($res as $u) {
            $billing_city = $u->mpop_billing_city ? array_pop(array_filter($comuni_all, function($c) use ($u) {return $c['codiceCatastale'] == $u->mpop_billing_city; } )) : '';
            $users[] = [
                'ID' => intval($u->ID),
                'login' => $u->user_login,
                'email' => $u->user_email,
                'role' => $u->roles[0],
                'registred' => $u->user_registered,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'mpop_mail_to_confirm' => boolval($u->mpop_mail_to_confirm ),
                'mpop_billing_state' => $u->mpop_billing_state,
                'mpop_billing_city' => $billing_city ? $billing_city['nome'] : '',
                'zones' => []
            ];
        }
        return [$users, $total, $limit];
    }

    public function discourse_req_ca($verify, $url) {
        $discourse_connect_options = get_option('discourse_connect');
        if (
            is_array($discourse_connect_options)
            && $discourse_connect_options['url']
            && str_starts_with($url, $discourse_connect_options['url'])
            && file_exists( MULTIPOP_PLUGIN_PATH . '/discourse.ca' )
        ) {
            return MULTIPOP_PLUGIN_PATH . '/discourse.ca';
        }
        return $verify;
    }
    private function discourse_utilities() {
        if (class_exists('WPDiscourse\Utilities\PublicUtilities')) {
            return new WPDiscourse\Utilities\PublicUtilities();
        }
        return false;
    }
}

?>