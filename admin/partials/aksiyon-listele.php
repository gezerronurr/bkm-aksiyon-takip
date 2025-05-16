<?php
// Direkt erişimi engelle
if (!defined('WPINC')) {
    die;
}
$current_user = wp_get_current_user();
?>
    
    <div class="bkm-header">
        <div class="header-left">
            <h1>BKM Aksiyon Takip Sistemi</h1>
            <p>Hoş geldin, <?php echo esc_html(wp_get_current_user()->display_name); ?></p>
        </div>

    <!-- Stats -->
    <div class="stats-container">
        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <i class="fas fa-clock fa-lg"></i>
            </div>
            <div class="stat-value" data-value="<?php echo $pending_count; ?>">
                <?php echo $pending_count; ?>
            </div>
            <div class="stat-label">Bekleyen Aksiyonlar</div>
            <div class="stat-trend trend-up">
                <i class="fas fa-arrow-up"></i>
                12% artış
            </div>
        </div>

        <div class="stat-card stat-completed">
            <div class="stat-icon">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div class="stat-value" data-value="<?php echo $completed_count; ?>">
                <?php echo $completed_count; ?>
            </div>
            <div class="stat-label">Tamamlanan Aksiyonlar</div>
            <div class="stat-trend trend-up">
                <i class="fas fa-arrow-up"></i>
                8% artış
            </div>
        </div>

        <div class="stat-card stat-urgent">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle fa-lg"></i>
            </div>
            <div class="stat-value" data-value="<?php echo $urgent_count; ?>">
                <?php echo $urgent_count; ?>
            </div>
            <div class="stat-label">Acil Aksiyonlar</div>
            <div class="stat-trend trend-down">
                <i class="fas fa-arrow-down"></i>
                5% azalış
            </div>
        </div>

        <div class="stat-card stat-mytasks">
            <div class="stat-icon">
                <i class="fas fa-user-clock fa-lg"></i>
            </div>
            <div class="stat-value" data-value="<?php echo $my_tasks; ?>">
                <?php echo $my_tasks; ?>
            </div>
            <div class="stat-label">Benim Aksiyonlarım</div>
            <div class="stat-trend trend-neutral">
                <i class="fas fa-minus"></i>
                Değişim yok
            </div>
        </div>
    </div>

    <!-- Filters -->
<div class="view-options">
    <button class="view-btn active" data-view="table">
        <i class="fas fa-table"></i>
        Tablo Görünümü
    </button>
        <div class="header-actions">
            <button class="bkm-btn btn-primary" id="new-action-btn">
                <i class="fas fa-plus"></i>
                Yeni Aksiyon
            </button>
            <button class="bkm-btn btn-secondary" id="export-btn">
                <i class="fas fa-download"></i>
                Rapor İndir
            </button>
        </div>
    </div>
    <div class="filter-section">
        <div class="filter-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Aksiyonlarda ara...">
            </div>
            <div class="filter-group">
                <select class="bkm-select" id="kategori-filter">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($kategoriler as $kategori): ?>
                        <option value="<?php echo esc_attr($kategori->id); ?>">
                            <?php echo esc_html($kategori->kategori_adi); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="bkm-select" id="onem-filter">
                    <option value="">Önem Derecesi</option>
                    <option value="1">Yüksek</option>
                    <option value="2">Orta</option>
                    <option value="3">Düşük</option>
                </select>
                <select class="bkm-select" id="durum-filter">
                    <option value="">Durum</option>
                    <option value="aktif">Devam Eden</option>
                    <option value="tamamlandi">Tamamlanan</option>
                </select>
                <button class="bkm-btn btn-secondary" id="clear-filters">
                    <i class="fas fa-undo"></i>
                    Filtreleri Temizle
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="bkm-table" id="aksiyonlar-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Başlık</th>
                    <th>Önem</th>
                    <th>Kategori</th>
                    <th>Sorumlular</th>
                    <th>Hedef Tarih</th>
                    <th>İlerleme</th>
                    <th>Durum</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <!-- Tablo içeriği dinamik olarak doldurulacak -->
            </tbody>
        </table>
    </div>
</div>
<div class="bkm-container">
    <!-- Header -->
<!-- Üst kısma eklenecek -->
<div class="header-stats">
    <div class="quick-stat">
        <i class="fas fa-chart-line"></i>
        <span>Bu ay: 45 yeni aksiyon</span>
    </div>
    <div class="quick-stat">
        <i class="fas fa-clock"></i>
        <span>Ortalama tamamlanma: 5 gün</span>
    </div>
</div>

