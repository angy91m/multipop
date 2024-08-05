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
    private static function is_valid_email( $email ) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
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
    private static function is_strong_password( $password ) {
        if ( mb_strlen( $password, 'UTF-8' ) < 24 ) {return false;}
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

    // DB PREFIX FOR PLUGIN TABLES
    private static function db_prefix( $table ) {
        global $wpdb;
        $prefix = '`' . DB_NAME . '`.`' . $wpdb->prefix . 'mpop_' . $table . '`';
        return $prefix;
    }

    function __construct() {
        // INIT HOOK
        add_action('init', [$this, 'init']);
        // ACTIVATION HOOK
        register_activation_hook(MULTIPOP_PLUGIN_PATH . '/multipop.php', [$this, 'activate']);
        // TEMPLATE REDIRECT HOOK
        add_action('template_redirect', [$this, 'template_redirect'] );
        // CONNECT wp_mail to $this->send_mail()
        add_filter('pre_wp_mail', function ($res, $atts) {
            return $this->send_mail(['to' => $atts['to'], 'subject' => $atts['subject'], 'body' => $atts['message'], 'attachments' => $atts['attachments']]);
        }, 10, 2 );
        // `wp_loaded` HOOK
        add_action( 'wp_loaded', [$this, 'wp_loaded'] );
        // `admin_init` HOOK
        add_action('admin_init', [$this, 'admin_init']);


        // LOGIN
        // ADD ELEMENTS TO LOGIN PAGE
        add_action( 'woocommerce_login_form_start', [$this, 'html_login_mail_confirm'] );
        // CHANGE STYLE FOR LOGIN PAGE
        add_action( 'woocommerce_login_form_end', [$this, 'html_login_placeholder'] );
        // CHECK AFTER LOGIN
        add_action('wp_login', [$this,'filter_login'], 10, 2);

        // REGISTRATION PAGE SHORTCODE
        add_shortcode('mpop_register_form', [$this, 'register_sc'] );
        // PASSWORD CHANGE PAGE SHORTCODE
        // add_shortcode('mpop_password_change_form', [$this, 'password_change_sc']);
    
        // ADD MULTIPOPOLARE DASHBOARD
        add_action('admin_menu', function() {
            add_menu_page('Multipop Plugin', 'Multipop', 'edit_private_posts', 'multipop_settings', [$this, 'menu_page'], 'dashicons-fullscreen-exit-alt', 61);
        });
        // ADD USER META IN ADMIN EDIT USER PAGE
        add_action('edit_user_profile', [$this, 'add_user_meta']);
        // SAVE USER META IN ADMIN EDIT USER PAGE
        add_action('user_profile_update_errors', [$this, 'user_profile_update_errors'], 10, 3);

        // MY ACCOUNT USER PAGE
        // ADD/REMOVE MENU ITEMS TO MY ACCOUNT PAGE
        add_filter('woocommerce_account_menu_items', [$this, 'add_my_account_menu_items'], 10, 2);
        // PERSONAL CARD PAGE TITLE
        add_filter('the_title', [$this, 'filter_my_account_card_page_title'], 10, 2);
        // PERSONAL CARD PAGE CONTENT
        add_action('woocommerce_account_card_endpoint', [$this, 'my_account_card_html']);
    }

    // INITIALIZE PLUGIN
    public function init() {
        $this->disable_without_woocommerce();
        $this->get_settings();
        $this->flush_db();
        $this->update_tempmail();

        // SET `customer` role name to 'Multipopolano'
        global $wp_roles;
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        $wp_roles->roles['customer']['name'] = 'Multipopolano';
        $wp_roles->role_names['customer'] = 'Multipopolano';

        $req_url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https': 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $this->req_url = $req_url;
        $this->req_path = preg_replace('/^https?:\/\/[^\/]+/', '', $req_url);

        if ($this->current_user_is_admin()) {
            return;
        }
        // REDIRECT AFTER EMAIL CONFIRMATION
        // (IF USER CLICK ON A CONFIRMATION LINK BUT HE'S LOGGED IN WITH ANOTHER USER)
        if (
            str_starts_with($req_url, get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' ))))
            && isset($_REQUEST['mpop_mail_token'])
        ) {
            if ( !preg_match('/^[a-f0-9]{32}$/', $_REQUEST['mpop_mail_token']) ) {
                $this->location_not_found();
            }
            $user_verified = $this->verify_temp_token($_REQUEST['mpop_mail_token'], 'email_confirmation_link');
            if (!$user_verified) {
                $this->location_not_found();
            }
            $user_id = get_current_user_id();
            if ($user_id == $user_verified) {
                $mail_to_confirm = get_user_meta($user_id, 'mpop_mail_to_confirm', true);
                if ($mail_to_confirm) {
                    $this->logout_redirect($req_url);
                } else {
                    $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                    update_user_meta($user_id, 'mpop_mail_changing', false);
                    wp_redirect(get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' ))) . '?mpop_mail_confirmed=1');
                    exit;
                }
            } elseif ($user_id) {
                $this->logout_redirect($req_url);
            }
        }

        // ADD USER CARD PERSONAL PAGE ENDPOINT
        add_rewrite_endpoint( 'card', EP_ROOT | EP_PAGES );
    }

    // PLUGIN ACTIVATION TRIGGER
    public function activate() {
        if (!$this->check_woocommerce_activation()) {
            return wp_die('WooCommerce not activated!');
        }
        $this->set_db_tables();

    
        // ADD RESPONSABILE ROLE
        add_role('multipopolare_resp', 'Responsabile Multipopolare', [
            'read' => true,
            'level_0' => true
        ]);

        // ADD DYNAMIC PAGES
        add_rewrite_endpoint( 'card', EP_ROOT | EP_PAGES );
        flush_rewrite_rules();
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
                $this->add_user_notice("L'indirizzo e-mail non è ancora confermato. Controlla nella tua casella di posta per il link di conferma.", 'error', ['id'=> 'mail_not_confirmed']);
            }
        } else {
            $mail_changing = get_user_meta($user_id, 'mpop_mail_changing', true);
            if ($mail_changing) {
                $this->add_user_notice("L'indirizzo e-mail non è ancora confermato. Controlla nella tua casella di posta per il link di conferma.", 'error', ['id' => 'mail_not_confirmed']);
            }
            if (isset($_REQUEST['mpop_mail_confirmed'])) {
                $this->remove_user_notice('mail_not_confirmed');
                $this->add_user_notice("Indirizzo e-mail confermato correttamente", 'success', ['id'=> 'mail_confirmed']);
            }
        }
    }
    
    // `admin_init` TRIGGER
    public function admin_init() {
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
    private function set_db_tables() {
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
            `last_webcard_number` BIGINT,
            `master_doc_key` VARCHAR(255) NULL,
            PRIMARY KEY (`id`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        dbDelta( $q );
    
        // EMAIL CONFIRMATION TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('temp_tokens') . " (
            `id` CHAR(32) NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `expiration` BIGINT UNSIGNED NOT NULL,
            `scope` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        dbDelta( $q );
    
        // EMAIL CONFIRMATION TABLE
        $q = "CREATE TABLE IF NOT EXISTS " . $this::db_prefix('subscription_proposal') . " (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `YEAR` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        dbDelta( $q );
    
        // ADD SETTINGS ROW IF NOT EXISTS
        if (!$this->get_settings()) {
            $q = "INSERT INTO " . $this::db_prefix('plugin_settings')
                . " (`id`, `tempmail_urls`, `last_tempmail_update`, `mail_host`, `mail_port`, `mail_encryption`, `mail_username`, `mail_password`, `mail_from`, `mail_from_name`, `mail_general_notifications`, `last_webcard_number`)"
                . " VALUES (1, '" . json_encode(['block' => [], 'allow' => []]) . "', 0, '', 465, 'SMTPS', '', '', '" . get_bloginfo('admin_email') . "', '" . get_bloginfo('name') . "', '" . get_bloginfo('admin_email') . "', 0) ;";
            $wpdb->query($q);
            $this->get_settings();
        }
    }

    // CHECK IF WOOCOMMERCE IS ENABLED
    private function check_woocommerce_activation() {
        return in_array( 'woocommerce/woocommerce.php', get_option('active_plugins') );
    }

    // AUTO-DISABLE IF WOOCOMMERCE IS DISABLED
    private function disable_without_woocommerce() {
        if (!$this->check_woocommerce_activation()) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
            deactivate_plugins('multipop/multipop.php');
        }
    }

    // RETURN ECHO FROM FUNCTION NAME TO STRING
    private function html_to_string( $html_func, ...$args ) {
        ob_start();
        $html_func(...$args);
        return ob_get_clean();
    }

    // DOWLOAD FILE
    private function file_download($url, $timeout = 5) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
        $confirmation_link = get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' )));
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
        $settings['tempmail_urls'] = json_decode($settings['tempmail_urls'], true);
        $settings['last_tempmail_update'] = intval($settings['last_tempmail_update']);
        $settings['mail_port'] = intval($settings['mail_port']);
        $settings['master_doc_key'] = boolval($settings['master_doc_key']);
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
    }

    // UPDATE TEMPMAIL LIST
    private function update_tempmail($force = false) {
        if (isset( $this->settings ) && is_array( $this->settings )) {
            $last_update = date_create();
            $last_update->setTimestamp($this->settings['last_tempmail_update']);
            $last_update->add(new DateInterval('P30D'));
            if ($force || $last_update->getTimestamp() <= time()) {
                $old_list = file_get_contents(MULTIPOP_PLUGIN_PATH . 'tempmail/list.txt');
                $block_list = $old_list !== false ? preg_split('/\r\n|\r|\n/', trim($old_list)) : [];
                foreach($this->settings['tempmail_urls']['block'] as $l ) {
                    $res = $this->file_download($l);
                    if (!$res) continue;
                    $res = preg_split('/\r\n|\r|\n/', trim($res));
                    foreach($res as $i=>$b) {
                        $res[$i] = mb_strtolower(trim($b), 'UTF-8');
                    }
                    $block_list = array_unique(array_merge($block_list, array_filter($res, function($e) {return $e && !str_starts_with($e, '#');})));
                }
                $block_list = array_values( $block_list );
                foreach($this->settings['tempmail_urls']['allow'] as $l) {
                    $res = $this->file_download($l);
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
    private function add_user_notice($msg, $type = 'error', $data = []) {
        $session = WC()->session;
        if ( null === $session ) {
            return;
        }
        $all_notices = $session->get( 'wc_notices', [] );
        if (!isset($all_notices[$type])) {
            $all_notices[$type] = [];
        }
        $found = array_filter($all_notices[$type], function($n) use ($msg) {return $n['notice'] === $msg;});
        if (!count($found)) {
            $all_notices[$type][] = ['notice' => $msg, 'data' => $data];
            $session->set('wc_notices', $all_notices);
        }
    }

    // REMOVE USER NOTICE BY data['id']
    private function remove_user_notice($data_id) {
        $session = WC()->session;
        if ( null === $session ) {
            return;
        }
        $all_notices = $session->get( 'wc_notices', array() );
        foreach ($all_notices as $k=>$notice_group) {
            $all_notices[$k] = array_values(array_filter($notice_group, function ($notice) use ($data_id) {
                return !isset($notice['data']) || !is_array($notice['data']) || !isset($notice['data']['id']) || $notice['data']['id'] != $data_id;
            }));
        }
        $session->set('wc_notices', $all_notices);
    }

    // MAIN TEMPLATE REDIRECT
    // (USED TO HIDE SOME MY ACCOUNT SUB-PAGES)
    public function template_redirect() {
        global $wp;
        if (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename'] == $this->my_account_page_slug() && isset($wp->query_vars['edit-address'])) {
            $this->location_not_found();
        }
    }

    // RETUTN MY ACCOUNT PAGE SLUG
    private function my_account_page_slug() {
        $my_account_addr_arr = explode('/',preg_replace('/https?:\/\//', '', get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' )))));
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

    // CHANGE STYLE FOR LOGIN PAGE
    public function html_login_placeholder() { ?>
        <script type="text/javascript">
            Array.from(document.querySelectorAll('.woocommerce-form-login label')).forEach(e => {
                if (e.nextElementSibling.type == 'text' || e.nextElementSibling.type == 'password') {
                    e.nextElementSibling.placeholder = e.innerText.replaceAll('*', '').trim();
                    e.remove();
                }
            });
        </script>
        <?php
    }

    // CHECK AFTER LOGIN
    public function filter_login($username, $user) {
        $roles = $user->roles;
        if (count( $roles ) == 0) {
            $this->logout_redirect();
        } else if (count( $roles ) == 1 && $roles[0] == 'customer') {
            $mail_to_confirm = get_user_meta($user->ID, 'mpop_mail_to_confirm', true);
            $mail_changing = get_user_meta($user->ID, 'mpop_mail_changing', true);
            if ($mail_to_confirm || $mail_changing) {
                if (
                    isset($_REQUEST['mpop_mail_token'])
                    && preg_match('/^[a-f0-9]{32}$/', $_REQUEST['mpop_mail_token'])
                ) {
                    $user_id = $this->verify_temp_token($_REQUEST['mpop_mail_token'], 'email_confirmation_link');
                    if ($user_id == $user->ID) {
                        $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                        update_user_meta( $user->ID, 'mpop_mail_' . ($mail_to_confirm ? 'to_confirm' : 'changing'), false );
                        wp_redirect(preg_replace('/&?mpop_mail_token=[a-f0-9]{32}/', '', $this->req_url) . '&mpop_mail_confirmed=1');
                        exit;
                    } else {
                        if ($user_id) {
                            $this->delete_temp_token($_REQUEST['mpop_mail_token']);
                        }
                        $this->logout_redirect(get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' ))) . '?invalid_mpop_mail_token=1');
                    }
                } else if ($mail_to_confirm) {
                    $this->logout_redirect(get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' ))) . '?mpop_mail_not_confirmed=1');
                }
            }
        }
    }


    // REGISTRATION
    
    // REGISTRATION PAGE HTML
    private function register_form() {
        $confirmation_link = get_permalink(intval(get_option( 'woocommerce_myaccount_page_id' )));
        require( MULTIPOP_PLUGIN_PATH . '/shortcodes/register.php' );
    }
    
    // REGISTRATION PAGE SHORTCODE
    public function register_sc() {
        return $this->html_to_string( [$this, 'register_form'] );
    }


    // PASSWORD CHANGE
    // private function password_change_form() {
    //     require( MULTIPOP_PLUGIN_PATH . '/shortcodes/password-change.php' );
    // }
    // public function password_change_sc() {
    //     return $this->html_to_string( [$this, 'password_change_form'] );
    // }


    // DASHBOARD
    
    // PLUGIN SETTINGS PAGE
    public function menu_page() { 
        require(MULTIPOP_PLUGIN_PATH . '/settings.php');
    }

    // ADD USER META IN ADMIN EDIT USER PAGE
    public function add_user_meta( $user ) {
        $mail_to_confirm = get_user_meta( $user->ID, 'mpop_mail_to_confirm', true );
        $mail_changing = get_user_meta( $user->ID, 'mpop_mail_changing', true );
        $card_active = get_user_meta( $user->ID, 'mpop_card_active', true );
    ?>
        <h2>Tessera</h2>
        <table class="form-table">
            <tr>
                <th><label for="mpop_mail_to_confirm"></label> E-mail confermata</th>
                <td id="mpop_mail_to_confirm"><?= $mail_to_confirm ? 'No' : 'Sì' ?></td>
            </tr>
            <tr>
                <th>Cambio e-mail in attesa di conferma</th>
                <td><?= $mail_changing ? 'Sì - Indirizzo precedente: ' . $mail_changing : 'No' ?></td>
            </tr>
            <tr>
                <th>Tessera attiva</th>
                <td><?= $card_active ? 'Sì' : 'No' ?></td>
            </tr>
        </table>
        <input type="hidden" id="mail_confirmed" value="<?=$mail_to_confirm || $mail_changing ? '' : '1'?>" />
        <script type="text/javascript" src="<?=plugins_url()?>/multipop/js/user-edit.js"></script>
    <?php
    }

    // CHECK FIELDS WHEN USER IS EDITED BY DASHBOARD
    public function user_profile_update_errors(&$errors, $update, &$user) {
        $user->user_email = mb_strtolower(trim($user->user_email), 'UTF-8');
        $user_meta = [];
        if ($update) {
            if (!$errors->has_errors) {
                $old_user = get_user_by('ID', $user->ID);
                $old_user_meta = get_user_meta($user->ID);
                if (
                    isset($_POST['resend_mail_confirmation']) && $_POST['resend_mail_confirmation']
                    && (
                        (isset($old_user_meta['mpop_mail_to_confirm']) && $old_user_meta['mpop_mail_to_confirm'][0] )
                        || (isset($old_user_meta['mpop_mail_changing']) && $old_user_meta['mpop_mail_changing'][0])
                    )
                ) {
                    $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                    $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                    $this->send_confirmation_mail($token, $old_user->user_email);
                    $user = $old_user;
                    return;
                }
                if ($old_user->user_email !== $user->user_email) {
                    $this->delete_temp_token_by_user_id($user->ID, 'email_confirmation_link');
                }
                if (isset($_POST['email_confirmed']) && $_POST['email_confirmed']) {
                    $user_meta['mpop_mail_to_confirm'] = false;
                    $user_meta['mpop_mail_changing'] = false;
                } else {
                    if ($old_user->user_email !== $user->user_email) {
                        if ( (!isset($old_user_meta['mpop_mail_to_confirm']) || !$old_user_meta['mpop_mail_to_confirm'][0] ) && (!isset($old_user_meta['mpop_mail_changing']) || !$old_user_meta['mpop_mail_changing'][0])) {
                            $user_meta['mpop_mail_changing'] = $old_user->user_email;
                        }
                        if (isset($_POST['send_mail_confirmation']) && $_POST['send_mail_confirmation']) {
                            $token = $this->create_temp_token( $user->ID, 'email_confirmation_link' );
                            $this->send_confirmation_mail($token, $user->user_email);
                        }
                    }
                }
                foreach($user_meta as $k => $v) {
                    update_user_meta($user->ID, $k, $v);
                } 
            }
        }
    }

    
    // MY ACCOUNT USER PAGE

    // ADD/REMOVE MENU ITEMS TO MY ACCOUNT PAGE
    public function add_my_account_menu_items($items, $endpoints) {
        $new_items = [];
        $i = 0;
        foreach( $items as $k => $v ) {
            if ($i == 1) {
                $new_items['card'] = 'Tessera';
            }
            if ($k != 'edit-address') {
                $new_items[$k] = $v;
            }
            $i++;
        }
        return $new_items;
    }
    // PERSONAL CARD PAGE TITLE
    public function filter_my_account_card_page_title($title, $post_id) {
        if ( is_singular() && in_the_loop() && is_main_query() && $post_id == intval(get_option( 'woocommerce_myaccount_page_id' )) ) {
            global $wp;
            if (isset($wp->query_vars['card'])) {
                return 'Tessera';
            }
        }
        return $title;
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
        } else if (get_class($user_id) == 'WP_User') {
            $user_id = $user_id->ID;
        }
        if (!is_int($user_id) || $user_id <= 0) {
            return false;
        }
        return (bool) get_user_meta($user->ID, 'mpop_card_active', true);
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

    // CHECK IF CURRENT USER IS ADMIN
    private function current_user_is_admin() {
        $current_user = wp_get_current_user();
        if ( in_array('administrator', $current_user->roles) ){
            return $current_user->ID;
        }
        return false;
    }

    // GET LOCAL ADMIN URL (ex: /wp-admin/)
    private function get_admin_url() {
        return preg_replace('/^https?:\/\/[^\/]+/', '', get_admin_url());
    }
}

?>