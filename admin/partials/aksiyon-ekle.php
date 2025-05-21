<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$current_user_id = get_current_user_id();
$current_date = '2025-05-21 08:13:20'; // UTC zaman bilgisi
$current_user_login = 'gezerronurr';

// Yetki kontrolü
if (!current_user_can('edit_posts')) {
    wp_die(__('Bu sayfaya erişim yetkiniz bulunmamaktadır.', 'bkm-aksiyon-takip'));
}

// Aksiyon ID kontrolü
$aksiyon_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$aksiyon = null;

if ($aksiyon_id > 0) {
    $aksiyon = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bkm_aksiyonlar WHERE id = %d",
        $aksiyon_id
    ));
}

// Kategori listesi
$kategoriler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_kategoriler ORDER BY kategori_adi ASC");

// Performans listesi
$performanslar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_performanslar ORDER BY performans_adi ASC");

// Kullanıcı listesi
$users = get_users(['role__in' => ['administrator', 'editor', 'author']]);

// Form başlığı
$page_title = $aksiyon_id > 0 ? __('Aksiyon Düzenle', 'bkm-aksiyon-takip') : __('Yeni Aksiyon', 'bkm-aksiyon-takip');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $page_title; ?></h1>
    
    <?php if ($aksiyon_id > 0): ?>
    <a href="<?php echo admin_url('admin.php?page=bkm-aksiyon-takip&action=new'); ?>" class="page-title-action">
        <?php _e('Yeni Ekle', 'bkm-aksiyon-takip'); ?>
    </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php
    // Başarı mesajı gösterimi
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             __('Aksiyon başarıyla güncellendi.', 'bkm-aksiyon-takip') . 
             '</p></div>';
    }
    ?>

    <form id="bkm-aksiyon-form" class="bkm-form" method="post" data-id="<?php echo $aksiyon_id; ?>">
        <?php wp_nonce_field('bkm_aksiyon_nonce', 'bkm_nonce'); ?>
        
        <div class="form-grid">
            <!-- Aksiyonu Tanımlayan -->
            <div class="form-group">
                <label for="tanimlayan_id" class="form-label required">
                    <?php echo __('Aksiyonu Tanımlayan', 'bkm-aksiyon-takip'); ?>
                </label>
                <select name="tanimlayan_id" id="tanimlayan_id" class="form-control select2" required>
                    <option value=""><?php echo __('Seçiniz', 'bkm-aksiyon-takip'); ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo esc_attr($user->ID); ?>" 
                                <?php selected($aksiyon ? $aksiyon->tanimlayan_id : $current_user_id, $user->ID); ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sıra No (Otomatik ID) -->
            <div class="form-group">
                <label class="form-label">
                    <?php echo __('Sıra No', 'bkm-aksiyon-takip'); ?>
                </label>
                <input type="text" class="form-control" value="<?php 
                    echo $aksiyon_id > 0 ? $aksiyon_id : 
                        $wpdb->get_var("SELECT AUTO_INCREMENT FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$wpdb->prefix}bkm_aksiyonlar'"); 
                ?>" readonly>
            </div>

            <!-- Önem Derecesi -->
            <div class="form-group">
                <label for="onem_derecesi" class="form-label required">
                    <?php echo __('Aksiyon Önem Derecesi', 'bkm-aksiyon-takip'); ?>
                </label>
                <select name="onem_derecesi" id="onem_derecesi" class="form-control" required>
                    <option value=""><?php echo __('Seçiniz', 'bkm-aksiyon-takip'); ?></option>
                    <option value="1" <?php selected($aksiyon ? $aksiyon->onem_derecesi : '', '1'); ?>>1 - Yüksek</option>
                    <option value="2" <?php selected($aksiyon ? $aksiyon->onem_derecesi : '', '2'); ?>>2 - Orta</option>
                    <option value="3" <?php selected($aksiyon ? $aksiyon->onem_derecesi : '', '3'); ?>>3 - Düşük</option>
                </select>
                <?php if ($aksiyon && $aksiyon->onem_derecesi): ?>
                    <span class="onem-badge <?php echo $aksiyon->onem_derecesi == 1 ? 'high' : ($aksiyon->onem_derecesi == 2 ? 'medium' : 'low'); ?>">
                        <i class="fas fa-<?php echo $aksiyon->onem_derecesi == 1 ? 'exclamation-circle' : ($aksiyon->onem_derecesi == 2 ? 'exclamation' : 'info-circle'); ?>"></i>
                        <?php echo $aksiyon->onem_derecesi == 1 ? 'Yüksek' : ($aksiyon->onem_derecesi == 2 ? 'Orta' : 'Düşük'); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Açılma Tarihi -->
            <div class="form-group">
                <label for="acilma_tarihi" class="form-label required">
                    <?php echo __('Aksiyon Açılma Tarihi', 'bkm-aksiyon-takip'); ?>
                </label>
                <input type="date" name="acilma_tarihi" id="acilma_tarihi" 
                       class="form-control datepicker" required
                       value="<?php echo $aksiyon ? $aksiyon->acilma_tarihi : date('Y-m-d'); ?>">
            </div>

            <!-- Hafta -->
            <div class="form-group">
                <label for="hafta" class="form-label required">
                    <?php echo __('Hafta', 'bkm-aksiyon-takip'); ?>
                </label>
                <input type="number" name="hafta" id="hafta" class="form-control" 
                       min="1" max="53" required
                       value="<?php echo $aksiyon ? $aksiyon->hafta : date('W'); ?>">
            </div>

            <!-- Kategori -->
            <div class="form-group">
                <label for="kategori_id" class="form-label required">
                    <?php echo __('Kategori', 'bkm-aksiyon-takip'); ?>
                </label>
                <select name="kategori_id" id="kategori_id" class="form-control select2" required>
                    <option value=""><?php echo __('Seçiniz', 'bkm-aksiyon-takip'); ?></option>
                    <?php foreach ($kategoriler as $kategori): ?>
                        <option value="<?php echo esc_attr($kategori->id); ?>"
                                <?php selected($aksiyon ? $aksiyon->kategori_id : '', $kategori->id); ?>>
                            <?php echo esc_html($kategori->kategori_adi); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Aksiyon Sorumlusu (Çoklu) -->
            <div class="form-group">
                <label for="sorumlular" class="form-label required">
                    <?php echo __('Aksiyon Sorumlusu', 'bkm-aksiyon-takip'); ?>
                </label>
                <select name="sorumlular[]" id="sorumlular" class="form-control select2" multiple required>
                    <?php 
                    $selected_users = $aksiyon ? explode(',', $aksiyon->sorumlular) : array();
                    foreach ($users as $user): 
                    ?>
                        <option value="<?php echo esc_attr($user->ID); ?>"
                                <?php echo in_array($user->ID, $selected_users) ? 'selected' : ''; ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tespit Nedeni -->
            <div class="form-group full-width">
                <label for="tespit_nedeni" class="form-label required">
                    <?php echo __('Aksiyon Tespitine Neden Olan Konu', 'bkm-aksiyon-takip'); ?>
                </label>
                <textarea name="tespit_nedeni" id="tespit_nedeni" class="form-control" 
                          rows="3" required><?php echo $aksiyon ? esc_textarea($aksiyon->tespit_nedeni) : ''; ?></textarea>
            </div>

            <!-- Açıklama -->
            <div class="form-group full-width">
                <label for="aciklama" class="form-label required">
                    <?php echo __('Aksiyon Açıklaması', 'bkm-aksiyon-takip'); ?>
                </label>
                <textarea name="aciklama" id="aciklama" class="form-control" 
                          rows="5" required><?php echo $aksiyon ? esc_textarea($aksiyon->aciklama) : ''; ?></textarea>
            </div>

            <!-- Hedef Tarih -->
            <div class="form-group">
                <label for="hedef_tarih" class="form-label required">
                    <?php echo __('Hedef Tarih', 'bkm-aksiyon-takip'); ?>
                </label>
                <input type="date" name="hedef_tarih" id="hedef_tarih" 
                       class="form-control datepicker" required
                       value="<?php echo $aksiyon ? $aksiyon->hedef_tarih : ''; ?>">
            </div>

            <!-- Kapanma Tarihi -->
            <div class="form-group">
                <label for="kapanma_tarihi" class="form-label">
                    <?php echo __('Aksiyon Kapanma Tarihi', 'bkm-aksiyon-takip'); ?>
                </label>
                <input type="date" name="kapanma_tarihi" id="kapanma_tarihi" 
                       class="form-control datepicker"
                       value="<?php echo $aksiyon ? $aksiyon->kapanma_tarihi : ''; ?>">
            </div>

            <!-- Performans -->
            <div class="form-group">
                <label for="performans_id" class="form-label required">
                    <?php echo __('Performans', 'bkm-aksiyon-takip'); ?>
                </label>
                <select name="performans_id" id="performans_id" class="form-control select2" required>
                    <option value=""><?php echo __('Seçiniz', 'bkm-aksiyon-takip'); ?></option>
                    <?php foreach ($performanslar as $performans): ?>
                        <option value="<?php echo esc_attr($performans->id); ?>"
                                <?php selected($aksiyon ? $aksiyon->performans_id : '', $performans->id); ?>>
                            <?php echo esc_html($performans->performans_adi); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- İlerleme Durumu -->
            <div class="form-group">
                <label for="ilerleme_durumu" class="form-label required">
                    <?php echo __('İlerleme Durumu (%)', 'bkm-aksiyon-takip'); ?>
                </label>
                <div class="progress-input-container">
                    <input type="range" name="ilerleme_durumu" id="ilerleme_durumu" 
                           class="progress-slider" min="0" max="100" 
                           value="<?php echo $aksiyon ? $aksiyon->ilerleme_durumu : 0; ?>" required>
                    <div class="progress-display">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $aksiyon ? $aksiyon->ilerleme_durumu : 0; ?>%"></div>
                        </div>
                        <span class="progress-value"><?php echo $aksiyon ? $aksiyon->ilerleme_durumu : 0; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Notlar -->
            <div class="form-group full-width">
                <label for="notlar" class="form-label">
                    <?php echo __('Notlar', 'bkm-aksiyon-takip'); ?>
                </label>
                <textarea name="notlar" id="notlar" class="form-control" 
                          rows="5"><?php echo $aksiyon ? esc_textarea($aksiyon->notlar) : ''; ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="button button-primary">
                <i class="fas fa-save"></i> 
                <?php echo $aksiyon_id > 0 ? __('Güncelle', 'bkm-aksiyon-takip') : __('Kaydet', 'bkm-aksiyon-takip'); ?>
            </button>
            <button type="reset" class="button">
                <i class="fas fa-undo"></i> <?php echo __('Sıfırla', 'bkm-aksiyon-takip'); ?>
            </button>
            <?php if ($aksiyon_id > 0): ?>
                <a href="<?php echo admin_url('admin.php?page=bkm-aksiyon-takip'); ?>" class="button">
                    <i class="fas fa-arrow-left"></i> <?php echo __('Listeye Dön', 'bkm-aksiyon-takip'); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($aksiyon_id > 0): ?>
            <input type="hidden" name="id" value="<?php echo $aksiyon_id; ?>">
        <?php endif; ?>
    </form>
</div>

<!-- Yükleniyor Göstergesi -->
<div class="bkm-loader" style="display: none;">
    <div class="bkm-loader-content">
        <i class="fas fa-spinner fa-spin"></i>
        <span><?php echo __('Yükleniyor...', 'bkm-aksiyon-takip'); ?></span>
    </div>
</div>