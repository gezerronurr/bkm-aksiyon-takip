<?php
if (!defined('ABSPATH')) {
    exit;
}

class BKM_Aksiyon_Public {
    private $plugin_name;
    private $version;
    private $current_date = '2025-05-21 07:01:45'; // UTC zaman bilgisi
    private $current_user_login = 'gezerronurr';

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Shortcode sınıfını başlat
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/shortcodes/aksiyon-shortcodes.php';
        new BKM_Aksiyon_Shortcodes($this->plugin_name, $this->version);
    }

    /**
     * Public scripts
     */
    public function enqueue_scripts() {
        // Styles
        wp_enqueue_style($this->plugin_name . '-public', 
            plugin_dir_url(__FILE__) . 'css/public.css', 
            array(), 
            $this->version, 
            'all'
        );

        // Scripts
        wp_enqueue_script($this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . 'js/public.js',
            array('jquery'),
            $this->version,
            true
        );
    }

    /**
     * AJAX: Aksiyonları yükle
     */
    public function load_public_aksiyonlar() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        global $wpdb;

        // Filtre parametreleri
        $kategori = isset($_POST['kategori']) ? intval($_POST['kategori']) : 0;
        $durum = isset($_POST['durum']) ? sanitize_text_field($_POST['durum']) : '';
        $siralama = isset($_POST['siralama']) ? sanitize_text_field($_POST['siralama']) : 'son_guncelleme';

        // SQL sorgusu için koşullar
        $where = array('1=1');
        $order_by = 'a.updated_at DESC';

        if ($kategori > 0) {
            $where[] = $wpdb->prepare('a.kategori_id = %d', $kategori);
        }

        switch ($durum) {
            case 'tamamlandi':
                $where[] = 'a.ilerleme_durumu = 100';
                break;
            case 'devam':
                $where[] = 'a.ilerleme_durumu < 100';
                break;
            case 'geciken':
                $where[] = 'a.ilerleme_durumu < 100 AND a.hedef_tarih < CURDATE()';
                break;
        }

        switch ($siralama) {
            case 'onem_derecesi':
                $order_by = 'a.onem_derecesi ASC, a.updated_at DESC';
                break;
            case 'hedef_tarih':
                $order_by = 'a.hedef_tarih ASC, a.updated_at DESC';
                break;
            case 'ilerleme':
                $order_by = 'a.ilerleme_durumu DESC, a.updated_at DESC';
                break;
        }

        // SQL sorgusu
        $aksiyonlar = $wpdb->get_results("
            SELECT a.*, 
                   k.kategori_adi,
                   p.performans_adi,
                   u.display_name as tanimlayan_adi
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->prefix}bkm_performanslar p ON a.performans_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.tanimlayan_id = u.ID
            WHERE " . implode(' AND ', $where) . "
            ORDER BY " . $order_by
        );

        wp_send_json_success($aksiyonlar);
    }

    /**
     * AJAX: Aksiyon detayı yükle
     */
    public function load_aksiyon_detay() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        $aksiyon_id = isset($_POST['aksiyon_id']) ? intval($_POST['aksiyon_id']) : 0;

        if ($aksiyon_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz aksiyon ID'));
        }

        global $wpdb;

        $aksiyon = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, 
                   k.kategori_adi,
                   p.performans_adi,
                   u.display_name as tanimlayan_adi,
                   GROUP_CONCAT(DISTINCT u2.display_name) as sorumlular_adi
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->prefix}bkm_performanslar p ON a.performans_id = p.id
            LEFT JOIN {$wpdb->users} u ON a.tanimlayan_id = u.ID
            LEFT JOIN {$wpdb->users} u2 ON FIND_IN_SET(u2.ID, a.sorumlular)
            WHERE a.id = %d
            GROUP BY a.id
        ", $aksiyon_id));

        if (!$aksiyon) {
            wp_send_json_error(array('message' => 'Aksiyon bulunamadı'));
        }

        wp_send_json_success($aksiyon);
    }

    /**
     * AJAX: İlerleme durumu güncelle
     */
    public function update_aksiyon_ilerleme() {
        check_ajax_referer('bkm_aksiyon_nonce', 'nonce');

        $aksiyon_id = isset($_POST['aksiyon_id']) ? intval($_POST['aksiyon_id']) : 0;
        $ilerleme_durumu = isset($_POST['ilerleme_durumu']) ? intval($_POST['ilerleme_durumu']) : 0;

        if ($aksiyon_id <= 0) {
            wp_send_json_error(array('message' => 'Geçersiz aksiyon ID'));
        }

        if ($ilerleme_durumu < 0 || $ilerleme_durumu > 100) {
            wp_send_json_error(array('message' => 'Geçersiz ilerleme durumu'));
        }

        global $wpdb;

        // Güncelleme verilerini hazırla
        $update_data = array(
            'ilerleme_durumu' => $ilerleme_durumu,
            'updated_at' => current_time('mysql')
        );

        // İlerleme %100 ise kapanma tarihini ayarla
        if ($ilerleme_durumu == 100) {
            $update_data['kapanma_tarihi'] = current_time('mysql');
        }

        // Güncelleme işlemi
        $result = $wpdb->update(
            $wpdb->prefix . 'bkm_aksiyonlar',
            $update_data,
            array('id' => $aksiyon_id),
            array('%d', '%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Güncelleme başarısız'));
        }

        wp_send_json_success(array(
            'message' => 'İlerleme durumu güncellendi',
            'ilerleme_durumu' => $ilerleme_durumu
        ));
    }

    /**
     * Public init işlemleri
     */
    public function init() {
        // AJAX actions
        add_action('wp_ajax_load_public_aksiyonlar', array($this, 'load_public_aksiyonlar'));
        add_action('wp_ajax_nopriv_load_public_aksiyonlar', array($this, 'load_public_aksiyonlar'));
        
        add_action('wp_ajax_load_aksiyon_detay', array($this, 'load_aksiyon_detay'));
        add_action('wp_ajax_nopriv_load_aksiyon_detay', array($this, 'load_aksiyon_detay'));
        
        add_action('wp_ajax_update_aksiyon_ilerleme', array($this, 'update_aksiyon_ilerleme'));
    }
}