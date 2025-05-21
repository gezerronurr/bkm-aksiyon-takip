<?php
/**
 * Plugin Name: BKM Aksiyon Takip
 * Plugin URI: https://github.com/gezerronurr/bkm-aksiyon-takip
 * Description: BKM için özel geliştirilmiş aksiyon takip sistemi.
 * Version: 1.0.0
 * Author: Onur Gezer
 * Author URI: https://github.com/gezerronurr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bkm-aksiyon-takip
 * Domain Path: /languages
 */

// Doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Plugin sabitleri
define('BKM_AKSIYON_VERSION', '1.0.0');
define('BKM_AKSIYON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BKM_AKSIYON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BKM_AKSIYON_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('BKM_AKSIYON_CURRENT_DATE', '2025-05-21 07:09:25'); // UTC zaman bilgisi
define('BKM_AKSIYON_CURRENT_USER', 'gezerronurr');

// Composer autoloader
if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Gerekli sınıfları dahil et
require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/class-bkm-aksiyon.php';
require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/class-bkm-aksiyon-activator.php';
require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/class-bkm-aksiyon-deactivator.php';

/**
 * Plugin aktif edildiğinde çalışacak fonksiyon
 */
function activate_bkm_aksiyon() {
    require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/class-bkm-aksiyon-activator.php';
    BKM_Aksiyon_Activator::activate();
}

/**
 * Plugin deaktif edildiğinde çalışacak fonksiyon
 */
function deactivate_bkm_aksiyon() {
    require_once BKM_AKSIYON_PLUGIN_DIR . 'includes/class-bkm-aksiyon-deactivator.php';
    BKM_Aksiyon_Deactivator::deactivate();
}

// Aktivasyon ve deaktivasyon kancaları
register_activation_hook(__FILE__, 'activate_bkm_aksiyon');
register_deactivation_hook(__FILE__, 'deactivate_bkm_aksiyon');

/**
 * Plugin güncelleme kontrolü
 */
function bkm_check_version() {
    if (get_site_option('bkm_aksiyon_version') != BKM_AKSIYON_VERSION) {
        BKM_Aksiyon_Activator::activate();
        update_site_option('bkm_aksiyon_version', BKM_AKSIYON_VERSION);
    }
}
add_action('plugins_loaded', 'bkm_check_version');

/**
 * Plugin yükleme hatası kontrolü
 */
function bkm_check_php_version() {
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(BKM_AKSIYON_PLUGIN_BASENAME);
        wp_die(
            'Bu plugin PHP 7.4 veya üstü bir sürüm gerektirir. ' .
            'Lütfen PHP sürümünüzü güncelleyin veya hosting sağlayıcınızla iletişime geçin.',
            'Plugin Aktivasyon Hatası',
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'bkm_check_php_version');

/**
 * Plugin çalışma zamanı hata kontrolü
 */
function bkm_check_wp_version() {
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' .
                 'BKM Aksiyon Takip plugini WordPress 5.0 veya üstü bir sürüm gerektirir. ' .
                 'Lütfen WordPress\'inizi güncelleyin.' .
                 '</p></div>';
        });
        return;
    }
}
add_action('admin_init', 'bkm_check_wp_version');

/**
 * Plugin için gerekli dosyaları yükle
 */
function bkm_load_textdomain() {
    load_plugin_textdomain(
        'bkm-aksiyon-takip',
        false,
        dirname(BKM_AKSIYON_PLUGIN_BASENAME) . '/languages/'
    );
}
add_action('plugins_loaded', 'bkm_load_textdomain');

/**
 * Plugin ana sınıfını başlat
 */
function run_bkm_aksiyon() {
    $plugin = new BKM_Aksiyon();
    $plugin->run();
}

// Plugin'i çalıştır
run_bkm_aksiyon();

/**
 * Ayar bağlantısı ekle
 */
function bkm_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=bkm-aksiyon-takip">' . 
                     __('Ayarlar', 'bkm-aksiyon-takip') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . BKM_AKSIYON_PLUGIN_BASENAME, 'bkm_add_settings_link');

/**
 * Debug modu için log fonksiyonu
 */
function bkm_log($message) {
    if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } else {
            error_log($message);
        }
    }
}