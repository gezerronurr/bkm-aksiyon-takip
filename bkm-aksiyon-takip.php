<?php
/**
 * Plugin Name:       BKM Aksiyon Takip
 * Plugin URI:        https://example.com/bkm-aksiyon-takip
 * Description:       BKM için özel geliştirilmiş aksiyon ve görev takip sistemi
 * Version:          1.0.0
 * Author:           Onur Gezer
 * Author URI:       https://example.com
 * License:          GPL-2.0+
 * License URI:      http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:      bkm-aksiyon-takip
 * Domain Path:      /languages
 */

// Doğrudan erişimi engelle
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('BKM_AKSIYON_VERSION', '1.0.0');

/**
 * Aktivasyon ve deaktivasyon kancaları için gerekli fonksiyonlar
 */
function activate_bkm_aksiyon() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-bkm-aksiyon-activator.php';
    BKM_Aksiyon_Activator::activate();
}

function deactivate_bkm_aksiyon() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-bkm-aksiyon-deactivator.php';
    BKM_Aksiyon_Deactivator::deactivate();
}

// Dil dosyası yükleme işlemini init kancasına bağla
function bkm_aksiyon_load_textdomain() {
    load_plugin_textdomain(
        'bkm-aksiyon-takip',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('init', 'bkm_aksiyon_load_textdomain');

// Aktivasyon ve deaktivasyon kancaları
register_activation_hook(__FILE__, 'activate_bkm_aksiyon');
register_deactivation_hook(__FILE__, 'deactivate_bkm_aksiyon');

/**
 * Eklentinin ana sınıfını yükle
 */
function run_bkm_aksiyon() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-bkm-aksiyon.php';
    $plugin = new BKM_Aksiyon();
    $plugin->run();
}

// WordPress init kancasına bağla
add_action('init', 'run_bkm_aksiyon');

run_bkm_aksiyon();