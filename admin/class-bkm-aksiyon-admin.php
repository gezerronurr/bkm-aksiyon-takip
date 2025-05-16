<?php
class BKM_Aksiyon_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    // Admin menülerini oluştur
    public function add_plugin_admin_menu() {
        // Ana menü
        add_menu_page(
            'BKM Aksiyon Takip', // Sayfa başlığı
            'Aksiyon Takip', // Menü başlığı
            'edit_posts', // Gerekli yetki (editör ve üstü)
            'bkm-aksiyon-takip', // Menü slug
            array($this, 'display_plugin_admin_dashboard'), // Fonksiyon
            'dashicons-clipboard', // İkon
            30 // Pozisyon
        );

        // Alt menüler
        add_submenu_page(
            'bkm-aksiyon-takip', // Ana menü slug
            'Aksiyon Ekle', // Sayfa başlığı
            'Aksiyon Ekle', // Menü başlığı
            'edit_posts', // Gerekli yetki
            'bkm-aksiyon-ekle', // Menü slug
            array($this, 'display_aksiyon_ekle_page') // Fonksiyon
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

    // Ana dashboard sayfası
    public function display_plugin_admin_dashboard() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/aksiyon-listele.php';
    }

    public function display_aksiyon_ekle_page() {
    // Sayfa başlığını ekle
    add_action('admin_head', function() {
        echo '<h2>Aksiyon Ekle</h2>';
    });

    // Gerekli stil ve script dosyalarını yükle
    wp_enqueue_style('bkm-admin-css', plugin_dir_url(__FILE__) . 'css/admin.css');
    wp_enqueue_script('bkm-admin-js', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '', true);

    // Ajax URL'sini JavaScript'e aktar
    wp_localize_script('bkm-admin-js', 'bkm_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bkm_aksiyon_nonce')
    ));

    // Partial dosyasını yükle
    require_once plugin_dir_path(__FILE__) . 'partials/aksiyon-ekle.php';
}

// Ajax işleyicilerini ekle
public function register_ajax_handlers() {
    add_action('wp_ajax_add_aksiyon', array($this, 'handle_add_aksiyon'));
    add_action('wp_ajax_edit_aksiyon', array($this, 'handle_edit_aksiyon'));
}

// Yeni aksiyon ekleme işleyicisi
public function handle_add_aksiyon() {
    check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bkm_aksiyonlar';

    // Form verilerini al ve temizle
    $data = array(
        'tanimlayan_id' => intval($_POST['tanimlayan_id']),
        'onem_derecesi' => intval($_POST['onem_derecesi']),
        'acilma_tarihi' => sanitize_text_field($_POST['acilma_tarihi']),
        'hafta' => intval($_POST['hafta']),
        'kategori_id' => intval($_POST['kategori_id']),
        'performans_id' => intval($_POST['performans_id']),
        'tespit_nedeni' => sanitize_textarea_field($_POST['tespit_nedeni']),
        'aciklama' => sanitize_textarea_field($_POST['aciklama']),
        'hedef_tarih' => sanitize_text_field($_POST['hedef_tarih']),
        'kapanma_tarihi' => !empty($_POST['kapanma_tarihi']) ? sanitize_text_field($_POST['kapanma_tarihi']) : null,
        'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
        'notlar' => sanitize_textarea_field($_POST['notlar']),
        'sorumlular' => isset($_POST['sorumlular']) ? implode(',', array_map('intval', $_POST['sorumlular'])) : '',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );

    // Veritabanına ekle
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

// Aksiyon düzenleme işleyicisi
public function handle_edit_aksiyon() {
    check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bkm_aksiyonlar';
    $aksiyon_id = intval($_POST['aksiyon_id']);

    // Form verilerini al ve temizle
    $data = array(
        'tanimlayan_id' => intval($_POST['tanimlayan_id']),
        'onem_derecesi' => intval($_POST['onem_derecesi']),
        'acilma_tarihi' => sanitize_text_field($_POST['acilma_tarihi']),
        'hafta' => intval($_POST['hafta']),
        'kategori_id' => intval($_POST['kategori_id']),
        'performans_id' => intval($_POST['performans_id']),
        'tespit_nedeni' => sanitize_textarea_field($_POST['tespit_nedeni']),
        'aciklama' => sanitize_textarea_field($_POST['aciklama']),
        'hedef_tarih' => sanitize_text_field($_POST['hedef_tarih']),
        'kapanma_tarihi' => !empty($_POST['kapanma_tarihi']) ? sanitize_text_field($_POST['kapanma_tarihi']) : null,
        'ilerleme_durumu' => intval($_POST['ilerleme_durumu']),
        'notlar' => sanitize_textarea_field($_POST['notlar']),
        'sorumlular' => isset($_POST['sorumlular']) ? implode(',', array_map('intval', $_POST['sorumlular'])) : '',
        'updated_at' => current_time('mysql')
    );

    // Veritabanını güncelle
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

    // Kategoriler sayfası
    public function display_kategoriler_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/kategoriler.php';
    }

    // Performanslar sayfası
    public function display_performanslar_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/performanslar.php';
    }

    // Raporlar sayfası
    public function display_raporlar_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/raporlar.php';
    }

    // Admin stil dosyalarını yükle
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
// Select2 CSS
    wp_enqueue_style(
        'select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css'
    );        
 // Flatpickr CSS
    wp_enqueue_style(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
    );
// Bootstrap ekle
        wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        // DataTables ekle
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css');
wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    }

    // Admin script dosyalarını yükle
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $this->version, false);
        // Bootstrap JS
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'));
        // DataTables
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_script('datatables-bootstrap-js', 'https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js', array('datatables-js'));
// Select2 JS
    wp_enqueue_script(
        'select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
        array('jquery'),
        null,
        true
    );

    // Flatpickr JS
    wp_enqueue_script(
        'flatpickr',
        'https://cdn.jsdelivr.net/npm/flatpickr',
        array('jquery'),
        null,
        true
    );

    // Flatpickr Türkçe dil desteği
    wp_enqueue_script(
        'flatpickr-tr',
        'https://npmcdn.com/flatpickr/dist/l10n/tr.js',
        array('flatpickr'),
        null,
        true
    );

    // Admin JS
    wp_enqueue_script(
        'bkm-admin-js',
        plugin_dir_url(__FILE__) . 'js/admin.js',
        array('jquery', 'select2', 'flatpickr'),
        $this->version,
        true
    );
}    
}
