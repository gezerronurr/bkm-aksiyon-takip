<?php
if (!defined('ABSPATH')) {
    exit;
}

class BKM_Aksiyon_Shortcodes {
    private $current_date = '2025-05-21 07:25:09'; // UTC zaman bilgisi
    private $current_user_login = 'gezerronurr';
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_shortcode('bkm_aksiyonlar', array($this, 'render_aksiyonlar_listesi'));
        add_shortcode('bkm_aksiyon_ozet', array($this, 'render_aksiyon_ozet'));
        add_shortcode('aksiyon_takipx', array($this, 'render_aksiyon_takipx'));
    }

    /**
     * Aksiyon listesi shortcode
     */
    public function render_aksiyonlar_listesi($atts) {
        // Shortcode parametreleri
        $atts = shortcode_atts(array(
            'kategori' => '',
            'limit' => 10,
            'durum' => '',
            'siralama' => 'son_guncelleme'
        ), $atts);

        // Script ve style dosyalarını yükle
        wp_enqueue_style($this->plugin_name . '-public');
        wp_enqueue_script($this->plugin_name . '-public');

        // Font Awesome
        wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

        // AJAX için gerekli verileri ekle
        wp_localize_script($this->plugin_name . '-public', 'bkm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkm_aksiyon_nonce'),
            'current_user' => get_current_user_id(),
            'strings' => array(
                'error' => __('Bir hata oluştu', 'bkm-aksiyon-takip'),
                'success' => __('İşlem başarılı', 'bkm-aksiyon-takip'),
                'loading' => __('Yükleniyor...', 'bkm-aksiyon-takip'),
                'no_results' => __('Sonuç bulunamadı', 'bkm-aksiyon-takip')
            ),
            'filters' => array(
                'kategori' => $atts['kategori'],
                'limit' => $atts['limit'],
                'durum' => $atts['durum'],
                'siralama' => $atts['siralama']
            )
        ));

        // Template dosyasını yükle
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'partials/aksiyon-listesi.php';
        return ob_get_clean();
    }

    /**
     * Aksiyon özet shortcode
     */
    public function render_aksiyon_ozet($atts) {
        global $wpdb;

        // Shortcode parametreleri
        $atts = shortcode_atts(array(
            'gosterim' => 'kart',
            'kategori' => '',
            'limit' => 5
        ), $atts);

        // İstatistikleri hesapla
        $where = array('1=1');
        $where_values = array();

        if (!empty($atts['kategori'])) {
            $where[] = 'kategori_id = %d';
            $where_values[] = intval($atts['kategori']);
        }

        $where_clause = !empty($where_values) 
            ? $wpdb->prepare(implode(' AND ', $where), $where_values) 
            : implode(' AND ', $where);

        // Toplam aksiyon sayısı
        $total_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bkm_aksiyonlar 
            WHERE $where_clause
        ");

        // Tamamlanan aksiyon sayısı
        $completed_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}bkm_aksiyonlar 
            WHERE $where_clause AND ilerleme_durumu = 100
        ");

        // Son aksiyonlar
        $recent_actions = $wpdb->get_results("
            SELECT a.*, k.kategori_adi, u.display_name as tanimlayan_adi
            FROM {$wpdb->prefix}bkm_aksiyonlar a
            LEFT JOIN {$wpdb->prefix}bkm_kategoriler k ON a.kategori_id = k.id
            LEFT JOIN {$wpdb->users} u ON a.tanimlayan_id = u.ID
            WHERE $where_clause
            ORDER BY a.created_at DESC
            LIMIT " . intval($atts['limit'])
        );

        // Tamamlanma oranı
        $completion_rate = $total_count > 0 ? round(($completed_count / $total_count) * 100) : 0;

        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'partials/aksiyon-ozet.php';
        return ob_get_clean();
    }

    /**
     * Aksiyon takip shortcode
     */
    public function render_aksiyon_takipx($atts) {
        // Kullanıcı giriş yapmamışsa login formunu göster
        if (!is_user_logged_in()) {
            return $this->render_login_form();
        }

        // Script ve style dosyalarını yükle
        wp_enqueue_style($this->plugin_name . '-public');
        wp_enqueue_script($this->plugin_name . '-public');
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'));
        wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'));
        wp_enqueue_script('flatpickr-tr', 'https://npmcdn.com/flatpickr/dist/l10n/tr.js', array('flatpickr'));

        // Shortcode parametreleri
        $atts = shortcode_atts(array(
            'limit' => 10,
            'kategori' => '',
            'siralama' => 'son_guncelleme'
        ), $atts);

        // AJAX için gerekli verileri ekle
        wp_localize_script($this->plugin_name . '-public', 'bkm_ajax_takipx', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bkm_aksiyon_takipx_nonce'),
            'current_user' => get_current_user_id(),
            'current_date' => $this->current_date,
            'strings' => array(
                'error' => __('Bir hata oluştu', 'bkm-aksiyon-takip'),
                'success' => __('İşlem başarılı', 'bkm-aksiyon-takip'),
                'loading' => __('Yükleniyor...', 'bkm-aksiyon-takip'),
                'no_results' => __('Sonuç bulunamadı', 'bkm-aksiyon-takip'),
                'confirm_delete' => __('Bu aksiyonu silmek istediğinize emin misiniz?', 'bkm-aksiyon-takip'),
                'confirm_gorev_delete' => __('Bu görevi silmek istediğinize emin misiniz?', 'bkm-aksiyon-takip'),
                'confirm_gorev_complete' => __('Bu görevi tamamlamak istediğinize emin misiniz?', 'bkm-aksiyon-takip')
            )
        ));

        ob_start();
        ?>
        <div class="bkm-container">
            <!-- Filtreler -->
            <div class="bkm-filters">
                <form id="bkm-filter-form" class="bkm-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="filter_kategori"><?php _e('Kategori', 'bkm-aksiyon-takip'); ?></label>
                            <select id="filter_kategori" class="select2">
                                <option value=""><?php _e('Tümü', 'bkm-aksiyon-takip'); ?></option>
                                <?php
                                global $wpdb;
                                $kategoriler = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bkm_kategoriler ORDER BY kategori_adi ASC");
                                foreach ($kategoriler as $kategori) {
                                    echo '<option value="' . esc_attr($kategori->id) . '">' . 
                                         esc_html($kategori->kategori_adi) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_durum"><?php _e('Durum', 'bkm-aksiyon-takip'); ?></label>
                            <select id="filter_durum">
                                <option value=""><?php _e('Tümü', 'bkm-aksiyon-takip'); ?></option>
                                <option value="aktif"><?php _e('Aktif', 'bkm-aksiyon-takip'); ?></option>
                                <option value="tamamlandi"><?php _e('Tamamlandı', 'bkm-aksiyon-takip'); ?></option>
                                <option value="gecikti"><?php _e('Gecikti', 'bkm-aksiyon-takip'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filter_hafta"><?php _e('Hafta', 'bkm-aksiyon-takip'); ?></label>
                            <input type="number" id="filter_hafta" min="1" max="53">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="bkm-btn primary">
                                <i class="fas fa-filter"></i> <?php _e('Filtrele', 'bkm-aksiyon-takip'); ?>
                            </button>
                            <button type="reset" class="bkm-btn secondary">
                                <i class="fas fa-undo"></i> <?php _e('Sıfırla', 'bkm-aksiyon-takip'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Aksiyonlar Tablosu -->
            <div class="bkm-table-responsive">
                <table class="bkm-table" id="aksiyonlar-table">
                    <thead>
                        <tr>
                            <th><?php _e('Görevler', 'bkm-aksiyon-takip'); ?></th>
                            <th>ID</th>
                            <th>Kategori</th>
                            <th>Önem</th>
                            <th>Açılma Tarihi</th>
                            <th>Hafta</th>
                            <th>Sorumlu</th>
                            <th>Hedef Tarih</th>
                            <th>İlerleme</th>
                            <th>Performans</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- AJAX ile doldurulacak -->
                    </tbody>
                </table>
            </div>

            <!-- Detay Modal -->
            <div class="bkm-modal" id="aksiyon-detay-modal">
                <div class="bkm-modal-content">
                    <div class="bkm-modal-header">
                        <h2><?php _e('Aksiyon Detayı', 'bkm-aksiyon-takip'); ?></h2>
                        <span class="bkm-modal-close">&times;</span>
                    </div>
                    <div class="bkm-modal-body">
                        <!-- AJAX ile doldurulacak -->
                    </div>
                </div>
            </div>

            <!-- Görev Ekle Modal -->
            <div class="bkm-modal" id="gorev-ekle-modal">
                <div class="bkm-modal-content">
                    <div class="bkm-modal-header">
                        <h2><?php _e('Görev Ekle', 'bkm-aksiyon-takip'); ?></h2>
                        <span class="bkm-modal-close">&times;</span>
                    </div>
                    <div class="bkm-modal-body">
                        <form id="gorev-ekle-form" class="bkm-form">
                            <input type="hidden" name="action" value="save_gorev">
                            <input type="hidden" name="gorev_id" id="gorev_id" value="">
                            <input type="hidden" name="aksiyon_id" id="aksiyon_id" value="">
                            <?php wp_nonce_field('bkm_aksiyon_takipx_nonce', 'gorev_nonce'); ?>
                            
                            <!-- Görev İçeriği -->
                            <div class="form-group">
                                <label for="gorev_icerik" class="form-label required">
                                    <?php _e('Görevin İçeriği', 'bkm-aksiyon-takip'); ?>
                                </label>
                                <textarea name="gorev_icerik" id="gorev_icerik" class="form-control" rows="3" required></textarea>
                            </div>

                            <!-- Başlangıç Tarihi -->
                            <div class="form-group">
                                <label for="baslangic_tarihi" class="form-label required">
                                    <?php _e('Başlangıç Tarihi', 'bkm-aksiyon-takip'); ?>
                                </label>
                                <input type="date" name="baslangic_tarihi" id="baslangic_tarihi" 
                                       class="form-control datepicker" required>
                            </div>

                            <!-- Sorumlu Kişi -->
                            <div class="form-group">
                                <label for="sorumlu_id" class="form-label required">
                                    <?php _e('Sorumlu Kişi', 'bkm-aksiyon-takip'); ?>
                                </label>
                                <select name="sorumlu_id" id="sorumlu_id" class="form-control select2" required>
                                    <option value=""><?php _e('Seçiniz', 'bkm-aksiyon-takip'); ?></option>
                                    <?php
                                    $users = get_users(['role__in' => ['administrator', 'editor', 'author', 'contributor']]);
                                    foreach ($users as $user): 
                                    ?>
                                        <option value="<?php echo esc_attr($user->ID); ?>">
                                            <?php echo esc_html($user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Hedeflenen Bitiş Tarihi -->
                            <div class="form-group">
                                <label for="hedef_bitis_tarihi" class="form-label required">
                                    <?php _e('Hedeflenen Bitiş Tarihi', 'bkm-aksiyon-takip'); ?>
                                </label>
                                <input type="date" name="hedef_bitis_tarihi" id="hedef_bitis_tarihi" 
                                       class="form-control datepicker" required>
                            </div>

                            <!-- İlerleme Durumu -->
                            <div class="form-group">
                                <label for="ilerleme_durumu" class="form-label required">
                                    <?php _e('İlerleme Durumu (%)', 'bkm-aksiyon-takip'); ?>
                                </label>
                                <div class="progress-input-container">
                                    <input type="range" name="ilerleme_durumu" id="gorev_ilerleme_durumu" 
                                           class="progress-slider" min="0" max="100" value="0" required>
                                    <div class="progress-display">
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <span class="progress-value">0%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="bkm-btn primary">
                                    <i class="fas fa-save"></i> <?php _e('Kaydet', 'bkm-aksiyon-takip'); ?>
                                </button>
                                <button type="button" class="bkm-btn secondary bkm-modal-cancel">
                                    <i class="fas fa-times"></i> <?php _e('İptal', 'bkm-aksiyon-takip'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Görev Düzenle Modal -->
            <div class="bkm-modal" id="gorev-duzenle-modal">
                <div class="bkm-modal-content">
                    <div class="bkm-modal-header">
                        <h2><?php _e('Görevi Düzenle', 'bkm-aksiyon-takip'); ?></h2>
                        <span class="bkm-modal-close">&times;</span>
                    </div>
                    <div class="bkm-modal-body">
                        <!-- AJAX ile doldurulacak -->
                    </div>
                </div>
            </div>

            <!-- Görevler Modal -->
            <div class="bkm-modal" id="gorevler-modal">
                <div class="bkm-modal-content">
                    <div class="bkm-modal-header">
                        <h2><?php _e('Aksiyon Görevleri', 'bkm-aksiyon-takip'); ?></h2>
                        <span class="bkm-modal-close">&times;</span>
                    </div>
                    <div class="bkm-modal-body">
                        <!-- AJAX ile doldurulacak -->
                    </div>
                    <div class="bkm-modal-footer">
                        <button type="button" class="bkm-btn primary gorev-ekle-btn">
                            <i class="fas fa-plus"></i> <?php _e('Görev Ekle', 'bkm-aksiyon-takip'); ?>
                        </button>
                        <button type="button" class="bkm-btn secondary bkm-modal-cancel">
                            <i class="fas fa-times"></i> <?php _e('Kapat', 'bkm-aksiyon-takip'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Login form render
     */
    private function render_login_form() {
        ob_start();
        ?>
        <div class="bkm-login-container">
            <div class="bkm-login-box">
                <h2><?php _e('Giriş Yapın', 'bkm-aksiyon-takip'); ?></h2>
                <?php 
                    wp_login_form(array(
                        'redirect' => get_permalink(),
                        'form_id' => 'bkm-login-form',
                        'label_username' => __('Kullanıcı Adı', 'bkm-aksiyon-takip'),
                        'label_password' => __('Şifre', 'bkm-aksiyon-takip'),
                        'label_remember' => __('Beni Hatırla', 'bkm-aksiyon-takip'),
                        'label_log_in' => __('Giriş Yap', 'bkm-aksiyon-takip'),
                        'remember' => true
                    ));
                ?>
                <div class="bkm-login-links">
                    <a href="<?php echo wp_lostpassword_url(); ?>">
                        <?php _e('Şifremi Unuttum', 'bkm-aksiyon-takip'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}