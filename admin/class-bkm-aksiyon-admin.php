<?php
class BKM_Aksiyon_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_plugin_admin_menu() {
        // Ana menü
        add_menu_page(
            'BKM Aksiyon Takip',  // Sayfa başlığı
            'Aksiyon Takip',      // Menü başlığı
            'edit_posts',         // Gerekli yetki
            'bkm-aksiyon-takip',  // Menü slug
            array($this, 'display_plugin_admin_dashboard'), // Callback fonksiyon
            'dashicons-clipboard', // İkon
            30                    // Pozisyon
        );

        // Alt menüler
        add_submenu_page(
            'bkm-aksiyon-takip',  // Ana menü slug
            'Aksiyon Listesi',    // Sayfa başlığı
            'Aksiyon Listesi',    // Menü başlığı
            'edit_posts',         // Gerekli yetki
            'bkm-aksiyon-takip',  // Menü slug (ana menü ile aynı)
            array($this, 'display_plugin_admin_dashboard') // Callback fonksiyon
        );

        add_submenu_page(
            'bkm-aksiyon-takip',
            'Aksiyon Ekle',
            'Aksiyon Ekle',
            'edit_posts',
            'bkm-aksiyon-ekle',
            array($this, 'display_aksiyon_ekle_page')
        );

        add_submenu_page(
            'bkm-aksiyon-takip',
            'Kategoriler',
            'Kategoriler',
            'edit_posts',
            'bkm-kategoriler',
            array($this, 'display_kategoriler_page')
        );

        add_submenu_page(
            'bkm-aksiyon-takip',
            'Performanslar',
            'Performanslar',
            'edit_posts',
            'bkm-performanslar',
            array($this, 'display_performanslar_page')
        );

        add_submenu_page(
            'bkm-aksiyon-takip',
            'Raporlar',
            'Raporlar',
            'edit_posts',
            'bkm-raporlar',
            array($this, 'display_raporlar_page')
        );
    }

    public function enqueue_styles($hook) {
        if (strpos($hook, 'bkm-aksiyon') === false) {
            return;
        }

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css');
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'bkm-aksiyon') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-datepicker');
        
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
        wp_enqueue_script('flatpickr-tr', 'https://npmcdn.com/flatpickr/dist/l10n/tr.js', array('flatpickr'), null, true);
        wp_enqueue_script('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'), null, true);
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js', array('jquery'), null, true);
        wp_enqueue_script('datatables-bootstrap', 'https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js', array('datatables'), null, true);
        
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery', 'select2', 'flatpickr', 'bootstrap', 'datatables'), $this->version, true);

        wp_localize_script($this->plugin_name, 'bkm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkm_ajax_nonce')
        ));
    }

    public function display_plugin_admin_dashboard() {
        require_once plugin_dir_path(__FILE__) . 'partials/aksiyon-listele.php';
    }

    public function display_aksiyon_ekle_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/aksiyon-ekle.php';
    }

    public function display_kategoriler_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/kategoriler.php';
    }

    public function display_performanslar_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/performanslar.php';
    }

    public function display_raporlar_page() {
        require_once plugin_dir_path(__FILE__) . 'partials/raporlar.php';
    }

    public function register_ajax_handlers() {
        add_action('wp_ajax_add_aksiyon', array($this, 'handle_add_aksiyon'));
        add_action('wp_ajax_edit_aksiyon', array($this, 'handle_edit_aksiyon'));
        add_action('wp_ajax_delete_aksiyon', array($this, 'handle_delete_aksiyon'));
    }

    public function handle_add_aksiyon() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bkm_aksiyonlar';

        $data = array(
            'tanimlayan_id' => get_current_user_id(),
            'onem_derecesi' => intval($_POST['onem_derecesi']),
            'acilma_tarihi' => sanitize_text_field($_POST['acilma_tarihi']),
            'hafta' => intval(date('W', strtotime($_POST['acilma_tarihi']))),
            'kategori_id' => intval($_POST['kategori_id']),
            'performans_id' => intval($_POST['performans_id']),
            'tespit_nedeni' => sanitize_textarea_field($_POST['tespit_nedeni']),
            'aciklama' => sanitize_textarea_field($_POST['aciklama']),
            'hedef_tarih' => sanitize_text_field($_POST['hedef_tarih']),
            'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
            'notlar' => sanitize_textarea_field($_POST['notlar']),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->insert($table_name, $data);

        if ($result === false) {
            wp_send_json_error(array('message' => 'Veritabanı hatası oluştu.'));
            return;
        }

        wp_send_json_success(array(
            'message' => 'Aksiyon başarıyla eklendi.',
            'id' => $wpdb->insert_id
        ));
    }

    public function handle_edit_aksiyon() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bkm_aksiyonlar';
        $aksiyon_id = intval($_POST['aksiyon_id']);

        $data = array(
            'onem_derecesi' => intval($_POST['onem_derecesi']),
            'kategori_id' => intval($_POST['kategori_id']),
            'performans_id' => intval($_POST['performans_id']),
            'tespit_nedeni' => sanitize_textarea_field($_POST['tespit_nedeni']),
            'aciklama' => sanitize_textarea_field($_POST['aciklama']),
            'hedef_tarih' => sanitize_text_field($_POST['hedef_tarih']),
            'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
            'notlar' => sanitize_textarea_field($_POST['notlar']),
            'updated_at' => current_time('mysql')
        );

        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $aksiyon_id)
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Veritabanı hatası oluştu.'));
            return;
        }

        wp_send_json_success(array('message' => 'Aksiyon başarıyla güncellendi.'));
    }

    public function handle_delete_aksiyon() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'bkm_aksiyonlar';
        $aksiyon_id = intval($_POST['aksiyon_id']);

        $result = $wpdb->delete(
            $table_name,
            array('id' => $aksiyon_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Veritabanı hatası oluştu.'));
            return;
        }

        wp_send_json_success(array('message' => 'Aksiyon başarıyla silindi.'));
    }
}