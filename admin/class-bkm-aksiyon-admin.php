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

    // Aksiyon ekleme sayfası
    public function display_aksiyon_ekle_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/aksiyon-ekle.php';
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
        // Bootstrap ekle
        wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
        // DataTables ekle
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css');
    }

    // Admin script dosyalarını yükle
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $this->version, false);
        // Bootstrap JS
        wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'));
        // DataTables
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_script('datatables-bootstrap-js', 'https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js', array('datatables-js'));
    }
}