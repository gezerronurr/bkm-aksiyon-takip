<?php
if (!defined('ABSPATH')) {
    exit;
}

class BKM_Aksiyon_Deactivator {
    private static $current_date = '2025-05-21 07:06:22'; // UTC zaman bilgisi
    private static $current_user_login = 'gezerronurr';

    public static function deactivate() {
        // Zamanlanmış görevleri temizle
        wp_clear_scheduled_hook('bkm_aksiyon_daily_cleanup');
        wp_clear_scheduled_hook('bkm_aksiyon_weekly_report');
        
        // Geçici verileri temizle
        delete_transient('bkm_aksiyon_cache');
        delete_transient('bkm_kategori_cache');
        delete_transient('bkm_performans_cache');
        
        // Kullanıcı meta verilerini temizle
        $users = get_users(array('role__in' => array('administrator', 'editor', 'author')));
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'bkm_aksiyon_preferences');
            delete_user_meta($user->ID, 'bkm_last_viewed_aksiyon');
        }
        
        // Opsiyonel: Veritabanı tablolarını temizle
        // Not: Varsayılan olarak tabloları silmiyoruz, kullanıcı verileri kaybetmesin
        /*
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bkm_aksiyonlar");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bkm_kategoriler");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bkm_performanslar");
        */
        
        // Plugin ayarlarını temizle
        delete_option('bkm_aksiyon_version');
        delete_option('bkm_aksiyon_settings');
        
        // Deaktivasyon logunu kaydet
        self::log_deactivation();
    }

    /**
     * Deaktivasyon logunu kaydet
     */
    private static function log_deactivation() {
        $log_file = WP_CONTENT_DIR . '/bkm-aksiyon-logs/deactivation.log';
        $log_dir = dirname($log_file);

        // Log dizini yoksa oluştur
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        // Log mesajını oluştur
        $log_message = sprintf(
            "[%s] Plugin deaktive edildi. Kullanıcı: %s\n",
            self::$current_date,
            self::$current_user_login
        );

        // Logu kaydet
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}