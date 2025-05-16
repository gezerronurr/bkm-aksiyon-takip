<?php
// Direkt erişimi engelle
if (!defined('WPINC')) { die; }

// WordPress global database değişkenini ekle
global $wpdb;

$current_user = wp_get_current_user();
$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$aksiyon_id = $editing ? intval($_GET['id']) : 0;

// Performans verilerini getir - Hata kontrolü ile
$performanslar = [];
try {
    $performanslar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_performanslar ORDER BY performans_adi ASC");
} catch (Exception $e) {
    // Hata durumunda boş array kullan
    $performanslar = [];
}

// Kategorileri getir - Hata kontrolü ile
$kategoriler = [];
try {
    $kategoriler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_kategoriler ORDER BY kategori_adi ASC");
} catch (Exception $e) {
    // Hata durumunda boş array kullan
    $kategoriler = [];
}

// Düzenleme modunda aksiyon bilgilerini getir
if ($editing) {
    try {
        $aksiyon = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bkm_aksiyonlar WHERE id = %d",
            $aksiyon_id
        ));
    } catch (Exception $e) {
        $aksiyon = null;
    }
}
$current_user = wp_get_current_user();
$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$aksiyon_id = $editing ? intval($_GET['id']) : 0;

// Performans verilerini getir
$performanslar = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_performanslar ORDER BY performans_adi ASC");
?>

