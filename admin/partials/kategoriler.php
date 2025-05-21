<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();
$current_date = '2025-05-21 06:51:24'; // UTC zaman bilgisi

// Yetki kontrolü
if (!current_user_can('edit_posts')) {
    wp_die(__('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'bkm-aksiyon-takip'));
}
?>

<div class="wrap">
    <!-- Header -->
    <div class="bkm-header">
        <div class="header-left">
            <h1>Kategoriler</h1>
            <p>Aksiyon kategorilerini yönetin</p>
        </div>
        <div class="header-actions">
            <button type="button" class="bkm-btn btn-primary" data-toggle="modal" data-target="#kategoriModal">
                <i class="fas fa-plus"></i> Yeni Kategori
            </button>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stats-container">
        <?php
        // Toplam kategori sayısı
        $total_kategoriler = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkm_kategoriler");
        
        // En çok kullanılan kategori
        $en_cok_kullanilan = $wpdb->get_row("
            SELECT k.*, COUNT(a.id) as aksiyon_sayisi 
            FROM {$wpdb->prefix}bkm_kategoriler k
            LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON k.id = a.kategori_id
            GROUP BY k.id
            ORDER BY aksiyon_sayisi DESC
            LIMIT 1
        ");
        
        // Atanmış aksiyonlar
        $atanmis_aksiyonlar = $wpdb->get_var("
            SELECT COUNT(DISTINCT a.id) 
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            WHERE a.kategori_id IN (SELECT id FROM {$wpdb->prefix}bkm_kategoriler)
        ");
        ?>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-folder"></i></div>
            <div class="stat-value"><?php echo $total_kategoriler; ?></div>
            <div class="stat-label">Toplam Kategori</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-value">
                <?php echo $en_cok_kullanilan ? esc_html($en_cok_kullanilan->kategori_adi) : '-'; ?>
            </div>
            <div class="stat-label">En Çok Kullanılan Kategori</div>
            <?php if ($en_cok_kullanilan): ?>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i> <?php echo $en_cok_kullanilan->aksiyon_sayisi; ?> Aksiyon
                </div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tasks"></i></div>
            <div class="stat-value"><?php echo $atanmis_aksiyonlar; ?></div>
            <div class="stat-label">Atanmış Aksiyon</div>
        </div>
    </div>

    <!-- Kategori Tablosu -->
    <div class="form-container">
        <table id="kategoriler-table" class="bkm-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kategori Adı</th>
                    <th>Aksiyon Sayısı</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $kategoriler = $wpdb->get_results("
                    SELECT k.*, COUNT(a.id) as aksiyon_sayisi 
                    FROM {$wpdb->prefix}bkm_kategoriler k
                    LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON k.id = a.kategori_id
                    GROUP BY k.id
                    ORDER BY k.id DESC
                ");

                foreach ($kategoriler as $kategori):
                    $aksiyon_sayisi = intval($kategori->aksiyon_sayisi);
                    ?>
                    <tr>
                        <td>#<?php echo $kategori->id; ?></td>
                        <td><?php echo esc_html($kategori->kategori_adi); ?></td>
                        <td>
                            <span class="status-badge <?php echo $aksiyon_sayisi > 0 ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $aksiyon_sayisi; ?> Aksiyon
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($kategori->created_at)); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" 
                                        class="bkm-btn btn-info btn-sm edit-kategori" 
                                        data-id="<?php echo $kategori->id; ?>"
                                        data-name="<?php echo esc_attr($kategori->kategori_adi); ?>"
                                        title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($aksiyon_sayisi == 0): ?>
                                    <button type="button" 
                                            class="bkm-btn btn-danger btn-sm delete-kategori" 
                                            data-id="<?php echo $kategori->id; ?>"
                                            title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Kategori Modal -->
<div class="modal fade" id="kategoriModal" tabindex="-1" role="dialog" aria-labelledby="kategoriModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kategoriModalLabel">Yeni Kategori</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="kategori-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_kategori">
                    <input type="hidden" name="kategori_id" id="kategori_id" value="">
                    <?php wp_nonce_field('bkm_kategori_nonce', 'bkm_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="kategori_adi" class="form-label required">Kategori Adı</label>
                        <input type="text" name="kategori_adi" id="kategori_adi" class="form-control" required>
                        <div class="invalid-feedback">Kategori adı gereklidir</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="bkm-btn btn-secondary" data-dismiss="modal">İptal</button>
                    <button type="submit" class="bkm-btn btn-primary">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>