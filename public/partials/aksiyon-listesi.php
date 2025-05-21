<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$current_date = '2025-05-21 06:58:32'; // UTC zaman bilgisi
$current_user_login = 'gezerronurr';
?>

<div class="bkm-container">
    <!-- Filtreler -->
    <div class="bkm-filters">
        <form id="bkm-filter-form">
            <div class="bkm-filter-grid">
                <div class="bkm-filter-group">
                    <label for="filter_kategori" class="bkm-filter-label">Kategori</label>
                    <select name="kategori" id="filter_kategori" class="bkm-filter-select">
                        <option value="">Tümü</option>
                        <?php
                        global $wpdb;
                        $kategoriler = $wpdb->get_results("
                            SELECT k.*, COUNT(a.id) as aksiyon_sayisi 
                            FROM {$wpdb->prefix}bkm_kategoriler k
                            LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON k.id = a.kategori_id
                            GROUP BY k.id
                            HAVING aksiyon_sayisi > 0
                            ORDER BY k.kategori_adi ASC
                        ");

                        foreach ($kategoriler as $kategori) {
                            echo '<option value="' . esc_attr($kategori->id) . '">' . 
                                 esc_html($kategori->kategori_adi) . ' (' . $kategori->aksiyon_sayisi . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="bkm-filter-group">
                    <label for="filter_durum" class="bkm-filter-label">Durum</label>
                    <select name="durum" id="filter_durum" class="bkm-filter-select">
                        <option value="">Tümü</option>
                        <option value="tamamlandi">Tamamlanan</option>
                        <option value="devam">Devam Eden</option>
                        <option value="geciken">Geciken</option>
                    </select>
                </div>

                <div class="bkm-filter-group">
                    <label for="filter_siralama" class="bkm-filter-label">Sıralama</label>
                    <select name="siralama" id="filter_siralama" class="bkm-filter-select">
                        <option value="son_guncelleme">Son Güncelleme</option>
                        <option value="onem_derecesi">Önem Derecesi</option>
                        <option value="hedef_tarih">Hedef Tarih</option>
                        <option value="ilerleme">İlerleme Durumu</option>
                    </select>
                </div>
            </div>

            <div class="bkm-filter-actions">
                <button type="submit" class="bkm-btn bkm-btn-primary">
                    <i class="fas fa-filter"></i> Filtrele
                </button>
                <button type="reset" class="bkm-btn bkm-btn-secondary">
                    <i class="fas fa-times"></i> Temizle
                </button>
            </div>
        </form>
    </div>

    <!-- Aksiyon Kartları -->
    <div class="bkm-cards"></div>

    <!-- Aksiyon Detay Modal -->
    <div class="bkm-modal">
        <div class="bkm-modal-content"></div>
    </div>

    <!-- Yükleniyor Göstergesi -->
    <div class="bkm-loader" style="display: none;">
        <div class="bkm-loader-content">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Yükleniyor...</span>
        </div>
    </div>

    <!-- Bildirim Alanı -->
    <div class="bkm-notifications"></div>
</div>