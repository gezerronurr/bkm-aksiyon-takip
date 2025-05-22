<?php
/**
 * Plugin Name: BKM Aksiyon Takip
 * Plugin URI: https://github.com/gezerronurr/bkm-aksiyon-takip
 * Description: BKM için özel olarak geliştirilmiş aksiyon takip sistemi.
 * Version: 1.0.0
 * Author: Onur Gezer
 * Author URI: https://github.com/gezerronurr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bkm-aksiyon-takip
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class BKM_Aksiyon_Takip {
    private static $instance = null;
    private $admin_pages = [];
    private $current_date = '2025-05-21 10:02:37'; // UTC zaman bilgisi
    private $current_user_login = 'gezerronurr';

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }
    private function define_constants() {
        define('BKM_AKSIYON_VERSION', '1.0.0');
        define('BKM_AKSIYON_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('BKM_AKSIYON_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);

        // AJAX handlers
        add_action('wp_ajax_save_aksiyon', [$this, 'handle_save_aksiyon']);
        add_action('wp_ajax_delete_aksiyon', [$this, 'handle_delete_aksiyon']);
        add_action('wp_ajax_load_aksiyonlar', [$this, 'handle_load_aksiyonlar']);
        add_action('wp_ajax_auto_save_aksiyon', [$this, 'handle_auto_save_aksiyon']);
        add_action('wp_ajax_load_aksiyon_detay', [$this, 'handle_load_aksiyon_detay']);
        add_action('wp_ajax_export_aksiyonlar', [$this, 'handle_export_aksiyonlar']);

        // Kategori AJAX handlers
        add_action('wp_ajax_save_kategori', [$this, 'handle_save_kategori']);
        add_action('wp_ajax_delete_kategori', [$this, 'handle_delete_kategori']);
        add_action('wp_ajax_load_kategoriler', [$this, 'handle_load_kategoriler']);

        // Performans AJAX handlers
        add_action('wp_ajax_save_performans', [$this, 'handle_save_performans']);
        add_action('wp_ajax_delete_performans', [$this, 'handle_delete_performans']);
        add_action('wp_ajax_load_performanslar', [$this, 'handle_load_performanslar']);
        
        // Görev AJAX handlers
        add_action('wp_ajax_save_gorev', [$this, 'handle_save_gorev']);
        add_action('wp_ajax_delete_gorev', [$this, 'handle_delete_gorev']);
        add_action('wp_ajax_load_gorevler', [$this, 'handle_load_gorevler']);
        add_action('wp_ajax_complete_gorev', [$this, 'handle_complete_gorev']);
        add_action('wp_ajax_load_gorev_detay', [$this, 'handle_load_gorev_detay']);
    }
    public function add_admin_menu() {
        // Ana menü
        $main_hook = add_menu_page(
            __('Aksiyon Takip', 'bkm-aksiyon-takip'),
            __('Aksiyon Takip', 'bkm-aksiyon-takip'),
            'edit_posts',
            'bkm-aksiyon-takip',
            [$this, 'render_main_page'],
            'dashicons-clipboard',
            30
        );

        // Tüm Aksiyonlar (ana sayfa olarak)
        $list_hook = add_submenu_page(
            'bkm-aksiyon-takip',
            __('Tüm Aksiyonlar', 'bkm-aksiyon-takip'),
            __('Tüm Aksiyonlar', 'bkm-aksiyon-takip'),
            'edit_posts',
            'bkm-aksiyon-takip',
            [$this, 'render_main_page']
        );

        // Yeni Aksiyon
        $new_hook = add_submenu_page(
            'bkm-aksiyon-takip',
            __('Yeni Aksiyon', 'bkm-aksiyon-takip'),
            __('Yeni Aksiyon', 'bkm-aksiyon-takip'),
            'edit_posts',
            'bkm-aksiyon-ekle',
            [$this, 'render_new_page']
        );

        // Kategoriler
        $kategoriler_hook = add_submenu_page(
            'bkm-aksiyon-takip',
            __('Kategoriler', 'bkm-aksiyon-takip'),
            __('Kategoriler', 'bkm-aksiyon-takip'),
            'edit_posts',
            'bkm-aksiyon-kategoriler',
            [$this, 'render_kategoriler_page']
        );

        // Performanslar
        $performanslar_hook = add_submenu_page(
            'bkm-aksiyon-takip',
            __('Performanslar', 'bkm-aksiyon-takip'),
            __('Performanslar', 'bkm-aksiyon-takip'),
            'edit_posts',
            'bkm-aksiyon-performanslar',
            [$this, 'render_performanslar_page']
        );

        // Ayarlar
        $ayarlar_hook = add_submenu_page(
            'bkm-aksiyon-takip',
            __('Ayarlar', 'bkm-aksiyon-takip'),
            __('Ayarlar', 'bkm-aksiyon-takip'),
            'manage_options',
            'bkm-aksiyon-ayarlar',
            [$this, 'render_ayarlar_page']
        );

        // Hook'ları sakla
        $this->admin_pages = [
            'main' => $main_hook,
            'list' => $list_hook,
            'new' => $new_hook,
            'kategoriler' => $kategoriler_hook,
            'performanslar' => $performanslar_hook,
            'ayarlar' => $ayarlar_hook
        ];
    }
    public function enqueue_admin_assets($hook) {
        // Eklenti sayfalarını kontrol et
        $plugin_pages = array_values($this->admin_pages);
        if (!in_array($hook, $plugin_pages)) {
            return;
        }

        // Styles
        wp_enqueue_style(
            'bkm-admin-css',
            BKM_AKSIYON_PLUGIN_URL . 'admin/css/admin.css',
            [],
            BKM_AKSIYON_VERSION
        );

        wp_enqueue_style(
            'select2-css',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            [],
            '4.1.0-rc.0'
        );

        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );

        // Scripts
        wp_enqueue_script(
            'select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            ['jquery'],
            '4.1.0-rc.0',
            true
        );

        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr',
            ['jquery'],
            '4.6.13',
            true
        );

        wp_enqueue_script(
            'flatpickr-tr-js',
            'https://npmcdn.com/flatpickr/dist/l10n/tr.js',
            ['flatpickr-js'],
            '4.6.13',
            true
        );

        // Font Awesome
        wp_enqueue_style(
            'fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            [],
            '5.15.4'
        );

        // Admin scripts
        wp_register_script(
            'bkm-admin-js',
            BKM_AKSIYON_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'select2-js', 'flatpickr-js'],
            BKM_AKSIYON_VERSION,
            true
        );

        // Sayfalara özel scriptler
        if ($hook === $this->admin_pages['main'] || $hook === $this->admin_pages['list']) {
            wp_enqueue_script('bkm-admin-js');
        } elseif ($hook === $this->admin_pages['new']) {
            wp_enqueue_script('bkm-admin-js');
        } elseif ($hook === $this->admin_pages['kategoriler']) {
            wp_enqueue_script(
                'bkm-kategoriler-js',
                BKM_AKSIYON_PLUGIN_URL . 'admin/js/kategoriler.js',
                ['jquery'],
                BKM_AKSIYON_VERSION,
                true
            );
        } elseif ($hook === $this->admin_pages['performanslar']) {
            wp_enqueue_script(
                'bkm-performanslar-js',
                BKM_AKSIYON_PLUGIN_URL . 'admin/js/performanslar.js',
                ['jquery'],
                BKM_AKSIYON_VERSION,
                true
            );
        }

        // Localize script
        wp_localize_script('bkm-admin-js', 'bkm_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkm_admin_nonce'),
            'current_user' => $this->current_user_login,
            'current_date' => $this->current_date,
            'i18n' => [
                'confirmDelete' => __('Bu kaydı silmek istediğinize emin misiniz?', 'bkm-aksiyon-takip'),
                'errorMessage' => __('Bir hata oluştu', 'bkm-aksiyon-takip'),
                'successMessage' => __('İşlem başarılı', 'bkm-aksiyon-takip'),
                'loading' => __('Yükleniyor...', 'bkm-aksiyon-takip'),
                'noResults' => __('Sonuç bulunamadı', 'bkm-aksiyon-takip')
            ]
        ]);
    }
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'bkm-public-css',
            BKM_AKSIYON_PLUGIN_URL . 'public/css/public.css',
            [],
            BKM_AKSIYON_VERSION
        );

        wp_enqueue_script(
            'bkm-public-js',
            BKM_AKSIYON_PLUGIN_URL . 'public/js/public.js',
            ['jquery'],
            BKM_AKSIYON_VERSION,
            true
        );

        wp_localize_script('bkm-public-js', 'bkm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkm_public_nonce')
        ]);
    }

    public function activate() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Aksiyonlar tablosu
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkm_aksiyonlar (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tanimlayan_id bigint(20) NOT NULL,
            onem_derecesi tinyint(1) NOT NULL,
            acilma_tarihi date NOT NULL,
            hafta int(2) NOT NULL,
            kategori_id bigint(20) NOT NULL,
            sorumlular varchar(255) NOT NULL,
            tespit_nedeni text NOT NULL,
            aciklama text NOT NULL,
            hedef_tarih date NOT NULL,
            kapanma_tarihi date DEFAULT NULL,
            performans_id bigint(20) NOT NULL,
            ilerleme_durumu tinyint(3) NOT NULL DEFAULT 0,
            notlar text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tanimlayan_id (tanimlayan_id),
            KEY kategori_id (kategori_id),
            KEY performans_id (performans_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Kategoriler tablosu
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkm_kategoriler (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            kategori_adi varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY kategori_adi (kategori_adi)
        ) $charset_collate;";
        dbDelta($sql);

        // Performanslar tablosu
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkm_performanslar (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            performans_adi varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY performans_adi (performans_adi)
        ) $charset_collate;";
        dbDelta($sql);

        // Log tablosu
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bkm_log (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            aksiyon_id bigint(20) DEFAULT NULL,
            action varchar(50) NOT NULL,
            description text NOT NULL,
            ip_address varchar(45) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY aksiyon_id (aksiyon_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Varsayılan kategorileri ekle
        $default_kategoriler = [
            'Yazılım',
            'Donanım',
            'Network',
            'Güvenlik',
            'Veritabanı'
        ];

        foreach ($default_kategoriler as $kategori) {
            $wpdb->insert(
                "{$wpdb->prefix}bkm_kategoriler",
                ['kategori_adi' => $kategori],
                ['%s']
            );
        }

        // Varsayılan performansları ekle
        $default_performanslar = [
            'Çok İyi',
            'İyi',
            'Orta',
            'Kötü',
            'Çok Kötü'
        ];

        foreach ($default_performanslar as $performans) {
            $wpdb->insert(
                "{$wpdb->prefix}bkm_performanslar",
                ['performans_adi' => $performans],
                ['%s']
            );
        }

        // Aktivasyon zamanını kaydet
        update_option('bkm_aksiyon_activation_time', current_time('mysql'));

        // Varsayılan ayarları kaydet
        $default_settings = [
            'auto_save_interval' => 30,
            'items_per_page' => 10,
            'email_notifications' => true,
            'reminder_days' => 3
        ];
        update_option('bkm_aksiyon_settings', $default_settings);

        // Yetki tanımlamaları
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_bkm_aksiyonlar');
            $admin_role->add_cap('edit_bkm_aksiyonlar');
            $admin_role->add_cap('delete_bkm_aksiyonlar');
            $admin_role->add_cap('view_bkm_aksiyonlar');
        }

        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('edit_bkm_aksiyonlar');
            $editor_role->add_cap('view_bkm_aksiyonlar');
        }

        flush_rewrite_rules();
    }
    public function deactivate() {
        // Yetkileri kaldır
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('manage_bkm_aksiyonlar');
            $admin_role->remove_cap('edit_bkm_aksiyonlar');
            $admin_role->remove_cap('delete_bkm_aksiyonlar');
            $admin_role->remove_cap('view_bkm_aksiyonlar');
        }

        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->remove_cap('edit_bkm_aksiyonlar');
            $editor_role->remove_cap('view_bkm_aksiyonlar');
        }

        flush_rewrite_rules();
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'bkm-aksiyon-takip',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    public function render_main_page() {
        require_once BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/aksiyon-listele.php';
    }

    public function render_new_page() {
        $aksiyon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        require_once BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/aksiyon-ekle.php';
    }

    public function render_kategoriler_page() {
        require_once BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/kategoriler.php';
    }

    public function render_performanslar_page() {
        require_once BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/performanslar.php';
    }

    public function render_ayarlar_page() {
        require_once BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/raporlar.php';
    }

    // AJAX Handlers
    public function handle_save_aksiyon() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $aksiyon_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $data = [
            'tanimlayan_id' => get_current_user_id(),
            'onem_derecesi' => intval($_POST['onem_derecesi']),
            'acilma_tarihi' => sanitize_text_field($_POST['acilma_tarihi']),
            'hafta' => intval($_POST['hafta']),
            'kategori_id' => intval($_POST['kategori_id']),
            'sorumlular' => sanitize_text_field(implode(',', $_POST['sorumlular'])),
            'tespit_nedeni' => sanitize_textarea_field($_POST['tespit_nedeni']),
            'aciklama' => sanitize_textarea_field($_POST['aciklama']),
            'hedef_tarih' => sanitize_text_field($_POST['hedef_tarih']),
            'kapanma_tarihi' => !empty($_POST['kapanma_tarihi']) ? sanitize_text_field($_POST['kapanma_tarihi']) : null,
            'performans_id' => intval($_POST['performans_id']),
            'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
            'notlar' => sanitize_textarea_field($_POST['notlar'])
        ];

        if ($aksiyon_id > 0) {
            $result = $wpdb->update(
                "{$wpdb->prefix}bkm_aksiyonlar",
                $data,
                ['id' => $aksiyon_id],
                array_fill(0, count($data), '%s'),
                ['%d']
            );
        } else {
            $result = $wpdb->insert(
                "{$wpdb->prefix}bkm_aksiyonlar",
                $data,
                array_fill(0, count($data), '%s')
            );
            $aksiyon_id = $wpdb->insert_id;
        }

        if ($result === false) {
            wp_send_json_error(['message' => __('Veritabanı hatası oluştu', 'bkm-aksiyon-takip')]);
        }

        // Log kaydı
        $this->log_action(
            $aksiyon_id,
            'save',
            sprintf(
                __('Aksiyon %s', 'bkm-aksiyon-takip'),
                $aksiyon_id > 0 ? 'güncellendi' : 'oluşturuldu'
            )
        );

        wp_send_json_success([
            'message' => __('Aksiyon başarıyla kaydedildi', 'bkm-aksiyon-takip'),
            'aksiyon_id' => $aksiyon_id,
            'redirect_url' => admin_url('admin.php?page=bkm-aksiyon-takip')
        ]);
    }
    public function handle_delete_aksiyon() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $aksiyon_id = intval($_POST['aksiyon_id']);

        $result = $wpdb->delete(
            "{$wpdb->prefix}bkm_aksiyonlar",
            ['id' => $aksiyon_id],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Veritabanı hatası oluştu', 'bkm-aksiyon-takip')]);
        }

        // Log kaydı
        $this->log_action(
            $aksiyon_id,
            'delete',
            __('Aksiyon silindi', 'bkm-aksiyon-takip')
        );

        wp_send_json_success(['message' => __('Aksiyon başarıyla silindi', 'bkm-aksiyon-takip')]);
    }

    public function handle_load_aksiyonlar() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = get_option('posts_per_page', 10);
        $offset = ($page - 1) * $per_page;

        // WHERE koşullarını oluştur
        $where = [];
        $where_values = [];

        if (!empty($filters['kategori'])) {
            $where[] = 'a.kategori_id = %d';
            $where_values[] = intval($filters['kategori']);
        }

        if (!empty($filters['durum'])) {
            switch ($filters['durum']) {
                case 'acik':
                    $where[] = 'a.kapanma_tarihi IS NULL';
                    break;
                case 'kapali':
                    $where[] = 'a.kapanma_tarihi IS NOT NULL';
                    break;
                case 'geciken':
                    $where[] = 'a.hedef_tarih < CURDATE() AND a.kapanma_tarihi IS NULL';
                    break;
            }
        }

        if (!empty($filters['hafta'])) {
            $where[] = 'a.hafta = %d';
            $where_values[] = intval($filters['hafta']);
        }

        // WHERE clause oluştur
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Toplam kayıt sayısı
        $total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}bkm_aksiyonlar a $where_clause";
        $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));

        // Aksiyonları getir
        $query = "
            SELECT 
                a.*,
                k.kategori_adi,
                p.performans_adi,
                GROUP_CONCAT(DISTINCT u.display_name) as sorumlu_isimler,
                t.display_name as tanimlayan_isim
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->prefix}bkm_performanslar p ON a.performans_id = p.id
            LEFT JOIN {$wpdb->users} u ON FIND_IN_SET(u.ID, a.sorumlular)
            LEFT JOIN {$wpdb->users} t ON a.tanimlayan_id = t.ID
            $where_clause
            GROUP BY a.id
            ORDER BY a.created_at DESC
            LIMIT %d OFFSET %d
        ";

        $aksiyonlar = $wpdb->get_results(
            $wpdb->prepare(
                $query,
                array_merge($where_values, [$per_page, $offset])
            )
        );

        ob_start();
        if ($aksiyonlar) {
            foreach ($aksiyonlar as $aksiyon) {
                include BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/aksiyon-row.php';
            }
        } else {
            echo '<tr><td colspan="8" class="text-center">' . __('Kayıt bulunamadı', 'bkm-aksiyon-takip') . '</td></tr>';
        }
        $html = ob_get_clean();

        $total_pages = ceil($total / $per_page);

        wp_send_json_success([
            'html' => $html,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total
            ]
        ]);
    }
    public function handle_auto_save_aksiyon() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        $aksiyon_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($aksiyon_id === 0) {
            wp_send_json_error(['message' => __('Geçersiz aksiyon ID', 'bkm-aksiyon-takip')]);
        }

        $this->handle_save_aksiyon();
    }

    public function handle_load_aksiyon_detay() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $aksiyon_id = intval($_POST['aksiyon_id']);

        $aksiyon = $wpdb->get_row($wpdb->prepare("
            SELECT 
                a.*,
                k.kategori_adi,
                p.performans_adi,
                GROUP_CONCAT(DISTINCT u.display_name) as sorumlu_isimler,
                t.display_name as tanimlayan_isim
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->prefix}bkm_performanslar p ON a.performans_id = p.id
            LEFT JOIN {$wpdb->users} u ON FIND_IN_SET(u.ID, a.sorumlular)
            LEFT JOIN {$wpdb->users} t ON a.tanimlayan_id = t.ID
            WHERE a.id = %d
            GROUP BY a.id
        ", $aksiyon_id));

        if (!$aksiyon) {
            wp_send_json_error(['message' => __('Aksiyon bulunamadı', 'bkm-aksiyon-takip')]);
        }

        ob_start();
        include BKM_AKSIYON_PLUGIN_DIR . 'admin/partials/aksiyon-detay.php';
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function handle_export_aksiyonlar() {
        check_ajax_referer('bkm_admin_nonce', 'nonce');

        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $filters = isset($_POST['filters']) ? $_POST['filters'] : [];

        // WHERE koşullarını oluştur
        $where = [];
        $where_values = [];

        if (!empty($filters['kategori'])) {
            $where[] = 'a.kategori_id = %d';
            $where_values[] = intval($filters['kategori']);
        }

        if (!empty($filters['durum'])) {
            switch ($filters['durum']) {
                case 'acik':
                    $where[] = 'a.kapanma_tarihi IS NULL';
                    break;
                case 'kapali':
                    $where[] = 'a.kapanma_tarihi IS NOT NULL';
                    break;
                case 'geciken':
                    $where[] = 'a.hedef_tarih < CURDATE() AND a.kapanma_tarihi IS NULL';
                    break;
            }
        }

        if (!empty($filters['hafta'])) {
            $where[] = 'a.hafta = %d';
            $where_values[] = intval($filters['hafta']);
        }

        // WHERE clause oluştur
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Aksiyonları getir
        $query = "
            SELECT 
                a.*,
                k.kategori_adi,
                p.performans_adi,
                GROUP_CONCAT(DISTINCT u.display_name) as sorumlu_isimler,
                t.display_name as tanimlayan_isim
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->prefix}bkm_performanslar p ON a.performans_id = p.id
            LEFT JOIN {$wpdb->users} u ON FIND_IN_SET(u.ID, a.sorumlular)
            LEFT JOIN {$wpdb->users} t ON a.tanimlayan_id = t.ID
            $where_clause
            GROUP BY a.id
            ORDER BY a.created_at DESC
        ";

        $aksiyonlar = $wpdb->get_results(
            $wpdb->prepare($query, $where_values)
        );
        if (!$aksiyonlar) {
            wp_send_json_error(['message' => __('Dışa aktarılacak kayıt bulunamadı', 'bkm-aksiyon-takip')]);
        }

        // Excel dosyası oluştur
        require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Başlıkları ayarla
        $headers = [
            'ID',
            __('Tanımlayan', 'bkm-aksiyon-takip'),
            __('Önem Derecesi', 'bkm-aksiyon-takip'),
            __('Açılma Tarihi', 'bkm-aksiyon-takip'),
            __('Hafta', 'bkm-aksiyon-takip'),
            __('Kategori', 'bkm-aksiyon-takip'),
            __('Sorumlular', 'bkm-aksiyon-takip'),
            __('Tespit Nedeni', 'bkm-aksiyon-takip'),
            __('Açıklama', 'bkm-aksiyon-takip'),
            __('Hedef Tarih', 'bkm-aksiyon-takip'),
            __('Kapanma Tarihi', 'bkm-aksiyon-takip'),
            __('Performans', 'bkm-aksiyon-takip'),
            __('İlerleme (%)', 'bkm-aksiyon-takip'),
            __('Notlar', 'bkm-aksiyon-takip')
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        // Verileri ekle
        $row = 2;
        foreach ($aksiyonlar as $aksiyon) {
            $sheet->fromArray([
                $aksiyon->id,
                $aksiyon->tanimlayan_isim,
                $this->get_onem_derecesi_text($aksiyon->onem_derecesi),
                $aksiyon->acilma_tarihi,
                $aksiyon->hafta,
                $aksiyon->kategori_adi,
                $aksiyon->sorumlu_isimler,
                $aksiyon->tespit_nedeni,
                $aksiyon->aciklama,
                $aksiyon->hedef_tarih,
                $aksiyon->kapanma_tarihi,
                $aksiyon->performans_adi,
                $aksiyon->ilerleme_durumu,
                $aksiyon->notlar
            ], NULL, "A{$row}");
            $row++;
        }

        // Stil ayarları
        $styleArray = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'],
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($styleArray);

        // Sütun genişliklerini otomatik ayarla
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Dosyayı kaydet
        $file_name = 'aksiyonlar-' . date('Y-m-d-His') . '.xlsx';
        $file_path = wp_upload_dir()['path'] . '/' . $file_name;
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file_path);

        wp_send_json_success([
            'message' => __('Dışa aktarma başarılı', 'bkm-aksiyon-takip'),
            'download_url' => wp_upload_dir()['url'] . '/' . $file_name
        ]);
    }

    // Yardımcı fonksiyonlar
    private function get_onem_derecesi_text($derece) {
        switch ($derece) {
            case 1:
                return __('Yüksek', 'bkm-aksiyon-takip');
            case 2:
                return __('Orta', 'bkm-aksiyon-takip');
            case 3:
                return __('Düşük', 'bkm-aksiyon-takip');
            default:
                return '';
        }
    }

    private function log_action($aksiyon_id, $action, $description) {
        global $wpdb;

        $data = [
            'user_id' => get_current_user_id(),
            'aksiyon_id' => $aksiyon_id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];

        $wpdb->insert(
            "{$wpdb->prefix}bkm_log",
            $data,
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    public function handle_save_gorev() {
        check_ajax_referer('bkm_aksiyon_takipx_nonce', 'gorev_nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $gorev_id = isset($_POST['gorev_id']) ? intval($_POST['gorev_id']) : 0;
        $aksiyon_id = isset($_POST['aksiyon_id']) ? intval($_POST['aksiyon_id']) : 0;
        
        if ($aksiyon_id <= 0) {
            wp_send_json_error(['message' => __('Geçersiz aksiyon ID', 'bkm-aksiyon-takip')]);
        }

        $data = [
            'aksiyon_id' => $aksiyon_id,
            'icerik' => sanitize_textarea_field($_POST['gorev_icerik']),
            'baslangic_tarihi' => sanitize_text_field($_POST['baslangic_tarihi']),
            'sorumlu_id' => intval($_POST['sorumlu_id']),
            'hedef_bitis_tarihi' => sanitize_text_field($_POST['hedef_bitis_tarihi']),
            'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
            'updated_at' => current_time('mysql')
        ];

        if ($gorev_id > 0) {
            // Mevcut görevi güncelle
            $result = $wpdb->update(
                "{$wpdb->prefix}bkm_gorevler",
                $data,
                ['id' => $gorev_id],
                array_fill(0, count($data), '%s'),
                ['%d']
            );
        } else {
            // Yeni görev ekle
            $data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                "{$wpdb->prefix}bkm_gorevler",
                $data,
                array_fill(0, count($data), '%s')
            );
            $gorev_id = $wpdb->insert_id;
        }

        if ($result === false) {
            wp_send_json_error(['message' => __('Veritabanı hatası oluştu', 'bkm-aksiyon-takip')]);
        }

        // Görev sahibine email gönder
        $this->send_gorev_notification_email($gorev_id, $gorev_id > 0 ? 'update' : 'create');

        wp_send_json_success([
            'message' => __('Görev başarıyla kaydedildi', 'bkm-aksiyon-takip'),
            'gorev_id' => $gorev_id
        ]);
    }

    public function handle_delete_gorev() {
        check_ajax_referer('bkm_aksiyon_takipx_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $gorev_id = intval($_POST['gorev_id']);

        $result = $wpdb->delete(
            "{$wpdb->prefix}bkm_gorevler",
            ['id' => $gorev_id],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Veritabanı hatası oluştu', 'bkm-aksiyon-takip')]);
        }

        wp_send_json_success(['message' => __('Görev başarıyla silindi', 'bkm-aksiyon-takip')]);
    }

    public function handle_load_gorevler() {
        check_ajax_referer('bkm_aksiyon_takipx_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Giriş yapmalısınız', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $aksiyon_id = intval($_POST['aksiyon_id']);
        
        if ($aksiyon_id <= 0) {
            wp_send_json_error(['message' => __('Geçersiz aksiyon ID', 'bkm-aksiyon-takip')]);
        }

        $gorevler = $wpdb->get_results($wpdb->prepare("
            SELECT g.*, u.display_name as sorumlu_adi
            FROM {$wpdb->prefix}bkm_gorevler g
            LEFT JOIN {$wpdb->users} u ON g.sorumlu_id = u.ID
            WHERE g.aksiyon_id = %d
            ORDER BY g.created_at DESC
        ", $aksiyon_id));

        ob_start();
        if ($gorevler && count($gorevler) > 0) {
            echo '<div class="bkm-gorevler-container">';
            echo '<table class="bkm-table gorevler-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . __('Görev İçeriği', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('Başlangıç', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('Sorumlu', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('Hedef Bitiş', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('İlerleme', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('Gerçek Bitiş', 'bkm-aksiyon-takip') . '</th>';
            echo '<th>' . __('İşlemler', 'bkm-aksiyon-takip') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($gorevler as $gorev) {
                $is_completed = $gorev->ilerleme_durumu >= 100;
                $is_delayed = !$is_completed && strtotime($gorev->hedef_bitis_tarihi) < time();
                $is_owner = $gorev->sorumlu_id == get_current_user_id();
                $can_edit = current_user_can('edit_posts') || $is_owner;
                
                $row_class = $is_completed ? 'completed-row' : ($is_delayed ? 'delayed-row' : '');
                
                echo '<tr class="' . $row_class . '" data-id="' . $gorev->id . '">';
                echo '<td>' . esc_html($gorev->icerik) . '</td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($gorev->baslangic_tarihi)) . '</td>';
                echo '<td>' . esc_html($gorev->sorumlu_adi) . '</td>';
                echo '<td>' . date_i18n(get_option('date_format'), strtotime($gorev->hedef_bitis_tarihi)) . '</td>';
                
                echo '<td>';
                echo '<div class="progress-bar-container">';
                echo '<div class="progress-bar" style="width: ' . $gorev->ilerleme_durumu . '%"></div>';
                echo '<span class="progress-text">' . $gorev->ilerleme_durumu . '%</span>';
                echo '</div>';
                echo '</td>';
                
                echo '<td>' . ($gorev->gercek_bitis_tarihi ? date_i18n(get_option('date_format'), strtotime($gorev->gercek_bitis_tarihi)) : '-') . '</td>';
                
                echo '<td class="actions">';
                if ($can_edit && !$is_completed) {
                    echo '<button class="bkm-btn small edit-gorev-btn" data-id="' . $gorev->id . '" title="' . __('Düzenle', 'bkm-aksiyon-takip') . '"><i class="fas fa-edit"></i></button>';
                    echo '<button class="bkm-btn small success complete-gorev-btn" data-id="' . $gorev->id . '" title="' . __('Tamamla', 'bkm-aksiyon-takip') . '"><i class="fas fa-check"></i></button>';
                }
                if (current_user_can('edit_posts')) {
                    echo '<button class="bkm-btn small danger delete-gorev-btn" data-id="' . $gorev->id . '" title="' . __('Sil', 'bkm-aksiyon-takip') . '"><i class="fas fa-trash"></i></button>';
                }
                echo '</td>';
                
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo '<div class="bkm-no-results">';
            echo '<p>' . __('Bu aksiyona ait görev bulunamadı.', 'bkm-aksiyon-takip') . '</p>';
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    public function handle_complete_gorev() {
        check_ajax_referer('bkm_aksiyon_takipx_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Giriş yapmalısınız', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $gorev_id = intval($_POST['gorev_id']);
        
        // Görev bilgilerini al
        $gorev = $wpdb->get_row($wpdb->prepare("
            SELECT g.*, a.id as aksiyon_id, a.sorumlular
            FROM {$wpdb->prefix}bkm_gorevler g
            LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON g.aksiyon_id = a.id
            WHERE g.id = %d
        ", $gorev_id));
        
        if (!$gorev) {
            wp_send_json_error(['message' => __('Görev bulunamadı', 'bkm-aksiyon-takip')]);
        }
        
        // Yetki kontrolü
        $current_user_id = get_current_user_id();
        $can_complete = current_user_can('edit_posts') || $gorev->sorumlu_id == $current_user_id;
        
        if (!$can_complete) {
            wp_send_json_error(['message' => __('Bu görevi tamamlamaya yetkiniz yok', 'bkm-aksiyon-takip')]);
        }

        // Görevi tamamla
        $result = $wpdb->update(
            "{$wpdb->prefix}bkm_gorevler",
            [
                'ilerleme_durumu' => 100,
                'gercek_bitis_tarihi' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ],
            ['id' => $gorev_id],
            ['%d', '%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => __('Veritabanı hatası oluştu', 'bkm-aksiyon-takip')]);
        }

        // Email gönder
        $this->send_gorev_notification_email($gorev_id, 'complete');

        wp_send_json_success(['message' => __('Görev başarıyla tamamlandı', 'bkm-aksiyon-takip')]);
    }

    public function handle_load_gorev_detay() {
        check_ajax_referer('bkm_aksiyon_takipx_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Giriş yapmalısınız', 'bkm-aksiyon-takip')]);
        }

        global $wpdb;

        $gorev_id = intval($_POST['gorev_id']);
        
        $gorev = $wpdb->get_row($wpdb->prepare("
            SELECT g.*, u.display_name as sorumlu_adi
            FROM {$wpdb->prefix}bkm_gorevler g
            LEFT JOIN {$wpdb->users} u ON g.sorumlu_id = u.ID
            WHERE g.id = %d
        ", $gorev_id));
        
        if (!$gorev) {
            wp_send_json_error(['message' => __('Görev bulunamadı', 'bkm-aksiyon-takip')]);
        }

        wp_send_json_success($gorev);
    }

    private function send_gorev_notification_email($gorev_id, $action_type) {
        global $wpdb;
        
        $gorev = $wpdb->get_row($wpdb->prepare("
            SELECT g.*, u.display_name as sorumlu_adi, u.user_email as sorumlu_email,
                   a.id as aksiyon_id, a.tespit_nedeni as aksiyon_konu
            FROM {$wpdb->prefix}bkm_gorevler g
            LEFT JOIN {$wpdb->users} u ON g.sorumlu_id = u.ID
            LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON g.aksiyon_id = a.id
            WHERE g.id = %d
        ", $gorev_id));
        
        if (!$gorev || empty($gorev->sorumlu_email)) {
            return false;
        }
        
        $current_user = wp_get_current_user();
        $site_name = get_bloginfo('name');
        $subject = '';
        $message = '';
        
        switch ($action_type) {
            case 'create':
                $subject = sprintf(__('[%s] Size Yeni Görev Atandı', 'bkm-aksiyon-takip'), $site_name);
                $message = sprintf(
                    __('Merhaba %s,<br><br>%s tarafından size yeni bir görev atandı.<br><br>Görev Detayları:<br>Görev İçeriği: %s<br>Başlangıç Tarihi: %s<br>Hedef Bitiş Tarihi: %s<br>İlgili Aksiyon: %s<br><br>Görevlerinizi görüntülemek için sisteme giriş yapabilirsiniz.<br><br>Bu e-posta otomatik olarak gönderilmiştir, lütfen yanıtlamayınız.', 'bkm-aksiyon-takip'),
                    $gorev->sorumlu_adi,
                    $current_user->display_name,
                    $gorev->icerik,
                    date_i18n(get_option('date_format'), strtotime($gorev->baslangic_tarihi)),
                    date_i18n(get_option('date_format'), strtotime($gorev->hedef_bitis_tarihi)),
                    $gorev->aksiyon_konu
                );
                break;
                
            case 'update':
                $subject = sprintf(__('[%s] Görev Güncellendi', 'bkm-aksiyon-takip'), $site_name);
                $message = sprintf(
                    __('Merhaba %s,<br><br>%s tarafından göreviniz güncellendi.<br><br>Güncel Görev Detayları:<br>Görev İçeriği: %s<br>Başlangıç Tarihi: %s<br>Hedef Bitiş Tarihi: %s<br>İlgili Aksiyon: %s<br><br>Görevlerinizi görüntülemek için sisteme giriş yapabilirsiniz.<br><br>Bu e-posta otomatik olarak gönderilmiştir, lütfen yanıtlamayınız.', 'bkm-aksiyon-takip'),
                    $gorev->sorumlu_adi,
                    $current_user->display_name,
                    $gorev->icerik,
                    date_i18n(get_option('date_format'), strtotime($gorev->baslangic_tarihi)),
                    date_i18n(get_option('date_format'), strtotime($gorev->hedef_bitis_tarihi)),
                    $gorev->aksiyon_konu
                );
                break;
                
            case 'complete':
                $subject = sprintf(__('[%s] Görev Tamamlandı', 'bkm-aksiyon-takip'), $site_name);
                $message = sprintf(
                    __('Merhaba %s,<br><br>Aşağıdaki göreviniz tamamlandı olarak işaretlendi.<br><br>Görev Detayları:<br>Görev İçeriği: %s<br>Başlangıç Tarihi: %s<br>Hedef Bitiş Tarihi: %s<br>Gerçek Bitiş Tarihi: %s<br>İlgili Aksiyon: %s<br><br>Görevlerinizi görüntülemek için sisteme giriş yapabilirsiniz.<br><br>Bu e-posta otomatik olarak gönderilmiştir, lütfen yanıtlamayınız.', 'bkm-aksiyon-takip'),
                    $gorev->sorumlu_adi,
                    $gorev->icerik,
                    date_i18n(get_option('date_format'), strtotime($gorev->baslangic_tarihi)),
                    date_i18n(get_option('date_format'), strtotime($gorev->hedef_bitis_tarihi)),
                    date_i18n(get_option('date_format'), strtotime($gorev->gercek_bitis_tarihi)),
                    $gorev->aksiyon_konu
                );
                break;
        }
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($gorev->sorumlu_email, $subject, $message, $headers);
    }
}

// Eklentiyi başlat
function bkm_aksiyon_takip() {
    return BKM_Aksiyon_Takip::instance();
}

bkm_aksiyon_takip();