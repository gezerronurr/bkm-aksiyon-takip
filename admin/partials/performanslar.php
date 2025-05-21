<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();
$current_date = '2025-05-21 06:52:33'; // UTC zaman bilgisi
$current_user_login = 'gezerronurr';

// Yetki kontrolü
if (!current_user_can('edit_posts')) {
    wp_die(__('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'bkm-aksiyon-takip'));
}
?>

<div class="wrap">
    <!-- Header -->
    <div class="bkm-header">
        <div class="header-left">
            <h1>Performanslar</h1>
            <p>Aksiyon performans değerlerini yönetin</p>
        </div>
        <div class="header-actions">
            <button type="button" class="bkm-btn btn-primary" data-toggle="modal" data-target="#performansModal">
                <i class="fas fa-plus"></i> Yeni Performans
            </button>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stats-container">
        <?php
        // Toplam performans sayısı
        $total_performanslar = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bkm_performanslar");
        
        // En çok kullanılan performans
        $en_cok_kullanilan = $wpdb->get_row("
            SELECT p.*, COUNT(a.id) as aksiyon_sayisi 
            FROM {$wpdb->prefix}bkm_performanslar p
            LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON p.id = a.performans_id
            GROUP BY p.id
            ORDER BY aksiyon_sayisi DESC
            LIMIT 1
        ");
        
        // Ortalama performans
        $ortalama_performans = $wpdb->get_var("
            SELECT AVG(aksiyon_sayisi) as ortalama FROM (
                SELECT COUNT(a.id) as aksiyon_sayisi 
                FROM {$wpdb->prefix}bkm_performanslar p
                LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON p.id = a.performans_id
                GROUP BY p.id
            ) as performans_stats
        ");
        ?>
        
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-value"><?php echo $total_performanslar; ?></div>
            <div class="stat-label">Toplam Performans Değeri</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-value">
                <?php echo $en_cok_kullanilan ? esc_html($en_cok_kullanilan->performans_adi) : '-'; ?>
            </div>
            <div class="stat-label">En Çok Kullanılan Performans</div>
            <?php if ($en_cok_kullanilan): ?>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i> <?php echo $en_cok_kullanilan->aksiyon_sayisi; ?> Aksiyon
                </div>
            <?php endif; ?>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calculator"></i></div>
            <div class="stat-value"><?php echo number_format($ortalama_performans, 1); ?></div>
            <div class="stat-label">Ortalama Aksiyon/Performans</div>
        </div>
    </div>

    <!-- Performans Tablosu -->
    <div class="form-container">
        <table id="performanslar-table" class="bkm-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Performans Adı</th>
                    <th>Aksiyon Sayısı</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>Son Kullanım</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $performanslar = $wpdb->get_results("
                    SELECT p.*, 
                           COUNT(a.id) as aksiyon_sayisi,
                           MAX(a.created_at) as son_kullanim
                    FROM {$wpdb->prefix}bkm_performanslar p
                    LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON p.id = a.performans_id
                    GROUP BY p.id
                    ORDER BY p.id DESC
                ");

                foreach ($performanslar as $performans):
                    $aksiyon_sayisi = intval($performans->aksiyon_sayisi);
                    ?>
                    <tr>
                        <td>#<?php echo $performans->id; ?></td>
                        <td><?php echo esc_html($performans->performans_adi); ?></td>
                        <td>
                            <span class="status-badge <?php echo $aksiyon_sayisi > 0 ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $aksiyon_sayisi; ?> Aksiyon
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($performans->created_at)); ?></td>
                        <td>
                            <?php 
                            if ($performans->son_kullanim) {
                                echo date('d.m.Y H:i', strtotime($performans->son_kullanim));
                            } else {
                                echo '<span class="text-muted">Henüz kullanılmadı</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button type="button" 
                                        class="bkm-btn btn-info btn-sm edit-performans" 
                                        data-id="<?php echo $performans->id; ?>"
                                        data-name="<?php echo esc_attr($performans->performans_adi); ?>"
                                        title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($aksiyon_sayisi == 0): ?>
                                    <button type="button" 
                                            class="bkm-btn btn-danger btn-sm delete-performans" 
                                            data-id="<?php echo $performans->id; ?>"
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

<!-- Performans Modal -->
<div class="modal fade" id="performansModal" tabindex="-1" role="dialog" aria-labelledby="performansModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="performansModalLabel">Yeni Performans</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="performans-form">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_performans">
                    <input type="hidden" name="performans_id" id="performans_id" value="">
                    <?php wp_nonce_field('bkm_performans_nonce', 'bkm_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="performans_adi" class="form-label required">Performans Adı</label>
                        <input type="text" name="performans_adi" id="performans_adi" class="form-control" required>
                        <div class="invalid-feedback">Performans adı gereklidir</div>
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