<?php
class BKM_Aksiyon_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Aksiyonlar tablosu
        $table_aksiyonlar = $wpdb->prefix . 'bkm_aksiyonlar';
        $sql_aksiyonlar = "CREATE TABLE IF NOT EXISTS $table_aksiyonlar (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tanimlayan_id bigint(20) NOT NULL,
            onem_derecesi int(1) NOT NULL,
            acilma_tarihi datetime NOT NULL,
            hafta int(2) NOT NULL,
            kategori_id bigint(20) NOT NULL,
            tespit_nedeni text NOT NULL,
            aciklama text NOT NULL,
            hedef_tarih datetime NOT NULL,
            kapanma_tarihi datetime DEFAULT NULL,
            performans_id bigint(20) NOT NULL,
            ilerleme_durumu int(3) NOT NULL DEFAULT 0,
            notlar text,
            sorumlular text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Görevler tablosu
        $table_gorevler = $wpdb->prefix . 'bkm_gorevler';
        $sql_gorevler = "CREATE TABLE IF NOT EXISTS $table_gorevler (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            aksiyon_id bigint(20) NOT NULL,
            icerik text NOT NULL,
            baslangic_tarihi datetime NOT NULL,
            sorumlu_id bigint(20) NOT NULL,
            hedef_bitis_tarihi datetime NOT NULL,
            ilerleme_durumu int(3) NOT NULL DEFAULT 0,
            gercek_bitis_tarihi datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Kategoriler tablosu
        $table_kategoriler = $wpdb->prefix . 'bkm_kategoriler';
        $sql_kategoriler = "CREATE TABLE IF NOT EXISTS $table_kategoriler (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            kategori_adi varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Performanslar tablosu
        $table_performanslar = $wpdb->prefix . 'bkm_performanslar';
        $sql_performanslar = "CREATE TABLE IF NOT EXISTS $table_performanslar (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            performans_adi varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_aksiyonlar);
        dbDelta($sql_gorevler);
        dbDelta($sql_kategoriler);
        dbDelta($sql_performanslar);

        // Örnek verileri ekle
        self::add_default_data();
    }

    public static function add_default_data() {
        global $wpdb;
        
        // Kategoriler için örnek veriler
        $kategoriler = array(
            'Yazılım Geliştirme',
            'Sistem Yönetimi',
            'Güvenlik',
            'Veritabanı',
            'Network',
            'Destek'
        );

        $kategori_table = $wpdb->prefix . 'bkm_kategoriler';
        foreach ($kategoriler as $kategori) {
            $wpdb->insert(
                $kategori_table,
                array(
                    'kategori_adi' => $kategori,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s')
            );
        }

        // Performanslar için örnek veriler
        $performanslar = array(
            'Çok İyi',
            'İyi',
            'Orta',
            'Düşük',
            'Çok Düşük'
        );

        $performans_table = $wpdb->prefix . 'bkm_performanslar';
        foreach ($performanslar as $performans) {
            $wpdb->insert(
                $performans_table,
                array(
                    'performans_adi' => $performans,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s')
            );
        }
    }
}