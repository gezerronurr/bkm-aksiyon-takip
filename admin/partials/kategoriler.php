<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();
$current_date = '2025-05-21 08:54:19'; // UTC zaman bilgisi
$current_user_login = 'gezerronurr';

// Yetki kontrolü
if (!current_user_can('edit_posts')) {
    wp_die(__('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'bkm-aksiyon-takip'));
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __('Kategoriler', 'bkm-aksiyon-takip'); ?></h1>
    <hr class="wp-header-end">

    <?php
    // Başarı mesajı gösterimi
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Kategori başarıyla güncellendi.', 'bkm-aksiyon-takip') . 
             '</p></div>';
    }
    ?>

    <div class="bkm-container">
        <div class="bkm-grid-2">
            <!-- Kategori Ekleme/Düzenleme Formu -->
            <div class="bkm-card">
                <div class="bkm-card-header">
                    <h2 id="kategori_form_title" class="bkm-card-title">
                        <?php echo __('Yeni Kategori Ekle', 'bkm-aksiyon-takip'); ?>
                    </h2>
                    <p class="bkm-card-subtitle">
                        <?php echo __('Yeni kategori ekleyebilir veya mevcut kategorileri düzenleyebilirsiniz.', 'bkm-aksiyon-takip'); ?>
                    </p>
                </div>
                <form id="bkm-kategori-form" class="bkm-form" method="post">
                    <?php wp_nonce_field('bkm_kategori_nonce', 'bkm_nonce'); ?>
                    <input type="hidden" id="kategori_id" name="kategori_id" value="">
                    
                    <div class="form-group">
                        <label for="kategori_adi" class="form-label required">
                            <?php echo __('Kategori Adı', 'bkm-aksiyon-takip'); ?>
                        </label>
                        <input type="text" id="kategori_adi" name="kategori_adi" 
                               class="form-control" required 
                               placeholder="<?php echo __('Kategori adını giriniz', 'bkm-aksiyon-takip'); ?>">
                        <div class="form-hint">
                            <?php echo __('Kategori adı benzersiz olmalıdır.', 'bkm-aksiyon-takip'); ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="kategori_submit" class="bkm-btn primary">
                            <i class="fas fa-save"></i> <?php echo __('Ekle', 'bkm-aksiyon-takip'); ?>
                        </button>
                        <button type="button" id="kategori_cancel" class="bkm-btn secondary">
                            <i class="fas fa-times"></i> <?php echo __('İptal', 'bkm-aksiyon-takip'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Kategori Listesi -->
            <div class="bkm-card">
                <div class="bkm-card-header">
                    <h2 class="bkm-card-title">
                        <?php echo __('Kategori Listesi', 'bkm-aksiyon-takip'); ?>
                        <span class="badge kategori-count">0</span>
                    </h2>
                    <p class="bkm-card-subtitle">
                        <?php echo __('Mevcut kategoriler ve kullanım durumları.', 'bkm-aksiyon-takip'); ?>
                    </p>
                </div>
                <div class="bkm-table-responsive">
                    <table id="kategoriler-table" class="bkm-table">
                        <thead>
                            <tr>
                                <th><?php echo __('Kategori Adı', 'bkm-aksiyon-takip'); ?></th>
                                <th><?php echo __('Kullanım', 'bkm-aksiyon-takip'); ?></th>
                                <th><?php echo __('Oluşturma Tarihi', 'bkm-aksiyon-takip'); ?></th>
                                <th><?php echo __('Son Güncelleme', 'bkm-aksiyon-takip'); ?></th>
                                <th class="text-center"><?php echo __('İşlemler', 'bkm-aksiyon-takip'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $kategoriler = $wpdb->get_results("
                                SELECT k.*, COUNT(a.id) as kullanim_sayisi 
                                FROM {$wpdb->prefix}bkm_kategoriler k
                                LEFT JOIN {$wpdb->prefix}bkm_aksiyonlar a ON k.id = a.kategori_id
                                GROUP BY k.id
                                ORDER BY k.kategori_adi ASC
                            ");

                            if ($kategoriler) {
                                foreach ($kategoriler as $kategori) {
                                    ?>
                                    <tr data-id="<?php echo $kategori->id; ?>">
                                        <td><?php echo esc_html($kategori->kategori_adi); ?></td>
                                        <td>
                                            <span class="badge <?php echo $kategori->kullanim_sayisi > 0 ? 'badge-info' : 'badge-secondary'; ?>">
                                                <?php echo $kategori->kullanim_sayisi; ?> kullanım
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($kategori->created_at)) {
                                                echo date('d.m.Y H:i', strtotime($kategori->created_at));
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($kategori->updated_at)) {
                                                echo date('d.m.Y H:i', strtotime($kategori->updated_at));
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="bkm-button-group">
                                                <button type="button" 
                                                        class="bkm-btn primary kategori-duzenle-btn" 
                                                        data-id="<?php echo $kategori->id; ?>"
                                                        title="<?php echo __('Düzenle', 'bkm-aksiyon-takip'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($kategori->kullanim_sayisi == 0): ?>
                                                    <button type="button" 
                                                            class="bkm-btn danger kategori-sil-btn" 
                                                            data-id="<?php echo $kategori->id; ?>"
                                                            data-name="<?php echo esc_attr($kategori->kategori_adi); ?>"
                                                            title="<?php echo __('Sil', 'bkm-aksiyon-takip'); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" 
                                                            class="bkm-btn secondary" 
                                                            disabled
                                                            title="<?php echo __('Bu kategori kullanımda olduğu için silinemez', 'bkm-aksiyon-takip'); ?>">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">' . __('Kayıt bulunamadı', 'bkm-aksiyon-takip') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yükleniyor Göstergesi -->
<div class="bkm-loader" style="display: none;">
    <div class="bkm-loader-content">
        <i class="fas fa-spinner fa-spin"></i>
        <span><?php echo __('Yükleniyor...', 'bkm-aksiyon-takip'); ?></span>
    </div>
</div>