<div class="bkm-container">
    <!-- Header -->
    <div class="bkm-form-header">
        <div class="header-content">
            <div class="header-title">
                <h1><?php echo $editing ? 'Aksiyonu Düzenle' : 'Yeni Aksiyon Oluştur'; ?></h1>
                <p class="subtitle">Tüm zorunlu alanları (*) doldurunuz</p>
            </div>
            <div class="header-actions">
                <button type="button" class="bkm-btn btn-secondary" onclick="window.location.href='admin.php?page=bkm-aksiyon-takip'">
                    <i class="fas fa-arrow-left"></i>
                    Listeye Dön
                </button>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bkm-form-container">
        <form id="aksiyon-form" class="bkm-form" method="post">
            <?php wp_nonce_field('bkm_aksiyon_nonce', 'bkm_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo $editing ? 'edit_aksiyon' : 'add_aksiyon'; ?>">
            
            <div class="form-grid">
                <!-- Sol Kolon -->
                <div class="form-left">
                    <!-- Temel Bilgiler -->
                    <div class="form-section">
                        <h3 class="section-title">Temel Bilgiler</h3>
                        
                        <div class="form-group">
                            <label for="tanimlayan">
                                <i class="fas fa-user"></i>
                                Aksiyonu Tanımlayan *
                            </label>
                            <select id="tanimlayan" name="tanimlayan_id" required class="form-control">
                                <option value="">Seçiniz</option>
                                <?php
                                $users = get_users(['role__in' => ['administrator', 'editor', 'author']]);
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="sira_no">
                                    <i class="fas fa-hashtag"></i>
                                    Sıra No
                                </label>
                                <input type="text" id="sira_no" class="form-control" disabled 
                                       value="<?php echo $editing ? $aksiyon_id : 'Otomatik oluşturulacak'; ?>">
                            </div>

                            <div class="form-group">
                                <label for="onem">
                                    <i class="fas fa-exclamation-circle"></i>
                                    Önem Derecesi *
                                </label>
                                <select id="onem" name="onem_derecesi" required class="form-control">
                                    <option value="">Seçiniz</option>
                                    <option value="1" class="high-priority">1 - Yüksek</option>
                                    <option value="2" class="medium-priority">2 - Orta</option>
                                    <option value="3" class="low-priority">3 - Düşük</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="acilma_tarihi">
                                    <i class="fas fa-calendar-plus"></i>
                                    Aksiyon Açılma Tarihi *
                                </label>
                                <input type="date" id="acilma_tarihi" name="acilma_tarihi" required class="form-control"
                                       value="<?php echo $editing ? date('Y-m-d', strtotime($aksiyon->acilma_tarihi)) : date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="hafta">
                                    <i class="fas fa-calendar-week"></i>
                                    Hafta *
                                </label>
                                <input type="number" id="hafta" name="hafta" required class="form-control" min="1" max="53"
                                       value="<?php echo $editing ? $aksiyon->hafta : date('W'); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="kategori">
                                    <i class="fas fa-folder"></i>
                                    Kategori *
                                </label>
                                <select id="kategori" name="kategori_id" required class="form-control">
                                    <option value="">Seçiniz</option>
                                    <?php
                                    foreach ($kategoriler as $kategori):
                                        $selected = $editing && $aksiyon->kategori_id == $kategori->id ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $kategori->id; ?>" <?php echo $selected; ?>>
                                            <?php echo esc_html($kategori->kategori_adi); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="performans">
                                    <i class="fas fa-chart-line"></i>
                                    Performans *
                                </label>
                                <select id="performans" name="performans_id" required class="form-control">
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($performanslar as $performans): ?>
                                        <option value="<?php echo $performans->id; ?>">
                                            <?php echo esc_html($performans->performans_adi); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="form-right">
                    <!-- Aksiyon Detayları -->
                    <div class="form-section">
                        <h3 class="section-title">Aksiyon Detayları</h3>

                        <div class="form-group">
                            <label for="tespit_nedeni">
                                <i class="fas fa-search"></i>
                                Aksiyon Tespitine Neden Olan Konu *
                            </label>
                            <textarea id="tespit_nedeni" name="tespit_nedeni" required class="form-control" 
                                      rows="3" placeholder="Tespit nedenini detaylı olarak açıklayınız..."><?php echo $editing ? esc_textarea($aksiyon->tespit_nedeni) : ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="aciklama">
                                <i class="fas fa-align-left"></i>
                                Aksiyon Açıklaması *
                            </label>
                            <textarea id="aciklama" name="aciklama" required class="form-control" 
                                      rows="5" placeholder="Aksiyonu detaylı olarak açıklayınız..."><?php echo $editing ? esc_textarea($aksiyon->aciklama) : ''; ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hedef_tarih">
                                    <i class="fas fa-calendar-alt"></i>
                                    Hedef Tarih *
                                </label>
                                <input type="date" id="hedef_tarih" name="hedef_tarih" required class="form-control"
                                       value="<?php echo $editing ? date('Y-m-d', strtotime($aksiyon->hedef_tarih)) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="kapanma_tarihi">
                                    <i class="fas fa-calendar-check"></i>
                                    Aksiyon Kapanma Tarihi
                                </label>
                                <input type="date" id="kapanma_tarihi" name="kapanma_tarihi" class="form-control"
                                       value="<?php echo $editing && $aksiyon->kapanma_tarihi ? date('Y-m-d', strtotime($aksiyon->kapanma_tarihi)) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sorumlular">
                                <i class="fas fa-users"></i>
                                Aksiyon Sorumlusu *
                            </label>
                            <div class="user-selector">
                                <?php
                                foreach ($users as $user):
                                    $selected = $editing && in_array($user->ID, explode(',', $aksiyon->sorumlular)) ? 'checked' : '';
                                ?>
                                    <label class="user-option">
                                        <input type="checkbox" name="sorumlular[]" value="<?php echo $user->ID; ?>" <?php echo $selected; ?>>
                                        <span class="user-avatar">
                                            <?php echo get_avatar($user->ID, 32); ?>
                                        </span>
                                        <span class="user-name"><?php echo esc_html($user->display_name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ilerleme">
                                <i class="fas fa-tasks"></i>
                                İlerleme Durumu (%) *
                            </label>
                            <div class="progress-input">
                                <input type="range" id="ilerleme" name="ilerleme_durumu" 
                                       min="0" max="100" value="<?php echo $editing ? $aksiyon->ilerleme_durumu : '0'; ?>" 
                                       class="form-control">
                                <span class="progress-value">0%</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notlar">
                                <i class="fas fa-sticky-note"></i>
                                Notlar
                            </label>
                            <textarea id="notlar" name="notlar" class="form-control" 
                                      rows="5" placeholder="Varsa ek notlarınızı giriniz..."><?php echo $editing ? esc_textarea($aksiyon->notlar) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="bkm-btn btn-success">
                    <i class="fas fa-save"></i>
                    <?php echo $editing ? 'Değişiklikleri Kaydet' : 'Aksiyon Oluştur'; ?>
                </button>
                <button type="button" class="bkm-btn btn-secondary" onclick="history.back()">
                    <i class="fas fa-times"></i>
                    İptal
                </button>
            </div>
        </form>
    </div>
</div>